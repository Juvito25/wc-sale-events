# WC Sale Events Manager

Plugin de WordPress/WooCommerce para gestionar eventos de ofertas con descuentos automáticos, badges personalizables, páginas públicas y countdown timer.

## Características

- **Custom Post Type**: Gestión de eventos de ofertas (Black Friday, Cyber Monday, etc.)
- **Descuentos automáticos**: Porcentaje o precio fijo
- **Badges personalizables**: En productos con posición y color configurable
- **Páginas públicas**: Eventos accesibles via URL con rewrite
- **Countdown timer**: Muestra tiempo restante hasta fin del evento
- **Shortcodes**:
  - `[wc_sale_banner id="123"]` - Muestra banner del evento
  - `[wc_sale_event_page id="123"]` - Muestra página del evento
- **Resolución de conflictos**: Manejo de múltiples eventos activos

## Instalación

1. Descarga el archivo ZIP del plugin
2. Ve a Plugins → Añadir nuevo → Subir plugin
3. Activa el plugin
4. Crea tu primer evento de oferta en "Eventos de Oferta"

## Uso

### Crear un evento

1. Ve a **Eventos de Oferta → Añadir nuevo**
2. Configura:
   - Fechas de inicio y fin
   - Tipo de descuento (porcentaje/fijo)
   - Valor del descuento
   - Productos o categorías afectadas
   - Personalización del badge
   - Opciones de countdown

### Shortcodes

```php
// Mostrar banner de evento por ID
[wc_sale_banner id="123"]

// Mostrar banner por slug
[wc_sale_banner slug="black-friday"]

// Mostrar página de evento
[wc_sale_event_page id="123"]
```

## Requisitos

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Licencia

GPL-2.0+