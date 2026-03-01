<?php

namespace App\Controller\Admin;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/promotions')]
class PromotionController extends AbstractController
{
    private EntityManagerInterface $em;
    private ShopProductRepository $productRepository;

    public function __construct(EntityManagerInterface $em, ShopProductRepository $productRepository)
    {
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    /**
     * Liste des promotions
     */
    #[Route('/', name: 'admin_promotion_index')]
    public function index(): Response
    {
        // Limiter à 20 promotions pour éviter les problèmes de performance
        $promotions = $this->em->getRepository(Promotion::class)
            ->findBy([], ['createdAt' => 'DESC'], 20);
        
        return $this->render('admin/promotion/index.html.twig', [
            'promotions' => $promotions
        ]);
    }

    /**
     * Créer une nouvelle promotion
     */
    #[Route('/new', name: 'admin_promotion_new')]
    public function new(Request $request): Response
    {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Si la promotion s'applique à tous les produits, ajouter tous les produits actifs
            if ($promotion->isApplyToAllProducts()) {
                $products = $this->productRepository->findBy(['isActive' => true]);
                foreach ($products as $product) {
                    $promotion->addProduct($product);
                }
            }
            
            $this->em->persist($promotion);
            $this->em->flush();
            
            $this->addFlash('success', '✅ Promotion créée avec succès !');
            
            return $this->redirectToRoute('shop', ['type' => 'merch']);
        }
        
        return $this->render('admin/promotion/new.html.twig', [
            'form' => $form->createView(),
            'promotion' => $promotion
        ]);
    }

    /**
     * Créer une promotion (POST)
     */
    #[Route('/admin/promotions/create', name: 'admin_promotion_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
        }

        try {
            $promotion = new Promotion();
            $promotion->setCode($data['code'] ?? uniqid('PROMO_'));
            $promotion->setName($data['name']);
            $promotion->setDescription($data['description'] ?? null);
            $promotion->setType($data['type']);
            $promotion->setValue((float) $data['value']);
            $promotion->setMinAmount((float) ($data['minAmount'] ?? 0));
            $promotion->setStartDate(new \DateTime($data['startDate']));
            $promotion->setEndDate(new \DateTime($data['endDate']));
            $promotion->setIsActive($data['isActive'] ?? true);
            $promotion->setIsPublic($data['isPublic'] ?? true);
            $promotion->setApplyToAllProducts($data['applyToAllProducts'] ?? false);
            $promotion->setCreatedAt(new \DateTime());
            $promotion->setUpdatedAt(new \DateTime());

            // Ajouter les produits sélectionnés
            if (!empty($data['products'])) {
                foreach ($data['products'] as $productId) {
                    $product = $this->productRepository->find($productId);
                    if ($product) {
                        $promotion->addProduct($product);
                    }
                }
            }

            $this->em->persist($promotion);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => '✅ Promotion créée avec succès !',
                'promotion' => [
                    'id' => $promotion->getId(),
                    'code' => $promotion->getCode(),
                    'name' => $promotion->getName()
                ],
                'redirect' => $this->generateUrl('shop', ['type' => 'merch'])
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour récupérer les produits pour la création de promotion
     */
    #[Route('/admin/promotions/api/products', name: 'admin_promotion_api_products', methods: ['GET'])]
    public function apiProducts(Request $request): JsonResponse
    {
        $type = $request->query->get('type', 'all');
        $query = $request->query->get('q', '');
        
        $qb = $this->productRepository->createQueryBuilder('p');
        
        if ($type !== 'all') {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }
        
        if ($query) {
            $qb->andWhere('p.name LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        $products = $qb->getQuery()->getResult();
        
        $productData = array_map(function($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'type' => $product->getType(),
                'price' => $product->getPrice(),
                'image' => $product->getImage()
            ];
        }, $products);
        
        return $this->json([
            'success' => true,
            'products' => $productData
        ]);
    }

    /**
     * API pour calculer les statistiques de panier
     */
    #[Route('/admin/promotions/api/cart-stats', name: 'admin_promotion_api_cart_stats', methods: ['GET'])]
    public function apiCartStats(Request $request): JsonResponse
    {
        $productIds = $request->query->get('products', '');
        $discountType = $request->query->get('discountType', 'percentage');
        $discountValue = (float) $request->query->get('discountValue', 0);
        
        if (empty($productIds)) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun produit sélectionné'
            ]);
        }
        
        $ids = explode(',', $productIds);
        $products = $this->productRepository->findBy(['id' => $ids]);
        
        $totalOriginal = 0;
        $totalDiscount = 0;
        $totalFinal = 0;
        
        foreach ($products as $product) {
            $originalPrice = $product->getPriceAsFloat();
            $totalOriginal += $originalPrice;
            
            if ($discountType === 'percentage') {
                $discount = $originalPrice * ($discountValue / 100);
            } else {
                $discount = $discountValue;
            }
            
            $totalDiscount += $discount;
            $totalFinal += max(0, $originalPrice - $discount);
        }
        
        return $this->json([
            'success' => true,
            'stats' => [
                'productCount' => count($products),
                'totalOriginal' => round($totalOriginal, 2),
                'totalDiscount' => round($totalDiscount, 2),
                'totalFinal' => round($totalFinal, 2),
                'discountType' => $discountType,
                'discountValue' => $discountValue,
                'averageDiscount' => count($products) > 0 ? round($totalDiscount / count($products), 2) : 0
            ]
        ]);
    }

    /**
     * Supprimer une promotion
     */
    #[Route('/admin/promotions/{id}/delete', name: 'admin_promotion_delete', methods: ['POST'])]
    public function delete(Promotion $promotion): JsonResponse
    {
        try {
            $this->em->remove($promotion);
            $this->em->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Promotion supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}
