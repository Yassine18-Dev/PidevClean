document.addEventListener("DOMContentLoaded", () => {

    const openBtn = document.getElementById("open-payment-modal");
    const modal = document.getElementById("payment-modal");
    const closeBtn = document.getElementById("close-payment");
    const payBtn = document.getElementById("pay-btn");

    if (!openBtn || !modal || !closeBtn || !payBtn) {
        console.error("Payment elements missing");
        return;
    }

    const nameInput = modal.querySelector('input[placeholder="MOATEZ BEN SALEM"]');
    const cardInput = modal.querySelector('input[placeholder="4242 4242 4242 4242"]');
    const expInput  = modal.querySelector('input[placeholder="MM/AA"]');    
    const cvcInput  = modal.querySelector('input[placeholder="123"]');

    // OPEN / CLOSE MODAL
    openBtn.onclick = () => modal.style.display = "flex";
    closeBtn.onclick = () => modal.style.display = "none";

    // VALIDATION
    function isValidName(name){ return /^[A-Za-z ]{3,}$/.test(name); }
    function isVisa(card){ return /^4[0-9]{15}$/.test(card); }
    function isMaster(card){ return /^5[1-5][0-9]{14}$/.test(card); }

    function isValidExpiry(exp){
        if(!/^\d{2}\/\d{2}$/.test(exp)) return false;
        const [mm, yy] = exp.split("/").map(Number);
        if(mm < 1 || mm > 12) return false;

        const now = new Date();
        const year = now.getFullYear() % 100;
        const month = now.getMonth()+1;

        return (yy > year) || (yy === year && mm >= month);
    }

    function isValidCVC(cvc){ return /^\d{3}$/.test(cvc); }

    // CLICK PAY
    payBtn.onclick = () => {

        const name = nameInput.value.trim();
        const card = cardInput.value.replace(/\s/g,"");
        const exp  = expInput.value.trim();
        const cvc  = cvcInput.value.trim();
        const tokenElement = document.getElementById("csrf_token");
        const csrfToken = tokenElement ? tokenElement.value : "";
        if(!isValidName(name)){ alert("Nom invalide"); return; }
        if(!isVisa(card) && !isMaster(card)){ alert("Carte invalide"); return; }
        if(!isValidExpiry(exp)){ alert("Date invalide"); return; }
        if(!isValidCVC(cvc)){ alert("CVC invalide"); return; }

        payBtn.innerText = "Paiement en cours...";
        payBtn.disabled = true;

        // ✅ PAS DE BODY NI JSON
        fetch("/cart/checkout", {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                }
           
        }

    )
        .then(res => res.json())
        .then(data => {

            if(!data.success){
                alert(data.error || "Erreur paiement");
                payBtn.disabled = false;
                payBtn.innerText = "Payer";
                return;
            }

            modal.innerHTML = `
                <div class="payment-box">
                    <h2>✅ Paiement réussi</h2>
                    <p>Carte : ${card.startsWith("4") ? "VISA" : "MASTERCARD"}</p>
                    <p>Commande #${data.orderId}</p>
                </div>
            `;

            // Vider panier
            fetch("/cart/clear");

            setTimeout(() => {
                window.location.href = "/shop/merch";
            }, 2000);
        })
        .catch(err => {
            console.error(err);
            alert("Erreur serveur");
            payBtn.disabled = false;
            payBtn.innerText = "Payer";
        });

    };

});
