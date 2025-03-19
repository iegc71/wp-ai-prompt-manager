<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Ajax {
    public function __construct() {
        add_action('wp_ajax_increment_prompt_copy_count', array($this, 'increment_prompt_copy_count'));
        add_action('wp_ajax_nopriv_increment_prompt_copy_count', array($this, 'increment_prompt_copy_count'));
    }

    public function increment_prompt_copy_count() {
        // Verificar el nonce
        check_ajax_referer('prompts_copy_nonce', 'security');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if ($post_id <= 0) {
            wp_send_json_error('ID de post inválido');
        }

        $copy_count = get_post_meta($post_id, 'prompt_copy_count', true);
        $copy_count = ($copy_count === '') ? 0 : intval($copy_count);
        $new_copy_count = $copy_count + 1;

        update_post_meta($post_id, 'prompt_copy_count', $new_copy_count);

        wp_send_json_success($new_copy_count);
    }
}
