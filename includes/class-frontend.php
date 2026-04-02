<?php
defined('ABSPATH') || exit;

class WSE_Frontend {

    public static function init() {
        add_action('init',              [self::class, 'rewrite_rules'], 10);
        add_filter('query_vars',        [self::class, 'query_vars']);
        add_filter('template_include',  [self::class, 'load_template'], 99);
        add_action('wp_enqueue_scripts',[self::class, 'enqueue_assets']);
    }

    /**
     * URL directa sin prefijo: dominio.com/{slug}
     * Igual que lo muestra el campo "URL pública" en el metabox.
     */
    public static function rewrite_rules() {
        add_rewrite_tag('%wse_event_slug%', '([^&]+)');
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?wse_event_slug=$matches[1]',
            'top'
        );
    }

    public static function query_vars( $vars ) {
        $vars[] = 'wse_event_slug';
        return $vars;
    }

    public static function load_template( $template ) {
        $slug = get_query_var('wse_event_slug');
        if ( empty($slug) ) return $template;

        // Buscar evento con ese slug exacto
        $posts = get_posts([
            'post_type'   => 'wc_sale_event',
            'post_status' => 'publish',
            'numberposts' => 1,
            'meta_query'  => [[
                'key'     => '_wse_slug',
                'value'   => sanitize_title($slug),
                'compare' => '=',
            ]],
        ]);

        // Si no hay evento con ese slug dejar pasar normalmente
        // (puede ser una página, post, categoría, etc.)
        if ( empty($posts) ) return $template;

        $event_post = $posts[0];
        $is_active  = get_post_meta($event_post->ID, '_wse_is_active', true);

        // Evento inactivo → dejar pasar (no redirigir, para no romper otras URLs)
        if ( ! $is_active ) return $template;

        global $wse_current_event;
        $wse_current_event = $event_post;

        $tpl = WSE_PLUGIN_DIR . 'templates/event-page.php';
        return file_exists($tpl) ? $tpl : $template;
    }

    public static function enqueue_assets() {
        $slug = get_query_var('wse_event_slug');
        if ( empty($slug) ) return;
        wp_enqueue_style('wse-frontend',  WSE_PLUGIN_URL . 'assets/css/frontend.css', [], WSE_VERSION);
        wp_enqueue_script('wse-countdown', WSE_PLUGIN_URL . 'assets/js/countdown.js', [], WSE_VERSION, true);
    }

    // URL pública: dominio.com/{slug}/
    public static function get_event_url( int $post_id ): string {
        $slug = get_post_meta($post_id, '_wse_slug', true);
        return $slug ? home_url('/' . $slug . '/') : '';
    }
}
