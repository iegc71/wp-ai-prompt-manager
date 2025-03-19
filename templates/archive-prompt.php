<?php
get_header();
?>
<div class="prompts-archive container">
    <form class="prompt-search-form" method="get" action="">
        <input type="text" name="prompt_search" value="<?php echo esc_attr(isset($_GET['prompt_search']) ? $_GET['prompt_search'] : ''); ?>" placeholder="Buscar prompts por título o descripción...">
        <?php
        $categories = get_terms(array('taxonomy' => 'categoria-de-prompt', 'hide_empty' => false));
        $selected_categories = isset($_GET['categorias']) ? (array) $_GET['categorias'] : array();
        $selected_categories = array_map('sanitize_text_field', $selected_categories);
        if (!is_wp_error($categories) && !empty($categories)) {
            echo '<select name="categorias[]" multiple class="category-select">';
            foreach ($categories as $category) {
                $selected = in_array($category->slug, $selected_categories) ? ' selected' : '';
                echo '<option value="' . esc_attr($category->slug) . '"' . $selected . '>' . esc_html($category->name) . '</option>';
            }
            echo '</select>';
        }
        ?>
        <div class="botones">
            <button type="submit">
                <span class="dashicons dashicons-filter filter-button"></span>
            </button>
            <button type="button" class="clear-filters" title="Limpiar filtros">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </form>
    <div class="prompts-list">
        <?php
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type' => 'prompt',
            'posts_per_page' => 10,
            'paged' => $paged,
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );
        if (!empty($selected_categories) && !in_array('', $selected_categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'categoria-de-prompt',
                    'field' => 'slug',
                    'terms' => $selected_categories,
                    'operator' => 'IN',
                ),
            );
        }
        $args['meta_key'] = 'prompt_copy_count';
        $search_query = isset($_GET['prompt_search']) ? sanitize_text_field($_GET['prompt_search']) : '';
        $search_words = array_filter(explode(' ', strtolower(trim($search_query))));
        $normalized_search_words = array_map('remove_accents', $search_words);

        // Custom orderby callback function
        add_filter('posts_clauses', 'custom_order_by_copy_count_and_category', 10, 2);
        function custom_order_by_copy_count_and_category($clauses, $query) {
            if ($query->is_main_query() && $query->get('post_type') == 'prompt') {
                global $wpdb;
                $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS tr ON {$wpdb->posts}.ID = tr.object_id";
                $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
                $clauses['join'] .= " LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id";
                $clauses['orderby'] = "CAST(pm.meta_value AS UNSIGNED) DESC, t.name ASC";
                $clauses['groupby'] = "{$wpdb->posts}.ID";
                $clauses['where'] .= " AND tt.taxonomy = 'categoria-de-prompt'";
                // Remove the filter to avoid affecting other queries
                remove_filter('posts_clauses', 'custom_order_by_copy_count_and_category', 10);
            }
            return $clauses;
        }

        $prompts_query = new WP_Query($args);
        if ($prompts_query->have_posts()) {
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
                if ($matches_all) {
                    $highlighted_title = get_the_title();
                    $highlighted_desc = get_post_meta(get_the_ID(), 'prompt_description', true) ?: '';
                    foreach ($search_words as $word) {
                        $highlighted_title = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<span class="highlight">$1</span>', $highlighted_title);
                        $highlighted_desc = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<span class="highlight">$1</span>', $highlighted_desc);
                    }
                    $prompt_content = get_post_meta(get_the_ID(), 'prompt_content', true);
                    $plain_content = strip_tags($prompt_content);
                    $copy_count = get_post_meta(get_the_ID(), 'prompt_copy_count', true); // Get initial copy count
                    ?>
                    <div class="prompt-item-wrapper">
                        <div class="body-prompt-item">
                            <h2><a href="<?php the_permalink(); ?>"><?php echo $highlighted_title; ?></a></h2>
                            <?php if ($highlighted_desc) : ?>
                                <p class="prompt-description"><?php echo $highlighted_desc; ?></p>
                            <?php endif; ?>
                            <!--<?php if ($prompt_content) : ?>-->
                            <!--    <p class="prompt-text"><strong>Prompt:</strong> <?php echo wp_kses_post($prompt_content); ?></p>-->
                            <!--<?php endif; ?>-->
                        </div>
                        <div class="footer-prompt-item">
                            <p class="prompt-categories">
                                <strong>Categorías:</strong>
                                <?php
                                $categories = get_the_terms(get_the_ID(), 'categoria-de-prompt');
                                if ($categories && !is_wp_error($categories)) {
                                    $cat_names = array();
                                    foreach ($categories as $category) {
                                        //$cat_link = add_query_arg('categoria', $category->slug, remove_query_arg('prompt_search'));
                                        $cat_link = add_query_arg(array(
                                            'categorias' => array($category->slug)
                                        ), remove_query_arg(array('prompt_search', 'paged')));

                                        $cat_names[] = '<a href="' . esc_url($cat_link) . '">' . esc_html($category->name) . '</a>';
                                    }
                                    echo implode(', ', $cat_names);
                                }
                                ?>
                                <button class="copy-button"
                                    data-html="<?php echo esc_attr($prompt_content); ?>"
                                    data-text="<?php echo esc_attr($plain_content); ?>"
                                    data-postid="<?php echo get_the_ID(); ?>"
                                    data-copies="<?php echo esc_attr($copy_count); ?>"
                                    style="float: right; margin-left: 10px;">
                                    <span class="dashicons dashicons-clipboard"></span> Copiar
                                </button>
                            </p>
                        </div>
                    </div>
                    <?php
                }
            }
            wp_reset_postdata();
        } else {
            echo '<p>No se encontraron prompts.</p>';
            echo '<p>Debug: Búsqueda - ' . esc_html($search_query) . ' | Categorías - ' . esc_html(implode(', ', $selected_categories)) . ' | Encontrados - 0</p>';
        }
        ?>
    </div>
    <!-- Movemos la paginación fuera de prompts-list -->
    <div class="prompts-pagination">
    <?php
    echo paginate_links(array(
        'total' => $prompts_query->max_num_pages,
        'current' => $paged,
        'prev_text' => __('« Anterior'),
        'next_text' => __('Siguiente »'),
        'format' => '?paged=%#%',
        'base' => esc_url(str_replace(999999999, '%#%', get_pagenum_link(999999999))),
    ));
    ?>
    </div>
</div>
<?php
get_footer();
