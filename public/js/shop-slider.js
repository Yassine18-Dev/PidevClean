document.addEventListener('DOMContentLoaded', () => {
    // Fonction pour initialiser les sliders sur n'importe quelle page
    function initSliders() {
        // Debug: voir si des sliders sont trouvés
        const sliders = document.querySelectorAll('.secondary-slider');
        console.log('Sliders found:', sliders.length);
        
        sliders.forEach((slider, index) => {
            console.log(`Slider ${index}:`, slider);
            const images = slider.querySelectorAll('.secondary-img');
            const prevBtn = slider.querySelector('.prev-btn');
            const nextBtn = slider.querySelector('.next-btn');
            
            console.log(`Images in slider ${index}:`, images.length);
            
            if (images.length === 0) {
                console.log(`Slider ${index}: No images found`);
                return;
            }
            
            let currentIndex = 0;
            
            const updateView = () => {
                console.log(`Updating slider ${index}: showing image ${currentIndex}`);
                images.forEach((img, i) => {
                    img.style.display = i === currentIndex ? 'block' : 'none';
                });
            };

            updateView(); // affiche la première image

            nextBtn?.addEventListener('click', (e) => {
                console.log('Next button clicked on slider', index);
                e.preventDefault();
                e.stopPropagation(); // IMPORTANT (empêche le <a>)
                currentIndex = (currentIndex + 1) % images.length;
                updateView();
            });

            prevBtn?.addEventListener('click', (e) => {
                console.log('Prev button clicked on slider', index);
                e.preventDefault();
                e.stopPropagation();
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                updateView();
            });
        });
    }
    
    // Initialiser les sliders au chargement
    initSliders();
    
    // Réinitialiser les sliders si le contenu change (pour AJAX)
    const observer = new MutationObserver((mutations) => {
        initSliders();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
