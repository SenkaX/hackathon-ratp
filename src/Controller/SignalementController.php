<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Form\SignalementType;
use App\Repository\BusStopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SignalementController extends AbstractController
{
    #[Route('/signalement', name: 'app_signalement', methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        BusStopRepository $busStopRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        HttpClientInterface $client,
    ): Response {
        $signalement = new Signalement();
        $signalement->setIncidentDate(new \DateTimeImmutable());

        $stopId = $request->query->get('stop_id');
        if (\is_string($stopId) && $stopId !== '') {
            $signalement->setStop($busStopRepository->find($stopId));
        }

        $form = $this->createForm(SignalementType::class, $signalement);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setAccessToken(bin2hex(random_bytes(32)));
            $signalement->setSubmittedAt(new \DateTimeImmutable());

            $entityManager->persist($signalement);
            $entityManager->flush();

            $ticketPath = $this->generateUrl(
                'app_ticket_show',
                [
                    'id' => $signalement->getId(),
                    'token' => (string) $signalement->getAccessToken(),
                ],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );

            $ticketUrl = rtrim((string) $this->getParameter('app.url'), '/').$ticketPath;

            $payload = [
                'signalement_id' => $signalement->getId(),
                'email' => $signalement->getEmail(),
                'motif' => $signalement->getMotif()?->value,
                'details' => $signalement->getDetails(),
                'stop_id' => $signalement->getStop()?->getId(),
                'incident_date' => $signalement->getIncidentDate()?->format(\DateTimeInterface::ATOM),
                'submitted_at' => $signalement->getSubmittedAt()->format(\DateTimeInterface::ATOM),
                'ticket_url' => $ticketUrl,
            ];

            $webhookUrl = (string) $this->getParameter('n8n_webhook_url');
            if ($webhookUrl !== '') {
                try {
                    $response = $client->request('POST', $webhookUrl, [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'X-Webhook-Secret' => (string) $this->getParameter('n8n_webhook_secret'),
                        ],
                        'json' => $payload,
                    ]);

                    $statusCode = $response->getStatusCode();
                    if ($statusCode < 200 || $statusCode >= 300) {
                        $logger->error('Webhook n8n returned non-success status.', [
                            'status_code' => $statusCode,
                            'signalement_id' => $signalement->getId(),
                        ]);
                    }
                } catch (\Throwable $exception) {
                    $logger->error('Webhook n8n call failed.', [
                        'exception' => $exception,
                        'signalement_id' => $signalement->getId(),
                    ]);
                }
            } else {
                $logger->warning('N8N webhook URL is empty, webhook was not called.', [
                    'signalement_id' => $signalement->getId(),
                ]);
            }

            return $this->redirectToRoute('app_signalement_confirmation');
        }

        return $this->render('signalement/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/signalement/confirmation', name: 'app_signalement_confirmation', methods: ['GET'])]
    public function confirmation(): Response
    {
        return $this->render('signalement/confirmation.html.twig');
    }
}
