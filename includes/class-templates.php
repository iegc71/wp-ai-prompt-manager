<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Templates {
    public function __construct() {
        add_filter('template_include', array($this, 'template_include'));
    }

    public function template_include($template) {
        $plugin_dir = PROMPTS_PLUGIN_DIR . 'templates/';
        
        if (is_post_type_archive('prompt')) {
            $plugin_template = $plugin_dir . 'archive-prompt.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        if (is_singular('prompt')) {
            $plugin_template = $plugin_dir . 'single-prompt.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
}