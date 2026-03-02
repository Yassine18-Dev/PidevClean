<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserAdminController extends AbstractController
{
    #[Route('', name: 'ui_admin_users', methods: ['GET'])]
    public function index(Request $request, UserRepository $repo, EntityManagerInterface $em): Response
    {
        // Actions statut via query
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

        // Recherche + tri
        $q = trim((string)$request->query->get('q', ''));
        $sort = (string)$request->query->get('sort', 'id');
        $dir = (string)$request->query->get('dir', 'DESC');

        $users = $repo->qbSearchSort($q ?: null, $sort, $dir)
            ->getQuery()
            ->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'q' => $q,
            'sort' => $sort,
            'dir' => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
        ]);
    }

    #[Route('/new', name: 'ui_admin_user_new', methods: ['GET','POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => false]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string)$form->get('newPassword')->getData();

            $user->setRoles(['ROLE_USER']); // par défaut
            $user->setPassword($hasher->hashPassword($user, $plain));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created.');
            return $this->redirectToRoute('ui_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'title' => 'Create User',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'ui_admin_user_show', methods: ['GET'])]
    public function show(User $u): Response
    {
        return $this->render('admin/user_show.html.twig', ['u' => $u]);
    }

    #[Route('/{id}/edit', name: 'ui_admin_user_edit', methods: ['GET','POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string)$form->get('newPassword')->getData();
            if ($plain !== '') {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            $em->flush();
            $this->addFlash('success', 'User updated.');
            return $this->redirectToRoute('ui_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'title' => 'Edit User',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'ui_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('del_user_'.$user->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('ui_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted.');
        return $this->redirectToRoute('ui_admin_users');
    }
}
