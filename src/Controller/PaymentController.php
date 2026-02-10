<?php

namespace App\Controller;

use App\Entity\ShopOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentController extends AbstractController
{
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

        // 🔐 Sécurité : seul le propriétaire peut payer
        if ($order->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // ✅ Plus de Stripe ici ! On simule que tout est prêt.
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

        // 🔐 Sécurité
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // ✅ Paiement simulé réussi : On passe le statut à PAID
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