<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les statistiques des commandes
$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM orders
    GROUP BY status
");
$orderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques d'utilisation des imprimantes (derniers 30 jours)
$stmt = $pdo->query("
    SELECT 
        p.name,
        COUNT(pa.id) as total_prints,
        SUM(pa.estimated_duration) as total_duration
    FROM printers p
    LEFT JOIN printer_assignments pa ON p.id = pa.printer_id
    WHERE pa.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id, p.name
");
$printerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Types de filaments les plus utilisés
$stmt = $pdo->query("
    SELECT 
        filament_type,
        COUNT(*) as usage_count
    FROM orders
    GROUP BY filament_type
    ORDER BY usage_count DESC
");
$filamentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Commandes récentes
$stmt = $pdo->query("
    SELECT 
        o.*,
        u.email as user_email,
        pa.start_time,
        pa.end_time,
        p.name as printer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN printer_assignments pa ON o.id = pa.order_id
    LEFT JOIN printers p ON pa.printer_id = p.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Modifier la requête des stats utilisateurs (en dehors du if admin)
$stmt = $pdo->query("
    SELECT 
        u.username,  -- Changé de email à username
        COUNT(o.id) as total_orders
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id, u.username
    ORDER BY total_orders DESC
");
$userStats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>
    
    <div class="container">
        <h1>Tableau de bord</h1>

        <!-- Changement de stats-grid à stats-container -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Commandes en attente</h3>
                <div class="stat-number">
                    <?php
                    $waiting = array_filter($orderStats, function($stat) {
                        return $stat['status'] === 'en_attente';
                    });
                    echo !empty($waiting) ? current($waiting)['count'] : '0';
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Commandes en cours</h3>
                <div class="stat-number">
                    <?php
                    $inProgress = array_filter($orderStats, function($stat) {
                        return $stat['status'] === 'en_cours';
                    });
                    echo !empty($inProgress) ? current($inProgress)['count'] : '0';
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Commandes terminées</h3>
                <div class="stat-number">
                    <?php
                    $finished = array_filter($orderStats, function($stat) {
                        return $stat['status'] === 'termine';
                    });
                    echo !empty($finished) ? current($finished)['count'] : '0';
                    ?>
                </div>
            </div>
        </div>

        <!-- Première rangée de graphiques -->
        <div class="charts-container">
            <!-- Utilisation des imprimantes -->
            <div class="chart-card">
                <h3>Utilisation des imprimantes (30 derniers jours)</h3>
                <canvas id="printerChart"></canvas>
            </div>
            
            <!-- Types de filaments -->
            <div class="chart-card">
                <h3>Types de filaments utilisés</h3>
                <canvas id="filamentChart"></canvas>
            </div>
        </div>

        <!-- Deuxième rangée pour le graphique des utilisateurs -->
        <div class="chart-card">
            <h3>Nombre d'impressions par utilisateur</h3>
            <canvas id="userChart"></canvas>
        </div>

        <!-- Commandes récentes -->
        <div class="recent-orders">
            <h3>Commandes récentes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Imprimante</th>
                        <th>Statut</th>
                        <th>Début</th>
                        <th>Fin estimée</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td data-label="Client"><?php echo htmlspecialchars($order['client_name']); ?></td>
                        <td data-label="Imprimante"><?php echo htmlspecialchars($order['printer_name'] ?? 'Non assignée'); ?></td>
                        <td data-label="Statut">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td data-label="Début"><?php echo $order['start_time'] ? date('d/m/Y H:i', strtotime($order['start_time'])) : '-'; ?></td>
                        <td data-label="Fin estimée"><?php echo $order['end_time'] ? date('d/m/Y H:i', strtotime($order['end_time'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Graphique d'utilisation des imprimantes
        const printerCtx = document.getElementById('printerChart').getContext('2d');
        new Chart(printerCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($printerStats, 'name')); ?>,
                datasets: [{
                    label: 'Nombre d\'impressions',
                    data: <?php echo json_encode(array_column($printerStats, 'total_prints')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des types de filaments
        const filamentCtx = document.getElementById('filamentChart').getContext('2d');
        new Chart(filamentCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($filamentStats, 'filament_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($filamentStats, 'usage_count')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });

        // Graphique des impressions par utilisateur (visible pour tous)
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($userStats, 'username')); ?>,
                datasets: [{
                    label: 'Nombre d\'impressions',
                    data: <?php echo json_encode(array_column($userStats, 'total_orders')); ?>,
                    backgroundColor: 'rgba(14, 165, 233, 0.5)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
    
</body>
<?php require_once 'includes/footer.php'; ?> 
</html> 
