<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Enum\SignalementStatus;
use App\Form\SignalementType;
use App\Repository\BusStopRepository;
use App\Repository\MotifGraviteRepository;
use App\Security\TicketTokenHasher;
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
        MotifGraviteRepository $motifGraviteRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        HttpClientInterface $client,
        TicketTokenHasher $ticketTokenHasher,
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
            $signalement->setSubmittedAt(new \DateTimeImmutable());
            $signalement->setStatus(SignalementStatus::EnAttenteValidation);
            $signalement->setConfianceScore(100);

            $gravite = 1;
            if ($signalement->getMotif() !== null) {
                $gravite = $motifGraviteRepository->find($signalement->getMotif())?->getGravite() ?? 1;
            }

            $signalement->setPrioriteScore($this->computePriorityScore($gravite, $signalement->getConfianceScore()));

            $rawTicketToken = bin2hex(random_bytes(32));
            $signalement->setTokenHash($ticketTokenHasher->hashToken($rawTicketToken));
            $signalement->setAccessToken(bin2hex(random_bytes(32)));

            $ttlDays = max(1, (int) $this->getParameter('ticket_token_ttl_days'));
            $signalement->setTokenExpiresAt($signalement->getSubmittedAt()->modify(sprintf('+%d days', $ttlDays)));

            $entityManager->persist($signalement);
            $entityManager->flush();

            $ticketPath = $this->generateUrl(
                'app_ticket_show',
                [
                    'id' => $signalement->getId(),
                    'token' => $rawTicketToken,
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
            $webhookSecret = trim((string) $this->getParameter('n8n_webhook_secret'));
            $isDevLikeEnv = \in_array((string) $this->getParameter('kernel.environment'), ['dev', 'test'], true);

            if ($webhookUrl !== '') {
                if ($webhookSecret === '') {
                    $logger->error('N8N webhook secret is empty. Webhook call blocked.', [
                        'signalement_id' => $signalement->getId(),
                        'env' => (string) $this->getParameter('kernel.environment'),
                    ]);

                    if (!$isDevLikeEnv) {
                        throw new \RuntimeException('Webhook secret missing outside dev/test.');
                    }
                } else {
                    try {
                        $response = $client->request('POST', $webhookUrl, [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'X-Webhook-Secret' => $webhookSecret,
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
                        $reference = bin2hex(random_bytes(8));
                        $logger->error('Webhook n8n call failed.', [
                            'reference' => $reference,
                            'exception_class' => $exception::class,
                            'signalement_id' => $signalement->getId(),
                        ]);
                    }
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

    private function computePriorityScore(int $gravite, int $confianceScore): int
    {
        $score = ($gravite * 15) + intdiv($confianceScore, 4);

        return max(0, min(100, $score));
    }
}
