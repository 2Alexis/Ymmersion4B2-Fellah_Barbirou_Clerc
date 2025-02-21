<?php
session_start();
$hideNav = true;
require_once 'includes/header.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Connexion</h1>
        
        <?php if (isset($error)): ?>
            <div class="auth-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="auth-form-group">
                <label>Email</label>
                <input type="email" name="email" class="auth-input" required>
            </div>
            
            <div class="auth-form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="auth-input" required>
            </div>
            
            <div class="auth-actions">
                <button type="submit" class="btn btn-primary auth-btn">Se connecter</button>
            </div>
            
            <div class="auth-links">
                <span>Pas encore inscrit ?</span>
                <a href="register.php">Créer un compte</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>