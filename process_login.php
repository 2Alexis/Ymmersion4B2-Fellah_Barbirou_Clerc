<?php
session_start();

// Connexion à la base de données
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Stocker les informations dans la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        } else {
            throw new Exception("Email ou mot de passe incorrect");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
