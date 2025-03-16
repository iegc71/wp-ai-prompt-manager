<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Taxonomy {
    public function __construct() {
        add_action('init', array($this, 'register_taxonomy'));
    }

    public function register_taxonomy() {
        register_taxonomy('categoria-de-prompt', 'prompt', array(
            'labels' => array(
                'name' => __('Categorías de Prompt'),
                'singular_name' => __('Categoría de Prompt'),
            ),
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
}