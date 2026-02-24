<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontHomeController extends AbstractController
{
    #[Route('/', name: 'ui_home', methods: ['GET','POST'])]
    public function home(Request $request): Response
    {
        // Support form (validation côté serveur, pas de JS)
        if ($request->isMethod('POST')) {
            $subject = trim((string) $request->request->get('subject'));
            $message = trim((string) $request->request->get('message'));

            if ($subject === '' || strlen($subject) < 3) {
                $this->addFlash('error', 'Subject must be at least 3 characters.');
                return $this->redirectToRoute('ui_home', ['#' => 'support']);
            }

            if ($message === '' || strlen($message) < 10) {
                $this->addFlash('error', 'Message must be at least 10 characters.');
                return $this->redirectToRoute('ui_home', ['#' => 'support']);
            }

            // Ici tu peux plus tard envoyer mail / enregistrer en DB
            $this->addFlash('success', 'Support request sent! We will contact you soon.');
            return $this->redirectToRoute('ui_home', ['#' => 'support']);
        }

        return $this->render('front/home.html.twig');
    }
}