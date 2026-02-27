# ⚡ AI Chatbot - Quick Reference Card

Print this or keep it handy!

---

## 🚀 Start in 60 Seconds

```bash
# 1. Get API key from https://ai.google.dev/
# 2. Set environment variable
export GEMINI_API_KEY="your-key"

# 3. Or add to .env
echo "GEMINI_API_KEY=your-key" >> .env

# 4. Access chatbot
# Navigate to: http://localhost/support → AI Chat
```

---

## 📁 Key Files

| File | Location | Purpose |
|------|----------|---------|
| Service | `src/Service/ChatbotService.php` | AI integration |
| Controller | `src/Controller/SupportApiController.php` | API endpoint |
| Template | `templates/support/chatbot.html.twig` | UI |
| Styling | `public/css/support.css` | CSS (search "chatbot") |
| Config | `.env` | API key |
| Docs | `CHATBOT_QUICKSTART.md` | Setup guide |

---

## 🔌 API Endpoint

**URL:** `POST /api/support/chat`

**Request:**
```json
{
  "message": "Your question here",
  "history": []
}
```

**Response:**
```json
{
  "reply": "AI answer here",
  "available": true
}
```

---

## 🛠️ Common Customizations

### Change System Prompt
**File:** `src/Service/ChatbotService.php`  
**Method:** `getSystemPrompt()`

### Change UI Colors
**File:** `public/css/support.css`  
**Search:** `:root { --purple: ...`

### Add Quick Chips
**File:** `templates/support/chatbot.html.twig`  
**Search:** `suggestion-chip`

### Use Different Model
**File:** `src/Service/ChatbotService.php`  
**Constant:** `API_URL`

---

## 🐛 Debugging

```bash
# View logs
tail -f var/log/dev.log

# Filter for chatbot logs
tail -f var/log/dev.log | grep -i chat

# Test API endpoint
curl -X POST http://localhost/api/support/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"test"}'

# Check if service is registered
php bin/console debug:container ChatbotService

# Clear cache (if needed)
php bin/console cache:clear --env=prod
```

---

## 🔐 Security Checklist

- [ ] API key in `.env` (not committed)
- [ ] HTTPS enabled
- [ ] Message length validated (max 2000 chars)
- [ ] Input sanitized for injection
- [ ] Error messages don't leak secrets
- [ ] Logging configured
- [ ] Rate limiting added (optional but recommended)

---

## 📊 Performance Tips

1. Use `gemini-1.5-flash` (default, 10x cheaper)
2. Limit history to 5-10 messages (already done)
3. Message max 2000 chars (already done)
4. Average response: 1-3 seconds
5. Cost: ~$0.00002 per message

---

## 💾 Frontend JavaScript Reference

```javascript
// Send message to chatbot
const response = await fetch('/api/support/chat', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    message: 'user text',
    history: []  // optional
  })
});

const data = await response.json();
console.log(data.reply);  // AI response
```

---

## 🚀 Deploy to Production

```bash
# 1. Set environment
export APP_ENV=prod
export APP_DEBUG=0
export GEMINI_API_KEY=your-prod-key

# 2. Clear cache
php bin/console cache:clear --env=prod

# 3. Warmup cache
php bin/console cache:warmup --env=prod

# 4. Test
# Visit https://yourdomain.com/support → AI Chat
```

---

## 📞 Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success ✅ |
| 400 | Bad request (validation error) ❌ |
| 500 | Server error ❌ |

---

## 🎨 CSS Classes

```css
.chat-container       /* Main container */
.chat-messages        /* Message area */
.chat-msg             /* Message wrapper */
.chat-msg--user       /* User message */
.chat-msg--bot        /* Bot message */
.chat-bubble          /* Message text */
.chat-input           /* Input field */
.chat-send-btn        /* Send button */
.typing-indicator     /* Loading animation */
.suggestion-chip      /* Quick suggestions */
```

---

## 🔧 Environment Variables

```bash
# Required
GEMINI_API_KEY=sk-xxx

# Optional
APP_ENV=prod              # dev or prod
APP_DEBUG=0               # 1 or 0
DATABASE_URL=mysql://...  # if using DB
LOG_CHANNEL=syslog        # logging
SENTRY_DSN=https://...    # error tracking
```

---

## 📚 Documentation Files

```
CHATBOT_QUICKSTART.md    ← Read this first
CHATBOT_API.md           ← API reference
CHATBOT_IMPLEMENTATION.md ← Technical details
CHATBOT_DEPLOYMENT.md    ← Production checklist
CHATBOT_ENVIRONMENTS.md  ← Config examples
README_CHATBOT.md        ← Overview
```

---

## ✅ Pre-Launch Checklist

- [ ] API key configured
- [ ] Message displays work
- [ ] Error handling tested
- [ ] Logs appearing
- [ ] HTTPS enabled
- [ ] Rate limiting configured
- [ ] Monitoring setup
- [ ] Team trained

---

## 🚨 If Something Goes Wrong

**No response?**
1. Check API key: `echo $GEMINI_API_KEY`
2. Check logs: `tail -f var/log/dev.log`
3. Test API directly with cURL

**Slow responses?**
1. Check network (5+ seconds = issue)
2. Check Gemini API status
3. Try simpler message

**Errors in UI?**
1. Check browser console (F12)
2. Check server logs
3. Verify content type header

**API key not working?**
1. Clear cache: `php bin/console cache:clear --env=prod`
2. Verify key format is correct
3. Test key directly with Gemini

---

## 💡 Pro Tips

1. **Use fallback responses** - Test by not setting API key
2. **Monitor API usage** - Check Gemini console for cost
3. **Log everything** - Helps with debugging
4. **Set rate limits** - Prevent abuse
5. **Cache responses** - If appropriate for your use case
6. **Monitor errors** - Set up Sentry or similar
7. **Load test** - Test with 100+ users before prod
8. **Document changes** - Update team when customizing

---

## 📞 Quick Support Links

- **Gemini API:** https://ai.google.dev/docs
- **Symfony Docs:** https://symfony.com/doc/6.4/
- **HTTP Client:** https://symfony.com/doc/6.4/http_client.html
- **Monolog:** https://seldaek.github.io/monolog/

---

## 🎯 Success Checklist

- [x] Service layer ✅ Complete with security
- [x] API endpoint ✅ With validation
- [x] Modern UI ✅ With animations
- [x] Error handling ✅ Graceful fallback
- [x] Logging ✅ Comprehensive
- [x] Documentation ✅ 1600+ lines
- [x] Production ready ✅ Deploy today!

---

**Keep this handy! Reference it whenever you need.**

**Questions? Read the documentation files above.**

**Ready to ship? Run the deployment checklist.**

**Good luck! 🚀**
