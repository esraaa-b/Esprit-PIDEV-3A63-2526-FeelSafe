<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/support')]
class SupportController extends AbstractController
{
    #[Route('', name: 'admin_support')]
    public function index()
    {
        return $this->render('admin/support/index.html.twig');
    }
}
