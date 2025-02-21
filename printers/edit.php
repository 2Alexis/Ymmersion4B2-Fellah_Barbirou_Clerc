<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Récupération de l'imprimante
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM printers WHERE id = ?");
    $stmt->execute([$id]);
    $printer = $stmt->fetch();
    
    if (!$printer) {
        $_SESSION['error'] = "Imprimante non trouvée";
        header('Location: manage.php');
        exit;
    }
} else {
    header('Location: manage.php');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE printers 
            SET name = ?, status = ?, filament_type = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([
            $_POST['name'],
            $_POST['status'],
            $_POST['filament_type'],
            $id
        ])) {
            $_SESSION['success'] = "Imprimante modifiée avec succès";
            header('Location: manage.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>

<div class="container">
    <h1 class="page-title">Modifier une imprimante</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form">
        <div class="form-group">
            <label>Nom de l'imprimante:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($printer['name']); ?>" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Statut:</label>
            <select name="status" class="form-control" required>
                <option value="Libre" <?php echo $printer['status'] === 'Libre' ? 'selected' : ''; ?>>Libre</option>
                <option value="Occupe" <?php echo $printer['status'] === 'Occupe' ? 'selected' : ''; ?>>Occupé</option>
                <option value="Maintenance" <?php echo $printer['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
            </select>
        </div>

        <div class="form-group">
            <label>Type de filament:</label>
            <select name="filament_type" class="form-control" required>
                <option value="PLA" <?php echo $printer['filament_type'] === 'PLA' ? 'selected' : ''; ?>>PLA</option>
                <option value="ABS" <?php echo $printer['filament_type'] === 'ABS' ? 'selected' : ''; ?>>ABS</option>
                <option value="PETG" <?php echo $printer['filament_type'] === 'PETG' ? 'selected' : ''; ?>>PETG</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="manage.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?> 