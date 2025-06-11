<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class GoogleAuthenticator extends AbstractAuthenticator
{
private UserRepository $userRepository;
private EntityManagerInterface $entityManager;
private OAuth2ClientInterface $googleClient;

public function __construct(
UserRepository $userRepository,
EntityManagerInterface $entityManager,
#[Autowire(service: 'knpu.oauth2.client.google')] OAuth2ClientInterface $googleClient
) {
$this->userRepository = $userRepository;
$this->entityManager = $entityManager;
$this->googleClient = $googleClient;
}

public function authenticate(Request $request): Passport
{
// Récupération du token d'accès via Google OAuth
$accessToken = $this->fetchAccessToken($this->googleClient);
$googleUser = $this->googleClient->fetchUserFromToken($accessToken);

return new Passport(
new UserBadge($googleUser->getEmail(), function() use ($googleUser) {
return $this->getOrCreateUser($googleUser);
})
);
}

private function getOrCreateUser(GoogleUser $googleUser): User
{
$user = $this->userRepository->findOneBy(['email' => $googleUser->getEmail()]);

if (!$user) {
$user = new User();
$user->setEmail($googleUser->getEmail());
$user->setPassword('google-oauth'); // Juste pour éviter les erreurs de validation
$user->setFirstname($googleUser->getFirstName());
$user->setLastname($googleUser->getLastName());

$this->entityManager->persist($user);
$this->entityManager->flush();
}

return $user;
}

public function supports(Request $request): ?bool
{
return $request->getPathInfo() === '/auth/oauth/check'; // Vérifier la route OAuth
}

public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
// Exemple de redirection vers la page d'accueil après succès
return new Response('Authentication Success');
}

public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
{
// Gérer l'échec d'authentification
return new Response('Authentication Failed');
}

private function fetchAccessToken(OAuth2ClientInterface $client)
{
// Récupère l'access token
return $client->getAccessToken();
}
}
