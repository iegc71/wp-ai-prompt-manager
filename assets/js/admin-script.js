jQuery(function ($) {
    var ajax = typeof prompts_admin_ajax !== 'undefined' ? prompts_admin_ajax : null;
    if (!ajax) {
        return;
    }

    var $btn = $('#start-import');
    if (!$btn.length || !ajax.settings) {
        return;
    }

    var s = ajax.settings;

    $btn.on('click', function () {
        var fileInput = $('#prompts-import-file')[0];
        var overwrite = $('#overwrite_existing').is(':checked');

        if (!fileInput.files.length) {
            window.alert(s.selectFile);
            return;
        }

        var file = fileInput.files[0];
        var reader = new FileReader();

        reader.onload = function (e) {
            try {
                var data = JSON.parse(e.target.result);
                if (!Array.isArray(data)) {
                    throw new Error(s.jsonMustBeArray);
                }

                if (!data.length) {
                    window.alert(s.emptyFile);
                    return;
                }

                $('#import-progress-wrapper').show();
                $btn.prop('disabled', true);

                var total = data.length;
                var processed = 0;
                var created = 0;
                var updated = 0;
                var batchSize = 5;
                var i = 0;

                function processNextBatch() {
                    if (i >= data.length) {
                        $('#import-status-text').html(
                            '<strong>' +
                                s.completed +
                                '</strong> ' +
                                created +
                                ' ' +
                                s.created +
                                ', ' +
                                updated +
                                ' ' +
                                s.updated +
                                '.'
                        );
                        $btn.prop('disabled', false);
                        return;
                    }

                    var batch = data.slice(i, i + batchSize);
                    i += batchSize;

                    $.ajax({
                        url: ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'prompts_import_item',
                            security: ajax.import_nonce,
                            items: batch,
                            overwrite: overwrite,
                        },
                    })
                        .done(function (response) {
                            if (response.success) {
                                created += response.data.created;
                                updated += response.data.updated;
                            }
                        })
                        .always(function () {
                            processed += batch.length;
                            var percent = Math.round((processed / total) * 100);
                            $('#import-progress-bar').val(percent);
                            $('#import-status-text').text(
                                s.processing
                                    .replace('%1$s', String(percent))
                                    .replace('%2$s', String(processed))
                                    .replace('%3$s', String(total))
                            );
                            processNextBatch();
                        });
                }

                processNextBatch();
            } catch (err) {
                window.alert(s.readError + ' ' + err.message);
            }
        };

        reader.readAsText(file);
    });
});
