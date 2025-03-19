// assets/js/frontend-script.js
jQuery(document).ready(function($) {
    // Initialize Select2 to select categories
    $(".category-select").select2({
        placeholder: "Selecciona Categorías...",
        allowClear: true
    });

    // Clear filters
    $('.clear-filters').on('click', function(e) {
        e.preventDefault();
        var $form = $(this).closest('form');
        $form.find('input[name="prompt_search"]').val('');
        $form.find('.category-select').val(null).trigger('change');
        $form.submit();
    });

    // Copy the prompt and increment the copy count
    $('.copy-button').click(function() {
        var button = $(this);
        var postId = button.data('postid');
        var textToCopy = button.data('text');

        if (!postId) {
            console.error('Post ID no definido en el botón');
            return;
        }

        navigator.clipboard.writeText(textToCopy).then(function() {
            button.find('span').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function() {
                button.find('span').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1000);

            // console.log('Preparando AJAX - URL:', ajax_object.ajaxurl, 'Post ID:', postId, 'Nonce:', ajax_object.nonce);
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'increment_prompt_copy_count',
                    post_id: postId,
                    security: ajax_object.nonce
                },
                beforeSend: function() {
                    console.log('AJAX enviado');
                },
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    if (response.success) {
                        console.log('Contador actualizado:', response.data);
                        button.data('copies', response.data);
                        button.attr('data-copies', response.data);
                    } else {
                        console.error('Error en la respuesta:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX - Estado:', status, 'Mensaje:', error);
                    console.log('Respuesta completa:', xhr.responseText);
                }
            });
        }, function(err) {
            console.error('Failed to copy:', err);
        });
    });
});