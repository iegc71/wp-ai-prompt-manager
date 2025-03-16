<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Uninstall {
    public static function cleanup() {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Eliminar prompts
        $prompts = get_posts(array(
            'post_type' => 'prompt',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ));
        foreach ($prompts as $prompt_id) {
            wp_delete_post($prompt_id, true);
        }

        // Eliminar términos de la taxonomía
        $terms = get_terms(array(
            'taxonomy' => 'categoria-de-prompt',
            'hide_empty' => false,
            'fields' => 'ids',
        ));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term_id) {
                wp_delete_term($term_id, 'categoria-de-prompt');
            }
        }

        // Eliminar metadatos huérfanos
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'prompt_description'");
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'prompt_content'");
    }
}