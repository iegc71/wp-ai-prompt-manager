<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Ajax {
    public function __construct() {
        add_action('wp_ajax_increment_prompt_copy_count', array($this, 'increment_prompt_copy_count'));
        add_action('wp_ajax_nopriv_increment_prompt_copy_count', array($this, 'increment_prompt_copy_count'));
        // Nuevo endpoint para importación AJAX
        add_action('wp_ajax_prompts_import_item', array($this, 'import_prompt_item'));
    }

    public function increment_prompt_copy_count() {
        // Verificar el nonce
        check_ajax_referer('prompts_copy_nonce', 'security');

        $post_id = isset($_POST['post_id']) ? (int) wp_unslash($_POST['post_id']) : 0;

        if ($post_id <= 0) {
            wp_send_json_error(__('ID de post inválido.', 'prompts-plugin'));
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'prompt' || $post->post_status !== 'publish') {
            wp_send_json_error(__('Prompt no válido.', 'prompts-plugin'));
        }

        $copy_count = get_post_meta($post_id, 'prompt_copy_count', true);
        $copy_count = ($copy_count === '') ? 0 : intval($copy_count);
        $new_copy_count = $copy_count + 1;

        update_post_meta($post_id, 'prompt_copy_count', $new_copy_count);

        wp_send_json_success($new_copy_count);
    }

    public function import_prompt_item() {
        check_ajax_referer('prompts_import_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permisos insuficientes.', 'prompts-plugin'));
        }

        $items = isset($_POST['items']) ? wp_unslash($_POST['items']) : null;
        $overwrite = isset($_POST['overwrite']) && wp_unslash($_POST['overwrite']) === 'true';

        if (!$items || !is_array($items)) {
            wp_send_json_error(__('Datos del elemento no recibidos.', 'prompts-plugin'));
        }

        $results = ['created' => 0, 'updated' => 0];
        $current_user_id = get_current_user_id();

        foreach ($items as $item) {
            $title = sanitize_text_field($item['title'] ?? __('Sin título', 'prompts-plugin'));
            $existing_id = 0;

            if ($overwrite) {
                $existing_posts = get_posts([
                    'post_type'   => 'prompt',
                    'title'       => $title,
                    'numberposts' => 1,
                    'fields'      => 'ids',
                    'post_status' => 'any',
                ]);
                if (!empty($existing_posts)) {
                    $existing_id = $existing_posts[0];
                }
            }

            $post_data = [
                'post_type'   => 'prompt',
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_author' => $current_user_id,
            ];

            if ($existing_id) {
                $post_data['ID'] = $existing_id;
                $post_id = wp_update_post($post_data);
                if ($post_id && !is_wp_error($post_id)) {
                    wp_publish_post($post_id); // Asegura la visibilidad y dispara hooks de publicación
                    $results['updated']++;
                }
            } else {
                $post_id = wp_insert_post($post_data);
                if ($post_id && !is_wp_error($post_id)) {
                    wp_publish_post($post_id); // Asegura la visibilidad y dispara hooks de publicación
                    $results['created']++;
                }
            }

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, 'prompt_description', sanitize_textarea_field($item['description'] ?? ''));
                update_post_meta($post_id, 'prompt_content', wp_kses_post($item['content'] ?? ''));

                // Inicializar el contador de copias si no existe.
                // Esto asegura que el prompt aparezca en consultas que ordenen o filtren por este metadato.
                if (get_post_meta($post_id, 'prompt_copy_count', true) === '') {
                    update_post_meta($post_id, 'prompt_copy_count', 0);
                }

                if (!empty($item['categories'])) {
                    $category_ids = [];
                    foreach ((array)$item['categories'] as $cat_name) {
                        $cat_name = trim($cat_name);
                        if (empty($cat_name)) continue;

                        // Verificar si el término existe
                        $term = term_exists($cat_name, 'categoria-de-prompt');
                        $term_id = is_array($term) ? $term['term_id'] : $term;

                        // Si no existe, lo creamos
                        if (!$term_id) {
                            $inserted = wp_insert_term($cat_name, 'categoria-de-prompt');
                            if (!is_wp_error($inserted)) {
                                $term_id = $inserted['term_id'];
                            }
                        }

                        if ($term_id) {
                            $category_ids[] = (int) $term_id;
                        }
                    }
                    // Para taxonomías jerárquicas, pasamos el array de IDs
                    wp_set_post_terms($post_id, $category_ids, 'categoria-de-prompt');
                }
                clean_post_cache($post_id); // Limpia la caché para que aparezca en el frontend
            }
        }

        // Limpiar la caché de consultas de posts para forzar la actualización del listado
        wp_cache_delete('last_changed', 'posts');

        wp_send_json_success($results);
    }
}
