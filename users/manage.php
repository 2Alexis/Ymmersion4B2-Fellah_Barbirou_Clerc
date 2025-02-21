<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Supprimer un utilisateur
if (isset($_POST['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
    header('Location: manage.php');
    exit;
}

// Modifier le rôle d'un utilisateur
if (isset($_POST['update_role'])) {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$_POST['role'], $_POST['user_id']]);
    header('Location: manage.php');
    exit;
}

// Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY email");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des utilisateurs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>
    
    <div class="container">
    
            <h1 class="page-title">Gestion des utilisateurs</h1>
        
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role" class="form-control" onchange="this.form.submit()" <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
<?php require_once '../includes/footer.php'; ?> 
</html>