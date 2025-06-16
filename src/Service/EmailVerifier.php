<?php

// src/Service/EmailVerifier.php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerifier
{
    private $mailer;
    private $entityManager;
    private $router;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $entityManager, UrlGeneratorInterface $router)
    {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function sendEmailConfirmation(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('verification@example.com')
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'user' => $user,
                'signedUrl' => $this->generateSignedUrl('app_verify_email', ['id' => $user->getId()])
            ]);

        $this->mailer->send($email);
    }

    private function generateSignedUrl(string $routeName, array $parameters = []): string
    {
        return $this->router->generate($routeName, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
