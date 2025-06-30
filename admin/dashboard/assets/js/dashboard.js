/* ========================================================================
 *  PlinkLy Dashboard JS  – trend charts + sortable tables + dropdown menus
 *  (Core code after separating AI logic into ai-dashboard-analytics.js)
 * ===================================================================== */

/* ---------- Helper functions for data aggregation ---------- */
function aggregateData(data, period) {
  if (period === "daily") {
    return {
      labels: data.map(item => item.date),
      values: data.map(item => parseInt(item.total))
    };
  } else if (period === "weekly") {
    const labels = [], values = [];
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
    const grouped = {};
    data.forEach(item => {
      const d = new Date(item.date);
      const key = `${d.getFullYear()}-${d.getMonth() + 1}`;
      grouped[key] = (grouped[key] || 0) + parseInt(item.total);
    });
    const labels = Object.keys(grouped).sort();
    const values = labels.map(key => grouped[key]);
    return { labels, values };
  }
}

/* ---------- On document ready ---------- */
document.addEventListener("DOMContentLoaded", function () {

  /* ==== 1. Daily Trend Chart ==== */
  if (typeof PlinklyDashboard !== "undefined") {
    const trendData      = PlinklyDashboard.daily_counts;
    const trendChartElem = document.getElementById("dailyClickTrendChart");

    if (trendChartElem && Array.isArray(trendData)) {
      const trendCtx = trendChartElem.getContext("2d");
      let currentPeriod = "daily";
      let aggregated    = aggregateData(trendData, currentPeriod);

      const trendChart = new Chart(trendCtx, {
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
            x: { title: { display: true, text: "Date" } },
            y: { title: { display: true, text: "Clicks" }, beginAtZero: true }
          }
        }
      });

      const periodSelect = document.getElementById("chartPeriodSelect");
      if (periodSelect) {
        periodSelect.addEventListener("change", function () {
          const period  = this.value;
          const newData = aggregateData(trendData, period);
          trendChart.data.labels           = newData.labels;
          trendChart.data.datasets[0].data = newData.values;
          trendChart.data.datasets[0].label =
            `${period.charAt(0).toUpperCase() + period.slice(1)} Click Trend`;
          trendChart.update();
        });
      }
    }

    /* ==== 2. Top Posts Chart ==== */
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
                label: ctx => {
                  const full = PlinklyDashboard.top_post_titles_full;
                  return (full && full[ctx.dataIndex] ? full[ctx.dataIndex] : ctx.label) +
                         ": " + ctx.parsed;
                }
              }
            }
          }
        }
      });
    }

    /* ==== 3. Platforms Chart ==== */
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
                label: ctx => {
                  const full = PlinklyDashboard.platform_names_full;
                  return (full && full[ctx.dataIndex] ? full[ctx.dataIndex] : ctx.label) +
                         ": " + ctx.parsed;
                }
              }
            }
          }
        }
      });
    }
  }

  /* ==== 4. Sortable tables ==== */
  if (typeof jQuery !== "undefined") {
    jQuery(function ($) {
      $("table.sortable").each(function () {
        const $table   = $(this);
        const $headers = $table.find("thead th");

        $headers.each(function (index) {
          const $th = $(this).css("cursor", "pointer");

          $th.on("click", function () {
            const ascending = $th.data("order") === "asc";
            const rows      = $table.find("tbody tr").get();

            rows.sort((a, b) => {
              const A = $(a).children("td").eq(index).text().toUpperCase();
              const B = $(b).children("td").eq(index).text().toUpperCase();

              return $.isNumeric(A) && $.isNumeric(B)
                ? (ascending ? A - B : B - A)
                : (ascending ? A.localeCompare(B) : B.localeCompare(A));
            });

            $.each(rows, (_, row) => $table.children("tbody").append(row));

            // Reset arrow icons
            $headers.each((_, th) => {
              const $th2 = $(th);
              $th2.text($th2.text().replace(/[ \u25B2\u25BC]/g, ""));
              $th2.data("order", null);
            });

            // Toggle arrow icon
            $th.data("order", ascending ? "desc" : "asc")
               .text($th.text() + (ascending ? " ▼" : " ▲"));
          });
        });
      });
    });
  }

  /* ================================================================
  * 5. Dropdown menus (⋮)  → now works with all tables
  * ================================================================*/
  document.addEventListener("click", function (e) {
    // Close all dropdowns when clicking outside
    if (!e.target.closest(".top-buttons-menu-wrapper")) {
      document.querySelectorAll(".top-buttons-dropdown.show")
              .forEach(el => { el.classList.remove("show"); el.style.display = "none"; });
    }
  });

  // Event delegation to handle all present and future dropdown menus
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".top-buttons-menu-wrapper > button");
    if (!btn) return;

    e.preventDefault();
    const dropdown = btn.nextElementSibling;
    if (!dropdown || !dropdown.classList.contains("top-buttons-dropdown")) return;

    // Close other dropdowns, then toggle the current one
    document.querySelectorAll(".top-buttons-dropdown.show")
            .forEach(el => { if (el !== dropdown) { el.classList.remove("show"); el.style.display = "none"; } });

    dropdown.classList.toggle("show");
    dropdown.style.display = dropdown.classList.contains("show") ? "block" : "none";
  });

}); // DOMContentLoaded
