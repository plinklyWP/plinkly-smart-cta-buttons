/* ========================================================================
 * PlinkLy – AI Dashboard Analytics (stand-alone, improved rendering)
 *   • Collects dashboard data (plinklyCollectAnalyticsPayload)
 *   • Handles showing/hiding the AI panel
 *   • Sends data to ajax endpoint and renders the summary as an ordered list
 * ===================================================================== */

/* ---------- 1. Collect dashboard data ---------- */
function plinklyCollectAnalyticsPayload() {
  return {
    summary: {
      totalClicks  : PlinklyDashboard.total_clicks  || 0,
      avgClicksDay : PlinklyDashboard.avg_clicks    || 0,
      weeklyChange : PlinklyDashboard.weekly_change || 0,
      uniqueUsers  : PlinklyDashboard.unique_users  || 0
    },

    periodCounts : PlinklyDashboard.period_counts || {},

    dailyTrend   : PlinklyDashboard.daily_counts  || [],
    hourlyTrend  : PlinklyDashboard.hourly_counts || [],

    topPlatforms : {
      names  : PlinklyDashboard.platform_names_full || [],
      counts : PlinklyDashboard.platform_counts     || []
    },

    devices : {
      labels : PlinklyDashboard.device_labels || [],
      counts : PlinklyDashboard.device_counts || []
    },

    referrers : {
      labels : PlinklyDashboard.ref_labels || [],
      counts : PlinklyDashboard.ref_counts || []
    },

    topPosts : {
      titles : PlinklyDashboard.top_post_titles_full || [],
      clicks : PlinklyDashboard.top_posts            || []
    },

    abTest : {
      clicks : PlinklyDashboard.ab_clicks || [],
      views  : PlinklyDashboard.ab_views  || []
    }
  };
}

/* ---------- 2. Markdown-to-HTML helper (improved) ---------- */
function renderAiSummary(md) {
  if (!md || typeof md !== 'string') return md;

   /* Remove the intro, e.g.: "Here are three actionable insights ...:" */
  md = md.replace(/^.+?:\s*/i, '');

  /* bold → <strong> */
  let html = md.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

   /* First, try splitting by 1. 2. 3. */
  let parts = html.split(/\s*\d+\.\s+/).filter(Boolean);

  /* If no numbers are found, use <strong> as a separator */
  if (parts.length <= 1) {
    parts = html.split(/<\/strong>/).map(s => s.trim()).filter(Boolean)
                .map(s => s.startsWith('<strong>') ? s : '<strong>' + s);
  }

   /* If we have more than one item, convert them into <ol><li>… */
  if (parts.length > 1) {
    html = '<ol><li>' + parts.map(t => t.trim()).join('</li><li>') + '</li></ol>';
  }

  return html;
}

/* ---------- 3. Show/hide AI panel ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const aiBtn      = document.getElementById('show-ai-dashboard');
  const backBtn    = document.getElementById('hide-ai-dashboard');
  const aiSection  = document.getElementById('ai-dashboard-analytics-section');

  const dashboardSections = [
    '.promo-card',
    '.dashboard-summary-card',
    '.dashboard-trend-charts-card',
    '.dashboard-top-buttons-card',
    '.dashboard-ab-summary-card',
    '.detailed-data-card'
  ]
  .map(sel => document.querySelector(sel))
  .filter(Boolean);

  if (aiBtn && backBtn && aiSection) {
    aiBtn.addEventListener('click', () => {
      aiSection.style.display = '';
      backBtn.style.display   = '';
      aiBtn.style.display     = 'none';
      dashboardSections.forEach(el => (el.style.display = 'none'));
    });

    backBtn.addEventListener('click', () => {
      aiSection.style.display = 'none';
      backBtn.style.display   = 'none';
      aiBtn.style.display     = '';
      dashboardSections.forEach(el => (el.style.display = ''));
    });
  }
});

/* ---------- 4. Refresh button (send data to server) ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const refreshBtn = document.getElementById('ai-dashboard-refresh');
  const outputDiv  = document.getElementById('ai-dashboard-insights');
  if (!refreshBtn || !outputDiv) return;

  refreshBtn.addEventListener('click', () => {
    outputDiv.innerHTML = '<em>Your data is being analyzed, please wait....</em>';

    const payload  = plinklyCollectAnalyticsPayload();
    const ajaxUrl  = PlinklyDashboard.ajaxurl || window.ajaxurl || '';
    const formData = new FormData();

    formData.append('action',  'plinkly_ai_dashboard_insights');
    formData.append('nonce',   PlinklyDashboard.nonce || '');
    formData.append('metrics', JSON.stringify(payload));

    fetch(ajaxUrl, {
      method:      'POST',
      credentials: 'same-origin',
      body:        formData
    })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        outputDiv.innerHTML =
          `<span style="color:red">${data.data || 'Unknown error'}</span>`;
        return;
      }

      /* Fallback: some versions return the summary directly */
      const rawSummary = data.data?.summary ?? data.data;
      outputDiv.innerHTML =
        `<div class="ai-dashboard-summary">${renderAiSummary(rawSummary)}</div>`;
    })
    .catch(() => {
      outputDiv.innerHTML =
        '<span style="color:red">Connection failed</span>';
    });
  });
});
