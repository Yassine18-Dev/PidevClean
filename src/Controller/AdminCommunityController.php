<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin/community')]
#[IsGranted('ROLE_ADMIN')]
class AdminCommunityController extends AbstractController
{
    #[Route('/', name: 'admin_community_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Gérer uniquement les posts PENDING (signalés par l'IA)
        $posts = $em->getRepository(Post::class)->findBy(['status' => 'PENDING'], ['createdAt' => 'DESC']);

        return $this->render('admin_community/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}/approve', name: 'admin_community_approve')]
    public function approve(Post $post, EntityManagerInterface $em): Response
    {
        $post->setStatus('APPROVED');
        $em->flush();

        $this->addFlash('success', 'Le post a été approuvé et publié.');
        return $this->redirectToRoute('admin_community_index');
    }

    #[Route('/post/{id}/reject', name: 'admin_community_reject')]
    public function reject(Post $post, EntityManagerInterface $em): Response
    {
        $post->setStatus('REJECTED');
        $em->flush();

        $this->addFlash('error', 'Le post a été rejeté.');
        return $this->redirectToRoute('admin_community_index');
    }
}
