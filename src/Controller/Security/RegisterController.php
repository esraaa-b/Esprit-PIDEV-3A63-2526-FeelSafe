<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(Request $request): Response
    {
        // Get any errors or form data from previous submission
        $error = $request->query->get('error');
        $name = $request->query->get('name', '');
        $email = $request->query->get('email', '');
        $acceptTerms = $request->query->get('acceptTerms', false);

        return $this->render('auth/register/index.html.twig', [
            'error' => $error,
            'name' => $name,
            'email' => $email,
            'acceptTerms' => $acceptTerms,
            'isLoading' => false,
        ]);
    }

    #[Route('/register/submit', name: 'app_register_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        // Get form data
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirmPassword');
        $acceptTerms = $request->request->get('acceptTerms', false);

        // TODO: Add actual registration logic here
        // For now, simulate success
        return $this->redirectToRoute('app_login');
    }
}