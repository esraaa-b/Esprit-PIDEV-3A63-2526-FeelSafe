<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function index(Request $request): Response
    {
        // Check if form was submitted and show success state
        $isSubmitted = $request->query->get('submitted') === 'true';
        $email = $request->query->get('email', '');

        // Check for errors from form submission
        $error = $request->query->get('error');

        return $this->render('auth/forgot-password/index.html.twig', [
            'is_submitted' => $isSubmitted,
            'email' => $email,
            'error' => $error,
            'isLoading' => false,
        ]);
    }

    #[Route('/forgot-password/submit', name: 'app_forgot_password_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $email = $request->request->get('email');

        // TODO: Add actual password reset logic here
        // 1. Validate email
        // 2. Check if user exists
        // 3. Generate reset token
        // 4. Send email

        // For now, simulate success
        return $this->redirectToRoute('app_forgot_password', [
            'submitted' => 'true',
            'email' => $email,
        ]);
    }
}