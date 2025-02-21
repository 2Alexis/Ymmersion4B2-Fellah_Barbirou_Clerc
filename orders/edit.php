<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    header('Location: list.php');
    exit;
}

// Vérification des droits
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || ($_SESSION['role'] !== 'admin' && $order['user_id'] !== $_SESSION['user_id'])) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/notification_manager.php';
    
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET client_name = ?, color = ?, dimensions = ?, 
            filament_type = ?, status = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([
        $_POST['client_name'],
        $_POST['color'],
        $_POST['dimensions'],
        $_POST['filament_type'],
        $_POST['status'],
        $orderId
    ])) {
        $notifier = new NotificationManager();
        $notifier->sendOrderStatusUpdate($order);
    }
    
    header('Location: list.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modifier la commande</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2>Modifier la commande</h2>
            <form method="POST" class="form">
                <div class="form-group">
                    <label>Nom du client:</label>
                    <input type="text" name="client_name" class="form-control"
                           value="<?php echo htmlspecialchars($order['client_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Couleur:</label>
                    <input type="text" name="color" class="form-control"
                           value="<?php echo htmlspecialchars($order['color']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Dimensions:</label>
                    <input type="text" name="dimensions" class="form-control"
                           value="<?php echo htmlspecialchars($order['dimensions']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Type de filament:</label>
                    <select name="filament_type" class="form-control" required>
                        <option value="PLA" <?php echo $order['filament_type'] === 'PLA' ? 'selected' : ''; ?>>PLA</option>
                        <option value="ABS" <?php echo $order['filament_type'] === 'ABS' ? 'selected' : ''; ?>>ABS</option>
                        <option value="PETG" <?php echo $order['filament_type'] === 'PETG' ? 'selected' : ''; ?>>PETG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut:</label>
                    <select name="status" class="form-control" required>
                        <option value="En cours" <?php echo $order['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="Terminé" <?php echo $order['status'] === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="Annulé" <?php echo $order['status'] === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Modifier</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?> 