<?php
defined('ABSPATH') || exit;

class WSE_CPT {

    public static function init() {
        add_action('init', [self::class, 'register_post_type']);
        add_filter('use_block_editor_for_post_type', [self::class, 'disable_gutenberg'], 10, 2);
        add_filter('manage_wc_sale_event_posts_columns', [self::class, 'columns']);
        add_action('manage_wc_sale_event_posts_custom_column', [self::class, 'column_content'], 10, 2);
        add_action('wp_ajax_wse_toggle_event', [self::class, 'ajax_toggle']);
    }

    public static function register_post_type() {
        register_post_type('wc_sale_event', [
            'labels' => [
                'name'               => 'Eventos de Oferta',
                'singular_name'      => 'Evento de Oferta',
                'add_new'            => 'Añadir nuevo',
                'add_new_item'       => 'Añadir nuevo Evento',
                'edit_item'          => 'Editar Evento',
                'new_item'           => 'Nuevo Evento',
                'view_item'          => 'Ver Evento',
                'search_items'       => 'Buscar Eventos',
                'not_found'          => 'No se encontraron eventos',
                'not_found_in_trash' => 'No hay eventos en la papelera',
                'menu_name'          => 'Eventos de Oferta',
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_rest'      => false,
            'menu_icon'         => 'dashicons-tag',
            'menu_position'     => 56,
            'supports'          => ['title'],
            'has_archive'       => false,
            'capability_type'   => 'post',
        ]);
    }

    public static function disable_gutenberg($use, $post_type) {
        if ($post_type === 'wc_sale_event') return false;
        return $use;
    }

    public static function columns($cols) {
        return [
            'cb'          => $cols['cb'],
            'title'       => 'Nombre del Evento',
            'wse_dates'   => 'Fechas',
            'wse_discount'=> 'Descuento',
            'wse_items'   => 'Productos / Categorías',
            'wse_status'  => 'Estado',
        ];
    }

    public static function column_content($col, $post_id) {
        switch ($col) {
            case 'wse_dates':
                $s = get_post_meta($post_id, '_wse_start_date', true);
                $e = get_post_meta($post_id, '_wse_end_date', true);
                echo esc_html($s ? date('d/m/Y H:i', strtotime($s)) : '—');
                echo '<br><small>→ ' . esc_html($e ? date('d/m/Y H:i', strtotime($e)) : '—') . '</small>';
                break;
            case 'wse_discount':
                $type  = get_post_meta($post_id, '_wse_discount_type', true);
                $value = get_post_meta($post_id, '_wse_discount_value', true);
                $suffix = $type === 'percent' ? '%' : '$';
                echo esc_html($value . ' ' . $suffix);
                break;
            case 'wse_items':
                $prods = get_post_meta($post_id, '_wse_product_ids', true) ?: [];
                $cats  = get_post_meta($post_id, '_wse_category_ids', true) ?: [];
                echo count($prods) . ' prod. / ' . count($cats) . ' cat.';
                break;
            case 'wse_status':
                $active = get_post_meta($post_id, '_wse_is_active', true);
                $start  = get_post_meta($post_id, '_wse_start_date', true);
                $end    = get_post_meta($post_id, '_wse_end_date', true);
                $now    = time();
                if ($active) {
                    $label = 'Activo'; $color = '#00a651';
                } elseif ($start && strtotime($start) > $now) {
                    $label = 'Programado'; $color = '#2271b1';
                } elseif ($end && strtotime($end) < $now) {
                    $label = 'Finalizado'; $color = '#999';
                } else {
                    $label = 'Inactivo'; $color = '#f0a500';
                }
                $nonce = wp_create_nonce('wse_toggle_' . $post_id);
                echo '<span style="display:inline-block;padding:3px 10px;border-radius:12px;background:' . esc_attr($color) . ';color:#fff;font-size:11px;font-weight:600">' . esc_html($label) . '</span>';
                echo '<br><button class="button button-small wse-toggle-btn" style="margin-top:5px" data-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($nonce) . '">' . ($active ? 'Desactivar' : 'Activar') . '</button>';
                break;
        }
    }

    public static function ajax_toggle() {
        $post_id = absint($_POST['post_id'] ?? 0);
        check_ajax_referer('wse_toggle_' . $post_id, 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error('Sin permisos');
        $current = get_post_meta($post_id, '_wse_is_active', true);
        $new     = $current ? '0' : '1';
        update_post_meta($post_id, '_wse_is_active', $new);
        delete_transient('wse_active_events');
        wp_send_json_success(['active' => $new, 'label' => $new ? 'Activo' : 'Inactivo']);
    }
}
