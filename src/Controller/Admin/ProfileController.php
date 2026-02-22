<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'admin_profile')]
    public function index()
    {
        return $this->render('admin/profile/index.html.twig');
    }
}
