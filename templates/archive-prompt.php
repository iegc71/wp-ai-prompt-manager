<?php
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$archive_url = get_post_type_archive_link('prompt');
if (!$archive_url) {
    $archive_url = home_url('/');
}
?>
<div class="prompts-archive container">
    <?php
    echo prompts_plugin_render_prompt_list_block(
        array(
            'form_action_url' => $archive_url,
            'category_link_base' => $archive_url,
            'pagination_base_url' => $archive_url,
            'paged' => $paged,
            'posts_per_page' => 10,
            'is_prompt_post_archive' => true,
        )
    );
    ?>
</div>
<?php
get_footer();
