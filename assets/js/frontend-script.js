// script.js
document.addEventListener('DOMContentLoaded', () => {
  // Aquí defines tus prompts (reemplaza esto con tu lógica real)
  let prompts = []; // Podrías llenarlo con tus datos reales

  const promptContainer = document.getElementById('prompt-container');

  function renderPrompts() {
      promptContainer.innerHTML = ''; // Limpiar contenedor
      prompts.forEach(prompt => {
          const promptDiv = document.createElement('div');
          promptDiv.className = 'prompt-item';
          promptDiv.innerHTML = `
              <p>${prompt}</p>
              <button class="copy-btn" data-prompt="${prompt}">Copiar</button>
          `;
          promptContainer.appendChild(promptDiv);
      });
      attachCopyEvents();
  }

  function attachCopyEvents() {
      const copyButtons = document.querySelectorAll('.copy-btn');
      copyButtons.forEach(button => {
          button.removeEventListener('click', copyHandler); // Evitar duplicados
          button.addEventListener('click', copyHandler);
      });
  }

  function copyHandler(event) {
      const promptText = event.target.getAttribute('data-prompt');
      navigator.clipboard.writeText(promptText)
          .then(() => {
              alert('¡Copiado al portapapeles!');
          })
          .catch(err => {
              console.error('Error al copiar:', err);
          });
  }

  // Llama a renderPrompts cuando tengas los datos
  // Por ejemplo, podrías hacerlo después de cargar tus prompts reales
  renderPrompts();
});