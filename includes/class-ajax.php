<?php
defined('ABSPATH') || exit;

class WSE_Ajax {

    public static function init() {
        add_action('wp_ajax_wse_search_products',   [self::class, 'search_products']);
        add_action('wp_ajax_wse_search_categories', [self::class, 'search_categories']);
    }

    public static function search_products() {
        check_ajax_referer('wse_search_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Sin permisos');

        $term    = sanitize_text_field($_GET['s'] ?? '');
        $exclude = array_map('absint', json_decode(stripslashes($_GET['exclude'] ?? '[]'), true) ?: []);

        $args = [
            'status'   => 'publish',
            'limit'    => 15,
            'paginate' => false,
        ];
        if (!empty($term)) $args['s'] = $term;
        if (!empty($exclude)) $args['exclude'] = $exclude;

        $products = wc_get_products($args);
        $results  = [];

        foreach ($products as $product) {
            $thumb = get_the_post_thumbnail_url($product->get_id(), [48, 48]);
            $results[] = [
                'id'    => $product->get_id(),
                'name'  => $product->get_name(),
                'price' => strip_tags(wc_price($product->get_regular_price())),
                'sku'   => $product->get_sku(),
                'thumb' => $thumb ?: '',
            ];
        }

        wp_send_json_success($results);
    }

    public static function search_categories() {
        check_ajax_referer('wse_search_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Sin permisos');

        $term    = sanitize_text_field($_GET['s'] ?? '');
        $exclude = array_map('absint', json_decode(stripslashes($_GET['exclude'] ?? '[]'), true) ?: []);

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 20,
        ];
        if (!empty($term)) $args['search'] = $term;
        if (!empty($exclude)) $args['exclude'] = $exclude;

        $terms   = get_terms($args);
        $results = [];

        foreach ($terms as $term_obj) {
            $thumb_id  = get_term_meta($term_obj->term_id, 'thumbnail_id', true);
            $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, [48, 48]) : '';
            $results[] = [
                'id'    => $term_obj->term_id,
                'name'  => $term_obj->name,
                'count' => $term_obj->count,
                'thumb' => $thumb_url ?: '',
            ];
        }

        wp_send_json_success($results);
    }
}
