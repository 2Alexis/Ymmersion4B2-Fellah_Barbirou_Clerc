<?php
session_start();
require_once '../config/database.php';
require_once '../includes/printer_scheduler.php';



require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Initialiser le scheduler
$scheduler = new PrinterScheduler($pdo);

// Vérifier et mettre à jour les impressions terminées
$scheduler->checkAndUpdateFinishedPrints();

// Vérifier et assigner les commandes en attente
$scheduler->checkAndAssignPendingOrders();

// Gestion de la suppression
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // Vérifier si l'imprimante n'est pas en cours d'utilisation
        $stmt = $pdo->prepare("SELECT status FROM printers WHERE id = ?");
        $stmt->execute([$id]);
        $printer = $stmt->fetch();
        
        if ($printer && $printer['status'] !== 'occupe') {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Supprimer d'abord les assignations
            $stmt = $pdo->prepare("DELETE FROM printer_assignments WHERE printer_id = ?");
            $stmt->execute([$id]);
            
            // Puis supprimer l'imprimante
            $stmt = $pdo->prepare("DELETE FROM printers WHERE id = ?");
            $stmt->execute([$id]);
            
            // Valider la transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Imprimante supprimée avec succès";
        } else {
            $_SESSION['error'] = "Impossible de supprimer une imprimante en cours d'utilisation";
        }
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
    
    header('Location: manage.php');
    exit;
}

// Récupérer toutes les imprimantes
$printers = $pdo->query("SELECT * FROM printers ORDER BY name")->fetchAll();

// Récupérer les assignations en cours
$stmt = $pdo->prepare("
    SELECT pa.*, o.client_name, p.name as printer_name
    FROM printer_assignments pa
    JOIN orders o ON pa.order_id = o.id
    JOIN printers p ON pa.printer_id = p.id
    WHERE pa.status IN ('planifie', 'en_cours')
    ORDER BY pa.start_time
");
$stmt->execute();
$assignments = $stmt->fetchAll();
?>

<div class="container">
    <h1 class="page-title">Gestion des imprimantes</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="add.php" class="btn btn-primary"><span>➕</span>Ajouter une imprimante</a>
          </div>

    <div class="table-container">
        <table class="printers-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th>Type de filament</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($printers as $printer): ?>
                <tr>
                    <td data-label="Nom"><?php echo htmlspecialchars($printer['name']); ?></td>
                    <td data-label="Statut"><span class="status status-<?php echo htmlspecialchars($printer['status']); ?>"><?php echo htmlspecialchars($printer['status']); ?></span></td>
                    <td data-label="Type"><?php echo htmlspecialchars($printer['filament_type']); ?></td>
                    <td data-label="Actions">
                        <div class="actions">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            
                        <a href="edit.php?id=<?php echo $printer['id']; ?>" class="action-btn edit">Modifier</a>
                        <a href="?delete=<?php echo $printer['id']; ?>" class="action-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette imprimante ?')">Supprimer</a>
                    </div>
                <?php endif; ?>
            </td>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($printers)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucune imprimante trouvée</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card mt-4">
        <h2>Planning des impressions</h2>
        <div class="table-container">
            <table class="printers-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Imprimante</th>
                        <th>Début</th>
                        <th>Fin estimée</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td data-label="Client"><?php echo htmlspecialchars($assignment['client_name']); ?></td>
                        <td data-label="Imprimante"><?php echo htmlspecialchars($assignment['printer_name']); ?></td>
                        <td data-label="Début"><?php echo date('d/m/Y H:i', strtotime($assignment['start_time'])); ?></td>
                        <td data-label="Fin estimée"><?php echo date('d/m/Y H:i', strtotime($assignment['end_time'])); ?></td>
                        <td data-label="Statut">
                            <span class="status-badge status-<?php echo $assignment['status']; ?>">
                                <?php echo $assignment['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucune impression planifiée</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deletePrinter(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette imprimante ?')) {
        fetch(`delete_printer.php?id=${id}`, {
            method: 'POST'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        });
    }
}

// Mise à jour du statut
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const printerId = this.dataset.printerId;
        const status = this.value;
        
        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                printer_id: printerId,
                status: status
            })
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 