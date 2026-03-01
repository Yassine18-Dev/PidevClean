<?php
namespace App\Controller;

use App\Entity\Game; 
use App\Entity\ShopProduct;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/shop/{type}', name: 'shop')]
    public function index(Request $request, EntityManagerInterface $em, string $type): Response
    {
        // récupère le paramètre de tri, default = ASC
        $order = $request->query->get('order', 'asc');

        // récupère le paramètre de jeu sélectionné (optionnel)
        $game = $request->query->get('game'); // null si aucun

        // récupère tous les jeux pour le select (limité pour performance)
        $games = $em->getRepository(Game::class)->findBy([], ['name' => 'ASC'], 50);

        // récupération des produits selon type + filtre jeu avec eager loading pour éviter N+1
        $qb = $em->createQueryBuilder();
        $qb->select('p', 'i')
           ->from(ShopProduct::class, 'p')
           ->leftJoin('p.images', 'i')
           ->where('p.type = :type')
           ->andWhere('p.isActive = :active')
           ->setParameter('type', $type)
           ->setParameter('active', true);

        // filtre par jeu si choisi
        if ($game) {
            $qb->andWhere('p.game = :game')
               ->setParameter('game', $game);
        }

        // tri par prix avec LIMIT
        $qb->orderBy('p.price', $order === 'asc' ? 'ASC' : 'DESC')
           ->setMaxResults(50);

        $products = $qb->getQuery()->getResult();

        return $this->render('shop/index.html.twig', [
            'type' => strtoupper($type),
            'products' => $products,
            'order' => $order,
            'games' => $games,
            'game' => $game,
  
        ]);
    }
    
}
