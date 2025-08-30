<?php

namespace App\Controller;

use App\Class\Contact;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{

    #[Route('/contact', name: 'app_contact')]
    public function contact(MailerInterface $mailer, Request $request): Response
    {
        $contact = new Contact();
        $formContact = $this->createForm(ContactType::class, $contact);
        $formContact->handleRequest($request);

        if ($formContact->isSubmitted() && $formContact->isValid()) {
            // Remplacez 'votre_adresse@example.com' par votre propre adresse.
            $destinataire = 'Pcm.2025@petanque.eu';

            // Rendre le template Twig pour créer le corps HTML de l'email
            $htmlBody = $this->renderView('contact/email_template.html.twig', [
                'contact' => $contact,
                'subject' => $contact->getSubject(), // Passer le sujet au template
            ]);

            $email = (new Email())
                ->from('contact@votresite.com') // Une adresse de votre site
                ->to($destinataire) // Vous êtes le destinataire
                ->replyTo($contact->getEmail()) // Permet de répondre directement à l'expéditeur du formulaire
                ->subject($contact->getSubject())
                ->html($htmlBody); // Le contenu HTML stylisé

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('contact/contact.html.twig', [
            'formContact' => $formContact,
        ]);
    }

//    #[Route('/contact', name: 'app_contact')]
//    public function contact(MailerInterface $mailer): Response
//    {
//        $email = (new Email())
//            ->from('john.doe@example.com')
//            ->to('info@example.com')
//            ->subject('Question')
//            ->text('Je demande des infos...');
//        $mailer->send($email);
//        return $this->redirectToRoute('app_home');
//    }

}
