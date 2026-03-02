<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'ui_forgot_password', methods: ['GET','POST'])]
    public function forgot(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $emailTo = trim((string)$request->request->get('email'));
            $user = $repo->findOneBy(['email' => $emailTo]);

            if ($user instanceof User) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
                $em->flush();

                $resetLink = $request->getSchemeAndHttpHost()
                    . $this->generateUrl('ui_reset_password', ['token' => $token]);

                $mail = (new Email())
                    ->from('no-reply@arenamind.tn')
                    ->to($emailTo)
                    ->subject('ArenaMind - Reset your password')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'resetLink' => $resetLink,
                        'email' => $emailTo,
                    ]));

                try {
                    $mailer->send($mail);
                    $this->addFlash('success', 'Reset link sent! Check your email.');
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Mailer not configured. Use this link: '.$resetLink);
                }
            } else {
                $this->addFlash('success', 'If your email exists, you will receive a reset link.');
            }

            return $this->redirectToRoute('ui_forgot_password');
        }

        return $this->render('user/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'ui_reset_password', methods: ['GET','POST'])]
    public function reset(
        Request $request,
        string $token,
        UserRepository $repo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $repo->findOneBy(['resetToken' => $token]);

        if (!$user instanceof User || !$user->isResetTokenValid($token)) {
            return $this->render('user/reset_password.html.twig', ['valid' => false]);
        }

        if ($request->isMethod('POST')) {
            $p1 = (string)$request->request->get('password');
            $p2 = (string)$request->request->get('password_confirm');

            if (strlen($p1) < 6) {
                $this->addFlash('error', 'Password min 6 characters.');
                return $this->redirectToRoute('ui_reset_password', ['token' => $token]);
            }
            if ($p1 !== $p2) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('ui_reset_password', ['token' => $token]);
            }

            $user->setPassword($hasher->hashPassword($user, $p1));
            $user->setResetToken(null);
            $user->setResetExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'Password updated. Now login.');
            return $this->redirectToRoute('ui_login');
        }

        return $this->render('user/reset_password.html.twig', ['valid' => true]);
    }
}
