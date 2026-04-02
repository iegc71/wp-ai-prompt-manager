jQuery(function ($) {
    var $ta = $('#prompt_description');
    var $counter = $('#prompt_description_counter');
    if (!$ta.length || !$counter.length) {
        return;
    }
    var suffix =
        typeof prompts_metabox_i18n !== 'undefined' && prompts_metabox_i18n.charsSuffix
            ? prompts_metabox_i18n.charsSuffix
            : '/160 caracteres';
    function sync() {
        $counter.text($ta.val().length + suffix);
    }
    $ta.on('input', sync);
});
