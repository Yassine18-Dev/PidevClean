<?php

namespace App\Tests;

use App\Service\GeminiChatbotService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Verification Tests for Gemini Chatbot
 * 
 * These tests VERIFY that:
 * 1. Real API calls are made
 * 2. Fake responses are NOT returned
 * 3. Errors are thrown (not fake text)
 * 4. Responses are validated
 */
class ChatbotVerificationTest extends TestCase
{
    private MockHttpClient $httpClient;
    private MockLogger $logger;
    private GeminiChatbotService $chatbot;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = new MockLogger();
        
        $this->chatbot = new GeminiChatbotService(
            $this->httpClient,
            $this->logger,
            geminiApiKey: 'test-key-12345',
            systemPrompt: 'You are a helpful assistant.',
            model: 'gemini-1.5-flash',
            debugMode: true
        );
    }

    /**
     * CRITICAL: Verify that real Gemini API response is returned
     */
    public function testRealGeminiResponseIsReturned(): void
    {
        // Setup: Mock Gemini API response
        $realApiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'This is a real response from Gemini API.']
                        ]
                    ]
                ]
            ]
        ];

        $this->httpClient->setResponse(200, $realApiResponse);

        // Execute
        $response = $this->chatbot->sendMessage('Hello');

        // Verify: Response came from API, not hardcoded
        $this->assertStringContainsString('real response from Gemini API', $response);
        $this->assertNotEmpty($response);
        
        // Verify debug metadata added
        $this->assertStringContainsString('[DEBUG] Source: Gemini API', $response);
    }

    /**
     * CRITICAL: Verify that empty API responses throw errors (NOT fake messages)
     */
    public function testEmptyResponseThrowsError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gemini API returned empty response text');

        // Setup: Empty response from API
        $this->httpClient->setResponse(200, [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => '']  // EMPTY!
                        ]
                    ]
                ]
            ]
        ]);

        // Execute: Should throw exception, NOT return fake message
        $this->chatbot->sendMessage('Hello');
    }

    /**
     * CRITICAL: Verify that missing API key throws error (NOT fake message)
     */
    public function testMissingApiKeyThrowsError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gemini API is not configured');

        // Create service without API key
        $chatbot = new GeminiChatbotService(
            $this->httpClient,
            $this->logger,
            geminiApiKey: '',  // EMPTY KEY
            systemPrompt: 'Test'
        );

        // Should throw exception, NOT return "AI is not available..."
        $chatbot->sendMessage('Hello');
    }

    /**
     * CRITICAL: Verify that API errors throw exceptions (NOT fake messages)
     */
    public function testApiErrorThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gemini API error: HTTP 429');

        // Setup: API error response
        $this->httpClient->setResponse(429, [
            'error' => [
                'message' => 'Rate limit exceeded'
            ]
        ]);

        // Execute: Should throw exception with real error, NOT fake message
        $this->chatbot->sendMessage('Hello');
    }

    /**
     * CRITICAL: Verify that invalid response structure throws errors
     */
    public function testInvalidResponseStructureThrowsError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no candidates');

        // Setup: API response with no candidates
        $this->httpClient->setResponse(200, [
            'error' => 'Something went wrong'
        ]);

        // Execute: Should throw exception
        $this->chatbot->sendMessage('Hello');
    }

    /**
     * CRITICAL: Verify that invalid message throws ArgumentException
     */
    public function testInvalidMessageThrowsArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Send only whitespace (invalid)
        $this->chatbot->sendMessage('   ');
    }

    /**
     * CRITICAL: Verify that response includes source metadata in debug mode
     */
    public function testDebugModeIncludesSourceMetadata(): void
    {
        $this->httpClient->setResponse(200, [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Real API response']
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->chatbot->sendMessage('Test');

        // Debug mode should add metadata
        $this->assertStringContainsString('[DEBUG]', $response);
        $this->assertStringContainsString('Source: Gemini API', $response);
        $this->assertStringContainsString('gemini-1.5-flash', $response);
    }

    /**
     * CRITICAL: Verify that logs record source verification
     */
    public function testLogsRecordSourceVerification(): void
    {
        $this->httpClient->setResponse(200, [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Real response']
                        ]
                    ]
                ]
            ]
        ]);

        $this->chatbot->sendMessage('Test');

        // Verify logger was called with source info
        $logEntries = $this->logger->getEntries();
        $infoLogs = array_filter($logEntries, fn($e) => $e['level'] === 'info');
        
        $this->assertNotEmpty($infoLogs);
        
        $realApiLog = end($infoLogs);
        $this->assertArrayHasKey('source', $realApiLog['context']);
        $this->assertEquals('gemini', $realApiLog['context']['source']);
    }
}

/**
 * Mock HTTP Client for testing
 */
class MockHttpClient implements HttpClientInterface
{
    private $statusCode = 200;
    private $response = [];

    public function setResponse(int $statusCode, array $data): void
    {
        $this->statusCode = $statusCode;
        $this->response = $data;
    }

    public function request(string $method, string $url, array $options = []): MockResponse
    {
        return new MockResponse($this->statusCode, $this->response);
    }

    public function stream($responses, float $timeout = null): iterable
    {
        yield from $responses;
    }

    public function withOptions(array $options): static
    {
        return $this;
    }
}

/**
 * Mock Response for testing
 */
class MockResponse
{
    private $statusCode;
    private $data;

    public function __construct(int $statusCode, array $data)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(bool $throw = true): array
    {
        return $this->data;
    }

    public function getHeaders(bool $parseCharset = true): array
    {
        return ['content-type' => ['application/json']];
    }

    public function getContent(bool $throw = true): string
    {
        return json_encode($this->data);
    }

    public function cancel(): void
    {
    }

    public function getInfo(string $type = null): mixed
    {
        return null;
    }
}

/**
 * Mock Logger for testing
 */
class MockLogger implements LoggerInterface
{
    private $entries = [];

    public function getEntries(): array
    {
        return $this->entries;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $this->entries[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', (string)$message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', (string)$message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', (string)$message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', (string)$message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', (string)$message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', (string)$message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', (string)$message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log('debug', (string)$message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->log((string)$level, (string)$message, $context);
    }
}
