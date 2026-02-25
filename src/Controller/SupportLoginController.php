<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SupportLoginController extends AbstractController
{
    #[Route('/support/login', name: 'support_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        // If already logged in, go straight to support hub
        if ($this->getUser()) {
            return $this->redirectToRoute('support_role_select');
        }

        return $this->render('support/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }
}