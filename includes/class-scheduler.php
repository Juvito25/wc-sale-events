<?php
defined('ABSPATH') || exit;

class WSE_Scheduler {

    public static function init() {
        add_action('save_post_wc_sale_event', [self::class, 'schedule'], 20, 1);
        add_action('wse_cron_activate',       [self::class, 'activate']);
        add_action('wse_cron_deactivate',     [self::class, 'deactivate']);
    }

    public static function schedule(int $post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;

        $start = get_post_meta($post_id, '_wse_start_date', true);
        $end   = get_post_meta($post_id, '_wse_end_date', true);
        $now   = time();

        // Limpiar cron jobs previos
        wp_clear_scheduled_hook('wse_cron_activate',   [$post_id]);
        wp_clear_scheduled_hook('wse_cron_deactivate', [$post_id]);

        if ($start && $end) {
            $start_ts = strtotime($start);
            $end_ts   = strtotime($end);

            // Si el evento debe estar activo ahora
            if ($start_ts <= $now && $end_ts > $now) {
                update_post_meta($post_id, '_wse_is_active', '1');
                WSE_Discount::clear_cache();
            }
            // Si empieza en el futuro
            elseif ($start_ts > $now) {
                update_post_meta($post_id, '_wse_is_active', '0');
                wp_schedule_single_event($start_ts, 'wse_cron_activate', [$post_id]);
            }
            // Si ya terminó
            elseif ($end_ts <= $now) {
                update_post_meta($post_id, '_wse_is_active', '0');
                WSE_Discount::clear_cache();
            }

            // Siempre programar desactivación si el fin es futuro
            if ($end_ts > $now) {
                wp_schedule_single_event($end_ts, 'wse_cron_deactivate', [$post_id]);
            }
        }
    }

    public static function activate(int $post_id) {
        update_post_meta($post_id, '_wse_is_active', '1');
        WSE_Discount::clear_cache($post_id);
        add_action('shutdown', 'flush_rewrite_rules');
    }

    public static function deactivate(int $post_id) {
        update_post_meta($post_id, '_wse_is_active', '0');
        WSE_Discount::clear_cache($post_id);
    }
}
