# AI Chatbot API Reference

## Endpoint

```
POST /api/support/chat
```

---

## Request

### Headers

```http
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

### Body

```json
{
  "message": "User's message here",
  "history": [
    {
      "role": "user",
      "content": "Previous user message"
    },
    {
      "role": "assistant",
      "content": "Previous AI response"
    }
  ]
}
```

### Parameters

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| `message` | string | Yes | Non-empty message, max 2000 characters |
| `history` | array | No | Array of conversation history (max 10 items) |

---

## Response

### Success (200 OK)

```json
{
  "reply": "AI-generated response text",
  "available": true
}
```

### Error Responses

#### Missing Message (400 Bad Request)

```json
{
  "error": "Message is required and cannot be empty"
}
```

#### Message Too Long (400 Bad Request)

```json
{
  "error": "Message is too long (max 2000 characters)"
}
```

#### Invalid JSON (400 Bad Request)

```json
{
  "error": "Invalid request format"
}
```

#### Invalid History Format (400 Bad Request)

```json
{
  "error": "Invalid history format"
}
```

#### Server Error (500 Internal Server Error)

```json
{
  "error": "An unexpected error occurred"
}
```

---

## Examples

### Basic Request (No History)

```bash
curl -X POST http://localhost/api/support/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "How do I reset my password?"
  }'
```

**Response:**

```json
{
  "reply": "To reset your password:\n1. Go to the login page\n2. Click \"Forgot Password\"\n3. Enter your email and follow the reset link\n\nIf you need additional help, please create a support ticket.",
  "available": true
}
```

### With Conversation History

```bash
curl -X POST http://localhost/api/support/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Can you help me with payments?",
    "history": [
      {"role": "user", "content": "Hello"},
      {"role": "assistant", "content": "Hi! I am the ArenaMind Support Assistant. How can I help?"}
    ]
  }'
```

**Response:**

```json
{
  "reply": "Of course! I can help with payment-related questions. What specific issue are you experiencing?",
  "available": true
}
```

### API Unavailable

```json
{
  "reply": "I'm experiencing connectivity issues right now. Please try again or create a support ticket for further assistance.",
  "available": false
}
```

---

## Frontend Integration Example

```javascript
async function chatWithBot(message, history = []) {
  try {
    const response = await fetch('/api/support/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        message: message,
        history: history
      })
    });

    if (!response.ok) {
      throw new Error(`API error: ${response.status}`);
    }

    const data = await response.json();
    return {
      reply: data.reply,
      available: data.available
    };

  } catch (error) {
    console.error('Chatbot error:', error);
    return {
      reply: 'Network error. Please try again.',
      available: false
    };
  }
}

// Usage
const result = await chatWithBot(
  'I need help with my account',
  [
    { role: 'user', content: 'Hello' },
    { role: 'assistant', content: 'Hi there!' }
  ]
);

console.log(result.reply);
```

---

## Rate Limiting

To prevent abuse, the chatbot endpoint should be rate-limited. Example using Symfony RateLimiter:

```php
#[RateLimit(limit: 30, period: '1 hour')]
public function chat(Request $request, ChatbotService $chatbot): JsonResponse
{
    // ...
}
```

---

## Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful response |
| 400 | Bad Request | Invalid message or format |
| 500 | Internal Error | Unexpected server error |

---

## Notes

- Messages are limited to 2000 characters
- Conversation history is kept as-is (no additional preprocessing on backend)
- The `available` field indicates if Gemini API is configured and accessible
- Response times typically range from 1-3 seconds
- All requests should include `Content-Type: application/json` header
- The endpoint requires proper authentication if your app uses it
