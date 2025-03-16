<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar si el usuario activó la opción
$delete_data = get_option('prompts_plugin_delete_data_on_uninstall');

if ($delete_data) {
    global $wpdb;

    // Eliminar las opciones del plugin
    delete_option('prompts_plugin_delete_data_on_uninstall');

    // Eliminar los posts del Custom Post Type
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'prompt'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})");
}
