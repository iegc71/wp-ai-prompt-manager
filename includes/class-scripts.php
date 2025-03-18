<?php
class Prompts_Scripts {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        wp_enqueue_style('dashicons');
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_prompts-plugin' && get_post_type() !== 'prompt') {
            return;
        }
        wp_enqueue_style('prompts-plugin-admin-style', PROMPTS_PLUGIN_URL . 'assets/css/admin-style.css');
        wp_enqueue_script('prompts-plugin-admin-script', PROMPTS_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], '1.0', true);
    }

    public function enqueue_frontend_scripts() {
        if (is_singular('prompt') || is_post_type_archive('prompt')) {
            wp_enqueue_style('prompts-plugin-frontend-style', PROMPTS_PLUGIN_URL . 'assets/css/frontend-style.css');
            // Enqueue Select2 CSS from CDN
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
            // Enqueue Select2 JavaScript from CDN
            wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
            wp_enqueue_script('prompts-plugin-frontend-script', PROMPTS_PLUGIN_URL . 'assets/js/frontend-script.js', ['jquery'], '1.0', true);
        }
    }
}
