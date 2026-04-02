<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
?>
<div class="prompts-single container">
    <div class="prompt-item-single-prompt">
        <h1><?php the_title(); ?></h1>
        <?php
        $categories = get_the_terms(get_the_ID(), 'categoria-de-prompt');
        if ($categories && !is_wp_error($categories)) {
            $cat_names = array();
            foreach ($categories as $category) {
                $cat_link = get_term_link($category);
                $cat_names[] = '<a href="' . esc_url($cat_link) . '">' . esc_html($category->name) . '</a>';
            }
            echo '<p class="prompt-categories">' . esc_html__('Categorías:', 'prompts-plugin') . ' ' . implode(', ', $cat_names) . '</p>';
        }
        ?>
        <?php
        $desc = get_post_meta(get_the_ID(), 'prompt_description', true);
        if ($desc) : ?>
            <hr>
            <p class="prompt-description"><?php echo wp_kses_post($desc); ?></p>
            <p><strong><?php esc_html_e('Prompt:', 'prompts-plugin'); ?></strong></p>
        <?php endif; ?>
        <?php
        $prompt_content = get_post_meta(get_the_ID(), 'prompt_content', true);
        $plain_content = strip_tags($prompt_content); // Texto plano para fallback
        $copy_count = get_post_meta(get_the_ID(), 'prompt_copy_count', true); // Get initial copy count
        if ($prompt_content) : ?>
            <div class="prompt-text">
                <?php echo wp_kses_post($prompt_content); ?>
                <button class="copy-button"
                        data-html="<?php echo esc_attr($prompt_content); ?>"
                        data-text="<?php echo esc_attr($plain_content); ?>"
                        data-postid="<?php echo get_the_ID(); ?>"
                        data-copies="<?php echo esc_attr($copy_count); ?>"
                        style="float: right; margin-left: 10px;">
                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copiar', 'prompts-plugin'); ?>
                </button>
            </div>
        <?php endif; ?>
        <?php
        // Llama a la plantilla de comentarios
        if ( comments_open() || get_comments_number() ) {
            comments_template();
        }
        ?>
    </div>
</div>
<?php
endwhile; endif;
get_footer();