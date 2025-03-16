<?php
/*
Plugin Name: Prompts Plugin
Description: Un plugin para gestionar y mostrar prompts con búsqueda y filtros por categoría.
Version: 1.0
Author: Tu Nombre
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('PROMPTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PROMPTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar clases
require_once PROMPTS_PLUGIN_DIR . 'includes/class-post-type.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-taxonomy.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-metaboxes.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-scripts.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-templates.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-uninstall.php';

// Inicializar clases
new Prompts_Post_Type();
new Prompts_Taxonomy();
new Prompts_Metaboxes();
new Prompts_Scripts();
new Prompts_Templates();

// Registrar desinstalación
register_uninstall_hook(__FILE__, array('Prompts_Uninstall', 'cleanup'));

// Migrar contenido existente
function prompts_migrate_content() {
    $prompts = get_posts(array('post_type' => 'prompt', 'posts_per_page' => -1));
    foreach ($prompts as $prompt) {
        $content = get_post_field('post_content', $prompt->ID);
        if ($content && !get_post_meta($prompt->ID, 'prompt_content', true)) {
            update_post_meta($prompt->ID, 'prompt_content', $content);
        }
    }
}
add_action('admin_init', 'prompts_migrate_content');

// Forzar comentarios abiertos para nuevos prompts
function force_comments_open_for_prompts($data, $postarr) {
    if ($data['post_type'] === 'prompt') {
        $data['comment_status'] = 'open';
    }
    return $data;
}
add_filter('wp_insert_post_data', 'force_comments_open_for_prompts', 10, 2);

// Migrar comment_status de prompts existentes al activar el plugin
function prompts_activate() {
    $prompts = get_posts(array(
        'post_type' => 'prompt',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ));
    foreach ($prompts as $prompt) {
        if ($prompt->comment_status !== 'open') {
            wp_update_post(array(
                'ID' => $prompt->ID,
                'comment_status' => 'open',
            ));
        }
    }
}
register_activation_hook(__FILE__, 'prompts_activate');