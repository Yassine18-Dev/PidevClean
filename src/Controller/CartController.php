<?php

namespace App\Controller;

use App\Entity\ShopProduct;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderItem;
use App\Service\CartPromotionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;


class CartController extends AbstractController
{
    private CartPromotionService $cartPromotionService;

    public function __construct(CartPromotionService $cartPromotionService)
    {
        $this->cartPromotionService = $cartPromotionService;
    }
    // Ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(ShopProduct $product, SessionInterface $session, Request $request): Response
    {
        $cart = $session->get('cart', []);
        
        // Pour les produits merch, on utilise la clé produit_taille
        $cartKey = (string) $product->getId();
        if ($product->getType() === 'merch') {
            $selectedSize = $request->request->get('selected_size');
            if ($selectedSize) {
                $cartKey .= '_' . $selectedSize;
            } else {
                $this->addFlash('error', 'Veuillez sélectionner une taille pour ce produit.');
                return $this->redirectToRoute('shop', ['type' => strtolower($product->getType())]);
            }
        }

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]++; // incrémente la quantité
        } else {
            $cart[$cartKey] = 1; // première fois
        }

        $session->set('cart', $cart);
        
        // Debug pour voir ce qui est ajouté au panier
        error_log('Product added to cart: ' . json_encode(['cartKey' => $cartKey, 'cart' => $cart]));

        $this->addFlash('success', $product->getName() . ' ajouté au panier !');

        return $this->redirectToRoute('shop', ['type' => strtolower($product->getType())]);
    }

    // Afficher le panier
    #[Route('/cart', name: 'cart_show')]
    public function show(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        
        // Debug pour voir le contenu du panier
        error_log('Cart content: ' . json_encode($cart));
        
        $products = [];
        $sizes = [];

        if (!empty($cart)) {
            foreach ($cart as $cartKey => $quantity) {
                // Extraire l'ID du produit et la taille si c'est un merch
                $parts = explode('_', $cartKey);
                $productId = (int) $parts[0];
                $sizeId = isset($parts[1]) ? (int) $parts[1] : null;
                
                $product = $em->getRepository(ShopProduct::class)->find($productId);
                if ($product) {
                    $products[$cartKey] = $product;
                    if ($sizeId) {
                        $size = $em->getRepository(\App\Entity\Size::class)->find($sizeId);
                        $sizes[$cartKey] = $size;
                    }
                }
            }
        }

        // Préparer les données pour le service de promotion
        $cartItemsForPromotion = [];
        foreach ($cart as $cartKey => $quantity) {
            if (isset($products[$cartKey])) {
                $cartItemsForPromotion[$cartKey] = [
                    'product' => $products[$cartKey],
                    'quantity' => $quantity
                ];
            }
        }

        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
            'products' => $products,
            'sizes' => $sizes,
            'cart_calculation' => $this->cartPromotionService->calculateCartTotal($cartItemsForPromotion),
            'has_promotions' => $this->cartPromotionService->hasPromotions($cartItemsForPromotion)
        ]);
    }

    // API JSON pour le panier (utilisé par le JavaScript)
    #[Route('/cart/json', name: 'cart_json', methods: ['GET'])]
    public function cartJson(SessionInterface $session, EntityManagerInterface $em): JsonResponse
    {
        $cart = $session->get('cart', []);
        $products = [];
        $sizes = [];
        $total = 0;
        $count = 0;

        if (!empty($cart)) {
            foreach ($cart as $cartKey => $quantity) {
                // Extraire l'ID du produit et la taille si c'est un merch
                $parts = explode('_', $cartKey);
                $productId = (int) $parts[0];
                $sizeId = isset($parts[1]) ? (int) $parts[1] : null;
                
                $product = $em->getRepository(ShopProduct::class)->find($productId);
                if ($product) {
                    $itemTotal = $product->getPrice() * $quantity;
                    $total += $itemTotal;
                    $count += $quantity;
                    
                    $sizeName = '';
                    if ($sizeId) {
                        $size = $em->getRepository(\App\Entity\Size::class)->find($sizeId);
                        if ($size) {
                            $sizeName = $size->getName();
                            $sizes[$cartKey] = $size;
                        }
                    }
                    
                    $products[] = [
                        'cartKey' => $cartKey, // Ajout du cartKey pour la suppression
                        'id' => $productId,
                        'name' => $product->getName(),
                        'price' => $product->getPrice(),
                        'quantity' => $quantity,
                        'total' => $itemTotal,
                        'image' => $product->getImage(),
                        'size' => $sizeName // Ajout de la taille pour l'affichage
                    ];
                }
            }
        }

        return $this->json([
            'items' => $products,
            'total' => round($total, 2),
            'count' => $count,
            'sizes' => $sizes
        ]);
    }

    // Mettre à jour la quantité d'un produit dans le panier
    #[Route('/cart/update/{cartKey}', name: 'cart_update', methods: ['POST'])]
    public function update(string $cartKey, Request $request, SessionInterface $session, EntityManagerInterface $em): JsonResponse
    {
        $cart = $session->get('cart', []);
        $quantity = $request->request->get('quantity', 1);
        
        if ($quantity > 0) {
            $cart[$cartKey] = $quantity;
        } else {
            unset($cart[$cartKey]);
        }
        
        $session->set('cart', $cart);
        
        return $this->json(['success' => true, 'quantity' => $quantity]);
    }

    // Créer la commande et simuler le paiement
    #[Route('/cart/checkout', name: 'app_order_checkout', methods: ['POST'])]
public function checkout(SessionInterface $session, EntityManagerInterface $em): JsonResponse 
{
    try {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non connecté'], 401);
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->json(['success' => false, 'error' => 'Votre panier est vide'], 400);
        }

        // Création de la commande
        $order = new ShopOrder();
        $order->setUser($user);
        $order->setStatus('PAID');
        $order->setTotal(0); 
        
        $em->persist($order);

        $total = 0;
        foreach ($cart as $cartKey => $quantity) {
            $parts = explode('_', $cartKey);
            $productId = (int) $parts[0];
            $sizeId = isset($parts[1]) ? (int) $parts[1] : null;
            
            $product = $em->getRepository(ShopProduct::class)->find($productId);
            if (!$product) continue;

            $orderItem = new ShopOrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());
            
            // Ajouter la taille si c'est un merch
            if ($sizeId) {
                $size = $em->getRepository(\App\Entity\Size::class)->find($sizeId);
                if ($size) {
                    $orderItem->setSize($size);
                }
            }

            $em->persist($orderItem);
            $total += $product->getPrice() * $quantity;
        }

        $order->setTotal($total);
        $em->flush();

        // Vider panier
        $session->remove('cart');

        return $this->json([
            'success' => true,
            'orderId' => $order->getId()
        ]);

    } catch (\Exception $e) {
        // Cela te dira exactement quelle colonne manque en base de données dans la console F12
        return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
#[Route('/cart/remove/{cartKey}', name: 'cart_remove', methods: ['POST'])]
public function remove(string $cartKey, SessionInterface $session, EntityManagerInterface $em): JsonResponse
{
    $cart = $session->get('cart', []);

    // Si le cartKey est un ID simple (ex: "10"), chercher dans le panier
    if (is_numeric($cartKey)) {
        $foundKey = null;
        foreach ($cart as $key => $quantity) {
            $parts = explode('_', $key);
            if ((int) $parts[0] === (int) $cartKey) {
                $foundKey = $key;
                break;
            }
        }
        
        if ($foundKey) {
            $cartKey = $foundKey;
        }
    }

    if (isset($cart[$cartKey])) {
        unset($cart[$cartKey]);
        $session->set('cart', $cart);
    }

    // Recalcul du total et du compteur
    $count = array_sum($cart);
    $total = 0;
    if (!empty($cart)) {
        foreach ($cart as $cartKey => $quantity) {
            $parts = explode('_', $cartKey);
            $productId = (int) $parts[0];
            
            $product = $em->getRepository(ShopProduct::class)->find($productId);
            if ($product) {
                $total += $product->getPrice() * $quantity;
            }
        }
    }

    return $this->json([
        'success' => true,
        'count' => $count,
        'total' => $total,
        'cartKey' => $cartKey // Pour le debug
    ]);
}
#[Route('/cart/clear', name: 'cart_clear')]
public function clear(SessionInterface $session): Response
{
    $session->set('cart', []);
    return new JsonResponse(['status' => 'ok']);
}
#[Route('/cart/create-temp-order', name: 'cart_create_temp_order', methods: ['POST'])]
public function createTempOrder(SessionInterface $session, EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user instanceof User) {
        return $this->json(['error' => 'Vous devez être connecté'], 403);
    }

    $cart = $session->get('cart', []);
    if (empty($cart)) {
        return $this->json(['error' => 'Votre panier est vide'], 400);
    }

    // Vérifier s'il existe déjà une commande PENDING
    $existingOrder = $em->getRepository(ShopOrder::class)->findOneBy([
        'user' => $user,
        'status' => 'PENDING'
    ]);
    if ($existingOrder) {
        return $this->json(['success' => true, 'orderId' => $existingOrder->getId()]);
    }

    // Créer une commande PENDING
    $order = new ShopOrder();
    $order->setUser($user);
    $order->setStatus('PENDING');
    $em->persist($order);

    $total = 0;
    foreach ($cart as $cartKey => $quantity) {
        $parts = explode('_', $cartKey);
        $productId = (int) $parts[0];
        $sizeId = isset($parts[1]) ? (int) $parts[1] : null;
        
        $product = $em->getRepository(ShopProduct::class)->find($productId);
        if (!$product) continue;

        $orderItem = new ShopOrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $orderItem->setPrice($product->getPrice());
        
        // Ajouter la taille si c'est un merch
        if ($sizeId) {
            $size = $em->getRepository(\App\Entity\Size::class)->find($sizeId);
            if ($size) {
                // Vous pourriez ajouter une relation size dans ShopOrderItem si nécessaire
            }
        }
        $em->persist($orderItem);

        $total += $product->getPrice() * $quantity;
    }

    $order->setTotal($total);
    $em->flush();

    return $this->json([
        'success' => true,
        'orderId' => $order->getId(),
        'status' => $order->getStatus(),
        'total' => $total
    ]);
}
}
