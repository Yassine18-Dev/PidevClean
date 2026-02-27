<?php

namespace App\Controller;

use App\Service\AnalyticsService;
use App\Service\ChatbotService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support')]
#[IsGranted('ROLE_USER')]
class SupportApiController extends AbstractController
{
    private const MAX_MESSAGE_LENGTH  = 1000;
    private const MAX_HISTORY_TURNS   = 10;
    private const MAX_HISTORY_CONTENT = 2000;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/chat', name: 'api_support_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        ChatbotService $chatbot,
    ): JsonResponse {
        $data    = json_decode($request->getContent(), true) ?? [];
        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            return $this->json(['error' => 'Message is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return $this->json(
                ['error' => sprintf('Message too long (max %d characters).', self::MAX_MESSAGE_LENGTH)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $history = $this->sanitizeHistory($data['history'] ?? []);

        try {
            $reply = $chatbot->chat($message, $history);
        } catch (\Throwable $e) {
            $this->logger->error('Chatbot API failure', [
                'error'   => $e->getMessage(),
                'user_id' => $this->getUser()?->getId(),
            ]);

            return $this->json(
                ['error' => 'Chat service is temporarily unavailable. Please try again shortly or create a support ticket.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json([
            'reply'     => $reply,
            'available' => $chatbot->isAvailable(),
        ]);
    }

    /**
     * Sanitize client-supplied conversation history:
     * - Accept only 'user' and 'assistant' roles
     * - Cap history at last N turns
     * - Truncate individual content strings
     */
    private function sanitizeHistory(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach (array_slice($raw, -self::MAX_HISTORY_TURNS) as $msg) {
            if (!isset($msg['role'], $msg['content'])) {
                continue;
            }
            if (!in_array($msg['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $clean[] = [
                'role'    => $msg['role'],
                'content' => mb_substr((string) $msg['content'], 0, self::MAX_HISTORY_CONTENT),
            ];
        }

        return $clean;
    }

    #[Route('/analytics/categories', name: 'api_support_analytics_categories', methods: ['GET'])]
    public function analyticsCategories(AnalyticsService $analytics): JsonResponse
    {
        $data = $analytics->getTicketsByCategory();

        return $this->json([
            'labels' => array_column($data, 'category'),
            'values' => array_map('intval', array_column($data, 'cnt')),
        ]);
    }

    #[Route('/analytics/status', name: 'api_support_analytics_status', methods: ['GET'])]
    public function analyticsStatus(AnalyticsService $analytics): JsonResponse
    {
        $data = $analytics->getStatusDistribution();

        return $this->json([
            'labels' => array_map(
                static fn(string $s) => ucfirst(str_replace('_', ' ', $s)),
                array_keys($data)
            ),
            'values' => array_values($data),
        ]);
    }

    #[Route('/analytics/trends', name: 'api_support_analytics_trends', methods: ['GET'])]
    public function analyticsTrends(AnalyticsService $analytics): JsonResponse
    {
        $data = $analytics->getTicketTrends(30);

        return $this->json([
            'labels' => array_keys($data),
            'values' => array_values($data),
        ]);
    }
}