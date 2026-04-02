<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Post_Type {
    public function register_post_type() {
        $labels = array(
            'name' => __('Prompts', 'prompts-plugin'),
            'singular_name' => __('Prompt', 'prompts-plugin'),
            'menu_name' => __('Prompts', 'prompts-plugin'),
            'add_new' => __('Añadir nuevo', 'prompts-plugin'),
            'add_new_item' => __('Añadir nuevo prompt', 'prompts-plugin'),
            'edit_item' => __('Editar prompt', 'prompts-plugin'),
            'new_item' => __('Nuevo prompt', 'prompts-plugin'),
            'view_item' => __('Ver prompt', 'prompts-plugin'),
            'search_items' => __('Buscar prompts', 'prompts-plugin'),
            'not_found' => __('No se encontraron prompts', 'prompts-plugin'),
            'not_found_in_trash' => __('No se encontraron prompts en la papelera', 'prompts-plugin'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'prompts',
                'with_front' => false,
            ),
            'supports' => array('title', 'editor', 'custom-fields', 'comments'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-reddit',
            'comment_status' => 'open',
        );

        register_post_type('prompt', $args);
    }
}
