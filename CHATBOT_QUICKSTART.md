# 🤖 ArenaMind AI Chatbot Integration Guide

A production-ready AI chatbot integration using **Google Gemini API** with a **SaaS-quality interface** built on Symfony 6+.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Setup Instructions](#setup-instructions)
3. [Architecture](#architecture)
4. [Security](#security)
5. [Usage](#usage)
6. [Customization](#customization)
7. [Troubleshooting](#troubleshooting)

---

## Overview

This chatbot provides:

- ✅ **Production-ready code** following Symfony best practices
- ✅ **Secure API integration** with Google Gemini
- ✅ **Modern SaaS UI** with smooth animations and dark theme
- ✅ **Error handling** with fallback responses
- ✅ **Prompt injection protection** and input sanitization
- ✅ **Conversation history** support for context-aware responses
- ✅ **Comprehensive logging** for monitoring and debugging
- ✅ **Timeout protection** and rate limit preparation

### Key Features

| Feature | Details |
|---------|---------|
| **Model** | Google Gemini 1.5 Flash (optimized for speed/cost) |
| **API Endpoint** | `POST /api/support/chat` |
| **Response Time** | ~1-3 seconds average |
| **Max Message Length** | 2000 characters |
| **History Support** | Last 10 messages (20 sides) |
| **Error Handling** | Graceful fallback responses |
| **Security** | Input sanitization, API key isolation |

---

## Setup Instructions

### Step 1: Get a Gemini API Key

1. Go to [https://ai.google.dev/](https://ai.google.dev/)
2. Click **"Get API key"**
3. Select or create a Google Cloud project
4. Enable the **Generative Language API**
5. Copy your API key

### Step 2: Configure Environment

Add your API key to `.env`:

```bash
# .env
GEMINI_API_KEY=your-api-key-here
```

**Security Warning:** Never commit `.env` to version control. In production, use your deployment platform's environment variable management (e.g., Docker, Heroku, AWS).

### Step 3: Verify Installation

The chatbot is already integrated. Just verify these files exist:

- ✅ `src/Service/ChatbotService.php` — Core Gemini integration
- ✅ `src/Controller/SupportApiController.php` — API endpoints
- ✅ `templates/support/chatbot.html.twig` — UI template
- ✅ `public/css/support.css` — Modern styling
- ✅ `config/services.yaml` — Service configuration

### Step 4: Access the Chatbot

1. Navigate to the support section
2. Click **"🤖 AI Chat"** in the navigation
3. Start chatting with the AI assistant

---

## Architecture

### Component Overview

```
Frontend (Twig + Vanilla JS)
           ↓
      POST /api/support/chat
           ↓
   SupportApiController (Validation)
           ↓
   ChatbotService (Business Logic)
           ↓
   Google Gemini API
```

### Service Layer: `ChatbotService.php`

**Location:** `src/Service/ChatbotService.php`

**Key Methods:**

```php
// Send a message and get AI response
public function chat(string $userMessage, array $conversationHistory = []): string

// Check if API is available
public function isAvailable(): bool
```

**Features:**

- Typed PHP with full type hints
- Dependency injection (HttpClient, Logger)
- Automatic sanitization and validation
- Comprehensive error handling
- Structured logging for monitoring

### Controller Layer: `SupportApiController.php`

**Location:** `src/Controller/SupportApiController.php`

**Endpoint:** `POST /api/support/chat`

**Request Format:**

```json
{
  "message": "I can't login to my account",
  "history": [
    { "role": "user", "content": "Hello" },
    { "role": "assistant", "content": "Hi there!" }
  ]
}
```

**Response Format:**

```json
{
  "reply": "Here's how to reset your password...",
  "available": true
}
```

**Validation:**

- Message required and non-empty
- Max 2000 characters
- History format validation
- JSON error handling

### Frontend: `chatbot.html.twig`

**Location:** `templates/support/chatbot.html.twig`

**Features:**

- Modern SaaS interface with dark theme
- Real-time message display
- Typing indicator animation
- Quick suggestion chips
- Responsive design (mobile-friendly)
- XSS protection with HTML escaping
- CSRF protection (built-in Symfony)

---

## Security

### 1. API Key Protection

✅ **Stored securely in `.env`** (never exposed to frontend)  
✅ **All API calls go through backend** (no direct API access from browser)  
✅ **Never logged or exposed in error messages**  

### 2. Input Sanitization

The chatbot automatically:

- Removes prompt injection patterns (`system:`, `[JAILBREAK]`, etc.)
- Truncates oversized messages (max 2000 chars)
- Escapes HTML in frontend to prevent XSS
- Validates JSON structure

### 3. Error Handling

- Safe fallback responses when API fails
- No sensitive information in error messages
- Comprehensive server-side logging
- User-friendly error messages

### 4. Rate Limiting (Recommended)

To prevent abuse, add rate limiting to the controller:

```php
#[Route('/chat', name: 'api_support_chat', methods: ['POST'])]
#[IsGranted('ROLE_USER')] // Require login
#[RateLimit(limit: 30, period: '1 hour')]
public function chat(Request $request, ChatbotService $chatbot): JsonResponse
{
    // ... implementation
}
```

### 5. CSRF Protection

Symfony's CSRF protection is **automatically enabled** for state-changing requests.

---

## Usage

### Basic Chat Flow

1. **User sends message** → Frontend appends to UI, sends to API
2. **Backend validates** → Checks format, length, structure
3. **Service sanitizes** → Removes injection patterns
4. **API call made** → Sends to Gemini with conversation history
5. **Response returned** → AI reply sent back to frontend
6. **UI updates** → Message appended with smooth animation

### With Conversation History

The chatbot maintains context by sending previous messages:

```javascript
// Frontend JS
const response = await fetch('/api/support/chat', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    message: "How do I reset my password?",
    history: [
      { role: "user", content: "I can't login" },
      { role: "assistant", content: "Let me help..." }
    ]
  })
});
```

### Fallback Responses

If the Gemini API is unavailable, the service provides intelligent fallback responses based on keywords:

- `password`, `login` → Account help
- `team`, `captain` → Team management
- `payment`, `refund` → Payment support
- `tournament`, `match` → Tournament guidance
- Generic → General help message

---

## Customization

### Changing the System Prompt

**File:** `src/Service/ChatbotService.php`  
**Method:** `getSystemPrompt()`

```php
private function getSystemPrompt(): string
{
    return 'You are a professional support AI for [YOUR SERVICE]...'
           . 'Key responsibilities:' . "\n"
           . '1. Help with [YOUR TOPICS]' . "\n"
           . '2. Guide users to create tickets when needed' . "\n"
           // ... customize as needed
           ;
}
```

### Changing the AI Model

To use a different Gemini model:

**File:** `src/Service/ChatbotService.php`

```php
private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
// Options:
// - gemini-1.5-flash (default, fast & cheap)
// - gemini-1.5-pro (slower, more powerful)
// - gemini-2.0-flash (latest, if available)
```

### Adjusting Temperature & Parameters

**File:** `src/Service/ChatbotService.php`  
**Method:** `chat()`

```php
'generationConfig' => [
    'temperature' => 0.7,        // 0.0 = deterministic, 1.0 = creative
    'maxOutputTokens' => 500,    // Max response length
    'topP' => 0.95,              // Nucleus sampling
    'topK' => 40,                // Top-K sampling
],
```

### Customizing UI Appearance

**Chatbot header styling:** `public/css/support.css` (search `.chat-header`)  
**Message bubbles:** `.chat-bubble` classes  
**Suggestion chips:** `.suggestion-chip` classes  
**Colors:** Edit CSS variables at top of file (`:root { }`)

### Customizing Quick Suggestions

**File:** `templates/support/chatbot.html.twig`

```twig
<button type="button" class="suggestion-chip">🔐 Your Custom Chip</button>
```

The button text is sent as the message. Update icons and text as needed.

---

## Troubleshooting

### Chatbot Not Responding

**Check 1:** API key is set in `.env`

```bash
grep GEMINI_API_KEY .env
```

**Check 2:** Symfony can access the key

```bash
php bin/console config:dump-reference | grep gemini
```

**Check 3:** API key is valid (test it)

```bash
curl -X POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent \
  -H "Content-Type: application/json" \
  -d '{"contents":[{"parts":[{"text":"test"}]}]}' \
  -G --data-urlencode "key=YOUR_KEY_HERE"
```

### "Network Error" Message

**Cause:** Frontend can't reach the API endpoint

**Fix:**

1. Check network tab in browser DevTools
2. Verify endpoint exists: `php bin/console debug:router | grep chat`
3. Check for CORS issues (if applicable)

### "Empty Response" Error

**Cause:** Gemini API returned empty content

**Fix:**

1. Check logs: `tail -f var/log/dev.log`
2. Try with a simpler message
3. Check API quota isn't exceeded

### Logs Showing Rate Limit Errors

**Cause:** Too many requests to Gemini API

**Fix:**

1. Implement rate limiting on the controller (see Security section)
2. Increase time between requests in frontend
3. Check Gemini API dashboard for quota info

### "Invalid JSON" Error

**Cause:** Frontend sending malformed request

**Fix:**

1. Open browser console and check Network tab
2. Verify request body is valid JSON
3. Check message doesn't exceed 2000 characters

### High Response Times (> 5 seconds)

**Cause:** Gemini API is slow or network is poor

**Fix:**

1. Check internet connection
2. Use gemini-1.5-flash instead of pro model
3. Reduce message length
4. Check Gemini API status page

---

## Monitoring & Logging

The chatbot logs all interactions for monitoring:

**File:** `var/log/dev.log` (or your configured log location)

**Logged Events:**

```
INFO: Successful chatbot interaction (message_length, reply_length)
WARNING: Gemini API key not configured
WARNING: Empty response from Gemini API
ERROR: Gemini API transport error (exception, code)
ERROR: Unexpected chatbot error (exception, code)
```

**Monitor with:**

```bash
# View real-time logs
tail -f var/log/dev.log | grep chatbot

# Or in production
php bin/console monolog:tail --env=prod
```

---

## Production Checklist

Before deploying to production:

- [ ] ✅ API key stored in environment variable (not .env)
- [ ] ✅ HTTPS enabled (required for API calls)
- [ ] ✅ CSRF protection enabled in controller
- [ ] ✅ Rate limiting configured
- [ ] ✅ Authentication required (if needed)
- [ ] ✅ Logging configured
- [ ] ✅ Error monitoring set up (Sentry, DataDog, etc.)
- [ ] ✅ Load testing performed
- [ ] ✅ UI tested on mobile devices
- [ ] ✅ Fallback responses tested (disable API key temporarily)

---

## Performance Tips

1. **Use gemini-1.5-flash** instead of pro (10x cheaper, 95% as good)
2. **Limit history** to 5-10 recent messages (configured)
3. **Compress message content** if necessary
4. **Add frontend debouncing** to prevent accidental double-sends
5. **Cache fallback responses** if using frequently
6. **Monitor API costs** in Gemini console

---

## Support & Resources

- **Gemini API Docs:** https://ai.google.dev/docs
- **Symfony Docs:** https://symfony.com/doc/6.4/
- **Report Issues:** Check application logs first, then contact support

---

## License & Attribution

This chatbot implementation follows Symfony best practices and Google's official guidelines.

For questions about customization or issues, refer to the inline code comments in the service and controller files.

**Happy chatting! 🤖**
