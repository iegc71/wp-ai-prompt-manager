<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Scripts {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_prompts-plugin' && get_post_type() !== 'prompt') {
            return;
        }
        wp_enqueue_style('prompts-plugin-admin-style', PROMPTS_PLUGIN_URL . 'assets/css/admin-style.css');
        wp_enqueue_script('prompts-plugin-admin-script', PROMPTS_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], '1.0', true);

        $localize = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'import_nonce' => wp_create_nonce('prompts_import_ajax_nonce'),
        );

        if ($hook === 'settings_page_prompts-plugin') {
            $localize['settings'] = array(
                'selectFile' => __('Por favor, selecciona un archivo JSON.', 'prompts-plugin'),
                'emptyFile' => __('El archivo JSON no contiene ningún elemento.', 'prompts-plugin'),
                'jsonMustBeArray' => __('El JSON debe ser una lista.', 'prompts-plugin'),
                'processing' => __('Procesando: %1$s%% (%2$s/%3$s)', 'prompts-plugin'),
                'completed' => __('Importación finalizada.', 'prompts-plugin'),
                'created' => __('creados', 'prompts-plugin'),
                'updated' => __('actualizados', 'prompts-plugin'),
                'readError' => __('Error al leer el JSON:', 'prompts-plugin'),
            );
        }

        wp_localize_script('prompts-plugin-admin-script', 'prompts_admin_ajax', $localize);
    }

    public function enqueue_frontend_scripts() {
        global $post;
        if (!prompts_plugin_needs_frontend_assets($post)) {
            return;
        }
        wp_enqueue_style('dashicons');
        wp_enqueue_style('prompts-plugin-frontend-style', PROMPTS_PLUGIN_URL . 'assets/css/frontend-style.css');
        $select2_ver = '4.1.0';
        wp_enqueue_style(
            'select2-css',
            PROMPTS_PLUGIN_URL . 'assets/vendor/select2/select2.min.css',
            array(),
            $select2_ver
        );
        wp_enqueue_script(
            'select2-js',
            PROMPTS_PLUGIN_URL . 'assets/vendor/select2/select2.min.js',
            array('jquery'),
            $select2_ver,
            true
        );
        wp_enqueue_script('prompts-plugin-frontend-script', PROMPTS_PLUGIN_URL . 'assets/js/frontend-script.js', ['jquery'], '1.0', true);

        wp_localize_script(
            'prompts-plugin-frontend-script',
            'ajax_object',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('prompts_copy_nonce'),
                'i18n' => array(
                    'selectCategories' => __('Selecciona categorías…', 'prompts-plugin'),
                    'copyFailed' => __('No se pudo copiar al portapapeles.', 'prompts-plugin'),
                ),
            )
        );
    }
}