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
            __('Detalles del prompt', 'prompts-plugin'),
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
                <span id="prompt_description_counter" style="font-size: 12px; color: #666;"><?php echo (int) strlen($description); ?><?php echo esc_html(__('/160 caracteres', 'prompts-plugin')); ?></span>
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
                        'teeny' => false, // Editor completo; true = barra reducida
                        'quicktags' => true // Habilita opciones de formato
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'prompt') {
            return;
        }

        if (get_post_type($post_id) !== 'prompt') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!isset($_POST['prompts_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['prompts_nonce'])), 'prompts_save_meta_box')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['prompt_description'])) {
            $description = substr(sanitize_textarea_field(wp_unslash($_POST['prompt_description'])), 0, 160);
            update_post_meta($post_id, 'prompt_description', $description);
        }

        if (isset($_POST['prompt_content'])) {
            $content = wp_kses_post(wp_unslash($_POST['prompt_content']));
            update_post_meta($post_id, 'prompt_content', $content);
        }

        if (isset($_POST['prompt_copy_count'])) {
            $copy_count = get_post_meta($post_id, 'prompt_copy_count', true);
            $copy_count = ($copy_count === '' || $copy_count === null) ? 0 : (int) $copy_count;
            update_post_meta($post_id, 'prompt_copy_count', $copy_count);
        } elseif (get_post_meta($post_id, 'prompt_copy_count', true) === '') {
            update_post_meta($post_id, 'prompt_copy_count', 0);
        }
    }

    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'prompt') {
            return;
        }
        wp_enqueue_script(
            'prompts-admin-metabox',
            PROMPTS_PLUGIN_URL . 'assets/js/admin-metabox.js',
            array('jquery'),
            '1.0',
            true
        );
        wp_localize_script(
            'prompts-admin-metabox',
            'prompts_metabox_i18n',
            array(
                'charsSuffix' => __('/160 caracteres', 'prompts-plugin'),
            )
        );
    }
}