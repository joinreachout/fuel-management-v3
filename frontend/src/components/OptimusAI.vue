<template>
  <!-- Floating Button -->
  <button
    v-if="!isOpen"
    type="button"
    class="ai-assistant-button"
    @click="toggleChat"
    title="Open Optimus AI Assistant">
    🤖
  </button>

  <!-- Chat Container -->
  <div v-if="isOpen" class="ai-assistant-container">
    <!-- Header -->
    <div class="ai-assistant-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 24px;">🤖</span>
        <div>
          <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Optimus</h3>
          <p style="font-size: 11px; opacity: 0.9; margin: 2px 0 0 0;">
            AI Assistant
          </p>
        </div>
      </div>
      <button class="ai-assistant-close" type="button" @click="toggleChat">✕</button>
    </div>

    <!-- Messages Container -->
    <div class="ai-assistant-messages" ref="messagesContainer">
      <div v-if="messages.length === 0" class="text-center py-8 px-4">
        <span class="text-5xl mb-3 block">👋</span>
        <p class="text-gray-600 text-sm">Hi! I'm Optimus, your AI fuel management assistant.</p>
        <p class="text-gray-500 text-xs mt-2">Ask me anything about your fuel inventory, stations, or orders!</p>
      </div>

      <div
        v-for="(message, index) in messages"
        :key="index"
        :class="message.type === 'user' ? 'user-message' : 'ai-message'">
        {{ message.text }}
      </div>

      <div v-if="isTyping" class="ai-message">
        <div class="typing-indicator">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>

    <!-- Input Container -->
    <div class="ai-assistant-input-container">
      <input
        v-model="userInput"
        type="text"
        placeholder="Ask Optimus..."
        maxlength="500"
        @keyup.enter="sendMessage" />
      <button type="button" @click="sendMessage" title="Send">➤</button>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';

const isOpen = ref(false);
const messages = ref([]);
const userInput = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);

const toggleChat = () => {
  isOpen.value = !isOpen.value;
};

const sendMessage = async () => {
  if (!userInput.value.trim()) return;

  const messageText = userInput.value.trim();
  userInput.value = '';

  // Add user message
  messages.value.push({
    type: 'user',
    text: messageText
  });

  // Scroll to bottom
  await nextTick();
  scrollToBottom();

  // Show typing indicator
  isTyping.value = true;

  // Simulate AI response (in real implementation, this would call the backend API)
  setTimeout(() => {
    isTyping.value = false;

    // Simple mock responses based on keywords
    let response = getAIResponse(messageText);

    messages.value.push({
      type: 'ai',
      text: response
    });

    nextTick(() => scrollToBottom());
  }, 1000 + Math.random() * 1000);
};

const getAIResponse = (question) => {
  const lowerQuestion = question.toLowerCase();

  if (lowerQuestion.includes('hello') || lowerQuestion.includes('hi') || lowerQuestion.includes('привет')) {
    return "Hello! I'm Optimus, your AI fuel management assistant. How can I help you today?";
  }

  if (lowerQuestion.includes('station') || lowerQuestion.includes('станци')) {
    return "I can help you with station management! You have 9 active stations. Would you like to see critical stock levels or station performance metrics?";
  }

  if (lowerQuestion.includes('stock') || lowerQuestion.includes('inventory') || lowerQuestion.includes('запас')) {
    return "Your current total stock is 139.2M liters across all locations, with an average fill level of 68.4%. I can provide detailed breakdowns by fuel type or location if needed.";
  }

  if (lowerQuestion.includes('critical') || lowerQuestion.includes('low') || lowerQuestion.includes('shortage')) {
    return "Based on current consumption rates, I've identified several tanks requiring attention. Would you like me to generate procurement recommendations?";
  }

  if (lowerQuestion.includes('order') || lowerQuestion.includes('buy') || lowerQuestion.includes('purchase')) {
    return "I can help you create optimal procurement orders! I'll analyze current stock levels, consumption patterns, and supplier performance to recommend the best ordering strategy.";
  }

  // Default response
  return "I understand your question. In the full version, I'll provide detailed insights based on real-time data analysis. For now, try asking about stations, stock levels, or procurement recommendations!";
};

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};
</script>

<style scoped>
/* Floating Button */
.ai-assistant-button {
  position: fixed !important;
  bottom: 24px !important;
  right: 24px !important;
  left: auto !important;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #06b6d4 0%, #ec4899 100%);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 30px;
  box-shadow: 0 4px 20px rgba(236, 72, 153, 0.4);
  z-index: 1000;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: buttonPulse 3s ease-in-out infinite, buttonGlow 2s ease-in-out infinite;
}

@keyframes buttonPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@keyframes buttonGlow {
  0%, 100% {
    box-shadow: 0 4px 20px rgba(236, 72, 153, 0.4),
                0 0 30px rgba(6, 182, 212, 0.3);
  }
  50% {
    box-shadow: 0 6px 30px rgba(236, 72, 153, 0.6),
                0 0 40px rgba(6, 182, 212, 0.5);
  }
}

.ai-assistant-button:hover {
  transform: translateY(-3px) scale(1.1);
  box-shadow: 0 8px 40px rgba(236, 72, 153, 0.6),
              0 0 50px rgba(6, 182, 212, 0.6);
}

.ai-assistant-button:active {
  transform: translateY(-1px) scale(1.05);
}

/* Chat Container */
.ai-assistant-container {
  position: fixed !important;
  bottom: 96px !important;
  right: 24px !important;
  left: auto !important;
  width: 400px;
  max-width: calc(100vw - 48px);
  height: 540px;
  max-height: calc(100vh - 130px);
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15),
              0 0 30px rgba(236, 72, 153, 0.1);
  display: flex;
  flex-direction: column;
  z-index: 999;
  overflow: hidden;
  border: 2px solid transparent;
  background-clip: padding-box;
  animation: containerSlideUp 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes containerSlideUp {
  0% {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  60% {
    transform: translateY(-5px) scale(1.02);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Header */
.ai-assistant-header {
  background: linear-gradient(135deg, #06b6d4 0%, #ec4899 100%);
  color: white;
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.ai-assistant-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.ai-assistant-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

/* Messages Container */
.ai-assistant-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
}

.user-message,
.ai-message {
  max-width: 80%;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
  animation: messageSlideIn 0.3s ease;
}

@keyframes messageSlideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.user-message {
  align-self: flex-end;
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.ai-message {
  align-self: flex-start;
  background: white;
  color: #1f2937;
  border: 1px solid #e5e7eb;
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  gap: 4px;
  align-items: center;
  padding: 8px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #9ca3af;
  animation: typingBounce 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typingBounce {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-8px);
  }
}

/* Input Container */
.ai-assistant-input-container {
  padding: 16px;
  background: white;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.ai-assistant-input-container input {
  flex: 1;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  background: #f8fafc;
}

.ai-assistant-input-container input:focus {
  border-color: transparent;
  background: white;
  box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2),
              0 0 0 4px rgba(236, 72, 153, 0.1);
}

.ai-assistant-input-container button {
  background: linear-gradient(135deg, #06b6d4 0%, #ec4899 100%);
  color: white;
  border: none;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(236, 72, 153, 0.3);
}

.ai-assistant-input-container button:hover {
  transform: scale(1.1) rotate(15deg);
  box-shadow: 0 4px 16px rgba(236, 72, 153, 0.5),
              0 0 20px rgba(6, 182, 212, 0.3);
}

.ai-assistant-input-container button:active {
  transform: scale(1.05) rotate(10deg);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .ai-assistant-button {
    bottom: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    font-size: 28px;
  }

  .ai-assistant-container {
    bottom: 88px;
    right: 20px;
    left: 20px;
    width: auto;
    height: 500px;
  }

  .user-message,
  .ai-message {
    max-width: 85%;
    font-size: 13px;
  }
}
</style>
