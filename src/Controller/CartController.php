<?php

namespace App\Controller;

use App\Entity\ShopProduct;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartController extends AbstractController
{
    // Ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(ShopProduct $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$product->getId()])) {
            $cart[$product->getId()]++; // incrémente la quantité
        } else {
            $cart[$product->getId()] = 1; // première fois
        }

        $session->set('cart', $cart);

        $this->addFlash('success', $product->getName() . ' ajouté au panier !');

        return $this->redirectToRoute('shop', ['type' => strtolower($product->getType())]);
    }

    // Afficher le panier
    #[Route('/cart', name: 'cart_show')]
    public function show(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        $products = [];

        if (!empty($cart)) {
            $products = $em->getRepository(ShopProduct::class)->findBy(['id' => array_keys($cart)]);
        }

        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
            'products' => $products,
        ]);


    }

    // Créer la commande et simuler le paiement
    #[Route('/cart/checkout', name: 'app_order_checkout', methods: ['POST'])]
public function checkout(SessionInterface $session, EntityManagerInterface $em): JsonResponse 
{
    try {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Vous devez être connecté'], 403);
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
        foreach ($cart as $productId => $quantity) {
            $product = $em->getRepository(ShopProduct::class)->find($productId);
            if (!$product) continue;

            $orderItem = new ShopOrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());

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
    #[Route('/cart/json', name: 'cart_json')]
public function cartJson(SessionInterface $session, EntityManagerInterface $em): JsonResponse
{
    $cart = $session->get('cart', []);
    $items = [];
    $total = 0;

    if (!empty($cart)) {
        $products = $em->getRepository(ShopProduct::class)->findBy(['id' => array_keys($cart)]);
        foreach ($products as $product) {
            $quantity = $cart[$product->getId()];
            $items[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice() * $quantity,
                'quantity' => $quantity
            ];
            $total += $product->getPrice() * $quantity;
        }
    }

    $count = array_sum($cart);

    return $this->json([   // <--- ici, on peut appeler AbstractController::json
        'items' => $items,
        'total' => $total,
        'count' => $count
    ]);
}
#[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
public function remove($id, SessionInterface $session, EntityManagerInterface $em): JsonResponse
{
    $id = (int) $id; // convertit la chaîne en entier
    $cart = $session->get('cart', []);

    if (isset($cart[$id])) {
        unset($cart[$id]);
        $session->set('cart', $cart);
    }

    // Recalcul du total et du compteur
    $count = array_sum($cart);
    $total = 0;
    if (!empty($cart)) {
        $products = $em->getRepository(ShopProduct::class)->findBy(['id' => array_keys($cart)]);
        foreach ($products as $product) {
            $total += $product->getPrice() * $cart[$product->getId()];
        }
    }

    return $this->json([
        'success' => true,
        'count' => $count,
        'total' => $total,
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
    if (!$user) {
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
    foreach ($cart as $productId => $quantity) {
        $product = $em->getRepository(ShopProduct::class)->find($productId);
        if (!$product) continue;

        $orderItem = new ShopOrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $orderItem->setPrice($product->getPrice());
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
