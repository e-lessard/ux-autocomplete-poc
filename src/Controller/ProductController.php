<?php

namespace App\Controller;

use App\Form\ProductSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product')]
    public function index(Request $request): Response
    {

        $form = $this->createForm(ProductSearchType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dd($form->getData());
        }
        return $this->render('product/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
