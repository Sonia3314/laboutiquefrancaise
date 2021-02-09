<?php

namespace App\Controller;

use App\Form\OrderType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    /**
     * @Route("/commande", name="order")
     */
    public function index(): Response
    {   
        // si le user n'a pas d'adresse, redirection vers l'ajout d'une adresse
        if (!$this->getUser()->getAddresses()->getValues()){

            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class, null, [

            'user' => $this->getUser()
        ]); 
        
        return $this->render('order/index.html.twig', [

            'form' => $form->createView()
        ]);
    }
}
