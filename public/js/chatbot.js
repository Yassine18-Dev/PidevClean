// Minimal Vanilla JS for chatbot widget
(function() {
  const openBtn = document.getElementById('chatbotOpenBtn');
  const widget = document.getElementById('chatbotWidget');
  const messages = document.getElementById('chatbotMessages');
  const typing = document.getElementById('chatbotTyping');
  const form = document.getElementById('chatbotForm');
  const input = document.getElementById('chatbotInput');
  const sendBtn = document.getElementById('chatbotSendBtn');
  let history = [];
  let isLoading = false;

  openBtn.addEventListener('click', () => {
    widget.style.display = widget.style.display === 'none' ? 'flex' : 'none';
    if (widget.style.display === 'flex') input.focus();
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = input.value.trim();
    if (msg) sendMessage(msg);
  });

  function sendMessage(text) {
    if (isLoading) return;
    appendMessage(text, 'user');
    input.value = '';
    input.disabled = true;
    sendBtn.disabled = true;
    typing.style.display = 'flex';
    scrollToBottom();
    isLoading = true;
    fetch('/api/chatbot/message', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ message: text, history: history })
    })
    .then(r => r.json())
    .then(data => {
      typing.style.display = 'none';
      if (data.reply) {
        appendMessage(data.reply, 'ai');
        history.push({ role: 'user', content: text });
        history.push({ role: 'assistant', content: data.reply });
        if (history.length > 20) history = history.slice(-20);
      } else {
        appendMessage('I received an empty response. Please try again.', 'ai');
      }
    })
    .catch(() => {
      typing.style.display = 'none';
      appendMessage('😟 AI is temporarily unavailable. Please try again.', 'ai');
    })
    .finally(() => {
      isLoading = false;
      input.disabled = false;
      sendBtn.disabled = false;
      input.focus();
    });
  }

  function appendMessage(text, type) {
    const div = document.createElement('div');
    div.className = `chatbot-msg chatbot-msg--${type}`;
    const bubble = document.createElement('div');
    bubble.className = 'chatbot-bubble';
    bubble.innerHTML = `<p>${escapeHtml(text)}</p>`;
    div.appendChild(bubble);
    messages.appendChild(div);
    scrollToBottom();
  }

  function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[c];
    });
  }

  function scrollToBottom() {
    setTimeout(() => {
      messages.scrollTop = messages.scrollHeight;
    }, 0);
  }
})();
