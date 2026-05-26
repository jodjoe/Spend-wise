const chatMessages = document.getElementById('chat-messages');
const chatInput    = document.getElementById('chat-input');
const chatSend     = document.getElementById('chat-send');
const suggestions  = document.getElementById('suggestions');

// Enable send button only when there's text
chatInput.addEventListener('input', () => {
  chatSend.disabled = !chatInput.value.trim();
  // Auto-resize textarea
  chatInput.style.height = 'auto';
  chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
});

// Send on Enter (not Shift+Enter)
chatInput.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (!chatSend.disabled) sendMessage();
  }
});

chatSend.addEventListener('click', sendMessage);

// Suggestion chips
suggestions.addEventListener('click', e => {
  const btn = e.target.closest('.suggestion-btn');
  if (!btn) return;
  chatInput.value = btn.textContent;
  chatSend.disabled = false;
  sendMessage();
});

function appendBubble(html, role) {
  const wrap = document.createElement('div');
  wrap.className = `chat-bubble ${role === 'user' ? 'user-bubble' : 'ai-bubble'}`;

  if (role === 'ai') {
    wrap.innerHTML = `
      <div class="ai-avatar"><i class="fa fa-robot" style="color:var(--card);font-size:14px;"></i></div>
      <div class="bubble-content">${html}</div>`;
  } else {
    wrap.innerHTML = `<div class="bubble-content">${html}</div>`;
  }

  chatMessages.appendChild(wrap);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return wrap;
}

function appendThinking() {
  const wrap = document.createElement('div');
  wrap.className = 'chat-bubble ai-bubble thinking-bubble';
  wrap.innerHTML = `
    <div class="ai-avatar"><i class="fa fa-robot" style="color:var(--card);font-size:14px;"></i></div>
    <div class="bubble-content">
      <div class="thinking-dots"><span></span><span></span><span></span></div>
    </div>`;
  chatMessages.appendChild(wrap);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return wrap;
}

function escapeHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

async function sendMessage() {
  const prompt = chatInput.value.trim();
  if (!prompt) return;

  // Hide suggestions after first message
  if (suggestions) suggestions.style.display = 'none';

  appendBubble(escapeHtml(prompt), 'user');
  chatInput.value = '';
  chatInput.style.height = 'auto';
  chatSend.disabled = true;

  const thinking = appendThinking();

  try {
    const fd = new FormData();
    fd.append('prompt', prompt);
    appendCsrfToken(fd);

    const res  = await fetch('/api/chat.php', { method: 'POST', body: fd });
    const json = await res.json();

    thinking.remove();

    if (!json.success) {
      appendBubble(`<p style="color:var(--danger);">${escapeHtml(json.message || 'Something went wrong.')}</p>`, 'ai');
      return;
    }

    // Convert markdown-ish response to basic HTML
    const text = json.data.message || '';
    const html = text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n\n/g, '</p><p>')
      .replace(/\n/g, '<br>');

    appendBubble(`<p>${html}</p>`, 'ai');
  } catch {
    thinking.remove();
    appendBubble('<p style="color:var(--danger);">Could not reach the assistant. Check your connection.</p>', 'ai');
  }
}
