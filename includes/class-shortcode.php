<?php
defined('ABSPATH') || exit;

class WSE_Shortcode {

    public static function init() {
        add_action('init', [self::class, 'register']);
    }

    public static function register() {
        add_shortcode('wse_event',        [self::class, 'render_page']);
        add_shortcode('wse_sale_event',   [self::class, 'render_page']);
        add_shortcode('wse_banner',       [self::class, 'render_banner']);
        add_shortcode('wc_sale_banner',   [self::class, 'render_banner']);
        add_shortcode('wc_sale_event_page',[self::class, 'render_page']);
    }

    private static function get_event_post($atts): ?WP_Post {
        $atts = shortcode_atts(['id' => 0, 'slug' => ''], $atts);

        if (!empty($atts['id'])) {
            $post = get_post(absint($atts['id']));
        } elseif (!empty($atts['slug'])) {
            $posts = get_posts([
                'post_type'  => 'wc_sale_event',
                'numberposts'=> 1,
                'meta_query' => [['key' => '_wse_slug', 'value' => sanitize_title($atts['slug'])]],
            ]);
            $post = $posts[0] ?? null;
        } else {
            return null;
        }

        if (!$post || $post->post_type !== 'wc_sale_event') return null;
        if (!get_post_meta($post->ID, '_wse_is_active', true)) return null;
        return $post;
    }

    public static function render_banner($atts): string {
        $post = self::get_event_post($atts);
        if (!$post) return '';

        wp_enqueue_style('wse-frontend', WSE_PLUGIN_URL . 'assets/css/frontend.css', [], WSE_VERSION);
        wp_enqueue_script('wse-countdown', WSE_PLUGIN_URL . 'assets/js/countdown.js', [], WSE_VERSION, true);

        ob_start();
        global $wse_current_event;
        $wse_current_event = $post;
        include WSE_PLUGIN_DIR . 'templates/banner.php';
        return ob_get_clean();
    }

    public static function render_page($atts): string {
        $post = self::get_event_post($atts);
        if (!$post) return '';

        wp_enqueue_style('wse-frontend', WSE_PLUGIN_URL . 'assets/css/frontend.css', [], WSE_VERSION);
        wp_enqueue_script('wse-countdown', WSE_PLUGIN_URL . 'assets/js/countdown.js', [], WSE_VERSION, true);

        ob_start();
        global $wse_current_event;
        $wse_current_event = $post;
        include WSE_PLUGIN_DIR . 'templates/event-page.php';
        return ob_get_clean();
    }
}
