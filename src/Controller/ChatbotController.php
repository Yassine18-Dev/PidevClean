<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Simple API controller that proxies chat messages to the ChatbotService.
 * All requests must be POST with JSON { message: string, history: [] }
 */
final class ChatbotController extends AbstractController
{
    public function __construct(private ChatbotService $chatbot, private LoggerInterface $logger)
    {
    }

    #[Route('/api/chatbot/message', name: 'api_chatbot_message', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function message(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $message = isset($data['message']) ? (string)$data['message'] : '';
        $history = isset($data['history']) && is_array($data['history']) ? $data['history'] : [];

        if ($message === '') {
            return $this->json(['reply' => 'Please provide a message.'], 400);
        }

        try {
            $reply = $this->chatbot->chat($message, $history);
            return $this->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            $this->logger->error('Chatbot controller error', ['error' => $e->getMessage()]);
            return $this->json(['reply' => 'The AI assistant is currently unavailable. Please try again later.'], 500);
        }
    }
}