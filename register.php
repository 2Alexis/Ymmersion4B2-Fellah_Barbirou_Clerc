<?php
session_start();
$hideNav = true;
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Inscription</h1>
        
        <form method="post" action="process_register.php" class="auth-form">
            <div class="auth-form-group">
                <label>Email</label>
                <input type="email" name="email" class="auth-input" required>
            </div>
            <div class="auth-form-group">
    <label>Nom d'utilisateur</label>
    <input type="text" name="username" class="auth-input" required>
</div>

            <div class="auth-form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="auth-input" required>
            </div>
            
            <div class="auth-form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" class="auth-input" required>
            </div>
            
            <div class="auth-actions">
                <button type="submit" class="btn btn-primary auth-btn">S'inscrire</button>
            </div>
            
            <div class="auth-links">
                <span>Déjà inscrit ?</span>
                <a href="login.php">Se connecter</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>