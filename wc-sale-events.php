<?php
/**
 * Plugin Name: WC Sale Events Manager
 * Plugin URI:  https://example.com/wc-sale-events
 * Description: Gestor de eventos de ofertas para WooCommerce. Crea Black Friday, Cyber Monday y más con descuentos automáticos, countdown, badge personalizable y página pública.
 * Version:     1.0.0
 * Author:      Juvi Web
 * Author URI:  https://juviweb.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0+
 */

defined('ABSPATH') || exit;

define('WSE_VERSION',    '1.0.0');
define('WSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Verificar WooCommerce activo
function wse_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>WC Sale Events Manager</strong> requiere WooCommerce activo.</p></div>';
        });
        return false;
    }
    return true;
}

// Cargar includes
function wse_load() {
    if (!wse_check_woocommerce()) return;
    $files = [
        'class-cpt', 'class-metabox', 'class-ajax',
        'class-discount', 'class-badge', 'class-frontend',
        'class-shortcode', 'class-scheduler', 'class-conflicts',
    ];
    foreach ($files as $f) {
        require_once WSE_PLUGIN_DIR . "includes/{$f}.php";
    }
    WSE_CPT::init();
    WSE_Metabox::init();
    WSE_Ajax::init();
    WSE_Discount::init();
    WSE_Badge::init();
    WSE_Frontend::init();
    WSE_Shortcode::init();
    WSE_Scheduler::init();
}
add_action('plugins_loaded', 'wse_load');

// Activación
register_activation_hook(__FILE__, function () {
    if (!class_exists('WooCommerce')) return;
    require_once WSE_PLUGIN_DIR . 'includes/class-cpt.php';
    require_once WSE_PLUGIN_DIR . 'includes/class-frontend.php';
    WSE_CPT::register_post_type();
    WSE_Frontend::rewrite_rules();
    flush_rewrite_rules(); // Flush directo en activación (es seguro aquí)
    wse_create_demo_event();
});

// Desactivación
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('wse_cron_activate');
    wp_clear_scheduled_hook('wse_cron_deactivate');
    flush_rewrite_rules();
});

// Flush diferido
add_action('init', function () {
    if (get_option('wse_needs_flush')) {
        delete_option('wse_needs_flush');
        flush_rewrite_rules();
    }
}, 99);

// Evento demo
function wse_create_demo_event() {
    if (get_option('wse_demo_created')) return;
    $id = wp_insert_post([
        'post_title'  => 'Black Friday Demo',
        'post_type'   => 'wc_sale_event',
        'post_status' => 'publish',
    ]);
    if (is_wp_error($id)) return;
    $meta = [
        '_wse_slug'                   => 'black-friday-demo',
        '_wse_start_date'             => date('Y-m-d\TH:i', strtotime('+1 day')),
        '_wse_end_date'               => date('Y-m-d\TH:i', strtotime('+5 days')),
        '_wse_discount_type'          => 'percent',
        '_wse_discount_value'         => '20',
        '_wse_priority'               => '5',
        '_wse_conflict_rule'          => 'highest_discount',
        '_wse_banner_color_primary'   => '#1a1a2e',
        '_wse_banner_color_secondary' => '#e63946',
        '_wse_banner_text_color'      => '#ffffff',
        '_wse_is_active'              => '0',
        '_wse_show_countdown'         => '1',
        '_wse_badge_enabled'          => '1',
        '_wse_badge_text'             => 'OFERTA',
        '_wse_badge_bg_color'         => '#e63946',
        '_wse_badge_text_color'       => '#ffffff',
        '_wse_badge_size'             => 'md',
        '_wse_badge_position'         => 'tl',
        '_wse_product_ids'            => [],
        '_wse_category_ids'           => [],
    ];
    foreach ($meta as $k => $v) update_post_meta($id, $k, $v);
    update_option('wse_demo_created', true);
}
