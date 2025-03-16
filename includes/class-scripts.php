<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Scripts {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        if (is_post_type_archive('prompt') || is_singular('prompt')) {
            wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
            wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
            wp_enqueue_style('prompts-style', PROMPTS_PLUGIN_URL . 'style.css');
            wp_enqueue_style('dashicons'); // Cargar Dashicons
        }
        if (get_current_screen() && get_current_screen()->post_type === 'prompt') {
            wp_enqueue_script('prompts-admin-js', PROMPTS_PLUGIN_URL . 'admin-scripts.js', array('jquery'), '1.0', true);
        }
    }
}