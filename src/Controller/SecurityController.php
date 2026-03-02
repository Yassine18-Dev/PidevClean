<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'ui_login', methods: ['GET','POST'])]
    public function login(AuthenticationUtils $auth): Response
    {
        if ($this->getUser()) return $this->redirectToRoute('ui_profile');

        return $this->render('user/login.html.twig', [
            'last_username' => $auth->getLastUsername(),
            'error' => $auth->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'ui_logout')]
    public function logout(): void
    {
        throw new \LogicException('Handled by Symfony logout.');
    }
}
