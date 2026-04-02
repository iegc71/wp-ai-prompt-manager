<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$delete_data = get_option('prompts_plugin_delete_data_on_uninstall');

if (!$delete_data) {
    return;
}

global $wpdb;

$ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'prompt'");
if (!empty($ids)) {
    $ids = array_map('intval', $ids);
    $in_list = implode(',', $ids);
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in_list})");
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$in_list})");
}

$wpdb->delete($wpdb->posts, array('post_type' => 'prompt'), array('%s'));

delete_option('prompts_plugin_delete_data_on_uninstall');
