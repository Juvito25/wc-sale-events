<?php
defined('ABSPATH') || exit;

class WSE_Badge {

    public static function init() {
        /**
         * Inyectamos el badge DENTRO del HTML de la imagen del producto.
         * woocommerce_product_get_image filtra el HTML completo de la imagen
         * antes de renderizarse — es el único lugar 100% seguro para
         * meter un elemento position:absolute sobre la imagen,
         * sin depender de la estructura del tema.
         */
        add_filter('woocommerce_product_get_image', [self::class, 'inject_into_image'], 10, 2);

        // Suprimir el badge nativo "Sale!" de WooCommerce cuando el plugin tiene uno activo
        add_filter('woocommerce_sale_flash', [self::class, 'suppress_sale_flash'], 10, 3);
    }

    /**
     * Envuelve el <img> en un <span position:relative>
     * e inyecta el badge dentro, sobre la imagen.
     */
    public static function inject_into_image( string $html, $product ): string {
        if ( ! $product ) return $html;

        $event = WSE_Discount::get_event_for_product( $product->get_id() );
        if ( ! $event || empty( $event['badge_enabled'] ) ) return $html;

        $badge_html = self::get_badge_html( $event );
        if ( ! $badge_html ) return $html;

        // Envolver imagen + badge en un contenedor position:relative
        return '<span class="wse-img-wrap" style="position:relative;display:block;">'
             . $html
             . $badge_html
             . '</span>';
    }

    /**
     * Genera el HTML del badge con estilos inline.
     */
    private static function get_badge_html( array $event ): string {
        $text     = esc_html( $event['badge_text'] ?: 'OFERTA' );
        $bg       = esc_attr( $event['badge_bg_color'] ?: '#e63946' );
        $color    = esc_attr( $event['badge_text_color'] ?: '#ffffff' );
        $size_map = [ 'sm' => '11px', 'md' => '14px', 'lg' => '17px' ];
        $size     = $size_map[ $event['badge_size'] ?? 'md' ] ?? '14px';
        $pos      = $event['badge_position'] ?? 'tl';

        $pos_styles = [
            'tl' => 'top:10px;left:10px;',
            'tr' => 'top:10px;right:10px;',
            'bl' => 'bottom:10px;left:10px;',
            'br' => 'bottom:10px;right:10px;',
        ];
        $pos_style = $pos_styles[ $pos ] ?? $pos_styles['tl'];

        return '<span class="wse-badge" style="'
             . 'position:absolute;'
             . $pos_style
             . 'background:' . $bg . ';'
             . 'color:' . $color . ';'
             . 'font-size:' . $size . ';'
             . 'font-weight:700;'
             . 'padding:5px 10px;'
             . 'border-radius:4px;'
             . 'line-height:1.2;'
             . 'pointer-events:none;'
             . 'z-index:9;'
             . '">' . $text . '</span>';
    }

    /**
     * Suprime el badge nativo "Sale!" de WooCommerce
     * cuando el plugin tiene un badge activo para ese producto.
     */
    public static function suppress_sale_flash( $html, $post, $product ): string {
        if ( ! $product ) return $html;
        $event = WSE_Discount::get_event_for_product( $product->get_id() );
        if ( $event && ! empty( $event['badge_enabled'] ) ) return '';
        return $html;
    }
}
