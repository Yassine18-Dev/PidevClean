<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminUiController extends AbstractController
{
    #[Route('/admin', name: 'ui_admin', methods: ['GET'])]
    public function dashboard(UserRepository $repo): Response
    {
        $total = $repo->count([]);

        $active = $repo->count(['status' => User::STATUS_ACTIVE]);
        $suspended = $repo->count(['status' => User::STATUS_SUSPENDED]);
        $banned = $repo->count(['status' => User::STATUS_BANNED]);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => $total,
                'active' => $active,
                'suspended' => $suspended,
                'banned' => $banned,
            ],
        ]);
    }

    #[Route('/admin/users', name: 'ui_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        // ACTIONS status
        $action = $request->query->get('action');
        $id = $request->query->getInt('id');

        if ($action && $id) {
            $u = $repo->find($id);

            if ($u instanceof User) {
                if ($action === 'activate') $u->setStatus(User::STATUS_ACTIVE);
                if ($action === 'suspend')  $u->setStatus(User::STATUS_SUSPENDED);
                if ($action === 'ban')      $u->setStatus(User::STATUS_BANNED);

                $em->flush();
                $this->addFlash('success', "User #$id updated: $action");
            }
        }

        // LISTE + tri simple
        $users = $repo->findBy([], ['id' => 'DESC']);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'q' => '',
            'sort' => 'id',
            'dir' => 'DESC',
        ]);
    }

    #[Route('/admin/users/{id}', name: 'ui_admin_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $repo): Response
    {
        $u = $repo->find($id);
        if (!$u) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/user_show.html.twig', ['u' => $u]);
    }
}
