document.addEventListener('DOMContentLoaded', function() {
    console.log("Script chargé"); // Pour vérifier que le script se charge
    const menuBtn = document.getElementById('mobileMenuBtn');
    const menu = document.getElementById('navMenu');

    if (menuBtn && menu) {
        console.log("Éléments trouvés"); // Pour vérifier que les éléments sont trouvés
        menuBtn.addEventListener('click', function() {
            console.log("Click détecté"); // Pour vérifier que le click fonctionne
            menu.classList.toggle('show');
        });

        // Ferme le menu quand on clique en dehors
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Ferme le menu quand on clique sur un lien
        const links = menu.getElementsByTagName('a');
        for (let link of links) {
            link.addEventListener('click', function() {
                menu.classList.remove('show');
            });
        }
    } else {
        console.log("Éléments non trouvés"); // Pour debug
    }
}); 