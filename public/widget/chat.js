(function() {
  'use strict';

  const API_BASE = 'https://4life.ovh/api';
  let apiKey = null;
  let conversationId = null;
  let isOpen = false;

  const script = document.currentScript
    || document.querySelector('script[data-4life-key]');
  apiKey = script?.getAttribute('data-4life-key');

  if (!apiKey) {
    console.error('4life.chat: Missing data-4life-key');
    return;
  }

  const styles = document.createElement('style');
  styles.textContent = `
    #fl-chat-widget {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 99999;
      font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    }
    #fl-chat-bubble {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1a73e8, #0d47a1);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: transform 0.2s;
    }
    #fl-chat-bubble:hover { transform: scale(1.1); }
    #fl-chat-bubble svg { width: 28px; height: 28px; fill: white; }
    #fl-chat-window {
      display: none;
      width: 380px; height: 520px;
      border-radius: 16px;
      background: white;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      flex-direction: column;
      overflow: hidden;
      position: absolute;
      bottom: 72px; right: 0;
    }
    #fl-chat-window.open { display: flex; }
    #fl-chat-header {
      background: linear-gradient(135deg, #1a73e8, #0d47a1);
      color: white;
      padding: 16px;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    #fl-chat-close { cursor: pointer; font-size: 18px; }
    #fl-chat-messages {
      flex: 1; overflow-y: auto;
      padding: 16px;
    }
    .fl-msg {
      margin-bottom: 12px;
      max-width: 85%;
      padding: 10px 14px;
      border-radius: 12px;
      font-size: 14px; line-height: 1.5;
      word-wrap: break-word;
    }
    .fl-msg.user {
      background: #e3f2fd;
      margin-left: auto;
      border-bottom-right-radius: 4px;
    }
    .fl-msg.bot {
      background: #f5f5f5;
      border-bottom-left-radius: 4px;
    }
    .fl-msg.bot strong { font-weight: 600; }
    .fl-typing {
      padding: 10px 14px;
      background: #f5f5f5;
      border-radius: 12px;
      display: inline-block;
      margin-bottom: 12px;
      font-size: 14px;
      color: #999;
    }
    #fl-chat-input-area {
      padding: 12px;
      border-top: 1px solid #eee;
      display: flex; gap: 8px;
    }
    #fl-chat-input {
      flex: 1; border: 1px solid #ddd;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 14px; outline: none;
    }
    #fl-chat-input:focus { border-color: #1a73e8; }
    #fl-chat-send {
      background: #1a73e8; color: white;
      border: none; border-radius: 50%;
      width: 36px; height: 36px;
      cursor: pointer; font-size: 16px;
      display: flex; align-items: center; justify-content: center;
    }
    #fl-chat-send:disabled { opacity: 0.5; cursor: not-allowed; }
    #fl-powered {
      text-align: center;
      padding: 6px;
      font-size: 11px; color: #999;
    }
    #fl-powered a { color: #1a73e8; text-decoration: none; }
    @media (max-width: 420px) {
      #fl-chat-window { width: calc(100vw - 40px); height: 70vh; }
    }
  `;
  document.head.appendChild(styles);

  const widget = document.createElement('div');
  widget.id = 'fl-chat-widget';
  widget.innerHTML = `
    <div id="fl-chat-window">
      <div id="fl-chat-header">
        <span>\uD83C\uDF3F 4Life Asesor de Salud</span>
        <span id="fl-chat-close">\u2715</span>
      </div>
      <div id="fl-chat-messages">
        <div class="fl-msg bot">
          \u00A1Hola! Soy tu asesor de salud 4Life.
          Descr\u00EDbeme tus necesidades de salud y te
          recomendar\u00E9 los mejores suplementos.
        </div>
      </div>
      <div id="fl-chat-input-area">
        <input id="fl-chat-input"
          placeholder="Escribe tu consulta..."
          autocomplete="off" />
        <button id="fl-chat-send">\u27A4</button>
      </div>
      <div id="fl-powered">
        Powered by <a href="https://4life.ovh"
        target="_blank">4life.ovh</a>
      </div>
    </div>
    <div id="fl-chat-bubble">
      <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1
        0-2 .9-2 2v18l4-4h14c1.1 0 2-.9
        2-2V4c0-1.1-.9-2-2-2z"/></svg>
    </div>
  `;
  document.body.appendChild(widget);

  const bubble = document.getElementById('fl-chat-bubble');
  const chatWindow = document.getElementById('fl-chat-window');
  const closeBtn = document.getElementById('fl-chat-close');
  const input = document.getElementById('fl-chat-input');
  const sendBtn = document.getElementById('fl-chat-send');
  const messages = document.getElementById('fl-chat-messages');

  bubble.addEventListener('click', function() {
    isOpen = !isOpen;
    chatWindow.classList.toggle('open', isOpen);
    if (isOpen) input.focus();
  });

  closeBtn.addEventListener('click', function() {
    isOpen = false;
    chatWindow.classList.remove('open');
  });

  function addMessage(text, type) {
    const msg = document.createElement('div');
    msg.className = 'fl-msg ' + type;
    if (type === 'bot') {
      msg.innerHTML = text;
    } else {
      msg.textContent = text;
    }
    messages.appendChild(msg);
    messages.scrollTop = messages.scrollHeight;
  }

  function showTyping() {
    const el = document.createElement('div');
    el.className = 'fl-typing';
    el.id = 'fl-typing';
    el.textContent = 'Analizando tu consulta...';
    messages.appendChild(el);
    messages.scrollTop = messages.scrollHeight;
  }

  function hideTyping() {
    const el = document.getElementById('fl-typing');
    if (el) el.remove();
  }

  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;

    addMessage(text, 'user');
    input.value = '';
    input.disabled = true;
    sendBtn.disabled = true;
    showTyping();

    try {
      const res = await fetch(API_BASE + '/v1/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': apiKey,
        },
        body: JSON.stringify({
          message: text,
          conversation_id: conversationId,
        })
      });

      const data = await res.json();
      hideTyping();

      if (data.success) {
        conversationId = data.data.conversation_id;
        addMessage(data.data.response, 'bot');
      } else {
        addMessage('Error: ' + (data.error || 'Error desconocido'), 'bot');
      }
    } catch (err) {
      hideTyping();
      addMessage('Error de conexi\u00F3n. Intenta de nuevo.', 'bot');
    }

    input.disabled = false;
    sendBtn.disabled = false;
    input.focus();
  }

  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') sendMessage();
  });
})();
