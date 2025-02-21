<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container">
    <h1 class="page-title">Nouvelle commande</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="process_order.php" enctype="multipart/form-data" class="create-order-form">
        <div class="create-order-group">
            <label>Nom du client:</label>
            <input type="text" name="client_name" class="create-order-input" required>
        </div>

        <div class="form-group">
            <label>Fichier STL:</label>
            <div class="file-upload">
                <label for="stl_file" class="file-upload-label">
                    <span class="file-upload-icon">📁</span>
                    <span class="file-upload-text">Choisir un fichier STL</span>
                </label>
                <input type="file" id="stl_file" name="stl_file" accept=".stl" required>
                <div class="file-name"></div>
            </div>
        </div>

        <div class="create-order-group">
            <label>Couleur:</label>
            <input type="text" name="color" class="create-order-input" required>
        </div>

        <div class="create-order-group">
            <label>Dimensions:</label>
            <input type="text" name="dimensions" class="create-order-input" required>
        </div>

        <div class="create-order-group">
            <label>Type de filament:</label>
            <select name="filament_type" class="create-order-select" required>
                <option value="PLA">PLA</option>
                <option value="ABS">ABS</option>
                <option value="PETG">PETG</option>
            </select>
        </div>

        <div class="create-order-group">
            <label>Durée estimée (minutes):</label>
            <input type="number" name="estimated_time" class="create-order-input" required>
        </div>

        <div class="create-order-group">
            <label>Email:</label>
            <input type="email" name="client_email" class="create-order-input" required>
        </div>

        <div class="create-order-actions">
            <button type="submit" class="btn btn-primary">Créer la commande</button>
            <a href="list.php" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </form>
</div>

<script>
document.getElementById('stl_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    document.querySelector('.file-name').textContent = fileName || '';
    
    const label = document.querySelector('.file-upload-text');
    label.textContent = fileName ? 'Fichier sélectionné' : 'Choisir un fichier STL';
});
</script>

<?php require_once '../includes/footer.php'; ?>