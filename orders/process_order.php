<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug
        error_log("Début du traitement de la commande");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // Vérification du fichier
        if (!isset($_FILES['stl_file']) || $_FILES['stl_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors du téléchargement du fichier STL: " . $_FILES['stl_file']['error']);
        }

        $stlFile = $_FILES['stl_file'];
        $fileName = time() . '_' . basename($stlFile['name']); // Ajout timestamp pour éviter les doublons
        $uploadDir = __DIR__ . '/uploads/';
        
        // Création du dossier uploads
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception("Impossible de créer le dossier uploads");
            }
        }
        
        $targetPath = $uploadDir . $fileName;

        // Upload du fichier
        if (!move_uploaded_file($stlFile['tmp_name'], $targetPath)) {
            throw new Exception("Échec du déplacement du fichier uploadé");
        }

        error_log("Fichier uploadé avec succès: " . $targetPath);

        // Insertion dans la BDD
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                client_name,
                stl_file,
                color,
                dimensions,
                filament_type,
                estimated_time,
                client_email,
                status,
                user_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, NOW())
        ");

        $result = $stmt->execute([
            $_POST['client_name'],
            $fileName,
            $_POST['color'],
            $_POST['dimensions'],
            $_POST['filament_type'],
            $_POST['estimated_time'],
            $_POST['client_email'],
            $_SESSION['user_id']
        ]);

        if (!$result) {
            error_log("Erreur SQL: " . print_r($stmt->errorInfo(), true));
            throw new Exception("Erreur lors de l'insertion dans la base de données");
        }

        error_log("Commande créée avec succès");
        $_SESSION['success'] = "La commande a été créée avec succès";
        header('Location: list.php');
        exit;

    } catch (Exception $e) {
        error_log("Erreur lors du traitement: " . $e->getMessage());
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
        header('Location: create.php');
        exit;
    }
} else {
    header('Location: create.php');
    exit;
}
?>