<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Post_Type {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
    }

    public function register_post_type() {
        $labels = array(
            'name' => 'Prompts',
            'singular_name' => 'Prompt',
            'menu_name' => 'Prompts',
            'add_new' => 'A«Ðadir Nuevo',
            'add_new_item' => 'A«Ðadir Nuevo Prompt',
            'edit_item' => 'Editar Prompt',
            'new_item' => 'Nuevo Prompt',
            'view_item' => 'Ver Prompt',
            'search_items' => 'Buscar Prompts',
            'not_found' => 'No se encontraron prompts',
            'not_found_in_trash' => 'No se encontraron prompts en la papelera',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'prompts',
                'with_front' => false, // Elimina el prefijo /blog/
            ),
            'supports' => array('title', 'custom-fields', 'comments'),
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-reddit',
            'comment_status' => 'open', // Forzamos comentarios abiertos por defecto
        );

        register_post_type('prompt', $args);
    }
}