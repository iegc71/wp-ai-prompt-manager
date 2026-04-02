<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estado de búsqueda y categorías desde la petición actual.
 *
 * @return array{selected_categories: string[], search_query: string, search_words: string[], normalized_search_words: string[]}
 */
function prompts_plugin_prompt_list_get_state() {
    $selected_categories = isset($_GET['categorias']) ? (array) wp_unslash($_GET['categorias']) : array();
    $selected_categories = array_map(
        static function ($slug) {
            $slug = sanitize_text_field($slug);
            return $slug === '' ? '' : strtolower($slug);
        },
        $selected_categories
    );
    $selected_categories = array_values(array_filter($selected_categories, static function ($s) {
        return $s !== '';
    }));
    $search_query = isset($_GET['prompt_search']) ? sanitize_text_field(wp_unslash($_GET['prompt_search'])) : '';
    $search_words = array_filter(explode(' ', strtolower(trim($search_query))));
    $normalized_search_words = array_map('remove_accents', $search_words);

    return compact('selected_categories', 'search_query', 'search_words', 'normalized_search_words');
}

/**
 * URL sin parámetros de filtro del listado (base para enlaces de categoría).
 *
 * @param string $url URL completa.
 */
function prompts_plugin_prompt_list_clean_url($url) {
    return remove_query_arg(array('prompt_search', 'paged', 'categorias'), $url);
}

/**
 * Añade a una URL los mismos filtros GET activos (búsqueda y categorías) para paginación y enlaces.
 *
 * @param string $url URL base (permalink de página o archivo).
 * @param array  $state Estado de prompts_plugin_prompt_list_get_state().
 */
function prompts_plugin_prompt_list_merge_filter_query_args($url, array $state) {
    $args = array();
    if (!empty($state['search_query'])) {
        $args['prompt_search'] = $state['search_query'];
    }
    $cats = array();
    if (!empty($state['selected_categories'])) {
        foreach ($state['selected_categories'] as $slug) {
            if ($slug !== '') {
                $cats[] = $slug;
            }
        }
    }
    if (!empty($cats)) {
        $args['categorias'] = $cats;
    }
    if (empty($args)) {
        return $url;
    }
    return add_query_arg($args, $url);
}

/**
 * Convierte slugs de URL en IDs de término existentes (taxonomía categoria-de-prompt).
 *
 * @param string[] $slugs
 * @return int[]
 */
function prompts_plugin_resolve_prompt_category_slugs_to_term_ids(array $slugs) {
    $slugs = array_values(array_unique(array_filter(array_map(static function ($s) {
        $s = sanitize_text_field($s);
        return ($s === '') ? '' : strtolower($s);
    }, $slugs))));
    if (empty($slugs)) {
        return array();
    }
    $terms = get_terms(array(
        'taxonomy' => 'categoria-de-prompt',
        'slug' => $slugs,
        'hide_empty' => false,
    ));
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }
    $ids = array();
    foreach ($terms as $term) {
        $ids[] = (int) $term->term_id;
    }
    return array_values(array_unique($ids));
}

/**
 * Incluye hijos jerárquicos de cada término (misma taxonomía).
 *
 * @param int[]  $term_ids
 * @param string $taxonomy
 * @return int[]
 */
function prompts_plugin_prompt_category_expand_with_children(array $term_ids, $taxonomy) {
    $out = array();
    foreach ($term_ids as $tid) {
        $tid = (int) $tid;
        if ($tid <= 0) {
            continue;
        }
        $out[] = $tid;
        $children = get_term_children($tid, $taxonomy);
        if (!is_wp_error($children) && !empty($children)) {
            foreach ($children as $child_id) {
                $out[] = (int) $child_id;
            }
        }
    }
    return array_values(array_unique($out));
}

/**
 * IDs de posts publicados asignados a cualquiera de los términos (vía term_relationships; evita tax_query + SQL del plugin).
 *
 * @param int[] $term_ids IDs de términos (sin expandir; se expanden aquí).
 * @return int[]
 */
function prompts_plugin_post_ids_for_category_term_ids(array $term_ids) {
    if (empty($term_ids)) {
        return array();
    }
    $taxonomy = 'categoria-de-prompt';
    $expanded = prompts_plugin_prompt_category_expand_with_children($term_ids, $taxonomy);
    $objects = get_objects_in_term($expanded, $taxonomy);
    if (is_wp_error($objects) || empty($objects)) {
        return array();
    }
    return array_values(array_unique(array_map('intval', $objects)));
}

/**
 * Cruza dos listas de IDs de post (AND). null = aún sin restricción previa.
 *
 * @param int[]|null $base
 * @param int[]      $add
 * @return int[]
 */
function prompts_plugin_intersect_prompt_post_ids($base, array $add) {
    $add = array_values(array_unique(array_map('intval', $add)));
    if ($base === null) {
        return empty($add) ? array(0) : $add;
    }
    $base = array_values(array_unique(array_map('intval', (array) $base)));
    if (count($base) === 1 && 0 === $base[0]) {
        return array(0);
    }
    if (empty($add)) {
        return array(0);
    }
    $inter = array_values(array_intersect($base, $add));
    return empty($inter) ? array(0) : $inter;
}

/**
 * @param array $config ids, category_slug, use_get_filters, use_copy_order
 * @param array $state  Estado de prompts_plugin_prompt_list_get_state()
 * @return array{0: array<string, mixed>, 1: bool} Argumentos base de WP_Query y si aplica orden por copias
 */
function prompts_plugin_prompt_list_build_base_args(array $config, array $state) {
    $defaults = array(
        'ids' => '',
        'category_slug' => '',
        'use_get_filters' => true,
        'use_copy_order' => true,
    );
    $config = array_merge($defaults, $config);

    $args = array(
        'post_type' => 'prompt',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $restricted = null;

    if ($config['use_get_filters'] && !empty($state['selected_categories'])) {
        $term_ids = prompts_plugin_resolve_prompt_category_slugs_to_term_ids($state['selected_categories']);
        if (empty($term_ids)) {
            $restricted = array(0);
        } else {
            $post_ids = prompts_plugin_post_ids_for_category_term_ids($term_ids);
            $restricted = empty($post_ids) ? array(0) : $post_ids;
        }
    }

    $shortcode_cat = sanitize_title((string) $config['category_slug']);
    if ($shortcode_cat !== '') {
        $sc_terms = get_terms(array(
            'taxonomy' => 'categoria-de-prompt',
            'slug' => $shortcode_cat,
            'hide_empty' => false,
        ));
        if (is_wp_error($sc_terms) || empty($sc_terms)) {
            $restricted = prompts_plugin_intersect_prompt_post_ids($restricted, array(0));
        } else {
            $sc_post_ids = prompts_plugin_post_ids_for_category_term_ids(array((int) $sc_terms[0]->term_id));
            $restricted = prompts_plugin_intersect_prompt_post_ids($restricted, $sc_post_ids);
        }
    }

    if ($restricted !== null) {
        $args['post__in'] = array_values(array_unique(array_map('intval', $restricted)));
    }

    $use_copy_order = (bool) $config['use_copy_order'];

    if ($config['ids'] !== '') {
        $ids_array = array_map('intval', explode(',', $config['ids']));
        $ids_array = array_filter($ids_array);
        if (!empty($ids_array)) {
            $ids_array = array_values($ids_array);
            if (isset($args['post__in'])) {
                $args['post__in'] = prompts_plugin_intersect_prompt_post_ids($args['post__in'], $ids_array);
            } else {
                $args['post__in'] = $ids_array;
            }
            $args['orderby'] = 'post__in';
            $use_copy_order = false;
        }
    }

    return array($args, $use_copy_order);
}

/**
 * @param int   $post_id
 * @param array $normalized_search_words
 */
function prompts_plugin_prompt_post_matches_normalized_search($post_id, array $normalized_search_words) {
    if (empty($normalized_search_words)) {
        return true;
    }
    $title = strtolower(get_the_title($post_id));
    $desc = strtolower((string) get_post_meta($post_id, 'prompt_description', true));
    $nt = remove_accents($title);
    $nd = remove_accents($desc);
    foreach ($normalized_search_words as $word) {
        if (strpos($nt, $word) === false && strpos($nd, $word) === false) {
            return false;
        }
    }
    return true;
}

/**
 * IDs en orden (por copias o por post__in) que cumplen la búsqueda por texto.
 *
 * @param array $config ids, category_slug, use_get_filters, use_copy_order
 */
function prompts_plugin_prompt_list_ordered_matching_ids(array $config, array $state) {
    list($base_args, $use_copy_order) = prompts_plugin_prompt_list_build_base_args($config, $state);

    $args = $base_args;
    $args['posts_per_page'] = -1;
    $args['paged'] = 1;
    $args['no_found_rows'] = true;
    $args['fields'] = 'ids';

    if ($use_copy_order) {
        $GLOBALS['prompts_plugin_archive_inner_query'] = true;
        add_filter('posts_clauses', 'prompts_plugin_archive_order_by_copy_count', 10, 2);
    }

    $query = new WP_Query($args);

    if ($use_copy_order) {
        remove_filter('posts_clauses', 'prompts_plugin_archive_order_by_copy_count', 10);
        unset($GLOBALS['prompts_plugin_archive_inner_query']);
    }

    $norm = $state['normalized_search_words'];
    $out = array();
    foreach ($query->posts as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id && prompts_plugin_prompt_post_matches_normalized_search($post_id, $norm)) {
            $out[] = $post_id;
        }
    }

    return $out;
}

/**
 * @param array $config Incluye paged, posts_per_page, ids, category_slug, use_get_filters, use_copy_order
 * @return WP_Query
 */
function prompts_plugin_prompt_list_query($config) {
    $defaults = array(
        'paged' => 1,
        'posts_per_page' => 10,
        'ids' => '',
        'category_slug' => '',
        'use_get_filters' => true,
        'use_copy_order' => true,
    );
    $config = array_merge($defaults, $config);

    $state = prompts_plugin_prompt_list_get_state();
    list($base_args, $use_copy_order) = prompts_plugin_prompt_list_build_base_args($config, $state);

    $args = $base_args;
    $args['posts_per_page'] = (int) $config['posts_per_page'];
    $args['paged'] = (int) $config['paged'];

    if ($use_copy_order) {
        $GLOBALS['prompts_plugin_archive_inner_query'] = true;
        add_filter('posts_clauses', 'prompts_plugin_archive_order_by_copy_count', 10, 2);
    }

    $query = new WP_Query($args);

    if ($use_copy_order) {
        remove_filter('posts_clauses', 'prompts_plugin_archive_order_by_copy_count', 10);
        unset($GLOBALS['prompts_plugin_archive_inner_query']);
    }

    return $query;
}

/**
 * Formulario de búsqueda y categorías (mismo marcado que el archivo /prompts/).
 *
 * @param string $form_action Acción del formulario (URL).
 * @param array  $state       Estado de prompts_plugin_prompt_list_get_state().
 */
function prompts_plugin_prompt_list_search_form_html($form_action, $state) {
    $categories = get_terms(array('taxonomy' => 'categoria-de-prompt', 'hide_empty' => false));
    ob_start();
    ?>
    <form class="prompt-search-form" method="get" action="<?php echo esc_url($form_action); ?>">
        <input type="text" name="prompt_search" value="<?php echo esc_attr($state['search_query']); ?>" placeholder="<?php echo esc_attr(__('Buscar prompts por título o descripción…', 'prompts-plugin')); ?>">
        <?php
        if (!is_wp_error($categories) && !empty($categories)) {
            echo '<select name="categorias[]" multiple class="category-select">';
            foreach ($categories as $category) {
                $selected = in_array($category->slug, $state['selected_categories'], true) ? ' selected' : '';
                echo '<option value="' . esc_attr($category->slug) . '"' . $selected . '>' . esc_html($category->name) . '</option>';
            }
            echo '</select>';
        }
        ?>
        <div class="botones">
            <button type="submit">
                <span class="dashicons dashicons-filter filter-button"></span>
            </button>
            <button type="button" class="clear-filters" title="<?php echo esc_attr(__('Limpiar filtros', 'prompts-plugin')); ?>">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Enlaces de paginación coherentes con la URL base (archivo o página con shortcode).
 *
 * @param int    $total_pages
 * @param int    $current
 * @param string $base_url              Permalink de la página o URL del archivo CPT.
 * @param bool   $is_prompt_post_archive Si es el archivo del CPT `prompt`.
 * @param array  $state                 Estado de filtros (se fusiona en cada enlace).
 */
function prompts_plugin_prompt_list_paginate_links($total_pages, $current, $base_url, $is_prompt_post_archive = false, array $state = array()) {
    if ($total_pages <= 1) {
        return '';
    }
    if ($is_prompt_post_archive) {
        $archive = get_post_type_archive_link('prompt');
        if ($archive) {
            $base_url = $archive;
        }
    }
    $merged = prompts_plugin_prompt_list_merge_filter_query_args($base_url, $state);
    $paginate_base = esc_url_raw(add_query_arg('paged', '%#%', $merged));
    return paginate_links(array(
        'base' => $paginate_base,
        'format' => '',
        'total' => $total_pages,
        'current' => $current,
        'prev_text' => __('« Anterior', 'prompts-plugin'),
        'next_text' => __('Siguiente »', 'prompts-plugin'),
    ));
}

/**
 * Bucle de ítems (mismo marcado que archive-prompt.php).
 *
 * @param WP_Query $prompts_query
 * @param array    $state
 * @param string   $category_link_base URL limpia para add_query_arg de categorías.
 * @param bool     $search_prefiltered  Si true, la query ya solo incluye resultados de búsqueda (no filtrar de nuevo).
 */
function prompts_plugin_prompt_list_items_html($prompts_query, $state, $category_link_base, $search_prefiltered = false) {
    $normalized_search_words = $search_prefiltered ? array() : $state['normalized_search_words'];
    $search_words = $state['search_words'];
    $clean_base = prompts_plugin_prompt_list_clean_url($category_link_base);

    ob_start();
    if ($prompts_query->have_posts()) {
        $any = false;
        while ($prompts_query->have_posts()) {
            $prompts_query->the_post();
            $title = strtolower(get_the_title());
            $desc = strtolower(get_post_meta(get_the_ID(), 'prompt_description', true) ?: '');
            $normalized_title = remove_accents($title);
            $normalized_desc = remove_accents($desc);
            $matches_all = true;
            if (!empty($normalized_search_words)) {
                foreach ($normalized_search_words as $word) {
                    if (strpos($normalized_title, $word) === false && strpos($normalized_desc, $word) === false) {
                        $matches_all = false;
                        break;
                    }
                }
            }
            if (!$matches_all) {
                continue;
            }
            $any = true;
            $highlighted_title = get_the_title();
            $highlighted_desc = get_post_meta(get_the_ID(), 'prompt_description', true) ?: '';
            foreach ($search_words as $word) {
                $highlighted_title = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<span class="highlight">$1</span>', $highlighted_title);
                $highlighted_desc = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<span class="highlight">$1</span>', $highlighted_desc);
            }
            $prompt_content = get_post_meta(get_the_ID(), 'prompt_content', true);
            $plain_content = strip_tags($prompt_content);
            $copy_count = get_post_meta(get_the_ID(), 'prompt_copy_count', true);
            ?>
            <div class="prompt-item-wrapper">
                <div class="body-prompt-item">
                    <h2><a href="<?php the_permalink(); ?>"><?php echo wp_kses_post($highlighted_title); ?></a></h2>
                    <?php if ($highlighted_desc) : ?>
                        <p class="prompt-description"><?php echo wp_kses_post($highlighted_desc); ?></p>
                    <?php endif; ?>
                </div>
                <div class="footer-prompt-item">
                    <p class="prompt-categories">
                        <strong><?php esc_html_e('Categorías:', 'prompts-plugin'); ?></strong>
                        <?php
                        $post_categories = get_the_terms(get_the_ID(), 'categoria-de-prompt');
                        if ($post_categories && !is_wp_error($post_categories)) {
                            $cat_names = array();
                            foreach ($post_categories as $category) {
                                $cat_link = prompts_plugin_prompt_list_merge_filter_query_args(
                                    $clean_base,
                                    array(
                                        'search_query' => $state['search_query'],
                                        'selected_categories' => array($category->slug),
                                    )
                                );
                                $cat_names[] = '<a href="' . esc_url($cat_link) . '">' . esc_html($category->name) . '</a>';
                            }
                            echo implode(', ', $cat_names);
                        }
                        ?>
                        <button type="button" class="copy-button"
                            data-html="<?php echo esc_attr($prompt_content); ?>"
                            data-text="<?php echo esc_attr($plain_content); ?>"
                            data-postid="<?php echo (int) get_the_ID(); ?>"
                            data-copies="<?php echo esc_attr($copy_count); ?>"
                            style="float: right; margin-left: 10px;">
                            <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copiar', 'prompts-plugin'); ?>
                        </button>
                    </p>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        if (!$any) {
            echo '<p>' . esc_html__('No se encontraron prompts.', 'prompts-plugin') . '</p>';
        }
    } else {
        echo '<p>' . esc_html__('No se encontraron prompts.', 'prompts-plugin') . '</p>';
    }
    return ob_get_clean();
}

/**
 * Bloque completo: opcionalmente formulario, listado y paginación.
 *
 * @param array $config {
 *   @type string $form_action_url      URL del formulario (archivo CPT o permalink de página).
 *   @type string $category_link_base   Base para enlaces de categoría en ítems.
 *   @type string $pagination_base_url  Base para paginate_links.
 *   @type int    $paged
 *   @type int    $posts_per_page
 *   @type string $ids
 *   @type string $category_slug
 *   @type bool   $show_search_form
 *   @type bool   $is_prompt_post_archive Archivo CPT `prompt` (paginación distinta).
 * }
 */
function prompts_plugin_render_prompt_list_block($config) {
    $defaults = array(
        'form_action_url' => '',
        'category_link_base' => '',
        'pagination_base_url' => '',
        'paged' => 1,
        'posts_per_page' => 10,
        'ids' => '',
        'category_slug' => '',
        'show_search_form' => true,
        'is_prompt_post_archive' => false,
    );
    $config = array_merge($defaults, $config);

    $state = prompts_plugin_prompt_list_get_state();
    $ids_only = $config['ids'] !== '';

    $query_config = array(
        'ids' => $config['ids'],
        'category_slug' => $config['category_slug'],
        'use_get_filters' => !$ids_only,
        'use_copy_order' => true,
    );

    $per_page = max(1, (int) $config['posts_per_page']);
    $paged_req = max(1, (int) $config['paged']);
    $has_search = !empty($state['normalized_search_words']);

    if ($has_search) {
        $ordered_ids = prompts_plugin_prompt_list_ordered_matching_ids($query_config, $state);
        $total = count($ordered_ids);
        $pagination_pages = max(1, (int) ceil($total / $per_page));
        $paged = min($paged_req, $pagination_pages);
        $offset = ($paged - 1) * $per_page;
        $slice = array_slice($ordered_ids, $offset, $per_page);

        if (empty($slice)) {
            $query = new WP_Query(array('post__in' => array(0)));
        } else {
            $query = new WP_Query(array(
                'post_type' => 'prompt',
                'post_status' => 'publish',
                'post__in' => $slice,
                'orderby' => 'post__in',
                'posts_per_page' => count($slice),
            ));
        }
        $search_prefiltered = true;
    } else {
        $query = prompts_plugin_prompt_list_query(array_merge(
            $query_config,
            array(
                'paged' => $paged_req,
                'posts_per_page' => $per_page,
            )
        ));
        $pagination_pages = max(1, (int) $query->max_num_pages);
        $paged = $paged_req;
        $search_prefiltered = false;
    }

    $link_base = $config['category_link_base'];
    if ($link_base === '') {
        if (is_post_type_archive('prompt')) {
            $link_base = get_post_type_archive_link('prompt');
        } elseif (is_singular()) {
            $link_base = get_permalink();
        } else {
            $link_base = home_url('/');
        }
    }
    if ($config['form_action_url'] === '') {
        $config['form_action_url'] = $link_base;
    }
    if ($config['pagination_base_url'] === '') {
        $config['pagination_base_url'] = $link_base;
    }

    ob_start();
    if ($config['show_search_form'] && !$ids_only) {
        echo prompts_plugin_prompt_list_search_form_html($config['form_action_url'], $state);
    }
    echo '<div class="prompts-list">';
    echo prompts_plugin_prompt_list_items_html($query, $state, $link_base, $search_prefiltered);
    echo '</div>';
    echo '<div class="prompts-pagination">';
    echo prompts_plugin_prompt_list_paginate_links(
        $pagination_pages,
        $paged,
        $config['pagination_base_url'],
        (bool) $config['is_prompt_post_archive'],
        $state
    );
    echo '</div>';

    return ob_get_clean();
}
