<?php
session_start();

// Redirection vers login.php si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
require_once 'config/database.php';

// Récupération des statistiques
$stats = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN status = 'termine' THEN 1 ELSE 0 END) as termine
    FROM orders
")->fetch();

// Nombre d'imprimantes actives
$activePrinters = $pdo->query("
    SELECT COUNT(*) as count 
    FROM printers 
    WHERE status = 'Libre'
")->fetch();

// Dernières commandes
$recentOrders = $pdo->query("
    SELECT o.client_name as client, o.status, o.created_at 
    FROM orders o 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="container">
    <h1 class="page-title">Bienvenue sur la plateforme d'impression 3D</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3>Commandes en cours</h3>
                <div class="stat-number"><?php echo $stats['en_cours'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <h3>En attente</h3>
                <div class="stat-number"><?php echo $stats['en_attente'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h3>Terminées</h3>
                <div class="stat-number"><?php echo $stats['termine'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖨️</div>
            <div class="stat-info">
                <h3>Imprimantes actives</h3>
                <div class="stat-number"><?php echo $activePrinters['count'] ?? 0; ?></div>
            </div>
        </div>
    </div>

    <div class="quick-actions">
        <div class="action-card quick-links">
            <h2>Actions rapides</h2>
            <div class="action-buttons">
                <a href="orders/create.php" class="btn btn-primary">
                    <span>➕</span> Nouvelle commande
                </a>
                <a href="orders/list.php" class="btn btn-secondary">
                    <span>📋</span> Liste des commandes
                </a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="printers/manage.php" class="btn btn-secondary">
                    <span>⚙️</span> Gérer les imprimantes
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-card">
            <h2>Dernières commandes</h2>
            <div class="recent-orders">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['client']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="3" class="text-center">Aucune commande récente</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 