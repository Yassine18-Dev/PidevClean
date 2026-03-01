document.addEventListener('DOMContentLoaded', () => {
    const cartBtn = document.getElementById('cart-btn');
    const cartPanel = document.getElementById('cart-panel');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const cartCount = document.getElementById('cart-count');

    console.log('Cart script loaded'); // Debug

    function loadCart() {
        console.log('Loading cart...'); // Debug
        fetch('/cart/json')
            .then(res => {
                console.log('Cart response status:', res.status); // Debug
                return res.json();
            })
            .then(data => {
                console.log('Cart data received:', data); // Debug
                cartItems.innerHTML = '';
                cartTotal.textContent = data.total;
                cartCount.textContent = data.count;

                if (data.items.length === 0) {
                    cartItems.innerHTML = '<small class="text-muted">Panier vide</small>';
                    return;
                }

                data.items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'cart-item d-flex justify-content-between align-items-center mb-2';
                    
                    // Afficher le nom avec la taille si présente
                    const displayName = item.size ? `${item.name} (${item.size})` : item.name;
                    
                    div.innerHTML = `
                        <span>${displayName} × ${item.quantity}</span>
                        <span>${item.price} €</span>
                        <button class="btn btn-sm btn-danger remove-cart-btn" 
                                data-cartkey="${item.cartKey}" 
                                data-id="${item.id}"
                                title="Supprimer"
                                style="background: #dc3545; color: white; border: none; padding: 2px 8px; border-radius: 3px; cursor: pointer;">
                            ×
                        </button>
                    `;
                    cartItems.appendChild(div);
                });

                // Utiliser la délégation d'événements pour éviter les problèmes
                cartItems.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.remove-cart-btn');
                    if (removeBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Essayer data-cartkey d'abord, puis data-id
                        let cartKey = removeBtn.dataset.cartkey;
                        if (!cartKey) {
                            cartKey = removeBtn.dataset.id;
                            console.warn('Utilisation de data-id au lieu de data-cartkey:', cartKey);
                        }
                        
                        console.log('Tentative de suppression du cartKey:', cartKey);
                        
                        if (!cartKey) {
                            console.error('Aucun cartKey ou ID trouvé sur le bouton');
                            alert('Erreur: identifiant manquant');
                            return;
                        }
                        
                        // Confirmation
                        if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                            return;
                        }
                        
                        console.log('Envoi de la requête à:', `/cart/remove/${cartKey}`);
                        
                        // Désactiver le bouton pendant la requête
                        removeBtn.disabled = true;
                        removeBtn.textContent = '...';
                        
                        fetch(`/cart/remove/${cartKey}`, { 
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => {
                            console.log('Response status:', res.status);
                            if (!res.ok) {
                                throw new Error(`HTTP error! status: ${res.status}`);
                            }
                            return res.json();
                        })
                        .then(resData => {
                            console.log('Response data:', resData);
                            if (resData.success) {
                                console.log('Suppression réussie, rechargement du panier');
                                loadCart(); // recharge le panier
                            } else {
                                console.error('Échec de la suppression:', resData);
                                alert('Erreur lors de la suppression: ' + (resData.error || 'Erreur inconnue'));
                                // Réactiver le bouton en cas d'erreur
                                removeBtn.disabled = false;
                                removeBtn.textContent = '×';
                            }
                        })
                        .catch(err => {
                            console.error('Erreur lors de la suppression:', err);
                            alert('Erreur technique lors de la suppression: ' + err.message);
                            // Réactiver le bouton en cas d'erreur
                            removeBtn.disabled = false;
                            removeBtn.textContent = '×';
                        });
                    }
                });
            })
            .catch(err => {
                console.error('Erreur lors du chargement du panier:', err);
                cartItems.innerHTML = '<small class="text-danger">Erreur de chargement</small>';
            });
    }

    if (cartBtn) {
        cartBtn.addEventListener('click', () => {
            cartPanel.classList.toggle('open');
            loadCart();
        });
    } else {
        console.error('Cart button not found');
    }
    
    // Charger le panier au démarrage
    loadCart();

    const checkoutBtn = document.getElementById('checkout-btn');

if (checkoutBtn) {
    checkoutBtn.addEventListener('click', (e) => {
        e.preventDefault();

        fetch('/cart/checkout', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Vider le panier visuellement
                cartItems.innerHTML = '<small class="text-success">Commande passée ! ✅</small>';
                cartTotal.textContent = '0';
                cartCount.textContent = '0';

                // Animation de succès
                cartPanel.classList.add('success');
                setTimeout(() => {
                    cartPanel.classList.remove('success');
                    cartPanel.classList.remove('open'); // fermer le panneau
                }, 2000);
            } else {
                alert('Erreur lors du paiement.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur serveur lors du paiement.');
        });
    });
}

}



);
