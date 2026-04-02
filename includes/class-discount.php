<?php
defined('ABSPATH') || exit;

class WSE_Discount {

    private static array $cache = [];

    public static function init() {
        add_filter('woocommerce_product_get_price',          [self::class, 'filter_price'], 10, 2);
        add_filter('woocommerce_product_get_sale_price',     [self::class, 'filter_price'], 10, 2);
        add_filter('woocommerce_product_variation_get_price',[self::class, 'filter_price'], 10, 2);
        add_action('save_post_wc_sale_event', [self::class, 'clear_cache']);
    }

    public static function filter_price($price, $product) {
        if (!did_action('woocommerce_init')) return $price;
        if (is_admin() && !wp_doing_ajax()) return $price;

        $product_id = $product->get_id();
        $event      = self::get_event_for_product($product_id);
        if (!$event) return $price;

        $regular = floatval($product->get_regular_price());
        if ($regular <= 0) return $price;

        return WSE_Conflicts::final_price($regular, $event);
    }

    public static function get_event_for_product(int $product_id): ?array {
        if (isset(self::$cache[$product_id])) return self::$cache[$product_id];

        $active_events = self::get_all_active_events();
        if (empty($active_events)) {
            self::$cache[$product_id] = null;
            return null;
        }

        // Obtener categorías del producto
        $product_cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

        $matching = [];
        foreach ($active_events as $event) {
            $product_ids  = (array) ($event['product_ids'] ?? []);
            $category_ids = (array) ($event['category_ids'] ?? []);

            $in_products   = in_array($product_id, array_map('intval', $product_ids));
            $in_categories = !empty(array_intersect(array_map('intval', $category_ids), $product_cats));

            if ($in_products || $in_categories) {
                $matching[] = $event;
            }
        }

        if (empty($matching)) {
            self::$cache[$product_id] = null;
            return null;
        }

        $regular = floatval(get_post_meta($product_id, '_regular_price', true));
        $result  = WSE_Conflicts::resolve($matching, $regular);
        self::$cache[$product_id] = $result;
        return $result;
    }

    private static function get_all_active_events(): array {
        $cached = get_transient('wse_active_events');
        if ($cached !== false) return $cached;

        $query = new WP_Query([
            'post_type'      => 'wc_sale_event',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                ['key' => '_wse_is_active', 'value' => '1', 'compare' => '='],
            ],
            'no_found_rows'  => true,
        ]);

        $events = [];
        foreach ($query->posts as $post) {
            $events[] = [
                'id'             => $post->ID,
                'start_date'     => get_post_meta($post->ID, '_wse_start_date', true),
                'end_date'       => get_post_meta($post->ID, '_wse_end_date', true),
                'discount_type'  => get_post_meta($post->ID, '_wse_discount_type', true),
                'discount_value' => get_post_meta($post->ID, '_wse_discount_value', true),
                'priority'       => get_post_meta($post->ID, '_wse_priority', true),
                'conflict_rule'  => get_post_meta($post->ID, '_wse_conflict_rule', true),
                'product_ids'    => get_post_meta($post->ID, '_wse_product_ids', true) ?: [],
                'category_ids'   => get_post_meta($post->ID, '_wse_category_ids', true) ?: [],
                'banner_primary' => get_post_meta($post->ID, '_wse_banner_color_primary', true),
                'banner_secondary'=> get_post_meta($post->ID, '_wse_banner_color_secondary', true),
                'banner_text'    => get_post_meta($post->ID, '_wse_banner_text_color', true),
                'show_countdown' => get_post_meta($post->ID, '_wse_show_countdown', true),
                'badge_enabled'  => get_post_meta($post->ID, '_wse_badge_enabled', true),
                'badge_text'     => get_post_meta($post->ID, '_wse_badge_text', true),
                'badge_bg_color' => get_post_meta($post->ID, '_wse_badge_bg_color', true),
                'badge_text_color'=> get_post_meta($post->ID, '_wse_badge_text_color', true),
                'badge_size'     => get_post_meta($post->ID, '_wse_badge_size', true),
                'badge_position' => get_post_meta($post->ID, '_wse_badge_position', true),
                'slug'           => get_post_meta($post->ID, '_wse_slug', true),
                'title'          => $post->post_title,
            ];
        }

        set_transient('wse_active_events', $events, 5 * MINUTE_IN_SECONDS);
        return $events;
    }

    public static function clear_cache($post_id = null) {
        self::$cache = [];
        delete_transient('wse_active_events');
        if ($post_id) delete_transient('wse_event_products_' . $post_id);
    }

    // Método público para que otras clases accedan a los eventos activos
    public static function get_active_events(): array {
        return self::get_all_active_events();
    }
}
