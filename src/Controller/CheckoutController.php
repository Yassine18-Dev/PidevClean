<?php

namespace App\Controller;

use App\Entity\ShopOrder;
use App\Entity\ShopOrderItem;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function checkout(
        SessionInterface $session,
        ShopProductRepository $productRepo,
        EntityManagerInterface $em
    ) {
        $cart = $session->get('cart', []);

        if (!$cart) {
            return $this->redirectToRoute('cart_show');
        }

        $order = new ShopOrder();
        $order->setUser($this->getUser());

        $total = 0;

        foreach ($cart as $id => $qty) {
            $product = $productRepo->find($id);

            $item = new ShopOrderItem();
            $item->setProduct($product);
            $item->setQuantity($qty);
            $item->setPrice($product->getPrice());

            $order->addItem($item);
            $total += $product->getPrice() * $qty;
        }

        $order->setTotal($total);
        $order->setStatus('PAID');

        $em->persist($order);
        $em->flush();

        $session->remove('cart');

        return $this->redirectToRoute('home');
    }
}
