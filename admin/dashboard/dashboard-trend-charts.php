<?php
// File: admin/dashboard-trend-charts.php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// إعداد العناوين المختصرة والكاملة للمخططات
$top_post_titles_full = array_map(fn($r) => get_the_title($r->post_id), $top_posts);
$top_post_titles_short = array_map(function($r) {
  $title = get_the_title($r->post_id);
  return mb_strlen($title) > 18 ? mb_substr($title, 0, 18).'…' : $title;
}, $top_posts);

$platform_names_full = array_map(fn($r) => ucfirst($r->platform), $platform_counts);
$platform_names_short = array_map(function($r) {
  $name = ucfirst($r->platform);
  return mb_strlen($name) > 12 ? mb_substr($name, 0, 12).'…' : $name;
}, $platform_counts);
?>

<?php if ( $this_week_clicks > 0 || $last_week_clicks > 0 ) : ?>
  <div class="plinkly-smart-insight notice notice-info card">
    <?php if ( $weekly_change > 0 ) : ?>
      📈 <strong><?php esc_html_e('Clicks increased this week', 'plinkly-smart-cta-buttons'); ?>:</strong>
      <?php echo esc_html($weekly_change); ?>%
      <?php esc_html_e('compared to last week.', 'plinkly-smart-cta-buttons'); ?>
    <?php elseif ( $weekly_change < 0 ) : ?>
      📉 <strong><?php esc_html_e('Clicks decreased this week', 'plinkly-smart-cta-buttons'); ?>:</strong>
      <?php echo esc_html(abs($weekly_change)); ?>%
      <?php esc_html_e('compared to last week.', 'plinkly-smart-cta-buttons'); ?>
    <?php else : ?>
      ⏸️ <strong><?php esc_html_e('No change in clicks this week.', 'plinkly-smart-cta-buttons'); ?></strong>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="additional-cards-row">
  <div class="dashboard-column dashboard-column-left">
    <div class="card click-summary-card">
      <h3><?php esc_html_e('Click Summary', 'plinkly-smart-cta-buttons'); ?></h3>
      <table class="widefat fixed striped">
        <thead>
          <tr>
            <th><?php esc_html_e('Time', 'plinkly-smart-cta-buttons'); ?></th>
            <th><?php esc_html_e('Clicks', 'plinkly-smart-cta-buttons'); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php
        $periods = [
          esc_html__('Today', 'plinkly-smart-cta-buttons')       => ['from' => $today, 'to' => $today, 'count' => $period_counts['today']],
          esc_html__('Yesterday', 'plinkly-smart-cta-buttons')   => ['from' => date('Y-m-d', strtotime('-1 day')), 'to' => date('Y-m-d', strtotime('-1 day')), 'count' => $period_counts['yesterday']],
          esc_html__('This Month', 'plinkly-smart-cta-buttons')  => ['from' => date('Y-m-01'), 'to' => date('Y-m-t'), 'count' => $period_counts['this_month']],
          esc_html__('Last Month', 'plinkly-smart-cta-buttons')  => ['from' => date('Y-m-01', strtotime('first day of last month')), 'to' => date('Y-m-t', strtotime('last day of last month')), 'count' => $period_counts['last_month']],
          esc_html__('Last 7 Days', 'plinkly-smart-cta-buttons') => ['from' => date('Y-m-d', strtotime('-7 day')), 'to' => $today, 'count' => $period_counts['last_7']],
          esc_html__('Last 30 Days', 'plinkly-smart-cta-buttons')=> ['from' => date('Y-m-d', strtotime('-30 day')), 'to' => $today, 'count' => $period_counts['last_30']],
          esc_html__('Last 60 Days', 'plinkly-smart-cta-buttons')=> ['from' => date('Y-m-d', strtotime('-60 day')), 'to' => $today, 'count' => $period_counts['last_60']],
          esc_html__('Last 90 Days', 'plinkly-smart-cta-buttons')=> ['from' => date('Y-m-d', strtotime('-90 day')), 'to' => $today, 'count' => $period_counts['last_90']],
          esc_html__('This Year', 'plinkly-smart-cta-buttons')   => ['from' => date('Y-01-01'), 'to' => $today, 'count' => $period_counts['this_year']],
          esc_html__('Last Year', 'plinkly-smart-cta-buttons')   => ['from' => date('Y-01-01', strtotime('last year')), 'to' => date('Y-12-31', strtotime('last year')), 'count' => $period_counts['last_year']],
          esc_html__('Total', 'plinkly-smart-cta-buttons')       => ['from' => '', 'to' => '', 'count' => $total_clicks]
        ];
        foreach ($periods as $label => $data) {
          echo '<tr><td>' . esc_html($label) . '</td><td>';
          if ($data['from'] && $data['to']) {
            $url = admin_url('admin.php?page=plinkly-cta-dashboard&filter_from=' . esc_attr($data['from']) . '&filter_to=' . esc_attr($data['to']) . '#detailed-data-section');
            echo '<a href="' . esc_url($url) . '">' . esc_html(number_format_i18n($data['count'])) . '</a>';
          } else {
            echo esc_html(number_format_i18n($data['count']));
          }
          echo '</td></tr>';
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="dashboard-column dashboard-column-right">
    <div class="card daily-click-trend-card">
      <div class="daily-click-trend-header">
        <h3><?php esc_html_e('Daily Click Trend', 'plinkly-smart-cta-buttons'); ?></h3>
        <select id="chartPeriodSelect">
          <option value="daily"><?php esc_html_e('Daily','plinkly-smart-cta-buttons'); ?></option>
          <option value="weekly"><?php esc_html_e('Weekly','plinkly-smart-cta-buttons'); ?></option>
          <option value="monthly"><?php esc_html_e('Monthly','plinkly-smart-cta-buttons'); ?></option>
        </select>
      </div>
      <canvas id="dailyClickTrendChart" style="max-height:350px;"></canvas>
    </div>
    <div class="charts-card">
      <div class="top-posts card">
        <h3><?php esc_html_e('Top Posts by Clicks', 'plinkly-smart-cta-buttons'); ?></h3>
        <canvas id="plinkly_top_posts_chart" style="max-height:350px;"></canvas>
      </div>
      <div class="top-platform card">
        <h3><?php esc_html_e('Top Platforms', 'plinkly-smart-cta-buttons'); ?></h3>
        <canvas id="plinkly_platform_chart" style="max-height:350px;"></canvas>
      </div>
    </div>
  </div>
</div>
