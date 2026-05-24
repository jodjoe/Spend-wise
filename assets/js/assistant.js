const assistantForm = document.getElementById('assistant-form');
const assistantPrompt = document.getElementById('assistant-prompt');
const assistantHistory = document.getElementById('assistant-history');
const assistantSend = document.getElementById('assistant-send');

function createAssistantMessage(text, sender = 'assistant') {
  const message = document.createElement('div');
  message.className = `assistant-message ${sender}`;
  message.innerHTML = `
    <div class="assistant-message-bubble">
      <span>${text}</span>
    </div>
  `;
  return message;
}

function setLoadingState(isLoading) {
  assistantSend.disabled = isLoading;
  assistantSend.textContent = isLoading ? 'Thinking…' : 'Send';
}

function addConversationItem(text, sender) {
  const message = createAssistantMessage(text, sender);
  if (assistantHistory.querySelector('.assistant-empty')) {
    assistantHistory.innerHTML = '';
  }
  assistantHistory.appendChild(message);
  assistantHistory.scrollTop = assistantHistory.scrollHeight;
}

assistantForm.addEventListener('submit', async event => {
  event.preventDefault();

  const prompt = assistantPrompt.value.trim();
  if (!prompt) {
    showToast('Please type a question first.', 'warning');
    return;
  }

  addConversationItem(prompt, 'user');
  assistantPrompt.value = '';
  setLoadingState(true);

  try {
    const formData = new FormData();
    formData.append('prompt', prompt);
    appendCsrfToken(formData);

    const response = await fetch('/api/chat.php', {
      method: 'POST',
      body: formData
    }).then(res => res.json());

    if (!response.success) {
      showToast(response.message || 'Unable to reach the assistant.', 'error');
      setLoadingState(false);
      return;
    }

    addConversationItem(response.data.message, 'assistant');
    setLoadingState(false);
  } catch (error) {
    showToast('Error contacting assistant.', 'error');
    setLoadingState(false);
  }
});

