<?php
// File: admin/dashboard-top-buttons.php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$top_from = isset($_GET['top_from']) ? sanitize_text_field($_GET['top_from']) : '';
$top_to   = isset($_GET['top_to'])   ? sanitize_text_field($_GET['top_to'])   : '';
$top_nonce = wp_create_nonce('plinkly_export_csv');
?>
<div id="top-clicked-buttons-section" class="card top-clicked-buttons-card detailed-data-card">
  <div class="top-clicked-buttons-header">
    <h3><?php esc_html_e('Top Clicked Buttons', 'plinkly-smart-cta-buttons'); ?></h3>
    <div class="top-buttons-menu-wrapper">
      <button class="button">⋮</button>
      <div id="topButtonsMenu" class="top-buttons-dropdown">
        <a class="button export-button" href="<?php
          echo esc_url(
            admin_url('admin-ajax.php?action=plinkly_export_top_buttons_csv'
              . ($top_from ? '&top_from=' . rawurlencode($top_from) : '')
              . ($top_to   ? '&top_to='   . rawurlencode($top_to)   : '')
              . '&nonce=' . $top_nonce
            )
          );
        ?>">
            <?php esc_html_e('Export as CSV', 'plinkly-smart-cta-buttons'); ?>
        </a>
        <a href="<?php echo esc_url( admin_url('admin.php?page=plinkly-cta-dashboard') ); ?>#top-clicked-buttons-section" class="button">
          <?php esc_html_e('Reset Filters', 'plinkly-smart-cta-buttons'); ?>
        </a>
      </div>
    </div>
  </div>

  <div class="detailed-date-filter">
    <div class="detailed-date-filter-controls">
      <form method="get" action="<?php echo esc_url( admin_url('admin.php') ); ?>" class="detailed-date-form">
        <input type="hidden" name="page" value="plinkly-cta-dashboard" />
        <label class="date-label">
          <span class="label-text"><?php esc_html_e('From:', 'plinkly-smart-cta-buttons'); ?></span>
          <input type="date" name="top_from" value="<?php echo esc_attr($top_from); ?>" />
        </label>
        <label class="date-label">
          <span class="label-text"><?php esc_html_e('To:', 'plinkly-smart-cta-buttons'); ?></span>
          <input type="date" name="top_to" value="<?php echo esc_attr($top_to ?: date('Y-m-d')); ?>" />
        </label>
        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'plinkly-smart-cta-buttons'); ?>" />
      </form>
    </div>
  </div>

  <table class="widefat fixed striped sortable">
    <thead><tr>
      <th><?php esc_html_e('Text', 'plinkly-smart-cta-buttons'); ?></th>
      <th><?php esc_html_e('Link', 'plinkly-smart-cta-buttons'); ?></th>
      <th><?php esc_html_e('Clicks', 'plinkly-smart-cta-buttons'); ?></th>
      <th><?php esc_html_e('Impressions', 'plinkly-smart-cta-buttons'); ?></th>
      <th><?php esc_html_e('CTR', 'plinkly-smart-cta-buttons'); ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ( $top_buttons as $btn ) : ?>
      <tr>
        <td><?php echo esc_html( $btn->text ); ?></td>
        <td>
          <a href="<?php echo esc_url( $btn->link ); ?>" target="_blank">
            <?php echo esc_html( parse_url($btn->link, PHP_URL_HOST) ); ?>
          </a>
        </td>
        <td><?php echo esc_html( number_format_i18n( (int) $btn->clicks ) ); ?></td>
        <td><?php echo esc_html( number_format_i18n( (int) $btn->impressions ) ); ?></td>
        <td><?php echo esc_html( $btn->ctr ); ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ( $top_total_pages > 1 ) : ?>
    <div class="tab-pagination">
      <?php
      $range = 2;
      $start = max( 1, $top_paged - $range );
      $end   = min( $top_total_pages, $top_paged + $range );

      if ( $top_paged > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => 1 ] ) ) . '#top-clicked-buttons-section">&laquo; ' . esc_html__('First', 'plinkly-smart-cta-buttons') . '</a> ';
      }

      if ( $start > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => 1 ] ) ) . '#top-clicked-buttons-section">1</a> ';
        if ( $start > 2 ) echo '<span class="dots">...</span> ';
      }

      for ( $i = $start; $i <= $end; $i++ ) {
        if ( $i === $top_paged ) {
          echo '<span class="current">' . esc_html($i) . '</span> ';
        } else {
          echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $i ] ) ) . '#top-clicked-buttons-section">' . esc_html($i) . '</a> ';
        }
      }

      if ( $end < $top_total_pages ) {
        if ( $end < $top_total_pages - 1 ) echo '<span class="dots">...</span> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $top_total_pages ] ) ) . '#top-clicked-buttons-section">' . esc_html($top_total_pages) . '</a> ';
      }

      if ( $top_paged < $top_total_pages ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $top_total_pages ] ) ) . '#top-clicked-buttons-section">' . esc_html__('Last', 'plinkly-smart-cta-buttons') . ' &raquo;</a>';
      }
      ?>
    </div>
  <?php endif; ?>
</div>
