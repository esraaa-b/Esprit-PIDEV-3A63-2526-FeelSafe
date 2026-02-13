<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'admin_settings')]
    public function index()
    {
        return $this->render('admin/settings/index.html.twig');
    }
}
