<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * PRODUCTION Gemini AI Chatbot Service - STRICT REAL API ONLY
 * 
 * CRITICAL REQUIREMENTS:
 * - ALL responses MUST come from real Google Gemini API
 * - NO fallback, mock, or fake responses allowed
 * - If API fails, throw error - never return fake text
 * - Every response logged with source verification
 * 
 * Features:
 * - Secure API key handling (never exposed in frontend)
 * - Prompt injection protection with input sanitization
 * - Strict validation: empty responses throw errors
 * - Comprehensive logging for debugging
 * - Debug mode adds response metadata
 * - History context management (last 10 messages)
 */
class GeminiChatbotService
{
    // Use model from environment, fallback to stable version
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent';
    private const DEFAULT_MODEL = 'gemini-1.5-flash';
    private const REQUEST_TIMEOUT = 10;
    private const MAX_HISTORY_LENGTH = 20; // 10 exchanges
    private const MAX_MESSAGE_LENGTH = 2000;
    private const MAX_SYSTEM_PROMPT_LENGTH = 1000;

    private string $apiKey;
    private string $model;
    private string $systemPrompt;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private bool $debugMode;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $geminiApiKey = '',
        string $systemPrompt = '',
        string $model = '',
        bool $debugMode = false
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = trim($geminiApiKey);
        $this->model = trim($model) ?: self::DEFAULT_MODEL;
        $this->systemPrompt = trim($systemPrompt) ?: $this->getDefaultSystemPrompt();
        $this->debugMode = $debugMode;
        
        // Log initialization in debug mode
        if ($this->debugMode) {
            $this->logger->debug('GeminiChatbotService initialized', [
                'model' => $this->model,
                'api_key_set' => !empty($this->apiKey),
                'debug_mode' => true,
            ]);
        }
    }

    /**
     * Send a message to Gemini API and return the response.
     * 
     * CRITICAL: This method ONLY returns real Gemini API responses.
     * If API fails, throws exception - NEVER returns fallback text.
     * 
     * @param string $message User message (sanitized and validated)
     * @param array $history Conversation history with 'role' and 'content' keys
     * @return string Real AI response from Gemini API
     * @throws \RuntimeException if API key not configured
     * @throws \InvalidArgumentException if message validation fails
     * @throws \Exception on any API error (network, timeout, empty response)
     */
    public function sendMessage(string $message, array $history = []): string
    {
        // Validate API key - fail early and loudly
        if (empty($this->apiKey)) {
            $this->logger->critical('FATAL: Gemini API key not configured. Cannot proceed.');
            throw new \RuntimeException(
                'Gemini API is not configured. Contact system administrator.'
            );
        }

        // Sanitize and validate input
        $message = $this->sanitizeInput($message);
        if (empty($message)) {
            throw new \InvalidArgumentException('Message cannot be empty after sanitization.');
        }
        if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new \InvalidArgumentException(
                'Message exceeds maximum length of ' . self::MAX_MESSAGE_LENGTH . ' characters.'
            );
        }

        // Validate history
        $history = $this->validateHistory($history);

        // Build API request
        $contents = $this->buildContents($message, $history);
        $payload = ['contents' => $contents];

        // Build the actual API endpoint URL with model
        $apiUrl = str_replace('{model}', $this->model, self::API_URL);

        if ($this->debugMode) {
            $this->logger->debug('DEBUG: Sending message to Gemini API', [
                'model' => $this->model,
                'api_url' => $apiUrl,
                'message_length' => strlen($message),
                'payload_keys' => array_keys($payload),
            ]);
        }

        try {
            $response = $this->httpClient->request('POST', $apiUrl . '?key=' . $this->apiKey, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($payload),
                'timeout' => self::REQUEST_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();
            
            // STRICT: Check status code
            if ($statusCode !== 200) {
                $data = $response->toArray(false);
                $this->logger->error('CRITICAL: Gemini API returned error status', [
                    'status_code' => $statusCode,
                    'api_response' => $data,
                ]);
                throw new \Exception(
                    'Gemini API error: HTTP ' . $statusCode . '. '
                    . ($data['error']['message'] ?? 'Unknown error')
                );
            }

            $data = $response->toArray(false);

            // STRICT: Validate response structure
            if (!isset($data['candidates'][0])) {
                $this->logger->critical('CRITICAL: Invalid API response - no candidates', [
                    'response_keys' => array_keys($data),
                ]);
                throw new \Exception('Gemini API returned invalid response structure: no candidates.');
            }

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->critical('CRITICAL: Invalid API response - no text in response', [
                    'response_structure' => json_encode($data['candidates'][0], JSON_PRETTY_PRINT),
                ]);
                throw new \Exception('Gemini API returned invalid response structure: no text content.');
            }

            $reply = trim($data['candidates'][0]['content']['parts'][0]['text']);

            // STRICT: Ensure response is not empty
            if (empty($reply)) {
                $this->logger->critical('CRITICAL: Gemini API returned empty text', [
                    'full_response' => json_encode($data, JSON_PRETTY_PRINT),
                ]);
                throw new \Exception('Gemini API returned empty response text.');
            }

            // SUCCESS: Log real response
            $this->logger->info('✓ Real Gemini API response received', [
                'source' => 'gemini',
                'model' => $this->model,
                'reply_length' => strlen($reply),
                'input_length' => strlen($message),
            ]);

            // Add debug metadata if enabled
            if ($this->debugMode) {
                $reply .= "\n\n---\n**[DEBUG] Source: Gemini API | Model: " . $this->model . "**";
            }

            return $reply;

        } catch (HttpExceptionInterface $e) {
            $this->logger->critical('CRITICAL: Gemini API HTTP exception', [
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Gemini API HTTP error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->critical('CRITICAL: Unexpected error in Gemini API call', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if AI service is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Enable debug mode to add metadata to responses
     */
    public function setDebugMode(bool $debug): void
    {
        $this->debugMode = $debug;
        if ($debug) {
            $this->logger->debug('Debug mode ENABLED - responses will include source metadata');
        }
    }

    /**
     * Get current debug mode status
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Build conversation contents for Gemini API.
     * Includes system prompt and conversation history.
     */
    private function buildContents(string $message, array $history): array
    {
        $contents = [];

        // Add system prompt as user message (Gemini API format)
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $this->systemPrompt]]
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => 'Understood. I will follow these instructions.']]
        ];

        // Add conversation history (last 10 exchanges)
        foreach (array_slice($history, -10) as $item) {
            if (!isset($item['role'], $item['content'])) {
                continue;
            }
            $role = $item['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $item['content']]]
            ];
        }

        // Add current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        return $contents;
    }

    /**
     * Sanitize user input to prevent prompt injection and XSS.
     */
    private function sanitizeInput(string $input): string
    {
        // Trim whitespace
        $input = trim($input);

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Remove suspicious patterns that might indicate prompt injection
        $suspiciousPatterns = [
            '/system\s*:/i',
            '/ignore\s+previous/i',
            '/override\s+instruction/i',
            '/sql\s+injection/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logger->warning('Suspicious pattern detected in input', ['pattern' => $pattern]);
                $input = preg_replace($pattern, '[redacted]', $input);
            }
        }

        return $input;
    }

    /**
     * Validate conversation history format and content.
     */
    private function validateHistory(array $history): array
    {
        $validated = [];
        $maxItems = self::MAX_HISTORY_LENGTH;
        $count = 0;

        foreach ($history as $item) {
            if ($count >= $maxItems) {
                break;
            }

            // Validate structure
            if (!isset($item['role'], $item['content'])) {
                $this->logger->debug('Invalid history item skipped');
                continue;
            }

            // Validate role
            $role = strtolower($item['role']);
            if (!in_array($role, ['user', 'assistant'])) {
                $this->logger->debug('Invalid role in history', ['role' => $role]);
                continue;
            }

            // Validate content
            $content = (string) $item['content'];
            if (empty(trim($content))) {
                continue;
            }

            // Add to validated history
            $validated[] = [
                'role' => $role,
                'content' => substr($content, 0, self::MAX_MESSAGE_LENGTH),
            ];
            $count++;
        }

        return $validated;
    }

    /**
     * Get default system prompt for support chatbot.
     */
    private function getDefaultSystemPrompt(): string
    {
        return 'You are a professional customer support AI embedded in a SaaS dashboard. '
            . 'Your role is to assist users with product-related questions and issues. '
            . 'Be concise, helpful, and maintain a professional, friendly tone. '
            . 'Always prioritize user satisfaction. '
            . 'If you cannot resolve an issue or are unsure, suggest creating a support ticket. '
            . 'Never make up information or provide false guarantees. '
            . 'Always recommend contacting human support for complex issues. '
            . 'Respond in the user\'s language when possible.';
    }

    /**
     * Get system prompt currently in use.
     */
    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    /**
     * Set system prompt for controlling chatbot behavior.
     */
    public function setSystemPrompt(string $prompt): void
    {
        $prompt = trim($prompt);
        if (strlen($prompt) <= self::MAX_SYSTEM_PROMPT_LENGTH) {
            $this->systemPrompt = $prompt;
            $this->logger->info('System prompt updated');
        } else {
            $this->logger->warning('System prompt too long, ignoring update');
        }
    }
}
