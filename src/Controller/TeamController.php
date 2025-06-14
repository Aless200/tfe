<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamUser;
use App\Entity\Tournament;
use App\Entity\User;
use App\Form\TeamType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TeamController extends AbstractController
{
    private $httpClient;
    private $mailer;

    public function __construct(HttpClientInterface $httpClient, MailerInterface $mailer)
    {
        $this->httpClient = $httpClient;
        $this->mailer = $mailer;
    }

    #[Route('/tournoi/create-team/{slug}', name: 'app_create_team')]
    public function createTeam(Request $request, EntityManagerInterface $manager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();

        $slug = $request->attributes->get('slug');
        $tournament = $manager->getRepository(Tournament::class)->findOneBy(['slug' => $slug]);

        if (!$tournament) {
            throw $this->createNotFoundException('Le tournoi n\'existe pas.');
        }

        // Vérifier si l'utilisateur a déjà une équipe
        $existingTeamUser = $manager->getRepository(TeamUser::class)->findOneBy([
            'user' => $user,
            'tournament' => $tournament
        ]);

        if ($existingTeamUser) {
            $this->addFlash('error', 'Vous êtes déjà inscrit dans une équipe pour ce tournoi.');
            return $this->redirectToRoute('app_tournament');
        }

        $team = new Team();
        $tournamentType = $tournament->getTypeTournament();
        $isDoublette = !empty($tournamentType) && $tournamentType[0] === 'doublette';

        $form = $this->createForm(TeamType::class, $team, [
            'isDoublette' => $isDoublette,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $recaptchaSecretKey = $_ENV['GOOGLE_RECAPTCHA_SECRET_KEY'];

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $response = $this->httpClient->request('POST', $url, [
                'query' => [
                    'secret' => $recaptchaSecretKey,
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $recaptchaData = $response->toArray();
            if (!$recaptchaData['success']) {
                $this->addFlash('error', 'La vérification reCAPTCHA a échoué.');
                return $this->redirectToRoute('app_create_team');
            }

            $teamName = $form->get('teamName')->getData();
            $emailInvitation1 = $form->get('emailInvitation1')->getData();
            $emailInvitation2 = null;

            if (!$isDoublette && $form->has('emailInvitation2')) {
                $emailInvitation2 = $form->get('emailInvitation2')->getData();
            }

            $team->setTeamName($teamName);
            $team->setRound(1);
            $team->setTournament($tournament);
            $manager->persist($team);
            $manager->flush();

            // Envoi de la confirmation au créateur
            $this->sendCreationConfirmation($user, $team, $tournament, $mailer);

            $teamUser = new TeamUser();
            $teamUser->setTeam($team);
            $teamUser->setUser($user);
            $teamUser->setInvited(false);
            $teamUser->setCreatedAt(new \DateTimeImmutable());
            $teamUser->setTournament($tournament);
            $manager->persist($teamUser);

            $emails = [$emailInvitation1];
            if (!$isDoublette && $emailInvitation2) {
                $emails[] = $emailInvitation2;
            }

            foreach ($emails as $email) {
                $invitationToken = bin2hex(random_bytes(32));
                $invitationUrl = $this->generateUrl('team_accept_invite', ['token' => $invitationToken], UrlGeneratorInterface::ABSOLUTE_URL);

                $typeTournament = $tournament->getTypeTournament();
                $typeDisplay = in_array('doublette', $typeTournament) ? 'doublette' : (in_array('triplette', $typeTournament) ? 'triplette' : 'Type inconnu');

                $emailMessage = (new Email())
                    ->from('pc.noiseux@gmail.com')
                    ->to($email)
                    ->subject('Invitation à rejoindre une équipe')
                    ->html($this->renderView('team/email/invitation.html.twig', [
                        'inviterName' => $user->getFirstName() . ' ' . $user->getLastName(),
                        'teamName' => $teamName,
                        'tournamentName' => $tournament->getName(),
                        'date' => $tournament->getDateTournament()->format('d/m/Y'),
                        'address' => $tournament->getAdresse(),
                        'type' => $typeDisplay,
                        'invitationUrl' => $invitationUrl
                    ]));

                $mailer->send($emailMessage);

                $teamUser = new TeamUser();
                $teamUser->setTeam($team);
                $teamUser->setInvitationToken($invitationToken);
                $teamUser->setInvited(true);
                $teamUser->setCreatedAt(new \DateTimeImmutable());
                $teamUser->setTournament($tournament);
                $teamUser->setEmailGuest($email);

                $invitedUser = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($invitedUser) {
                    $teamUser->setUser($invitedUser);
                }

                $manager->persist($teamUser);
            }

            $manager->flush();
            $this->addFlash('success', 'Votre équipe a été créée avec succès !');
            return $this->redirectToRoute('app_tournament');
        }

        return $this->render('team/index.html.twig', [
            'form' => $form->createView(),
            'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'],
            'tournament' => $tournament
        ]);
    }

    private function sendCreationConfirmation(User $creator, Team $team, Tournament $tournament, MailerInterface $mailer): void
    {
        $typeTournament = $tournament->getTypeTournament();
        $typeDisplay = in_array('doublette', $typeTournament) ? 'doublette' : (in_array('triplette', $typeTournament) ? 'triplette' : 'Type inconnu');

        $email = (new Email())
            ->from('pc.noiseux@gmail.com')
            ->to($creator->getEmail())
            ->subject('Confirmation de création de votre équipe')
            ->html($this->renderView('team/email/creation_confirmation.html.twig', [
                'creatorName' => $creator->getFirstName(),
                'teamName' => $team->getTeamName(),
                'tournamentName' => $tournament->getName(),
                'date' => $tournament->getDateTournament()->format('d/m/Y'),
                'address' => $tournament->getAdresse(),
                'type' => $typeDisplay,
                'invitedCount' => count($team->getTeamUsers()) - 1
            ]));

        $mailer->send($email);
    }

    #[Route('/team/accept/{token}', name: 'team_accept_invite', methods: ['GET'])]
    public function acceptInvite($token, EntityManagerInterface $manager, MailerInterface $mailer): Response
    {
        $teamUser = $manager->getRepository(TeamUser::class)->findOneBy(['invitationToken' => $token]);

        if (!$teamUser) {
            throw $this->createNotFoundException('Invitation introuvable.');
        }

        if (!$teamUser->isInvited()) {
            $this->addFlash('error', 'Cette invitation a déjà été acceptée.');
            return $this->redirectToRoute('app_home');
        }

        $teamUser->setInvited(false);
        $manager->flush();

        $this->sendConfirmationToTeam($teamUser->getTeam(), $mailer);

        $this->addFlash('success', 'Vous avez rejoint l\'équipe avec succès !');
        return $this->redirectToRoute('app_tournament');
    }

    private function sendConfirmationToTeam(Team $team, MailerInterface $mailer): void
    {
        $teamUsers = $team->getTeamUsers();
        $teamName = $team->getTeamName();
        $tournament = $team->getTournament();

        $typeTournament = $tournament->getTypeTournament();
        $typeDisplay = in_array('doublette', $typeTournament) ? 'doublette' : (in_array('triplette', $typeTournament) ? 'triplette' : 'Type inconnu');

        foreach ($teamUsers as $teamUser) {
            $email = $teamUser->getUser() ? $teamUser->getUser()->getEmail() : $teamUser->getEmailGuest();

            if ($teamUser->getUser() === $team->getTeamUsers()->first()->getUser()) {
                $subject = 'Votre équipe est complète';
            } else {
                $subject = 'Confirmation de participation à l\'équipe';
            }

            $emailMessage = (new Email())
                ->from('pc.noiseux@gmail.com')
                ->to($email)
                ->subject($subject)
                ->html($this->renderView('team/email/confirmation.html.twig', [
                    'teamName' => $teamName,
                    'tournamentName' => $tournament->getName(),
                    'date' => $tournament->getDateTournament()->format('d/m/Y'),
                    'address' => $tournament->getAdresse(),
                    'type' => $typeDisplay
                ]));

            $mailer->send($emailMessage);
        }
    }
}