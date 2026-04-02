<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Eliminar todos los eventos
$ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wc_sale_event'");
foreach ($ids as $id) {
    wp_delete_post((int) $id, true);
}

// Eliminar opciones del plugin
delete_option('wse_demo_created');
delete_option('wse_needs_flush');

// Eliminar transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wse_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wse_%'");

// Limpiar cron
wp_clear_scheduled_hook('wse_cron_activate');
wp_clear_scheduled_hook('wse_cron_deactivate');

flush_rewrite_rules();
