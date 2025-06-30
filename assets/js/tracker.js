/**
 * PlinkLy – Click-Tracker
 * يسجِّل نقرة كل زر CTA ويرسلها إلى Ajax-handler
 * File: assets/js/tracker.js   (v1.1 – zone support)
 */

jQuery(document).ready(function ($) {

  // التفويض يلتقط الأزرار المُحمَّلة ديناميكيًا أيضًا
  $(document).on('click', '.affiliate-button', function () {

    const $btn = $(this);

    /* منع التكرار إذا ضغط الزائر بسرعة */
    if ($btn.data('ply-click-sent')) return;
    $btn.data('ply-click-sent', true);

    /* البيانات الأساسية */
    const text    = $btn.text().trim();
    const link    = $btn.attr('href');
    const variant = $btn.data('variant') || 'A';

    /* استنتاج المنصّة من الدومين */
    let platform = 'general';
    try {
      const url   = new URL(link, window.location.href);
      let host    = url.hostname.replace(/^www\./i, '');
      const parts = host.split('.');
      platform    = (parts.length > 2) ? parts.slice(-2).join('.') : host;
    } catch (e) {
      platform = 'general';
    }

    /* تحضير الـ Payload */
    const payload = {
      action:   'plinkly_track_click',
      post_id:  PlinkLyTracker.post_id,
      nonce:    PlinkLyTracker.nonce,
      text:     text,
      link:     link,
      platform: platform,
      variant:  variant
    };

    /* ───────── Zone support (Placement Tracker) ───────── */
    if (window.PlinklyPlacementTrackerActive && $btn[0]) {

      const rect = $btn[0].getBoundingClientRect();
      const docH = document.documentElement.scrollHeight;
      const docW = document.documentElement.clientWidth;

      /* 1) نسبة الارتفاع والعرض */
      const topPct  = ((rect.top + window.scrollY) / docH) * 100;
      const leftPct = (rect.left / docW) * 100;

      /* 2) تصنيف رأسي */
      const vPos = topPct < 25 ? 'Top'
                : topPct < 50 ? 'Upper-middle'
                : topPct < 75 ? 'Lower-middle'
                : 'Bottom';

      /* 3) تصنيف أفقي */
      const hPos = leftPct < 33 ? 'Left'
                : leftPct < 66 ? 'Center'
                : 'Right';

      payload.zone     = `${vPos}-${hPos}`;               // مثال: "Upper-middle-Center"
      payload.position = `top:${Math.round(rect.top)}|left:${Math.round(rect.left)}`; // احتياطي
    }

    /* إرسال الطلب إلى الخادم */
    $.post(PlinkLyTracker.ajax_url, payload);

  });

});
