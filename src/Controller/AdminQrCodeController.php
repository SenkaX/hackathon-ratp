<?php

namespace App\Controller;

use App\Entity\BusStop;
use App\Repository\BusStopRepository;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/qrcodes')]
final class AdminQrCodeController extends AbstractController
{
    #[Route('', name: 'app_admin_qrcodes', methods: ['GET'])]
    public function index(BusStopRepository $busStopRepository): Response
    {
        $writer = new PngWriter();
        $appUrl = rtrim((string) $this->getParameter('app.url'), '/');

        $items = [];
        foreach ($busStopRepository->findBy([], ['label' => 'ASC']) as $stop) {
            $targetUrl = sprintf('%s/signalement?stop_id=%s', $appUrl, $stop->getId());
            $result = $writer->write(new QrCode(data: $targetUrl, size: 300, margin: 10));

            $items[] = [
                'stop' => $stop,
                'target_url' => $targetUrl,
                'image_base64' => base64_encode($result->getString()),
            ];
        }

        return $this->render('admin/qrcodes.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/{id}/download', name: 'app_admin_qrcode_download', methods: ['GET'])]
    public function download(BusStop $stop): Response
    {
        $targetUrl = sprintf('%s/signalement?stop_id=%s', rtrim((string) $this->getParameter('app.url'), '/'), $stop->getId());
        $result = (new PngWriter())->write(new QrCode(data: $targetUrl, size: 1000, margin: 20));

        $response = new Response($result->getString());
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, sprintf('qrcode-%s.png', $stop->getId())));

        return $response;
    }
}
