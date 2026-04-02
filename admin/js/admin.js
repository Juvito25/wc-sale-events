;(function ($) {
  'use strict';

  var ajaxUrl = wseAdmin.ajaxUrl;
  var nonce   = wseAdmin.nonce;

  // ── Debounce helper ────────────────────────────────────────────────────
  function debounce(fn, ms) {
    var timer;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn.bind(this, arguments[0]), ms);
    };
  }

  // ── Generic AJAX search ───────────────────────────────────────────────
  function setupSearch(opts) {
    var $input    = $(opts.inputId);
    var $results  = $(opts.resultsId);
    var $hidden   = $(opts.hiddenId);
    var $chips    = $(opts.chipsId);
    var action    = opts.action;
    var type      = opts.type; // 'product' | 'category'

    if (!$input.length) return;

    function getSelected() {
      try { return JSON.parse($hidden.val() || '[]'); }
      catch (e) { return []; }
    }

    function setSelected(ids) {
      $hidden.val(JSON.stringify(ids));
    }

    function addChip(id, name, price, thumb) {
      var $chip = $('<span class="wse-chip" data-id="' + id + '">');
      if (thumb) {
        $chip.append('<img src="' + thumb + '" alt="">');
      } else {
        $chip.append('<span style="font-size:16px">' + (type === 'product' ? '📦' : '📁') + '</span>');
      }
      $chip.append('<span>' + name + '</span>');
      if (price) $chip.append('<span class="wse-price">' + price + '</span>');
      $chip.append('<button type="button" class="wse-chip-remove" data-type="' + type + '" data-id="' + id + '">×</button>');
      $chips.append($chip);
    }

    function removeChip(id) {
      $chips.find('[data-id="' + id + '"]').remove();
      var ids = getSelected().filter(function (i) { return i !== parseInt(id); });
      setSelected(ids);
    }

    // Remove chip on × click
    $chips.on('click', '.wse-chip-remove', function () {
      removeChip($(this).data('id'));
    });

    // Search
    var doSearch = debounce(function (e) {
      var q = $input.val().trim();
      if (q.length < 1) { $results.hide().empty(); return; }

      var exclude = getSelected();
      $results.html('<div class="wse-searching">Buscando...</div>').show();

      $.ajax({
        url: ajaxUrl,
        data: {
          action:  action,
          nonce:   nonce,
          s:       q,
          exclude: JSON.stringify(exclude),
        },
        success: function (res) {
          $results.empty();
          if (!res.success || !res.data.length) {
            $results.html('<div class="wse-no-results">Sin resultados para "' + q + '"</div>');
            return;
          }
          $.each(res.data, function (i, item) {
            var $item = $('<div class="wse-result-item" data-id="' + item.id + '">');
            if (item.thumb) {
              $item.append('<img src="' + item.thumb + '" alt="">');
            } else {
              $item.append('<div class="wse-ri-thumb-ph">' + (type === 'product' ? '📦' : '📁') + '</div>');
            }
            var sub = type === 'product'
              ? (item.sku ? 'SKU: ' + item.sku : 'Producto')
              : (item.count + ' productos');
            $item.append(
              '<div class="wse-ri-info">'
              + '<div class="wse-ri-name">' + item.name + '</div>'
              + '<div class="wse-ri-sub">' + sub + '</div>'
              + '</div>'
            );
            if (item.price) $item.append('<div class="wse-ri-price">$' + item.price + '</div>');
            $item.on('click', function () {
              var id   = parseInt($(this).data('id'));
              var ids  = getSelected();
              if (ids.indexOf(id) === -1) {
                ids.push(id);
                setSelected(ids);
                addChip(
                  id,
                  item.name,
                  item.price ? '$' + item.price : (item.count !== undefined ? item.count + ' productos' : ''),
                  item.thumb
                );
              }
              $results.hide().empty();
              $input.val('').focus();
            });
            $results.append($item);
          });
        },
        error: function () {
          $results.html('<div class="wse-no-results">Error al buscar. Intentá de nuevo.</div>');
        }
      });
    }, 300);

    $input.on('input', doSearch);

    // Close dropdown on outside click
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.wse-search-wrap').length) {
        $results.hide().empty();
      }
    });
  }

  // ── Banner color preview ───────────────────────────────────────────────
  function setupBannerPreview() {
    var $preview    = $('#wse_banner_preview');
    var $title      = $preview.find('.wse-preview-title');
    var $cdSpans    = $preview.find('.wse-preview-countdown span');
    var $postTitle  = $('#title');

    function updatePreview() {
      var primary   = $('#wse_color_primary').val();
      var secondary = $('#wse_color_secondary').val();
      var textColor = $('#wse_color_text').val();
      $preview.css('background', primary);
      $title.css('color', textColor);
      $cdSpans.css({ background: secondary, color: textColor });
      // Update hex labels
      $('#wse_color_primary_val').text(primary);
      $('#wse_color_secondary_val').text(secondary);
      $('#wse_color_text_val').text(textColor);
    }

    function updateTitle() {
      var val = $postTitle.val();
      if (val) $preview.find('.wse-preview-title').text(val);
    }

    $('#wse_color_primary, #wse_color_secondary, #wse_color_text').on('input', updatePreview);
    $postTitle.on('input', updateTitle);
    updatePreview();
  }

  // ── Badge preview ──────────────────────────────────────────────────────
  function setupBadgePreview() {
    function update() {
      var $badge = $('#wse_badge_preview_el');
      var text   = $('[name="wse_badge_text"]').val() || 'OFERTA';
      var bg     = $('[name="wse_badge_bg_color"]').val();
      var color  = $('[name="wse_badge_text_color"]').val();
      var size   = $('[name="wse_badge_size"]:checked').val() || 'md';
      var pos    = $('[name="wse_badge_position"]:checked').val() || 'tl';
      var sizes  = { sm: '11px', md: '14px', lg: '17px' };

      $badge
        .text(text)
        .css({ background: bg, color: color, fontSize: sizes[size] || '14px' })
        .removeClass('wse-badge--tl wse-badge--tr wse-badge--bl wse-badge--br')
        .addClass('wse-badge--' + pos);
    }

    $('[name="wse_badge_text"], [name="wse_badge_bg_color"], [name="wse_badge_text_color"]').on('input', update);
    $('[name="wse_badge_size"], [name="wse_badge_position"]').on('change', update);
    update();
  }

  // ── Badge toggle ───────────────────────────────────────────────────────
  function setupBadgeToggle() {
    $('[name="wse_badge_enabled"]').on('change', function () {
      var $opts  = $('.wse-badge-options');
      var $badge = $('#wse_badge_preview_el');
      if ($(this).is(':checked')) {
        $opts.show(); $badge.show();
      } else {
        $opts.hide(); $badge.hide();
      }
    });
  }

  // ── Slug live preview ─────────────────────────────────────────────────
  function setupSlug() {
    var $slug    = $('#wse_slug');
    var $preview = $('#wse_slug_preview');
    $slug.on('input', function () {
      var val = $(this).val()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9\-]/g, '');
      $(this).val(val);
      $preview.text(val || '...');
    });
  }

  // ── Discount type suffix ──────────────────────────────────────────────
  function setupDiscountSuffix() {
    $('[name="wse_discount_type"]').on('change', function () {
      var isFixed = $(this).val() === 'fixed';
      $('#wse_discount_suffix').text(isFixed ? '($)' : '(%)');
      $('#wse_suffix_badge').text(isFixed ? '$' : '%');
    });
  }

  // ── List page: toggle active via AJAX ─────────────────────────────────
  function setupListToggle() {
    $(document).on('click', '.wse-toggle-btn', function () {
      var $btn    = $(this);
      var post_id = $btn.data('id');
      var nonce   = $btn.data('nonce');
      $btn.prop('disabled', true).text('...');
      $.post(ajaxUrl, {
        action:  'wse_toggle_event',
        post_id: post_id,
        nonce:   nonce,
      }, function (res) {
        if (res.success) {
          $btn.text(res.data.active === '1' ? 'Desactivar' : 'Activar');
          var $chip = $btn.prev('span');
          if (res.data.active === '1') {
            $chip.text('Activo').css('background', '#00a651');
          } else {
            $chip.text('Inactivo').css('background', '#f0a500');
          }
        }
        $btn.prop('disabled', false);
      });
    });
  }

  // ── Init ──────────────────────────────────────────────────────────────
  $(function () {
    setupSearch({
      inputId:   '#wse_product_search',
      resultsId: '#wse_product_results',
      hiddenId:  '#wse_product_ids',
      chipsId:   '#wse_product_chips',
      action:    'wse_search_products',
      type:      'product',
    });

    setupSearch({
      inputId:   '#wse_category_search',
      resultsId: '#wse_category_results',
      hiddenId:  '#wse_category_ids',
      chipsId:   '#wse_category_chips',
      action:    'wse_search_categories',
      type:      'category',
    });

    setupBannerPreview();
    setupBadgePreview();
    setupBadgeToggle();
    setupSlug();
    setupDiscountSuffix();
    setupListToggle();
  });

}(jQuery));
