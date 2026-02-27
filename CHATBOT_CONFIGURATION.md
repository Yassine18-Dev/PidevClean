# Chatbot Service Configuration Reference

## Environment Variables

Add these to `.env.local`:

```env
# Google Gemini API Key (REQUIRED)
GEMINI_API_KEY="AIzaSyB_Zzf1ejwGdGLUXehaCx56DP_8rGzDNjk"

# Gemini Model to use (default: gemini-1.5-flash)
GEMINI_MODEL="gemini-1.5-flash"

# System prompt for chatbot behavior
GEMINI_SYSTEM_PROMPT="You are a helpful support assistant for our platform. Be concise, professional, and always direct users to create a ticket if you cannot help them."

# Enable debug mode (responses include source verification)
# Set to "false" in production
CHATBOT_DEBUG_MODE="true"
```

## Symfony Configuration

### services.yaml

Ensure `config/services.yaml` contains:

```yaml
# config/services.yaml

parameters:
    env(GEMINI_API_KEY): ''
    env(GEMINI_MODEL): 'gemini-1.5-flash'
    env(GEMINI_SYSTEM_PROMPT): ''
    env(CHATBOT_DEBUG_MODE): false

services:
    # ... existing services ...
    
    App\Service\GeminiChatbotService:
        arguments:
            $geminiApiKey: '%env(GEMINI_API_KEY)%'
            $systemPrompt: '%env(GEMINI_SYSTEM_PROMPT)%'
            $model: '%env(GEMINI_MODEL)%'
            $debugMode: '%env(bool:CHATBOT_DEBUG_MODE)%'
```

## API Endpoints

### Send Message

**POST** `/api/chatbot/message`

**Request:**
```json
{
  "message": "User's message",
  "history": [
    {
      "role": "user",
      "content": "First message"
    },
    {
      "role": "assistant",
      "content": "Assistant response"
    }
  ]
}
```

**Success Response (200):**
```json
{
  "reply": "Gemini's response (may include debug metadata if enabled)",
  "available": true
}
```

**Error Response (4xx/5xx):**
```json
{
  "error": "Descriptive error message from Gemini API or validation"
}
```

### Get Status

**GET** `/api/chatbot/status`

**Response:**
```json
{
  "available": true,
  "status": "online",
  "debug": {
    "message": "Debug mode is ENABLED - responses include Gemini API metadata",
    "note": "Disable debug mode in production"
  }
}
```

## Service Usage

### In Controller

```php
use App\Service\GeminiChatbotService;

class ChatbotController extends AbstractController
{
    public function message(Request $request, GeminiChatbotService $chatbot): JsonResponse
    {
        try {
            // Send message to Gemini
            $response = $chatbot->sendMessage(
                message: $userMessage,
                history: $conversationHistory
            );

            return $this->json(['reply' => $response]);
        } catch (\RuntimeException $e) {
            // API not configured
            return $this->json(['error' => $e->getMessage()], 503);
        } catch (\InvalidArgumentException $e) {
            // Input validation failed
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            // API call failed
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### Debug Mode Toggle

```php
use App\Service\GeminiChatbotService;

// Enable debug mode
$chatbot->setDebugMode(true);

// Disable debug mode
$chatbot->setDebugMode(false);

// Check current status
if ($chatbot->isDebugMode()) {
    echo "Debug mode is ON";
}
```

### Get System Prompt

```php
$prompt = $chatbot->getSystemPrompt();
echo $prompt;
```

### Update System Prompt

```php
$newPrompt = "You are a specialized support assistant...";
$chatbot->setSystemPrompt($newPrompt);
```

## Logging

All Gemini API interactions are logged to `var/log/dev.log` (development) or `var/log/prod.log` (production).

### Log Levels

| Level | Meaning | Example |
|-------|---------|---------|
| **CRITICAL** | System not configured or unexpected error | API key missing, invalid response |
| **ERROR** | API returned error | HTTP 429, malformed response |
| **WARNING** | Configuration issue | Debug API key detected |
| **INFO** | Successful operation | ✓ Real Gemini response received |
| **DEBUG** | Detailed debugging info | API URL, payload structure |

### Sample Logs

**Successful Response:**
```
[2026-02-24 12:00:00] app.INFO: ✓ Real Gemini API response received {
  "source": "gemini",
  "model": "gemini-1.5-flash",
  "reply_length": 247,
  "input_length": 45
}
```

**API Error:**
```
[2026-02-24 12:00:01] app.ERROR: CRITICAL: Gemini API returned error status {
  "status_code": 429,
  "api_response": {"error": {"message": "Rate limit exceeded"}}
}
```

**Missing API Key:**
```
[2026-02-24 12:00:02] app.CRITICAL: FATAL: Gemini API key not configured. Cannot proceed.
```

## Request Limits

| Parameter | Limit | Notes |
|-----------|-------|-------|
| Message Length | 2,000 characters | Enforced by service |
| History Length | 20 items (10 exchanges) | Automatically trimmed |
| System Prompt | 1,000 characters | Enforced by service |
| API Timeout | 10 seconds | Configurable in service |

## Model Versions

Supported models:
- `gemini-1.5-flash` (recommended, stable, fast)
- `gemini-2.0-flash` (latest, if available)
- `gemini-pro` (legacy, not recommended)

Change in `.env.local`:
```env
GEMINI_MODEL="gemini-1.5-flash"
```

## Error Handling Strategy

### Validation Errors (400 Bad Request)
- Invalid JSON
- Empty message
- Message too long
- Invalid history format

**Response:**
```json
{
  "error": "Invalid input: Message exceeds maximum length..."
}
```

### Configuration Errors (503 Service Unavailable)
- API key not set
- Service not initialized

**Response:**
```json
{
  "error": "AI service is not properly configured..."
}
```

### API Errors (500 Internal Server Error)
- Network error
- Timeout
- API returned error
- Invalid response structure
- Empty response

**Response:**
```json
{
  "error": "Gemini API error: HTTP 429. Rate limit exceeded"
}
```

## Security Best Practices

1. **Never expose API key in frontend JavaScript**
   - All API calls go through backend controller
   - API key stored in `.env.local` only

2. **Validate all user input**
   - Message length checks
   - History format validation
   - Prompt injection pattern detection

3. **Log errors securely**
   - API key never logged
   - Only first 20 chars logged for verification
   - Sensitive data redacted

4. **Handle rate limiting**
   - Gemini API rate limits: 60 requests/minute
   - Monitor logs for 429 errors
   - Implement client-side backoff

5. **Sanitize prompts**
   - Remove suspicious patterns
   - Escape special characters
   - Validate against injection attacks

## Performance Optimization

1. **Cache system prompt**
   - Loaded once on service initialization
   - Reused for all requests

2. **Limit conversation history**
   - Maximum 10 exchanges kept
   - Oldest messages trimmed automatically
   - Reduces API token usage

3. **Timeout configuration**
   - 10 second timeout for API calls
   - Prevents hanging requests
   - Configurable in service class

4. **Debug mode disabled in production**
   - Reduces response size
   - Slightly faster responses
   - Set `CHATBOT_DEBUG_MODE="false"`

## Monitoring & Alerting

### Metrics to Track

1. **Success Rate**
   - Count INFO logs with "✓ Real Gemini API response"
   - Alert if drops below 95%

2. **Response Time**
   - Monitor timestamp difference in logs
   - Alert if exceeds 10 seconds

3. **Error Rate**
   - Count ERROR and CRITICAL logs
   - Alert if exceeds 5%

4. **Rate Limiting**
   - Watch for HTTP 429 errors
   - Implement exponential backoff

### Sample Alert Query (ELK Stack)

```
level:ERROR AND message:"Gemini API" | stats count by status_code
```

## Troubleshooting

### Symptom: Responses are generic help text
- ✗ ChatbotService is being used instead of GeminiChatbotService
- **Fix:** Verify controller injects GeminiChatbotService

### Symptom: "AI is temporarily unavailable"
- ✗ Generic error message (fake fallback)
- **Fix:** Enable debug mode to see real error

### Symptom: No [DEBUG] metadata
- ✗ Debug mode not enabled
- **Fix:** Set `CHATBOT_DEBUG_MODE="true"`

### Symptom: Responses are empty
- ✗ API returned empty text
- **Fix:** Check API logs for errors, verify API key

### Symptom: Rate limit errors
- ✗ Too many requests to Gemini API
- **Fix:** Implement client-side throttling, cache responses

## Deployment Checklist

- [ ] `GEMINI_API_KEY` is set in `.env.local`
- [ ] `CHATBOT_DEBUG_MODE="false"` in production
- [ ] `APP_ENV="prod"` in production
- [ ] Tests pass: `./bin/phpunit`
- [ ] No fake response methods used anywhere
- [ ] Logs configured for production
- [ ] Error monitoring in place
- [ ] Rate limiting strategy implemented
- [ ] Backup API key stored securely
- [ ] Documentation reviewed by team

---

**Configuration Version:** 1.0  
**Last Updated:** February 24, 2026  
**Status:** Production Ready ✅

