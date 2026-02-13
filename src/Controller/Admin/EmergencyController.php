<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emergency')]
class EmergencyController extends AbstractController
{
    #[Route('', name: 'admin_emergency')]
    public function index()
    {
        return $this->render('admin/emergency/index.html.twig');
    }
}
