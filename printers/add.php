<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO printers (name, status, filament_type, created_at)
            VALUES (?, 'Libre', ?, NOW())
        ");
        
        if ($stmt->execute([
            $_POST['name'],
            $_POST['filament_type']
        ])) {
            $_SESSION['success'] = "Imprimante ajoutée avec succès";
            header('Location: manage.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout : " . $e->getMessage();
    }
}
?>

<div class="container">
    <h1 class="page-title">Ajouter une imprimante</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form class="add-printer-form" method="POST">
        <div class="form-group">
            <label for="name">Nom de l'imprimante</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="filament_type">Type de filament</label>
            <select id="filament_type" name="filament_type" class="form-control" required>
                <option value="">Sélectionner un type</option>
                <option value="PLA">PLA</option>
                <option value="ABS">ABS</option>
                <option value="PETG">PETG</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter l'imprimante</button>
            <a href="manage.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?> 