<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Si la petición actual debe cargar estilos/scripts del listado (archivo, single o shortcode).
 *
 * @param WP_Post|null $post Post en contexto (por defecto global $post).
 */
function prompts_plugin_needs_frontend_assets($post = null) {
    if (is_singular('prompt') || is_post_type_archive('prompt')) {
        return true;
    }
    if (!$post instanceof WP_Post) {
        global $post;
    }
    if (!$post instanceof WP_Post || $post->post_content === '') {
        return false;
    }
    $content = $post->post_content;
    if (has_shortcode($content, 'prompts_list')) {
        return true;
    }
    // Bloque shortcode, plantillas o contenido pegado sin pasar por has_shortcode.
    if (false !== strpos($content, '[prompts_list')) {
        return true;
    }
    return false;
}

// Verifica si un usuario tiene permisos para administrar el plugin
function prompts_plugin_user_can_manage() {
    return current_user_can('manage_options');
}

// Sanitiza valores antes de guardarlos
function prompts_plugin_sanitize_text($text) {
    return sanitize_text_field($text);
}

/**
 * Ordena el listado del archivo por copias sin excluir prompts sin meta `prompt_copy_count`.
 *
 * @param string[] $clauses Consulta SQL fragmentada.
 * @param WP_Query $query   Instancia de la consulta.
 * @return string[]
 */
function prompts_plugin_archive_order_by_copy_count($clauses, $query) {
    if (empty($GLOBALS['prompts_plugin_archive_inner_query']) || 'prompt' !== $query->get('post_type')) {
        return $clauses;
    }
    global $wpdb;
    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS pm_prompt_copy ON ({$wpdb->posts}.ID = pm_prompt_copy.post_id AND pm_prompt_copy.meta_key = 'prompt_copy_count')";
    $clauses['orderby'] = "COALESCE(CAST(pm_prompt_copy.meta_value AS UNSIGNED), 0) DESC, {$wpdb->posts}.post_date DESC";
    $tax_query = $query->get('tax_query');
    if (!empty($tax_query) && (empty($clauses['distinct']) || false === strpos((string) $clauses['distinct'], 'DISTINCT'))) {
        $clauses['distinct'] = 'DISTINCT';
    }
    return $clauses;
}

/**
 * Indica si la petición trae filtros del listado de prompts en la URL.
 */
function prompts_plugin_request_has_list_filters() {
    if (isset($_GET['prompt_search']) && $_GET['prompt_search'] !== '') {
        return true;
    }
    if (!isset($_GET['categorias'])) {
        return false;
    }
    $raw = wp_unslash($_GET['categorias']);
    if (is_array($raw)) {
        foreach ($raw as $v) {
            if ($v !== '' && $v !== null) {
                return true;
            }
        }
        return false;
    }
    return $raw !== '';
}

/**
 * Evita que redirect_canonical elimine categorias[] y prompt_search (parámetros no reconocidos por WP).
 */
function prompts_plugin_disable_canonical_for_list_filters($redirect_url, $requested_url) {
    if (!prompts_plugin_request_has_list_filters()) {
        return $redirect_url;
    }
    if (is_post_type_archive('prompt')) {
        return false;
    }
    if (is_singular()) {
        global $post;
        if ($post instanceof WP_Post && prompts_plugin_needs_frontend_assets($post)) {
            return false;
        }
    }
    return $redirect_url;
}

add_filter('redirect_canonical', 'prompts_plugin_disable_canonical_for_list_filters', 5, 2);

/**
 * @param string[] $vars
 * @return string[]
 */
function prompts_plugin_register_list_query_vars($vars) {
    $vars[] = 'prompt_search';
    return $vars;
}

add_filter('query_vars', 'prompts_plugin_register_list_query_vars');
