# AGENTS.md - WC Sale Events Manager

## Overview

WC Sale Events Manager is a WordPress/WooCommerce plugin that manages sale events with automatic discounts, customizable badges, public pages, and countdown timers.

## Project Structure

```
wc-sale-events/
├── wc-sale-events.php          # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── includes/                   # PHP classes
│   ├── class-plugin.php       # Main plugin class
│   ├── class-cpt.php         # Custom Post Type
│   ├── class-metabox.php     # Admin metabox
│   ├── class-ajax.php         # AJAX handlers
│   ├── class-discount.php     # Discount logic
│   ├── class-conflicts.php    # Conflict resolution
│   ├── class-badge.php        # Product badges
│   ├── class-frontend.php     # Frontend display
│   ├── class-shortcode.php    # Shortcodes
│   └── class-scheduler.php     # Event scheduling
├── templates/                  # Template files
│   ├── banner.php             # Banner template
│   └── event-page.php         # Event page template
├── assets/
│   ├── css/frontend.css       # Frontend styles
│   └── js/countdown.js        # Countdown timer JS
└── admin/
    ├── css/admin.css          # Admin styles
    └── js/admin.js            # Admin JS
```

## Key Classes

- **CPT**: Registers `wc_sale_event` post type
- **Discount**: Applies price modifications to products
- **Badge**: Adds sale badges to product images
- **Conflicts**: Resolves multiple active events
- **Shortcode**: Provides `[wc_sale_banner]` and `[wc_sale_event_page]`

## Testing Commands

```bash
# Zip the plugin for testing
cd /home/juan/Proyectos
zip -r wc-sale-events.zip wc-sale-events/

# Upload to WordPress test site
# Check for PHP errors in debug.log
```

## Common Issues

1. **Parse errors**: Check for unclosed brackets in PHP files
2. **Class not found**: Verify autoloader in main plugin file
3. **Styles not loading**: Check WSE_PLUGIN_URL constant
4. **Countdown not working**: Verify jQuery is loaded