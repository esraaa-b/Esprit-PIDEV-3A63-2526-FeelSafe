<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/security')]
class SecurityController extends AbstractController
{
    #[Route('', name: 'admin_security')]
    public function index()
    {
        return $this->render('admin/security/index.html.twig');
    }
}
