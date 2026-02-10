document.addEventListener('DOMContentLoaded', () => {
    const containers = document.querySelectorAll('.secondary-slider');

    containers.forEach(container => {
        const images = container.querySelectorAll('.secondary-img');
        const nextBtn = container.querySelector('.next-btn');
        const prevBtn = container.querySelector('.prev-btn');

        if (images.length <= 1) return;

        let currentIndex = 0;

        const updateView = () => {
            images.forEach((img, i) => {
                img.style.display = i === currentIndex ? 'block' : 'none';
            });
        };

        updateView(); // affiche la première image

        nextBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // IMPORTANT (empêche le <a>)
            currentIndex = (currentIndex + 1) % images.length;
            updateView();
        });

        prevBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateView();
        });
    });
});
