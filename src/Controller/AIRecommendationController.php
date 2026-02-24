<?php

namespace App\Controller;

use App\Service\AIProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class AIRecommendationController extends AbstractController
{
    private AIProductService $aiService;
    private RequestStack $requestStack;
    private EntityManagerInterface $em;

    public function __construct(AIProductService $aiService, RequestStack $requestStack, EntityManagerInterface $em)
    {
        $this->aiService = $aiService;
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    /**
     * API pour obtenir des recommandations basées sur un produit spécifique
     */
    #[Route('/api/recommendations/product/{id}', name: 'api_product_recommendations', methods: ['GET'])]
    public function getProductRecommendations(int $id): JsonResponse
    {
        $product = $this->em
            ->getRepository(\App\Entity\ShopProduct::class)
            ->find($id);

        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }

        try {
            // Utiliser le produit actuel comme référence pour les recommandations
            error_log('Getting recommendations for product ID: ' . $id);
            $recommendations = $this->aiService->getRecommendationsForProduct($product, 4);
            error_log('Recommendations found: ' . count($recommendations));

            // Formater les recommandations pour le JSON
            $formattedRecommendations = [];
            foreach ($recommendations as $recommendedProduct) {
                $formattedRecommendations[] = [
                    'id' => $recommendedProduct->getId(),
                    'name' => $recommendedProduct->getName(),
                    'price' => $recommendedProduct->getPrice(),
                    'type' => $recommendedProduct->getType(),
                    'image' => $recommendedProduct->getImage(),
                    'finalPrice' => $recommendedProduct->getFinalPrice(),
                    'hasPromotion' => $recommendedProduct->hasActivePromotion(),
                    'formattedDiscount' => $recommendedProduct->getFormattedDiscount()
                ];
            }

            return $this->json([
                'success' => true,
                'recommendations' => $formattedRecommendations,
                'count' => count($formattedRecommendations)
            ]);
        } catch (\Exception $e) {
            error_log('Error in getProductRecommendations: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour obtenir des recommandations AI
     */
    #[Route('/api/recommendations', name: 'api_recommendations', methods: ['GET'])]
    public function getRecommendations(): JsonResponse
    {
        // Récupérer les produits du panier de l'utilisateur
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $userProducts = [];
        
        if (!empty($cart)) {
            // Convertir les IDs du panier en objets produits
            foreach ($cart as $productId => $quantity) {
                $product = $this->em
                    ->getRepository(\App\Entity\ShopProduct::class)
                    ->find($productId);
                
                if ($product) {
                    $userProducts[] = $product;
                }
            }
        }

        // Obtenir les recommandations AI
        $recommendations = $this->aiService->getRecommendationsForUser($userProducts, 4);

        // Formater les recommandations pour le JSON
        $formattedRecommendations = [];
        foreach ($recommendations as $product) {
            $formattedRecommendations[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'type' => $product->getType(),
                'image' => $product->getImage(),
                'finalPrice' => $product->getFinalPrice(),
                'hasPromotion' => $product->hasActivePromotion(),
                'formattedDiscount' => $product->getFormattedDiscount()
            ];
        }

        return $this->json([
            'success' => true,
            'recommendations' => $formattedRecommendations,
            'count' => count($formattedRecommendations)
        ]);
    }

    /**
     * API pour obtenir les catégories tendances
     */
    #[Route('/api/trending-categories', name: 'api_trending_categories', methods: ['GET'])]
    public function getTrendingCategories(): JsonResponse
    {
        $categories = $this->aiService->getTrendingCategories();

        return $this->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Générer une description AI pour un produit (admin seulement)
     */
    #[Route('/admin/product/{id}/generate-description', name: 'admin_generate_description', methods: ['POST'])]
    public function generateDescription(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $product = $this->em
            ->getRepository(\App\Entity\ShopProduct::class)
            ->find($id);

        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }

        try {
            $description = $this->aiService->generateProductDescription($product);
            
            return $this->json([
                'success' => true,
                'description' => $description
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer des tags AI pour un produit (admin seulement)
     */
    #[Route('/admin/product/{id}/generate-tags', name: 'admin_generate_tags', methods: ['POST'])]
    public function generateTags(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $product = $this->em
            ->getRepository(\App\Entity\ShopProduct::class)
            ->find($id);

        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }

        try {
            $tags = $this->aiService->generateProductTags($product);
            
            return $this->json([
                'success' => true,
                'tags' => $tags
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }
}
