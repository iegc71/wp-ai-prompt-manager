<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Metaboxes {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_meta_box() {
        add_meta_box(
            'prompts_fields',
            __('Detalles del Prompt'),
            array($this, 'fields_callback'),
            'prompt',
            'normal',
            'high'
        );
    }

    public function fields_callback($post) {
        wp_nonce_field('prompts_save_meta_box', 'prompts_nonce');

        $description = get_post_meta($post->ID, 'prompt_description', true);
        $content = get_post_meta($post->ID, 'prompt_content', true);
        ?>
        <div class="prompts-metabox">
            <p>
                <label for="prompt_description" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php _e('Descripción', 'prompts-plugin'); ?> (máx. 160 caracteres)</label>
                <textarea style="width: 100%; height: 60px; margin-bottom: 5px;" name="prompt_description" id="prompt_description" maxlength="160"><?php echo esc_textarea($description); ?></textarea>
                <span id="prompt_description_counter" style="font-size: 12px; color: #666;"><?php echo strlen($description); ?>/160 caracteres</span>
            </p>
            <p>
                <label for="prompt_content" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php _e('Contenido del Prompt', 'prompts-plugin'); ?></label>
                <?php
                wp_editor(
                    $content,
                    'prompt_content',
                    array(
                        'textarea_name' => 'prompt_content',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false, // Editor completo, ajusta a true para una versi車n simplificada
                        'quicktags' => true // Habilita opciones de formato
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['prompts_nonce']) || !wp_verify_nonce($_POST['prompts_nonce'], 'prompts_save_meta_box')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['prompt_description'])) {
            $description = substr(sanitize_textarea_field($_POST['prompt_description']), 0, 160); // Limita a 160 caracteres
            if (!update_post_meta($post_id, 'prompt_description', $description)) {
                error_log("Error updating prompt description for post ID: $post_id");
            }
        }

        if (isset($_POST['prompt_content'])) {
            $content = wp_kses_post($_POST['prompt_content']); // Permite HTML seguro
            if (!update_post_meta($post_id, 'prompt_content', $content)) {
                error_log("Error updating prompt content for post ID: $post_id");
            }
        }
        // Add copy count handling
        if (isset($_POST['prompt_copy_count'])) {
            $copy_count = get_post_meta($post_id, 'prompt_copy_count', true) ?: 0; // Use null coalescing operator
            update_post_meta($post_id, 'prompt_copy_count', $copy_count);
        } else {
            // Initialize copy count if it doesn't exist
            if (!get_post_meta($post_id, 'prompt_copy_count', true)) {
                update_post_meta($post_id, 'prompt_copy_count', 0);
            }
        }
        // For any other custom fields
        if ( ! isset( $_POST['post_type'] ) || $_POST['post_type'] !== 'prompt' ) {
            return;
        }

    }

    public function enqueue_scripts() {
        if (get_current_screen()->post_type === 'prompt') {
            wp_enqueue_script('prompts-admin-js', PROMPTS_PLUGIN_URL . 'admin-scripts.js', array('jquery'), '1.0', true);
        }
    }
}