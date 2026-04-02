<?php
defined('ABSPATH') || exit;

class WSE_Metabox {

    public static function init() {
        add_action('add_meta_boxes',          [self::class, 'add']);
        add_action('save_post_wc_sale_event', [self::class, 'save'], 10, 1);
        add_action('admin_enqueue_scripts',   [self::class, 'enqueue']);
    }

    public static function enqueue( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'wc_sale_event' ) return;
        wp_enqueue_style('wse-admin',  WSE_PLUGIN_URL . 'admin/css/admin.css',  [], WSE_VERSION);
        wp_enqueue_script('wse-admin', WSE_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], WSE_VERSION, true);
        wp_localize_script('wse-admin', 'wseAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wse_search_nonce'),
        ]);
    }

    public static function add() {
        add_meta_box(
            'wse_config',
            '⚙️ Configuración del Evento',
            [self::class, 'render'],
            'wc_sale_event',
            'normal',
            'high'
        );
    }

    public static function render( $post ) {
        wp_nonce_field('wse_save_event_' . $post->ID, 'wse_nonce');
        $d = self::get_data($post->ID);
        $home = trailingslashit(home_url());
        ?>
        <div class="wse-metabox">

          <!-- ══ SECCIÓN 1: Identificación ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">🔗</span>
              <h3>Identificación del Evento</h3>
            </div>
            <div class="wse-section-body">
              <div class="wse-grid-2">

                <div class="wse-field">
                  <label class="wse-label">URL Amigable (Slug)</label>
                  <div class="wse-slug-box">
                    <span class="wse-slug-prefix"><?= esc_html($home) ?></span>
                    <input type="text" name="wse_slug" id="wse_slug" value="<?= esc_attr($d['slug']) ?>" placeholder="black-friday">
                  </div>
                  <span class="wse-hint">URL pública: <strong><?= esc_html($home) ?><span id="wse_slug_preview"><?= esc_html($d['slug'] ?: '...') ?></span></strong></span>
                </div>

                <div class="wse-field">
                  <label class="wse-label">Fechas del Evento</label>
                  <div class="wse-dates-row">
                    <div>
                      <div class="wse-date-label">▶ Inicio</div>
                      <input type="datetime-local" name="wse_start_date" value="<?= esc_attr($d['start_date']) ?>">
                    </div>
                    <div>
                      <div class="wse-date-label">⏹ Fin</div>
                      <input type="datetime-local" name="wse_end_date" value="<?= esc_attr($d['end_date']) ?>">
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- ══ SECCIÓN 2: Descuento ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">💰</span>
              <h3>Descuento Automático</h3>
            </div>
            <div class="wse-section-body">
              <div class="wse-grid-2">

                <div class="wse-field">
                  <label class="wse-label">Tipo de Descuento</label>
                  <div class="wse-pills">
                    <label class="wse-pill">
                      <input type="radio" name="wse_discount_type" value="percent" <?= checked($d['discount_type'], 'percent', false) ?>>
                      📊 Porcentaje (%)
                    </label>
                    <label class="wse-pill">
                      <input type="radio" name="wse_discount_type" value="fixed" <?= checked($d['discount_type'], 'fixed', false) ?>>
                      💵 Precio Fijo ($)
                    </label>
                  </div>
                </div>

                <div class="wse-field">
                  <label class="wse-label">Valor del Descuento <span id="wse_discount_suffix"><?= $d['discount_type'] === 'fixed' ? '($)' : '(%)' ?></span></label>
                  <div class="wse-input-suffix">
                    <input type="number" name="wse_discount_value" value="<?= esc_attr($d['discount_value']) ?>" min="0" step="0.01">
                    <span class="wse-suffix" id="wse_suffix_badge"><?= $d['discount_type'] === 'fixed' ? '$' : '%' ?></span>
                  </div>
                  <span class="wse-hint">El descuento se aplica en memoria — los precios originales no se modifican en la base de datos.</span>
                </div>

              </div>
            </div>
          </div>

          <!-- ══ SECCIÓN 3: Productos ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">📦</span>
              <h3>Productos del Evento</h3>
            </div>
            <div class="wse-section-body">

              <div class="wse-field" style="margin-bottom:16px">
                <label class="wse-label">Buscar y agregar productos</label>
                <div class="wse-search-wrap">
                  <input type="text" id="wse_product_search" class="wse-search-input" placeholder="Nombre o SKU del producto...">
                  <div id="wse_product_results" class="wse-results-dropdown" style="display:none"></div>
                </div>
                <input type="hidden" name="wse_product_ids" id="wse_product_ids" value="<?= esc_attr(json_encode($d['product_ids'])) ?>">
                <div id="wse_product_chips" class="wse-chips-wrap">
                  <?php foreach ($d['product_items'] as $item): ?>
                    <span class="wse-chip" data-id="<?= esc_attr($item['id']) ?>">
                      <?php if (!empty($item['thumb'])): ?>
                        <img src="<?= esc_url($item['thumb']) ?>" alt="">
                      <?php endif; ?>
                      <?= esc_html($item['name']) ?>
                      <span class="wse-price"><?= wp_kses_post($item['price']) ?></span>
                      <button type="button" class="wse-chip-remove" data-type="product" data-id="<?= esc_attr($item['id']) ?>">×</button>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="wse-field">
                <label class="wse-label">Buscar y agregar categorías</label>
                <div class="wse-search-wrap">
                  <input type="text" id="wse_category_search" class="wse-search-input" placeholder="Nombre de la categoría...">
                  <div id="wse_category_results" class="wse-results-dropdown" style="display:none"></div>
                </div>
                <input type="hidden" name="wse_category_ids" id="wse_category_ids" value="<?= esc_attr(json_encode($d['category_ids'])) ?>">
                <div id="wse_category_chips" class="wse-chips-wrap">
                  <?php foreach ($d['category_items'] as $item): ?>
                    <span class="wse-chip" data-id="<?= esc_attr($item['id']) ?>">
                      📁 <?= esc_html($item['name']) ?>
                      <span class="wse-price">(<?= esc_html($item['count']) ?> productos)</span>
                      <button type="button" class="wse-chip-remove" data-type="category" data-id="<?= esc_attr($item['id']) ?>">×</button>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>

            </div>
          </div>

          <!-- ══ SECCIÓN 4: Apariencia del Banner ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">🎨</span>
              <h3>Apariencia del Banner</h3>
            </div>
            <div class="wse-section-body">

              <div class="wse-colors-row">
                <div class="wse-color-field">
                  <label class="wse-label">Fondo Principal</label>
                  <div class="wse-color-swatch">
                    <input type="color" name="wse_banner_color_primary" id="wse_color_primary" value="<?= esc_attr($d['banner_color_primary'] ?: '#1a1a2e') ?>">
                    <span class="wse-color-name" id="wse_color_primary_val"><?= esc_html($d['banner_color_primary'] ?: '#1a1a2e') ?></span>
                  </div>
                </div>
                <div class="wse-color-field">
                  <label class="wse-label">Color de Acento</label>
                  <div class="wse-color-swatch">
                    <input type="color" name="wse_banner_color_secondary" id="wse_color_secondary" value="<?= esc_attr($d['banner_color_secondary'] ?: '#e63946') ?>">
                    <span class="wse-color-name" id="wse_color_secondary_val"><?= esc_html($d['banner_color_secondary'] ?: '#e63946') ?></span>
                  </div>
                </div>
                <div class="wse-color-field">
                  <label class="wse-label">Color de Texto</label>
                  <div class="wse-color-swatch">
                    <input type="color" name="wse_banner_text_color" id="wse_color_text" value="<?= esc_attr($d['banner_text_color'] ?: '#ffffff') ?>">
                    <span class="wse-color-name" id="wse_color_text_val"><?= esc_html($d['banner_text_color'] ?: '#ffffff') ?></span>
                  </div>
                </div>
              </div>

              <!-- Preview del banner -->
              <div class="wse-banner-preview-box">
                <div id="wse_banner_preview" class="wse-banner-preview" style="background:<?= esc_attr($d['banner_color_primary'] ?: '#1a1a2e') ?>">
                  <div class="wse-preview-title" style="color:<?= esc_attr($d['banner_text_color'] ?: '#ffffff') ?>"><?= esc_html(get_the_title($post->ID) ?: 'Nombre del Evento') ?></div>
                  <div class="wse-preview-countdown">
                    <?php foreach([['00','días'],['00','hrs'],['00','min'],['00','seg']] as [$n,$l]): ?>
                    <span style="background:<?= esc_attr($d['banner_color_secondary'] ?: '#e63946') ?>;color:<?= esc_attr($d['banner_text_color'] ?: '#ffffff') ?>"><?= $n ?><small><?= $l ?></small></span>
                    <?php endforeach; ?>
                  </div>
                </div>
                <div class="wse-preview-footer">Vista previa en tiempo real</div>
              </div>

            </div>
          </div>

          <!-- ══ SECCIÓN 5: Badge de Oferta ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">🏷️</span>
              <h3>Badge de Oferta sobre Productos</h3>
            </div>
            <div class="wse-section-body">

              <div class="wse-badge-layout">
                <div>
                  <label class="wse-toggle-label">
                    <input type="checkbox" name="wse_badge_enabled" value="1" <?= checked($d['badge_enabled'], '1', false) ?>>
                    <span class="wse-toggle-switch"></span>
                    Mostrar badge en productos
                  </label>

                  <div class="wse-badge-options" <?= $d['badge_enabled'] ? '' : 'style="display:none"' ?>>

                    <div class="wse-field">
                      <label class="wse-label">Texto del Badge</label>
                      <input type="text" name="wse_badge_text" value="<?= esc_attr($d['badge_text'] ?: 'OFERTA') ?>" placeholder="OFERTA" maxlength="20">
                    </div>

                    <div>
                      <div class="wse-label" style="margin-bottom:6px">Colores</div>
                      <div class="wse-inline-colors">
                        <label>Fondo <input type="color" name="wse_badge_bg_color" value="<?= esc_attr($d['badge_bg_color'] ?: '#e63946') ?>"></label>
                        <label>Texto <input type="color" name="wse_badge_text_color" value="<?= esc_attr($d['badge_text_color'] ?: '#ffffff') ?>"></label>
                      </div>
                    </div>

                    <div>
                      <div class="wse-label" style="margin-bottom:6px">Tamaño</div>
                      <div class="wse-size-row">
                        <?php foreach(['sm'=>'S','md'=>'M','lg'=>'L'] as $v=>$l): ?>
                          <label class="wse-size-btn"><input type="radio" name="wse_badge_size" value="<?=$v?>" <?= checked($d['badge_size']??'md',$v,false)?>><?=$l?></label>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <div>
                      <div class="wse-pos-label">Posición sobre la imagen</div>
                      <div class="wse-pos-grid">
                        <?php foreach(['tl'=>'↖','tr'=>'↗','bl'=>'↙','br'=>'↘'] as $v=>$a): ?>
                          <label class="wse-pos-btn" title="<?=$v?>"><input type="radio" name="wse_badge_position" value="<?=$v?>" <?= checked($d['badge_position']??'tl',$v,false)?>><?=$a?></label>
                        <?php endforeach; ?>
                      </div>
                    </div>

                  </div>
                </div>

                <!-- Badge preview -->
                <div class="wse-badge-preview-wrap">
                  <div class="wse-badge-preview-img">📦</div>
                  <span id="wse_badge_preview_el"
                    class="wse-badge-preview-badge wse-badge--<?= esc_attr($d['badge_position'] ?: 'tl') ?>"
                    style="background:<?= esc_attr($d['badge_bg_color'] ?: '#e63946') ?>;color:<?= esc_attr($d['badge_text_color'] ?: '#ffffff') ?>;font-size:<?= $d['badge_size']==='lg'?'17':($d['badge_size']==='sm'?'11':'14') ?>px;<?= $d['badge_enabled'] ? '' : 'display:none' ?>">
                    <?= esc_html($d['badge_text'] ?: 'OFERTA') ?>
                  </span>
                </div>
              </div>

            </div>
          </div>

          <!-- ══ SECCIÓN 6: Opciones Avanzadas ══ -->
          <div class="wse-section">
            <div class="wse-section-header">
              <span class="wse-section-icon">⚙️</span>
              <h3>Opciones Avanzadas</h3>
            </div>
            <div class="wse-section-body">
              <div class="wse-advanced-row">

                <div>
                  <label class="wse-toggle-label">
                    <input type="checkbox" name="wse_show_countdown" value="1" <?= checked($d['show_countdown'], '1', false) ?>>
                    <span class="wse-toggle-switch"></span>
                    Mostrar Countdown Timer
                  </label>
                </div>

                <div class="wse-field">
                  <label class="wse-label">Prioridad (1–10)</label>
                  <input type="number" name="wse_priority" value="<?= esc_attr($d['priority'] ?: '5') ?>" min="1" max="10">
                </div>

                <div class="wse-field">
                  <label class="wse-label">Regla ante eventos solapados</label>
                  <select name="wse_conflict_rule">
                    <?php foreach(['highest_discount'=>'Mayor descuento','lowest_discount'=>'Menor descuento','first_event'=>'Evento más antiguo','last_event'=>'Evento más reciente'] as $v=>$l): ?>
                      <option value="<?=$v?>" <?= selected($d['conflict_rule']??'highest_discount',$v,false)?>><?= esc_html($l)?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

              </div>
            </div>
          </div>

          <!-- ══ Barra de acceso al evento ══ -->
          <?php if ($d['slug']): ?>
          <div class="wse-view-bar">
            <a href="<?= esc_url(home_url('/' . $d['slug'] . '/')) ?>" target="_blank" class="button button-primary">👁 Ver página del evento</a>
            <span class="wse-flush-hint">Si ves 404, ir a <strong>Ajustes → Enlaces permanentes</strong> y hacer clic en <strong>Guardar cambios</strong> para regenerar las URLs.</span>
          </div>
          <?php endif; ?>

        </div>
        <?php
    }

    private static function get_data( $post_id ) {
        $product_ids  = get_post_meta($post_id, '_wse_product_ids',  true) ?: [];
        $category_ids = get_post_meta($post_id, '_wse_category_ids', true) ?: [];

        $product_items = [];
        foreach ((array) $product_ids as $pid) {
            $p = wc_get_product($pid);
            if (!$p) continue;
            $product_items[] = [
                'id'    => $pid,
                'name'  => $p->get_name(),
                'price' => wc_price($p->get_regular_price()),
                'thumb' => get_the_post_thumbnail_url($pid, [40, 40]),
            ];
        }

        $category_items = [];
        foreach ((array) $category_ids as $cid) {
            $term = get_term($cid, 'product_cat');
            if (!$term || is_wp_error($term)) continue;
            $category_items[] = [
                'id'    => $cid,
                'name'  => $term->name,
                'count' => $term->count,
            ];
        }

        return [
            'slug'                   => get_post_meta($post_id, '_wse_slug', true),
            'start_date'             => get_post_meta($post_id, '_wse_start_date', true),
            'end_date'               => get_post_meta($post_id, '_wse_end_date', true),
            'discount_type'          => get_post_meta($post_id, '_wse_discount_type', true) ?: 'percent',
            'discount_value'         => get_post_meta($post_id, '_wse_discount_value', true) ?: '0',
            'priority'               => get_post_meta($post_id, '_wse_priority', true),
            'conflict_rule'          => get_post_meta($post_id, '_wse_conflict_rule', true),
            'product_ids'            => array_map('intval', (array) $product_ids),
            'category_ids'           => array_map('intval', (array) $category_ids),
            'product_items'          => $product_items,
            'category_items'         => $category_items,
            'banner_color_primary'   => get_post_meta($post_id, '_wse_banner_color_primary', true),
            'banner_color_secondary' => get_post_meta($post_id, '_wse_banner_color_secondary', true),
            'banner_text_color'      => get_post_meta($post_id, '_wse_banner_text_color', true),
            'show_countdown'         => get_post_meta($post_id, '_wse_show_countdown', true),
            'badge_enabled'          => get_post_meta($post_id, '_wse_badge_enabled', true),
            'badge_text'             => get_post_meta($post_id, '_wse_badge_text', true),
            'badge_bg_color'         => get_post_meta($post_id, '_wse_badge_bg_color', true),
            'badge_text_color'       => get_post_meta($post_id, '_wse_badge_text_color', true),
            'badge_size'             => get_post_meta($post_id, '_wse_badge_size', true),
            'badge_position'         => get_post_meta($post_id, '_wse_badge_position', true),
        ];
    }

    public static function save( $post_id ) {
        static $saving = false;
        if ($saving) return;
        $saving = true;

        if (!isset($_POST['wse_nonce']) || !wp_verify_nonce($_POST['wse_nonce'], 'wse_save_event_' . $post_id)) { $saving = false; return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { $saving = false; return; }
        if (!current_user_can('edit_post', $post_id)) { $saving = false; return; }

        $old_slug = get_post_meta($post_id, '_wse_slug', true);
        $new_slug = sanitize_title($_POST['wse_slug'] ?? '');

        $fields = [
            '_wse_slug'                    => $new_slug,
            '_wse_start_date'              => sanitize_text_field($_POST['wse_start_date'] ?? ''),
            '_wse_end_date'                => sanitize_text_field($_POST['wse_end_date'] ?? ''),
            '_wse_discount_type'           => in_array($_POST['wse_discount_type'] ?? '', ['percent','fixed']) ? $_POST['wse_discount_type'] : 'percent',
            '_wse_discount_value'          => floatval($_POST['wse_discount_value'] ?? 0),
            '_wse_priority'                => absint($_POST['wse_priority'] ?? 5),
            '_wse_conflict_rule'           => sanitize_text_field($_POST['wse_conflict_rule'] ?? 'highest_discount'),
            '_wse_banner_color_primary'    => sanitize_hex_color($_POST['wse_banner_color_primary'] ?? '#1a1a2e'),
            '_wse_banner_color_secondary'  => sanitize_hex_color($_POST['wse_banner_color_secondary'] ?? '#e63946'),
            '_wse_banner_text_color'       => sanitize_hex_color($_POST['wse_banner_text_color'] ?? '#ffffff'),
            '_wse_show_countdown'          => isset($_POST['wse_show_countdown']) ? '1' : '0',
            '_wse_badge_enabled'           => isset($_POST['wse_badge_enabled']) ? '1' : '0',
            '_wse_badge_text'              => sanitize_text_field($_POST['wse_badge_text'] ?? 'OFERTA'),
            '_wse_badge_bg_color'          => sanitize_hex_color($_POST['wse_badge_bg_color'] ?? '#e63946'),
            '_wse_badge_text_color'        => sanitize_hex_color($_POST['wse_badge_text_color'] ?? '#ffffff'),
            '_wse_badge_size'              => in_array($_POST['wse_badge_size'] ?? '', ['sm','md','lg']) ? $_POST['wse_badge_size'] : 'md',
            '_wse_badge_position'          => in_array($_POST['wse_badge_position'] ?? '', ['tl','tr','bl','br']) ? $_POST['wse_badge_position'] : 'tl',
        ];

        $product_ids  = array_map('absint', json_decode(stripslashes($_POST['wse_product_ids']  ?? '[]'), true) ?: []);
        $category_ids = array_map('absint', json_decode(stripslashes($_POST['wse_category_ids'] ?? '[]'), true) ?: []);
        $fields['_wse_product_ids']  = $product_ids;
        $fields['_wse_category_ids'] = $category_ids;

        foreach ($fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        delete_transient('wse_active_events');
        delete_transient('wse_event_products_' . $post_id);

        if ($old_slug !== $new_slug) {
            add_action('shutdown', 'flush_rewrite_rules');
        }

        $saving = false;
    }
}
