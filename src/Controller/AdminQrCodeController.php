<?php

namespace App\Controller;

use App\Entity\BusStop;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/qrcodes')]
final class AdminQrCodeController extends AbstractController
{
    #[Route('', name: 'app_admin_qrcodes', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_moderation_qrcodes');
    }

    #[Route('/{id}/download', name: 'app_admin_qrcode_download', methods: ['GET'])]
    public function download(BusStop $stop): Response
    {
        return $this->redirectToRoute('app_moderation_qrcode_download', ['id' => $stop->getId()]);
    }
}
