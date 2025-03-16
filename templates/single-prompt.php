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
            echo '<p class="prompt-categories">Categorías: ' . implode(', ', $cat_names) . '</p>';
        }
        ?>
        <?php
        $desc = get_post_meta(get_the_ID(), 'prompt_description', true);
        if ($desc) : ?>
            <hr>
            <p class="prompt-description"><?php echo wp_kses_post($desc); ?></p>
            <p><strong>Prompt:</strong></p>
        <?php endif; ?>
        <?php
        $prompt_content = get_post_meta(get_the_ID(), 'prompt_content', true);
        $plain_content = strip_tags($prompt_content); // Texto plano para fallback
        if ($prompt_content) : ?>
            <div class="prompt-text">
                <?php echo wp_kses_post($prompt_content); ?>
                <button class="copy-button"
                        data-html="<?php echo esc_attr($prompt_content); ?>"
                        data-text="<?php echo esc_attr($plain_content); ?>"
                        style="float: right; margin-left: 10px;">
                    <span class="dashicons dashicons-clipboard"></span> Copiar
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
<script>
    jQuery(document).ready(function($) {
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
<?php
endwhile; endif;
get_footer();