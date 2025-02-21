<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    // Debug pour vérifier la session
    // var_dump($_SESSION);

    if ($_SESSION['role'] === 'admin') {
        // L'admin voit toutes les commandes
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
    } else {
        // L'utilisateur ne voit que ses commandes
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.user_id = :user_id 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
    }

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug pour vérifier les commandes récupérées
    // var_dump($orders);

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des commandes : " . $e->getMessage();
    $orders = [];
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Gestion de la suppression
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    try {
        $id = (int)$_GET['delete'];
        
        // Supprimer d'abord les assignations d'imprimante
        $stmt = $pdo->prepare("DELETE FROM printer_assignments WHERE order_id = ?");
        $stmt->execute([$id]);
        
        // Puis supprimer la commande
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Commande supprimée avec succès";
        }
        
        header('Location: list.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js"></script>

<div class="container">
    <h1 class="page-title">Liste des commandes</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="create.php" class="btn btn-primaryL"><span>➕</span>Nouvelle commande</a>
        <a href="export/orders_export.php" class="btn btn-secondary"><span>📊</span>Exporter en Excel</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Fichier STL</th>
                    <th>Couleur</th>
                    <th>Dimensions</th>
                    <th>Type de filament</th>
                    <th>Durée estimée</th>
                    <th>Statut</th>
                    <th>Date de création</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td data-label="Client"><?php echo htmlspecialchars($order['client_name']); ?></td>
                    <td data-label="Fichier STL">
                        <div class="stl-viewer" data-file="/Ymmersion4B2/orders/uploads/<?php echo htmlspecialchars($order['stl_file']); ?>" style="width: 100px; height: 100px;"></div>
                    </td>
                    <td data-label="Couleur"><?php echo htmlspecialchars($order['color']); ?></td>
                    <td data-label="Dimensions"><?php echo htmlspecialchars($order['dimensions']); ?></td>
                    <td data-label="Type de filament"><?php echo htmlspecialchars($order['filament_type']); ?></td>
                    <td data-label="Durée estimée"><?php echo $order['estimated_time']; ?> min</td>
                    <td data-label="Statut">
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </td>
                    <td data-label="Date de création"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td data-label="Actions">
                            <a href="?delete=<?php echo $order['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')">
                                Supprimer
                            </a>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="<?php echo $_SESSION['role'] === 'admin' ? '9' : '8'; ?>" class="text-center">
                        Aucune commande trouvée
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function initSTLViewer(element) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xffffff);

    const camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
    camera.position.z = 5;

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(100, 100);
    element.appendChild(renderer.domElement);

    const loader = new THREE.STLLoader();
    const filePath = element.dataset.file;

    loader.load(filePath, function (geometry) {
        const material = new THREE.MeshPhongMaterial({
            color: 0x808080,
            specular: 0x111111,
            shininess: 200
        });
        const mesh = new THREE.Mesh(geometry, material);

        // Centrer et ajuster l'échelle
        geometry.computeBoundingBox();
        const center = geometry.boundingBox.getCenter(new THREE.Vector3());
        geometry.center();
        const size = geometry.boundingBox.getSize(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 3 / maxDim;
        mesh.scale.set(scale, scale, scale);

        scene.add(mesh);

        // Éclairage
        const light = new THREE.DirectionalLight(0xffffff, 1);
        light.position.set(1, 1, 1);
        scene.add(light);
        scene.add(new THREE.AmbientLight(0x404040));

        // Animation
        function animate() {
            requestAnimationFrame(animate);
            mesh.rotation.y += 0.01;
            renderer.render(scene, camera);
        }
        animate();
    });
}

// Initialiser tous les visualiseurs STL
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stl-viewer').forEach(function(element) {
        initSTLViewer(element);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 