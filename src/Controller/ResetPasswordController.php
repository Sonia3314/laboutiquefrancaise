<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Entity\ResetPassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{   
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {   
        if ($this->getUser()){

            return $this->redirectToRoute('home');
        }
        if ($request->get('email')){
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user){
                //1: enregistrement en bdd de la demande de reset du mot de passe avec user token et createdAt
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \Datetime());
                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                // 2 : Envoi d'un mail au user avec un lien permettant la mise à jour du mot de passe
                
                $url = $this->generateUrl('update_password', [

                    'token' => $reset_password->getToken()
                ]);

                $content = "Bonjour ".$user->getFirstname().",<br>Vous avez demandé à réinitialiser votre mot de passe sur le site La Boutique Française.<br><br>"; 
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe</a>.";
                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname().' '. $user->getLastname(), 'Réinitialiser votre mot de passe sur La Boutique Française', $content);
                
                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un email avec la procédure de réinitialisation de votre mot de passe.');

            } else {

                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="update_password")
     */
    public function update(Request $request, $token, UserPasswordEncoderInterface $encoder){

        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if(!$reset_password) {
            return $this->redirectToRoute('reset_password');
        }
        
        // verifier si le createdAt = now -3h

        $now = new \Datetime();

        if ($now > $reset_password->getCreatedAt()->modify('+ 3 hour')){

            $this->addFlash('notice', 'Votre demande de réinitialisation de mot de passe a expiré. <br> Merci de la renouveler.');
            return $this->redirectToRoute('reset_password');
        
        }

        // rendre une vue avec mot de passe et confirmez votre mot de passe

        $form = $this->createForm(ResetPasswordtype::class); 
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $new_pwd = $form->get('new_password')->getData();

            //Encodage des mots de passe
            $password = $encoder->encodePassword($reset_password->getUser(), $new_pwd);

            $reset_password->getUser()->setPassword($password);

            //Flush en base de données
            $this->entityManager->flush();

            //redirection de l'utilisateur vers la page de connexion
            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            
            'form' => $form->createView()
        ]);


    }
}
