<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $username = trim($_POST['username']); // Récupérer le username
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Vérification des champs
        if (empty($username)) {
            throw new Exception("Le nom d'utilisateur est requis");
        }
        if ($password !== $confirmPassword) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        // Vérification si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cet email est déjà utilisé");
        }

        // Hashage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insertion du nouvel utilisateur avec username
        $stmt = $pdo->prepare("
            INSERT INTO users (email, username, password, role, created_at) 
            VALUES (?, ?, ?, 'user', NOW())
        ");

        if ($stmt->execute([$email, $username, $hashedPassword])) {
            header('Location: login.php');
            exit;
        } else {
            throw new Exception("Erreur lors de la création du compte");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: register.php');
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}
