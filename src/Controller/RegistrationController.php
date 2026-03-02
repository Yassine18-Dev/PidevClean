<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'ui_register', methods: ['GET','POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): Response {
        if ($this->getUser()) return $this->redirectToRoute('ui_profile');

        if ($request->isMethod('POST')) {
            $u = new User();
            $u->setUsername(trim((string)$request->request->get('username')));
            $u->setEmail(trim((string)$request->request->get('email')));
            $u->setRoleType((string)$request->request->get('roleType','PLAYER'));
            $u->setStatus(User::STATUS_ACTIVE);
            $u->setRoles(['ROLE_USER']);

            $plain = (string)$request->request->get('password');
            if (strlen($plain) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters.');
                return $this->redirectToRoute('ui_register');
            }

            $u->setPassword($hasher->hashPassword($u, $plain));

            $errors = $validator->validate($u);
            if (count($errors) > 0) {
                foreach ($errors as $e) $this->addFlash('error', $e->getPropertyPath().': '.$e->getMessage());
                return $this->redirectToRoute('ui_register');
            }

            $em->persist($u);
            $em->flush();

            $this->addFlash('success', 'Account created! You can login.');
            return $this->redirectToRoute('ui_login');
        }

        return $this->render('user/register.html.twig');
    }
}
