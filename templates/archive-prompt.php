<?php
get_header();
?>
<div class="prompts-container">
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
    <script>
        jQuery(document).ready(function($) {
            $(".category-select").select2({
                placeholder: "Selecciona Categorías...",
                allowClear: true
            });
        });
    </script>
    <div class="prompts-list">
        <?php
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type' => 'prompt',
            'posts_per_page' => 10,
            'paged' => $paged,
            'post_status' => 'publish',
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
        $search_query = isset($_GET['prompt_search']) ? sanitize_text_field($_GET['prompt_search']) : '';
        $search_words = array_filter(explode(' ', strtolower(trim($search_query))));
        $normalized_search_words = array_map('remove_accents', $search_words);
        
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
                    ?>
                    <div class="prompt-item">
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
                                        $cat_link = add_query_arg('categoria', $category->slug, remove_query_arg('prompt_search'));
                                        $cat_names[] = '<a href="' . esc_url($cat_link) . '">' . esc_html($category->name) . '</a>';
                                    }
                                    echo implode(', ', $cat_names);
                                }
                                ?>
                                <button class="copy-button" 
                                        data-html="<?php echo esc_attr($prompt_content); ?>" 
                                        data-text="<?php echo esc_attr($plain_content); ?>" 
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
        ));
        ?>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('.clear-filters').on('click', function(e) {
                e.preventDefault();
                var $form = $(this).closest('form');
                $form.find('input[name="prompt_search"]').val('');
                $form.find('.category-select').val(null).trigger('change');
                $form.submit();
            });
            $('.copy-button').on('click', async function(e) {
                e.preventDefault();
                var htmlContent = $(this).data('html');
                var textContent = $(this).data('text');
                try {
                    await navigator.clipboard.write([
                        new ClipboardItem({
                            'text/plain': new Blob([textContent], { type: 'text/plain' }),
                            'text/html': new Blob([htmlContent], { type: 'text/html' })
                        })
                    ]);
                    alert('¡Prompt copiado con formato al portapapeles!');
                } catch (err) {
                    console.error('Error al copiar: ', err);
                    navigator.clipboard.writeText(textContent).then(function() {
                        alert('Copiado como texto plano debido a un error.');
                    });
                }
            });
        });
    </script>
</div>
<?php
get_footer();