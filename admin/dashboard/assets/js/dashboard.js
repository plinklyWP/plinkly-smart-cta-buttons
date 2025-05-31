function aggregateData(data, period) {
  if (period === "daily") {
    return {
      labels: data.map(item => item.date),
      values: data.map(item => parseInt(item.total))
    };
  } else if (period === "weekly") {
    let labels = [], values = [];
    for (let i = 0; i < data.length; i += 7) {
      let weekSum = 0;
      for (let j = i; j < i + 7 && j < data.length; j++) {
        weekSum += parseInt(data[j].total);
      }
      labels.push("Week " + (Math.floor(i / 7) + 1));
      values.push(weekSum);
    }
    return { labels, values };
  } else if (period === "monthly") {
    let grouped = {};
    data.forEach(item => {
      let d = new Date(item.date);
      let key = `${d.getFullYear()}-${d.getMonth() + 1}`;
      grouped[key] = (grouped[key] || 0) + parseInt(item.total);
    });
    let labels = Object.keys(grouped).sort();
    let values = labels.map(key => grouped[key]);
    return { labels, values };
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // تأكد أن البيانات متاحة عبر PlinklyDashboard
  if (typeof PlinklyDashboard === "undefined") return;

  // ==== الرسم البياني الرئيسي (Daily Click Trend) ====
  const trendData = PlinklyDashboard.daily_counts;
  const trendChartElem = document.getElementById("dailyClickTrendChart");
  if (trendChartElem) {
    const trendCtx = trendChartElem.getContext("2d");
    let currentPeriod = "daily";
    let aggregated = aggregateData(trendData, currentPeriod);
    let trendChart = new Chart(trendCtx, {
      type: "line",
      data: {
        labels: aggregated.labels,
        datasets: [{
          label: "Daily Click Trend",
          data: aggregated.values,
          borderColor: "#003F91",
          backgroundColor: "rgba(0,63,145,0.3)",
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { title: { display: true, text: 'Date' } },
          y: { title: { display: true, text: 'Clicks' }, beginAtZero: true }
        }
      }
    });

    // تغيير الفترة (يومي، أسبوعي، شهري)
    const periodSelect = document.getElementById("chartPeriodSelect");
    if (periodSelect) {
      periodSelect.addEventListener("change", function(){
        const period = this.value;
        const newData = aggregateData(trendData, period);
        trendChart.data.labels = newData.labels;
        trendChart.data.datasets[0].data = newData.values;
        trendChart.data.datasets[0].label = `${period.charAt(0).toUpperCase() + period.slice(1)} Click Trend`;
        trendChart.update();
      });
    }
  }

  // ==== مخطط أفضل المقالات (Top Posts) ====
  const topPostsElem = document.getElementById("plinkly_top_posts_chart");
  if (topPostsElem) {
    new Chart(topPostsElem, {
      type: "doughnut",
      data: {
        labels: PlinklyDashboard.top_post_titles_short,
        datasets: [{
          label: "Top Posts",
          data: PlinklyDashboard.top_posts,
          backgroundColor: ["#003F91", "#0057C1", "#006EF1", "#66AAF6", "#B3D4FB"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) {
                const full = PlinklyDashboard.top_post_titles_full;
                return (full && full[ctx.dataIndex] ? full[ctx.dataIndex] : ctx.label) + ': ' + ctx.parsed;
              }
            }
          }
        }
      }
    });
  }

  // ==== مخطط المنصات (Platforms) ====
  const platformsElem = document.getElementById("plinkly_platform_chart");
  if (platformsElem) {
    new Chart(platformsElem, {
      type: "doughnut",
      data: {
        labels: PlinklyDashboard.platform_names_short,
        datasets: [{
          label: "Platforms",
          data: PlinklyDashboard.platform_counts,
          backgroundColor: ["#003F91", "#0057C1", "#006EF1", "#66AAF6", "#B3D4FB"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) {
                const full = PlinklyDashboard.platform_names_full;
                return (full && full[ctx.dataIndex] ? full[ctx.dataIndex] : ctx.label) + ': ' + ctx.parsed;
              }
            }
          }
        }
      }
    });
  }

  // ==== جداول قابلة للفرز (Sortable Tables) ====
  if (typeof jQuery !== "undefined") {
    jQuery(document).ready(function($) {
      $('table.sortable').each(function() {
        const $table = $(this);
        const $headers = $table.find('thead th');
        $headers.each(function(index) {
          const $th = $(this);
          $th.css('cursor', 'pointer').on('click', function() {
            const ascending = $th.data('order') === 'asc';
            const rows = $table.find('tbody tr').get();
            rows.sort((a, b) => {
              const A = $(a).children('td').eq(index).text().toUpperCase();
              const B = $(b).children('td').eq(index).text().toUpperCase();
              return $.isNumeric(A) && $.isNumeric(B) ? (ascending ? A - B : B - A) : (ascending ? A.localeCompare(B) : B.localeCompare(A));
            });
            $.each(rows, (i, row) => $table.children('tbody').append(row));
            $headers.each((i, th) => {
              const $th2 = $(th);
              $th2.text($th2.text().replace(/[ \u25B2\u25BC]/g, ''));
              $th2.data('order', null);
            });
            $th.data('order', ascending ? 'desc' : 'asc').text($th.text() + (ascending ? ' ▼' : ' ▲'));
          });
        });
      });
    });
  }
});




// Top Clicked Buttons Menu (dropdown open/close behavior)
document.addEventListener('DOMContentLoaded', function() {
  const menuWrapper = document.querySelector('.top-buttons-menu-wrapper');
  const menu = document.getElementById('topButtonsMenu');
  if (menuWrapper && menu) {
    // Toggle menu by button
    menuWrapper.querySelector('button').addEventListener('click', function(e) {
      menu.classList.toggle('show');
      menu.style.display = menu.classList.contains('show') ? 'block' : 'none';
      e.stopPropagation();
    });
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.top-buttons-menu-wrapper')) {
        menu.classList.remove('show');
        menu.style.display = 'none';
      }
    });
  }
});

// Detailed Data Menu (dropdown open/close behavior)
document.addEventListener('DOMContentLoaded', function() {
  const detailedMenuWrapper = document.querySelector('#detailed-data-section .top-buttons-menu-wrapper');
  const detailedMenu = document.getElementById('detailedDataMenu');
  if (detailedMenuWrapper && detailedMenu) {
    // Toggle menu by button
    detailedMenuWrapper.querySelector('button').addEventListener('click', function(e) {
      detailedMenu.classList.toggle('show');
      detailedMenu.style.display = detailedMenu.classList.contains('show') ? 'block' : 'none';
      e.stopPropagation();
    });
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('#detailed-data-section .top-buttons-menu-wrapper')) {
        detailedMenu.classList.remove('show');
        detailedMenu.style.display = 'none';
      }
    });
  }
});

