jQuery(document).ready(function ($) {
    var i18n = $.extend(
        {
            selectCategories: 'Selecciona categorías…',
            copyFailed: 'No se pudo copiar al portapapeles.',
        },
        typeof ajax_object !== 'undefined' && ajax_object.i18n ? ajax_object.i18n : {}
    );

    function copyToClipboard(text, onSuccess, onFail) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
                fallbackCopyText(text, onSuccess, onFail);
            });
            return;
        }
        fallbackCopyText(text, onSuccess, onFail);
    }

    function fallbackCopyText(text, onSuccess, onFail) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            var ok = document.execCommand('copy');
            document.body.removeChild(ta);
            if (ok) {
                onSuccess();
            } else {
                onFail();
            }
        } catch (e) {
            document.body.removeChild(ta);
            onFail();
        }
    }

    $('.category-select').select2({
        placeholder: i18n.selectCategories,
        allowClear: true,
    });

    $('.prompt-search-form').on('submit', function () {
        var $form = $(this);
        var $sel = $form.find('select.category-select');
        if (!$sel.length) {
            return;
        }
        $form.find('input.prompts-cat-sync').remove();
        var vals = $sel.val();
        if (vals && vals.length) {
            vals.forEach(function (slug) {
                $('<input>', {
                    type: 'hidden',
                    class: 'prompts-cat-sync',
                    name: 'categorias[]',
                    value: slug,
                }).appendTo($form);
            });
        }
        $sel.prop('disabled', true);
    });

    $('.clear-filters').on('click', function (e) {
        e.preventDefault();
        var $form = $(this).closest('form');
        $form.find('input[name="prompt_search"]').val('');
        $form.find('.category-select').val(null).trigger('change');
        $form.submit();
    });

    $('.copy-button').on('click', function () {
        var button = $(this);
        var postId = button.data('postid');
        var textToCopy = button.data('text');

        if (!postId) {
            return;
        }

        function flashSuccessIcon() {
            button.find('span').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function () {
                button.find('span').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1000);
        }

        function sendIncrementAjax() {
            if (typeof ajax_object === 'undefined') {
                return;
            }
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'increment_prompt_copy_count',
                    post_id: postId,
                    security: ajax_object.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        button.data('copies', response.data);
                        button.attr('data-copies', response.data);
                    }
                },
            });
        }

        copyToClipboard(
            textToCopy,
            function () {
                flashSuccessIcon();
                sendIncrementAjax();
            },
            function () {
                window.alert(i18n.copyFailed);
            }
        );
    });
});
