document.addEventListener('DOMContentLoaded', () => {
    const cartBtn = document.getElementById('cart-btn');
    const cartPanel = document.getElementById('cart-panel');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const cartCount = document.getElementById('cart-count');

    function loadCart() {
        fetch('/cart/json')
            .then(res => res.json())
            .then(data => {
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
                    div.innerHTML = `
                        <span>${item.name} × ${item.quantity}</span>
                        <span>${item.price} €</span>
                        <button class="btn btn-sm btn-danger remove-cart-btn" data-id="${item.id}">×</button>
                    `;
                    cartItems.appendChild(div);
                });

                // événements suppression
                document.querySelectorAll('.remove-cart-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        fetch(`/cart/remove/${id}`, { method: 'POST' })
                            .then(res => res.json())
                            .then(resData => {
                                if (resData.success) {
                                    loadCart(); // recharge le panier
                                }
                            });
                    });
                });
            });
    }

    cartBtn.addEventListener('click', () => {
        cartPanel.classList.toggle('open');
        loadCart();
    });
    

    loadCart(); // initial

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
