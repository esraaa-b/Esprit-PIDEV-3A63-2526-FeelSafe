<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/forum')]
class ForumController extends AbstractController
{
    #[Route('', name: 'admin_forum')]
    public function index()
    {
        return $this->render('admin/forum/index.html.twig');
    }
}
