<nav class="navbar">
    <div class="navbar-container">
        <a href="/Ymmersion4B2" class="navbar-brand">FabLab</a>
        
        <button class="burger-menu" id="burger-menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-menu" id="navbar-menu">
            <a href="/Ymmersion4B2/orders/list.php" class="navbar-link <?php echo strpos($_SERVER['REQUEST_URI'], 'orders/list') !== false ? 'active' : ''; ?>">Liste des commandes</a>
            <a href="/Ymmersion4B2/dashboard.php" class="navbar-link <?php echo $_SERVER['REQUEST_URI'] == '/Ymmersion4B2/dashboard.php' ? 'active' : ''; ?>">Tableau de bord</a>
            <a href="/Ymmersion4B2/printers/manage.php" class="navbar-link <?php echo strpos($_SERVER['REQUEST_URI'], 'printers') !== false ? 'active' : ''; ?>">Gestion des imprimantes</a>
            <a href="/Ymmersion4B2/users/manage.php" class="navbar-link <?php echo strpos($_SERVER['REQUEST_URI'], 'users') !== false ? 'active' : ''; ?>">Gestion des utilisateurs</a>
            <a href="/Ymmersion4B2/logout.php" class="navbar-link">Déconnexion</a>
        </div>
    </div>
</nav>

<script>
document.getElementById('burger-menu').addEventListener('click', function() {
    document.getElementById('navbar-menu').classList.toggle('active');
    
    // Animation du burger
    this.classList.toggle('active');
    const spans = this.getElementsByTagName('span');
    if (this.classList.contains('active')) {
        spans[0].style.transform = 'rotate(-45deg) translate(-5px, 6px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(45deg) translate(-5px, -6px)';
    } else {
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    }
});
</script>