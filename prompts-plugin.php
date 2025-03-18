<?php
/**
 * Plugin Name: Prompts Plugin
 * Description: Un plugin para gestionar prompts en WordPress.
 * Version: 1.0.0
 * Author: Ivan Garcia Cordero
 * Text Domain: prompts-plugin
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir la ruta base del plugin
define('PROMPTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PROMPTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos de clases
require_once PROMPTS_PLUGIN_DIR . 'includes/class-post-type.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-taxonomy.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-metaboxes.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-scripts.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-templates.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/class-settings.php';
require_once PROMPTS_PLUGIN_DIR . 'includes/helpers.php';

// Función para inicializar la clase Prompts_Post_Type y registrar el post type
function prompts_register_post_type() {
    $post_type = new Prompts_Post_Type();
    $post_type->register_post_type(); // Llamamos directamente al método de registro
}

// Función para inicializar las clases adicionales
function initialize_prompts_additional_classes() {
    new Prompts_Taxonomy();
    new Prompts_Metaboxes();
    new Prompts_Scripts();
    new Prompts_Templates();
    new Prompts_Settings();
}

// Registro normal en init
add_action('init', function() {
    prompts_register_post_type();
    initialize_prompts_additional_classes();
});

// Función de activación
function prompts_activate_function() {
    // Registrar el post type inmediatamente
    prompts_register_post_type();

    // Forzar actualización de reglas de reescritura
    flush_rewrite_rules(true);
}
register_activation_hook(__FILE__, 'prompts_activate_function');

// Función de desactivación
function prompts_deactivate_function() {
    flush_rewrite_rules(true);
}
register_deactivation_hook(__FILE__, 'prompts_deactivate_function');