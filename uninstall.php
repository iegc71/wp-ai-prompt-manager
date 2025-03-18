<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar si el usuario activó la opción
$delete_data = get_option('prompts_plugin_delete_data_on_uninstall');

if ($delete_data) {
    global $wpdb;

    // Primero eliminar los datos importantes
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'prompt'");
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})");

    // Luego eliminar la opción
    delete_option('prompts_plugin_delete_data_on_uninstall');

    // Set a transient to show the admin notice on next load
    set_transient('prompts_plugin_data_deleted_notice', true, 60); // Expires in 60 seconds (adjust as needed)
}
