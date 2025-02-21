<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'impression3d');
define('DB_PASS', '!1Azerty1!');
define('DB_NAME', 'gestion_commandes');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Vérifier si un admin existe déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();

    // Si aucun admin n'existe, en créer un
    if ($adminCount == 0) {
        $email = "admin@impression3d.com"; // Changez cet email
        $password = "!1Azerty1!"; // Changez ce mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$email, $hashedPassword]);

        echo "Compte administrateur créé :<br>";
       
    }

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}