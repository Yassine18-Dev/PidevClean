class AIRecommendations {
    constructor() {
        this.recommendations = [];
        this.isLoading = false;
        this.init();
    }

    init() {
        this.loadRecommendations();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Recharger les recommandations quand le panier change
        document.addEventListener('cartUpdated', () => {
            this.loadRecommendations();
        });
    }

    async loadRecommendations() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch('/api/recommendations');
            const data = await response.json();
            
            if (data.success) {
                this.recommendations = data.recommendations;
                this.renderRecommendations();
            } else {
                this.showError();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des recommandations:', error);
            this.showError();
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    renderRecommendations() {
        const container = document.getElementById('ai-recommendations');
        if (!container) return;

        if (this.recommendations.length === 0) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div style="color: #a78bfa; font-size: 2rem;">🤖</div>
                    <p style="color: #a78bfa;">Ajoutez des produits au panier pour voir nos recommandations IA</p>
                </div>
            `;
            return;
        }

        const recommendationsHTML = this.recommendations.map(product => this.createProductCard(product)).join('');
        
        container.innerHTML = `
            <div class="ai-recommendations-header mb-3">
                <h3 style="color: #facc15; font-size: 1.2rem;">
                    🤖 Recommandations IA pour vous
                </h3>
                <p style="color: #a78bfa; font-size: 0.9rem;">Basées sur vos produits dans le panier</p>
            </div>
            <div class="row">
                ${recommendationsHTML}
            </div>
        `;
    }

    createProductCard(product) {
        const priceDisplay = product.hasPromotion ? 
            `<div style="text-decoration: line-through; color: #888; font-size: 0.9rem;">${product.price} €</div>
             <div style="color: #facc15; font-weight: bold;">${product.finalPrice} €</div>` :
            `<div style="color: #facc15; font-weight: bold;">${product.price} €</div>`;

        const promotionBadge = product.hasPromotion ? 
            `<span class="badge" style="background: #ef4444; color: white; font-size: 0.7rem;">🔥 ${product.formattedDiscount}</span>` : '';

        return `
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card" style="background: #140b25; border: 1px solid #7c3aed; border-radius: 8px; height: 100%;">
                    <div class="p-3">
                        <div class="text-center mb-2">
                            ${product.image ? 
                                `<img src="/uploads/shop/${product.image}" alt="${product.name}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;">` :
                                `<div style="width: 100%; height: 120px; background: rgba(124, 58, 237, 0.1); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #a78bfa;">📦</div>`
                            }
                        </div>
                        <h6 class="text-white text-center mb-2" style="font-size: 0.9rem;">${product.name}</h6>
                        <div class="text-center mb-2">
                            ${priceDisplay}
                        </div>
                        <div class="text-center mb-2">
                            ${promotionBadge}
                        </div>
                        <div class="d-flex justify-content-center">
                            <a href="/shop/product/${product.id}" class="btn btn-sm" style="background: #7c3aed; color: white; border: none; border-radius: 4px;">
                                Voir
                            </a>
                            <form method="post" action="/cart/add/${product.id}" style="margin-left: 5px;">
                                <button type="submit" class="btn btn-sm" style="background: #10b981; color: white; border: none; border-radius: 4px;">
                                    🛒
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    showLoading() {
        const container = document.getElementById('ai-recommendations');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border" role="status" style="color: #7c3aed;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p style="color: #a78bfa; margin-top: 10px;">L'IA analyse vos préférences...</p>
                </div>
            `;
        }
    }

    hideLoading() {
        // Le chargement est masqué automatiquement par renderRecommendations()
    }

    showError() {
        const container = document.getElementById('ai-recommendations');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div style="color: #ef4444; font-size: 2rem;">⚠️</div>
                    <p style="color: #ef4444;">Impossible de charger les recommandations</p>
                    <button onclick="aiRecommendations.loadRecommendations()" class="btn btn-sm" style="background: #ef4444; color: white; border: none; border-radius: 4px; margin-top: 10px;">
                        Réessayer
                    </button>
                </div>
            `;
        }
    }
}

// Initialiser les recommandations IA quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    window.aiRecommendations = new AIRecommendations();
});

// Événement personnalisé pour notifier les changements du panier
function notifyCartUpdated() {
    document.dispatchEvent(new Event('cartUpdated'));
}
