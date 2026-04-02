;(function () {
  'use strict';

  function pad(n) { return String(n).padStart(2, '0'); }

  function initCountdown() {
    var data = window.wseCountdown;
    if (!data || !data.endDate) return;

    var endDate = parseInt(data.endDate, 10);
    var eventId = data.eventId;
    var el      = document.getElementById('wse-countdown-' + eventId);
    if (!el) return;

    var daysEl    = el.querySelector('.wse-cd-days');
    var hoursEl   = el.querySelector('.wse-cd-hours');
    var minutesEl = el.querySelector('.wse-cd-minutes');
    var secondsEl = el.querySelector('.wse-cd-seconds');

    function update() {
      var diff = endDate - Date.now();

      if (diff <= 0) {
        el.innerHTML = '<div class="wse-countdown-expired">🎉 Este evento ha finalizado</div>';
        var products = document.getElementById('wse-products-' + eventId);
        if (products) products.style.display = 'none';
        return;
      }

      var days    = Math.floor(diff / 86400000);
      var hours   = Math.floor((diff % 86400000) / 3600000);
      var minutes = Math.floor((diff % 3600000) / 60000);
      var seconds = Math.floor((diff % 60000) / 1000);

      if (daysEl)    daysEl.textContent    = pad(days);
      if (hoursEl)   hoursEl.textContent   = pad(hours);
      if (minutesEl) minutesEl.textContent = pad(minutes);
      if (secondsEl) secondsEl.textContent = pad(seconds);
    }

    update();
    setInterval(update, 1000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountdown);
  } else {
    initCountdown();
  }
})();
