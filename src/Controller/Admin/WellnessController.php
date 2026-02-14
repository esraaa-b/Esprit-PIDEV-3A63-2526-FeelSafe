<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/wellness')]
class WellnessController extends AbstractController
{
    #[Route('', name: 'admin_wellness')]
    public function index()
    {
        return $this->render('admin/wellness/index.html.twig');
    }
}
