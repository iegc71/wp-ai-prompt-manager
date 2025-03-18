// script.js
jQuery(document).ready(function($) {
  $('.clear-filters').on('click', function(e) {
      e.preventDefault();
      var $form = $(this).closest('form');
      $form.find('input[name="prompt_search"]').val('');
      $form.find('.category-select').val(null).trigger('change');
      $form.submit();
  });
  $('.copy-button').on('click', async function(e) {
      e.preventDefault();
      var htmlContent = $(this).data('html');
      var textContent = $(this).data('text');
      try {
          await navigator.clipboard.write([
              new ClipboardItem({
                  'text/plain': new Blob([textContent], { type: 'text/plain' }),
                  'text/html': new Blob([htmlContent], { type: 'text/html' })
              })
          ]);
          alert('¡Prompt copiado con formato al portapapeles!');
      } catch (err) {
          console.error('Error al copiar: ', err);
          navigator.clipboard.writeText(textContent).then(function() {
              alert('Copiado como texto plano debido a un error.');
          });
      }
  });
});