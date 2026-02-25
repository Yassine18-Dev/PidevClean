# Chatbot Manual Testing Guide
**Purpose:** Verify that Gemini API responses are real (not fake)

---

## Quick Test (2 minutes)

### 1. Enable Debug Mode
Edit `.env.local`:
```env
CHATBOT_DEBUG_MODE="true"
```

Restart server:
```bash
symfony server:stop
symfony server:start
```

### 2. Test in Browser
Open your chatbot in the browser (e.g., support page with embedded chatbot).

Send a message: **"What is your name?"**

Expected response will include:
```
[DEBUG] Source: Gemini API | Model: gemini-1.5-flash
```

If you see this metadata, response is REAL Gemini, not fake.

### 3. Check Server Logs
```bash
tail -50 var/log/dev.log | grep "Real Gemini"
```

Expected output:
```
[2026-02-24 12:00:00] app.INFO: ✓ Real Gemini API response received {"source":"gemini","model":"gemini-1.5-flash","reply_length":247,"input_length":18}
```

---

## Detailed Test Suite

### Test 1: Real Response Shows Source Verification

**Steps:**
1. Open browser console (F12)
2. Send message: "Hello, how are you?"
3. Check network tab → Response payload

**Expected:**
```json
{
  "reply": "I'm doing well, thank you for asking!...\n\n---\n**[DEBUG] Source: Gemini API | Model: gemini-1.5-flash**",
  "available": true
}
```

**Fail Case:**
If response has NO `[DEBUG]` metadata and no mention of "Source: Gemini", it's a fake response.

---

### Test 2: Error Handling (Missing API Key)

**Setup:**
1. Edit `.env.local`:
```env
GEMINI_API_KEY=""
```
2. Restart server

**Steps:**
1. Send message: "Hello"

**Expected Error Response:**
```json
{
  "error": "AI service is not properly configured. Contact support."
}
```

**Fail Case (FAKE):**
If response is: "Hello! I'm the ArenaMind Support Assistant..." → THIS IS FAKE

---

### Test 3: Error Handling (Invalid API Key)

**Setup:**
1. Edit `.env.local`:
```env
GEMINI_API_KEY="invalid-key-xxxxx"
```
2. Restart server

**Steps:**
1. Send message: "Hello"

**Expected Error:**
```json
{
  "error": "Gemini API error: HTTP 401. Invalid API key."
}
```

**Important:** You'll see REAL Gemini error message, not fake text.

---

### Test 4: Long Message Validation

**Steps:**
1. Create message longer than 2000 characters (copy-paste text 100+ times)
2. Send it

**Expected Error:**
```json
{
  "error": "Invalid input: Message exceeds maximum length of 2000 characters."
}
```

**Fail Case (FAKE):**
If response contains helpful text about your issue → THIS IS FAKE

---

### Test 5: Empty Message Validation

**Steps:**
1. Send only whitespace: `"    "` (5 spaces)

**Expected Error:**
```json
{
  "error": "Invalid input: Message cannot be empty after sanitization."
}
```

---

### Test 6: Conversation History

**Steps:**
1. Send first message: "What's a common password?"
2. Copy the response from server logs (the exact text)
3. Send second message: "Tell me more"
4. Include first message in history:

```javascript
// In browser console
fetch('/api/chatbot/message', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    message: 'Tell me more',
    history: [
      {role: 'user', content: "What's a common password?"},
      {role: 'assistant', content: '[paste the exact response from first message]'}
    ]
  })
})
.then(r => r.json())
.then(data => console.log(data))
```

**Expected:**
Response should reference the context from previous message.

**Fail Case (FAKE):**
If response is generic and doesn't reference context → history not being used → likely fake

---

### Test 7: Server Logs Proof

**Steps:**
1. Enable debug mode
2. Send message: "Debug test message"
3. Run:
```bash
grep "Real Gemini API" var/log/dev.log | tail -1
```

**Expected Output:**
```
[2026-02-24 12:34:56] app.INFO: ✓ Real Gemini API response received {"source":"gemini","model":"gemini-1.5-flash","reply_length":123,"input_length":18}
```

**Key Indicators:**
- ✅ `"source":"gemini"` - Proves API call
- ✅ `"model":"gemini-1.5-flash"` - Proves correct model
- ✅ Timestamp matches when you sent message
- ✅ `reply_length` > 0 - Not empty

---

### Test 8: API Status Endpoint

**Steps:**
```bash
curl http://localhost:8000/api/chatbot/status
```

**Expected Response:**
```json
{
  "available": true,
  "status": "online",
  "debug": {
    "message": "Debug mode is ENABLED - responses include Gemini API metadata",
    "note": "Disable debug mode in production via setDebugMode(false)"
  }
}
```

---

### Test 9: Check for Fake Response Methods

**Steps:**
Search codebase for fake response methods:

```bash
# Should find NO matches in active code
grep -r "getFallbackResponse\|getTimeoutFallback\|getErrorFallback" src/Service/GeminiChatbotService.php

# Should find them ONLY in deprecated ChatbotService
grep -r "getFallbackResponse" src/Service/ChatbotService.php
# ✓ SHOULD FIND - It's marked as deprecated
```

---

### Test 10: Network Monitoring

**Steps:**
1. Open browser DevTools → Network tab
2. Open chatbot widget
3. Send message
4. Look for requests to `/api/chatbot/message`

**Expected:**
- Request is to **your local server**, not to Google
- Request body contains your message and history
- Response body contains `"reply"` field with Gemini's text

**Verify in Response:**
```json
{
  "reply": "Real Gemini response here...",
  "available": true
}
```

---

## Automated Test Execution

### Run Unit Tests
```bash
./bin/phpunit tests/ChatbotVerificationTest.php
```

This runs 9 critical tests verifying:
- ✅ Real API responses returned
- ✅ Empty responses throw errors
- ✅ Missing API key throws error
- ✅ API errors throw exceptions
- ✅ Invalid structure throws error
- ✅ Invalid message throws error
- ✅ Debug metadata included
- ✅ Logs record source verification
- ✅ Conversation history used

All tests must PASS for production deployment.

---

## Production Safety Checks

### Before Deployment

1. **Disable Debug Mode**
```env
CHATBOT_DEBUG_MODE="false"
```

2. **Verify No Fake Logic**
```bash
grep -r "Hello! How can I help" src/
grep -r "🔐 **Password & Login Help**" src/
# Should find ZERO matches
```

3. **Check API Key is Set**
```bash
# Should NOT be empty
echo $GEMINI_API_KEY
```

4. **Test Error Scenario**
- Temporarily set wrong API key
- Send message
- Verify error response (not fake text)
- Fix API key

5. **Monitor First 24 Hours**
```bash
# Watch for errors
tail -f var/log/prod.log | grep -E "CRITICAL|ERROR|Real Gemini"
```

---

## Troubleshooting

### Issue: No [DEBUG] metadata in response

**Cause:** Debug mode not enabled or not working

**Fix:**
1. Check `.env.local` has `CHATBOT_DEBUG_MODE="true"`
2. Clear cache: `php bin/console cache:clear`
3. Restart server

### Issue: Response is generic help text (e.g., "Hello! I'm the ArenaMind...")

**Cause:** ChatbotService fallback is running instead of GeminiChatbotService

**Fix:**
1. Verify ChatbotController uses GeminiChatbotService: 
```bash
grep -A5 "public function message" src/Controller/ChatbotController.php | grep Gemini
# Should show: GeminiChatbotService $chatbot
```
2. Check no code injects ChatbotService instead
3. Verify error wasn't thrown and caught silently

### Issue: "AI is not available" message

**Cause:** API key missing or empty

**Fix:**
```bash
# Check API key is set
echo $GEMINI_API_KEY
# Should show: AIzaSyB_Zzf...

# If empty, add to .env.local
GEMINI_API_KEY="AIzaSyB_Zzf1ejwGdGLUXehaCx56DP_8rGzDNjk"
```

### Issue: No logs appearing

**Cause:** Logger not configured or log level too high

**Fix:**
1. Check `APP_ENV` is not `prod` (or production logging configured)
2. Check log file permissions: `ls -la var/log/dev.log`
3. Check log level in `config/packages/monolog.yaml`

---

## Success Indicators

✅ **Response includes `[DEBUG] Source: Gemini API`**  
✅ **Logs show `"source":"gemini"` metadata**  
✅ **No fake help text responses**  
✅ **Errors show actual Gemini error messages**  
✅ **Empty/invalid responses throw exceptions**  
✅ **Conversation history is used in context**  
✅ **Status endpoint shows `"available": true`**  
✅ **Unit tests all pass**  

---

## Failure Indicators (Fix These!)

❌ **Response is generic help text** → Fake response from ChatbotService  
❌ **No [DEBUG] metadata even with debug enabled** → Debug mode not working  
❌ **Error says "AI is temporarily unavailable"** → Fake error message  
❌ **Logs show no `"source":"gemini"`** → Not calling real API  
❌ **Response is always the same** → Hardcoded/cached fake response  
❌ **Tests fail** → Code changes broke something  

---

## Contact Support

If responses appear fake:
1. Collect 3-5 examples of suspicious responses
2. Check server logs for last 30 minutes
3. Verify API key is set and valid
4. Run: `php bin/console cache:clear`
5. Test again
6. If still failing, contact engineering team with:
   - Examples of responses
   - Last 50 lines of logs
   - Value of `GEMINI_API_KEY` (first 20 chars visible only)
   - Value of `APP_ENV`

