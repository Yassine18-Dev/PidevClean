<?php

namespace App\Controller;

use App\Form\PaymentType;
use App\Service\CartPromotionService;
use App\Entity\ShopOrder;
use App\Entity\ShopProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentController extends AbstractController
{
    private CartPromotionService $cartPromotionService;

    public function __construct(CartPromotionService $cartPromotionService)
    {
        $this->cartPromotionService = $cartPromotionService;
    }

    #[Route('/payment', name: 'payment')]
    public function payment(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        $cartCalculation = null;
        
        if (!empty($cart)) {
            // Préparer les produits pour le service de promotion
            $cartItemsForPromotion = [];
            foreach ($cart as $cartKey => $quantity) {
                $parts = explode('_', $cartKey);
                $productId = (int) $parts[0];
                
                $product = $em->getRepository(ShopProduct::class)->find($productId);
                if ($product) {
                    $cartItemsForPromotion[$cartKey] = [
                        'product' => $product,
                        'quantity' => $quantity
                    ];
                }
            }
            
            $cartCalculation = $this->cartPromotionService->calculateCartTotal($cartItemsForPromotion);
        }

        // Créer le formulaire
        $form = $this->createForm(PaymentType::class, [
            'amount' => $cartCalculation ? $cartCalculation['total_final'] : 0
        ]);

        return $this->render('payment/index.html.twig', [
            'cart' => $cart,
            'cartCalculation' => $cartCalculation,
            'hasPromotions' => !empty($cartCalculation['applied_promotions'] ?? []),
            'paymentForm' => $form->createView()
        ]);
    }

    #[Route('/payment/process', name: 'payment_process', methods: ['POST'])]
    public function processPayment(Request $request, SessionInterface $session): JsonResponse
    {
        // Récupérer les données du formulaire HTML
        $cardNumber = $request->request->get('cardNumber', '');
        $cardHolder = $request->request->get('cardHolder', '');
        $expiryDate = $request->request->get('expiryDate', '');
        $cvv = $request->request->get('cvv', '');
        $amount = $request->request->get('amount', 0);
        $terms = $request->request->get('terms', false);
        $saveCard = $request->request->get('saveCard', false);
        
        // Validation des données
        $errors = [];
        
        // Validation numéro de carte
        if (empty($cardNumber) || !preg_match('/^[0-9]{13,19}$/', (string) $cardNumber)) {
            $errors[] = 'Numéro de carte invalide';
        }
        
        // Validation nom du titulaire
        if (empty($cardHolder) || strlen((string) $cardHolder) < 3 || !preg_match('/^[a-zA-Z\s\-]+$/', (string) $cardHolder)) {
            $errors[] = 'Nom du titulaire invalide';
        }
        
        // Validation date d'expiration
        if (empty($expiryDate) || !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', (string) $expiryDate)) {
            $errors[] = 'Date d\'expiration invalide';
        } else {
            $parts = explode('/', (string) $expiryDate);
            $month = (int) $parts[0];
            $year = 2000 + (int) $parts[1];
            $expiryDateObj = new \DateTime("$year-$month-01");
            $currentDate = new \DateTime();
            
            if ($expiryDateObj <= $currentDate) {
                $errors[] = 'Carte expirée';
            }
        }
        
        // Validation CVC
        if (empty($cvv) || !preg_match('/^[0-9]{3,4}$/', (string) $cvv)) {
            $errors[] = 'CVC invalide';
        }
        
        // Validation conditions générales
        if (!$terms) {
            $errors[] = 'Vous devez accepter les conditions générales de vente';
        }
        
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'message' => implode(', ', $errors)
            ], 400);
        }

        // Simuler le traitement du paiement
        $paymentResult = $this->processPaymentSimulation([
            'cardNumber' => $cardNumber,
            'cardHolder' => $cardHolder,
            'expiryDate' => $expiryDate,
            'cvv' => $cvv,
            'amount' => $amount,
            'terms' => $terms,
            'saveCard' => $saveCard
        ]);
        
        if ($paymentResult['success']) {
            // Vider le panier après paiement réussi
            $session->set('cart', []);
            
            return $this->json([
                'success' => true,
                'message' => 'Paiement traité avec succès !',
                'orderId' => $paymentResult['orderId'] ?? '',
                'amount' => $paymentResult['amount'] ?? 0
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => $paymentResult['message']
            ], 400);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string, orderId?: string, amount?: float, cardLast4?: string, transactionId?: string}
     */
    private function processPaymentSimulation(array $data): array
    {
        // Simulation d'un traitement de paiement
        // En production, vous appelleriez une API comme Stripe, PayPal, etc.
        
        $cardNumber = (string) ($data['cardNumber'] ?? '');
        $cardHolder = (string) ($data['cardHolder'] ?? '');
        $expiryDate = (string) ($data['expiryDate'] ?? '');
        $cvv = (string) ($data['cvv'] ?? '');
        $amount = (float) ($data['amount'] ?? 0);
        
        // Validation de base (déjà faite dans le formulaire)
        if (empty($cardNumber) || empty($cardHolder) || empty($expiryDate) || empty($cvv)) {
            return [
                'success' => false,
                'message' => 'Informations de paiement incomplètes'
            ];
        }

        // Simulation de validation de carte (en production, utilisez une vraie API)
        if (!$this->validateCardExpiry($expiryDate)) {
            return [
                'success' => false,
                'message' => 'Carte expirée ou date invalide'
            ];
        }

        // Simuler un traitement réussi
        return [
            'success' => true,
            'message' => 'Paiement validé et traité',
            'orderId' => 'ORD_' . uniqid(),
            'amount' => $amount,
            'cardLast4' => substr($cardNumber, -4),
            'transactionId' => 'TXN_' . uniqid()
        ];
    }

    private function validateCardExpiry(string $expiryDate): bool
    {
        // Format MM/AA
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiryDate)) {
            return false;
        }

        $parts = explode('/', $expiryDate);
        $month = (int) $parts[0];
        $year = 2000 + (int) $parts[1];
        $currentDate = new \DateTime();
        $expiryDate = new \DateTime("$year-$month-01");
        
        return $expiryDate > $currentDate;
    }

    /**
     * Cette méthode remplace la création d'intention Stripe par une validation simple.
     */
    #[Route('/payment/create/{orderId}', name: 'payment_create', methods: ['POST'])]
    public function createPayment(int $orderId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(ShopOrder::class)->find($orderId);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        // Sécurité : seul le propriétaire peut payer
        if ($order->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Plus de Stripe ici ! On simule que tout est prêt.
        return $this->json([
            'success' => true,
            'message' => 'Prêt pour le paiement simulé',
            'orderId' => $order->getId()
        ]);
    }

    #[Route('/payment/success/{orderId}', name: 'payment_success')]
    public function paymentSuccess(int $orderId, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(ShopOrder::class)->find($orderId);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // Sécurité
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Paiement simulé réussi : On passe le statut à PAID
        if ($order->getStatus() !== 'PAID') {
            $order->setStatus('PAID');
            $em->flush();
        }

        // On redirige vers une vue de succès (assure-toi que le template existe)
        return $this->render('payment/success.html.twig', [
            'order' => $order,
            'redirect_after' => '/shop/merch'
        ]);
    }
}