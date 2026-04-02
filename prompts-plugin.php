<?php
/**
 * Plugin Name: Prompts Plugin
 * Description: Un plugin para gestionar prompts en WordPress.
 * Version: 1.0.0
 * Author: Ivan Garcia Cordero
 * Author URI: https://codelisto.com
 * Text Domain: prompts-plugin
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    load_plugin_textdomain('prompts-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 0);

// Definir rutas
define('PROMPTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PROMPTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos de clases
$includes = [
    'class-post-type.php',
    'class-taxonomy.php',
    'class-metaboxes.php',
    'class-scripts.php',
    'class-templates.php',
    'class-settings.php',
    'class-ajax-handler.php',
    'helpers.php',
    'prompt-list.php',
    'class-admin.php',
    'class-shortcodes.php',
];

foreach ($includes as $file) {
    $filepath = PROMPTS_PLUGIN_DIR . 'includes/' . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    }
}

// Registrar el Custom Post Type
function register_prompts_post_type() {
    $post_type = new Prompts_Post_Type();
    $post_type->register_post_type();
}
add_action('init', 'register_prompts_post_type', 5); // Prioridad 5 para ejecutarse antes de las taxonomías

// Registrar las taxonomías
function register_prompts_taxonomies() {
    $taxonomy = new Prompts_Taxonomy();
    $taxonomy->register_taxonomy();
}
add_action('init', 'register_prompts_taxonomies', 10); // Prioridad 10 para asegurarse de que el post type ya existe

// Registrar otras clases y funcionalidades
function initialize_prompts_additional_classes() {
    new Prompts_Metaboxes();
    new Prompts_Scripts();
    new Prompts_Templates();
    new Prompts_Settings();
    new Prompts_Ajax();
    new Prompts_Admin();
    new Prompts_Shortcodes();
}
add_action('init', 'initialize_prompts_additional_classes', 15); // Se ejecuta después del post type y taxonomías

// Función de activación
function prompts_activate_function() {
    // Registrar el post type y taxonomías en la activación
    register_prompts_post_type();
    register_prompts_taxonomies();

    // Forzar actualización de reglas de reescritura
    flush_rewrite_rules(true);
}
register_activation_hook(__FILE__, 'prompts_activate_function');

// Función de desactivación
function prompts_deactivate_function() {
    flush_rewrite_rules(true);
}
register_deactivation_hook(__FILE__, 'prompts_deactivate_function');

/**
 * Añade un enlace de 'Configuración' en la lista de plugins.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="options-general.php?page=prompts-plugin">' . __('Configuración', 'prompts-plugin') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
