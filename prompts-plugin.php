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

// Inicializar las clases
add_action('plugins_loaded', function() {
    new Prompts_Post_Type();
    new Prompts_Taxonomy();
    new Prompts_Metaboxes();
    new Prompts_Scripts();
    new Prompts_Templates();
    new Prompts_Settings();
});
function prompts_flush_rewrite_rules() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'prompts_flush_rewrite_rules');

function prompts_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'prompts_deactivate');