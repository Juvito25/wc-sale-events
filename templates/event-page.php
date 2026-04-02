<?php
defined('ABSPATH') || exit;

global $wse_current_event;
if (!$wse_current_event) {
    wp_redirect(home_url(), 302); exit;
}

$event_id  = $wse_current_event->ID;
$title     = get_the_title($event_id);
$end_date  = get_post_meta($event_id, '_wse_end_date', true);
$end_ts    = $end_date ? strtotime($end_date) * 1000 : 0;
$primary   = get_post_meta($event_id, '_wse_banner_color_primary', true) ?: '#1a1a2e';
$secondary = get_post_meta($event_id, '_wse_banner_color_secondary', true) ?: '#e63946';
$txt_color = get_post_meta($event_id, '_wse_banner_text_color', true) ?: '#ffffff';
$show_cd   = get_post_meta($event_id, '_wse_show_countdown', true);
$prod_ids  = get_post_meta($event_id, '_wse_product_ids', true) ?: [];
$cat_ids   = get_post_meta($event_id, '_wse_category_ids', true) ?: [];

wp_enqueue_style('wse-frontend', WSE_PLUGIN_URL . 'assets/css/frontend.css', [], WSE_VERSION);
wp_enqueue_script('wse-countdown', WSE_PLUGIN_URL . 'assets/js/countdown.js', [], WSE_VERSION, true);
wp_localize_script('wse-countdown', 'wseCountdown', [
    'endDate'  => $end_ts,
    'eventId'  => $event_id,
]);

get_header();
?>
<style>
  .wse-event-page {
    --wse-primary:   <?= esc_attr($primary) ?>;
    --wse-secondary: <?= esc_attr($secondary) ?>;
    --wse-text:      <?= esc_attr($txt_color) ?>;
  }
</style>

<div class="wse-event-page">

  <!-- BANNER -->
  <div class="wse-banner">
    <h1 class="wse-event-title"><?= esc_html($title) ?></h1>

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
  </div>

  <!-- PRODUCTOS -->
  <div class="wse-products-section" id="wse-products-<?= $event_id ?>">
    <?php
    $has_products = !empty($prod_ids);
    $has_cats     = !empty($cat_ids);

    if (!$has_products && !$has_cats) {
        echo '<p class="wse-no-products">Este evento no tiene productos asignados aún.</p>';
        get_footer();
        return;
    }

    $query_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ];

    if ($has_products && $has_cats) {
        // Ambos: productos individuales + productos de categorías
        // Usamos tax_query con OR y luego filtramos los IDs también
        $query_args['tax_query'] = [
            'relation' => 'OR',
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array_map('intval', (array) $cat_ids),
            ],
        ];
        // WP_Query no soporta post__in + tax_query combinados con OR fácilmente,
        // así que hacemos dos queries y unimos los IDs
        $cat_query = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => array_map('intval', (array) $cat_ids),
            ]],
        ]);
        $all_ids = array_unique(array_merge(
            array_map('intval', (array) $prod_ids),
            $cat_query->posts
        ));
        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__in'       => $all_ids,
            'orderby'        => 'post__in',
        ];
    } elseif ($has_products) {
        $query_args['post__in'] = array_map('intval', (array) $prod_ids);
        $query_args['orderby']  = 'post__in';
    } elseif ($has_cats) {
        $query_args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => array_map('intval', (array) $cat_ids),
        ]];
    }

    $loop = new WP_Query($query_args);

    if ($loop->have_posts()):
        echo '<ul class="wse-products-grid products columns-4">';
        while ($loop->have_posts()): $loop->the_post();
            global $product;
            wc_get_template_part('content', 'product');
        endwhile;
        echo '</ul>';
        wp_reset_postdata();
    else:
        echo '<p class="wse-no-products">No se encontraron productos para este evento.</p>';
    endif;
    ?>
  </div>

</div>

<?php get_footer(); ?>
