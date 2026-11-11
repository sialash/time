(function () {
  'use strict';

  var body = document.body;

  /* اختلاف ساعت سرور (تهران) با ساعت مرورگر برای محاسبات دقیق */
  var skew = 0;
  var serverTime = body.getAttribute('data-server-time');
  if (serverTime) {
    var serverNow = new Date(serverTime.replace(' ', 'T') + '+03:30').getTime();
    if (!isNaN(serverNow)) skew = serverNow - Date.now();
  }

  function faNum(s) {
    return String(s).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }

  /* ─── ساعت زنده تهران ─── */
  var clock = document.getElementById('live-clock');
  if (clock) {
    function tick() {
      var now = new Date(Date.now() + skew);
      try {
        clock.textContent = new Intl.DateTimeFormat('fa-IR', {
          timeZone: 'Asia/Tehran', dateStyle: 'full', timeStyle: 'medium'
        }).format(now);
      } catch (e) {
        var t = new Date(now.getTime() + 3.5 * 3600 * 1000);
        clock.textContent = (clock.getAttribute('data-date-text') || '') + ' — ' +
          faNum(pad(t.getUTCHours()) + ':' + pad(t.getUTCMinutes()) + ':' + pad(t.getUTCSeconds()));
      }
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ─── تایمر مدت کار جاری ─── */
  var timer = document.getElementById('elapsed');
  if (timer) {
    var start = new Date(timer.getAttribute('data-start').replace(' ', 'T') + '+03:30').getTime();
    function render() {
      var s = Math.max(0, Math.floor((Date.now() + skew - start) / 1000));
      timer.textContent = faNum(pad(Math.floor(s / 3600)) + ':' + pad(Math.floor((s % 3600) / 60)) + ':' + pad(s % 60));
    }
    render();
    setInterval(render, 1000);
  }

  /* ─── تایید حذف ─── */
  var confirmForms = document.querySelectorAll('form[data-confirm]');
  Array.prototype.forEach.call(confirmForms, function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* ─── ارسال خودکار فرم انتخاب ماه ─── */
  var nav = document.getElementById('month-nav');
  if (nav) {
    var selects = nav.querySelectorAll('select');
    Array.prototype.forEach.call(selects, function (s) {
      s.addEventListener('change', function () { nav.submit(); });
    });
  }
})();
