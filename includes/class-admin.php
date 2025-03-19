<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Admin {
    public function __construct() {
        add_filter('manage_prompt_posts_columns', array($this, 'add_copy_count_column'));
        add_action('manage_prompt_posts_custom_column', array($this, 'display_copy_count_column'), 10, 2);
    }

    public function add_copy_count_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key == 'title') {
                $new_columns['copy_count'] = __('Copias', 'prompts-plugin');
            }
        }
        return $new_columns;
    }

    public function display_copy_count_column($column, $post_id) {
        if ($column == 'copy_count') {
            $copy_count = get_post_meta($post_id, 'prompt_copy_count', true);
            echo esc_html($copy_count);
        }
    }
}
