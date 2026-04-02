<?php
defined('ABSPATH') || exit;

global $wse_current_event;
if (!$wse_current_event) return;

$event_id  = $wse_current_event->ID;
$title     = get_the_title($event_id);
$end_date  = get_post_meta($event_id, '_wse_end_date', true);
$end_ts    = $end_date ? strtotime($end_date) * 1000 : 0;
$primary   = get_post_meta($event_id, '_wse_banner_color_primary', true) ?: '#1a1a2e';
$secondary = get_post_meta($event_id, '_wse_banner_color_secondary', true) ?: '#e63946';
$txt_color = get_post_meta($event_id, '_wse_banner_text_color', true) ?: '#ffffff';
$show_cd   = get_post_meta($event_id, '_wse_show_countdown', true);
$slug      = get_post_meta($event_id, '_wse_slug', true);
$url       = $slug ? home_url('/sale-event/' . $slug) : '';

wp_localize_script('wse-countdown', 'wseCountdown', [
    'endDate' => $end_ts,
    'eventId' => $event_id,
]);
?>
<style>
.wse-banner-sc-<?= $event_id ?> {
  --wse-primary: <?= esc_attr($primary) ?>;
  --wse-secondary: <?= esc_attr($secondary) ?>;
  --wse-text: <?= esc_attr($txt_color) ?>;
}
</style>
<div class="wse-event-page wse-banner-sc-<?= $event_id ?>">
  <div class="wse-banner">
    <h2 class="wse-event-title"><?= esc_html($title) ?></h2>
    <?php if ($show_cd && $end_ts): ?>
    <div class="wse-countdown" id="wse-countdown-<?= $event_id ?>" data-end="<?= esc_attr($end_ts) ?>">
      <div class="wse-cd-unit"><span class="wse-cd-days">00</span><small>Días</small></div>
      <div class="wse-cd-sep">:</div>
      <div class="wse-cd-unit"><span class="wse-cd-hours">00</span><small>Horas</small></div>
      <div class="wse-cd-sep">:</div>
      <div class="wse-cd-unit"><span class="wse-cd-minutes">00</span><small>Min</small></div>
      <div class="wse-cd-sep">:</div>
      <div class="wse-cd-unit"><span class="wse-cd-seconds">00</span><small>Seg</small></div>
    </div>
    <?php endif; ?>
    <?php if ($url): ?>
    <a href="<?= esc_url($url) ?>" class="wse-banner-btn">Ver todas las ofertas →</a>
    <?php endif; ?>
  </div>
</div>
