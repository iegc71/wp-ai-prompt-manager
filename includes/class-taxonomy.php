<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Taxonomy {
    public function register_taxonomy() {
        register_taxonomy('categoria-de-prompt', 'prompt', array(
            'labels' => array(
                'name' => __('Categorías de prompt', 'prompts-plugin'),
                'singular_name' => __('Categoría de prompt', 'prompts-plugin'),
            ),
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
}