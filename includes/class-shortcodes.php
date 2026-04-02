<?php
if (!defined('ABSPATH')) {
    exit;
}

class Prompts_Shortcodes {
    public function __construct() {
        add_shortcode('prompts_list', array($this, 'render_prompts_list'));
    }

    public function render_prompts_list($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
            'category'       => '',
            'ids'            => '',
        ), $atts);

        $paged = (get_query_var('paged')) ? (int) get_query_var('paged') : ((get_query_var('page')) ? (int) get_query_var('page') : 1);
        $page_url = get_permalink();
        if (!$page_url) {
            $page_url = home_url('/');
        }

        $inner = prompts_plugin_render_prompt_list_block(
            array(
                'form_action_url' => $page_url,
                'category_link_base' => $page_url,
                'pagination_base_url' => $page_url,
                'paged' => $paged,
                'posts_per_page' => (int) $atts['posts_per_page'],
                'ids' => $atts['ids'],
                'category_slug' => $atts['category'],
                'is_prompt_post_archive' => false,
            )
        );

        return '<div class="prompts-archive container">' . $inner . '</div>';
    }
}