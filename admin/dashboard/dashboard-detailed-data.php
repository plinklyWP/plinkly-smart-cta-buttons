<?php
// File: admin/dashboard-detailed-data.php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$filter_from = isset($_GET['filter_from']) ? sanitize_text_field($_GET['filter_from']) : '';
$filter_to   = isset($_GET['filter_to'])   ? sanitize_text_field($_GET['filter_to'])   : '';
$nonce       = wp_create_nonce('plinkly_export_csv');
?>
<div id="detailed-data-section">
  <div class="card detailed-data-card">
    <div class="detailed-data-header">
      <h3><?php esc_html_e('Detailed Data', 'plinkly-smart-cta-buttons'); ?></h3>
      <div class="top-buttons-menu-wrapper">
        <button class="button">⋮</button>
        <div id="detailedDataMenu" class="top-buttons-dropdown">
          <a class="button export-button" href="<?php
            echo esc_url(
              admin_url(
                'admin-ajax.php?action=plinkly_export_csv'
                . ($filter_from ? '&filter_from=' . rawurlencode($filter_from) : '')
                . ($filter_to   ? '&filter_to='   . rawurlencode($filter_to)   : '')
                . '&nonce=' . $nonce
              )
            );
          ?>">
            <?php esc_html_e('Export as CSV', 'plinkly-smart-cta-buttons'); ?>
          </a>
          <a href="<?php echo esc_url( admin_url('admin.php?page=plinkly-cta-dashboard') ); ?>#detailed-data-section" class="button">
            <?php esc_html_e('Reset Filters', 'plinkly-smart-cta-buttons'); ?>
          </a>
        </div>
      </div>
    </div>

    <div class="detailed-date-filter">
      <div class="detailed-date-filter-controls">
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard#detailed-data-section' ) ); ?>" class="detailed-date-form">
          <input type="hidden" name="page" value="plinkly-cta-dashboard" />
          <label class="date-label">
            <span class="label-text"><?php esc_html_e('From:', 'plinkly-smart-cta-buttons'); ?></span>
            <input type="date" name="filter_from" value="<?php echo esc_attr($filter_from); ?>" />
          </label>
          <label class="date-label">
            <span class="label-text"><?php esc_html_e('To:', 'plinkly-smart-cta-buttons'); ?></span>
            <input type="date" name="filter_to" value="<?php echo esc_attr($filter_to ?: gmdate('Y-m-d')); ?>" />
          </label>
          <input type="submit" class="button" value="<?php esc_attr_e('Filter Detailed Data', 'plinkly-smart-cta-buttons'); ?>" />
        </form>
      </div>
    </div>

    <?php if ( $clicks ) : ?>
    <table class="widefat fixed striped sortable">
      <thead><tr>
        <th><?php esc_html_e('Text', 'plinkly-smart-cta-buttons'); ?></th>
        <th><?php esc_html_e('Link', 'plinkly-smart-cta-buttons'); ?></th>
        <th><?php esc_html_e('Platform', 'plinkly-smart-cta-buttons'); ?></th>
        <th><?php esc_html_e('Post', 'plinkly-smart-cta-buttons'); ?></th>
        <th><?php esc_html_e('Time', 'plinkly-smart-cta-buttons'); ?></th>
      </tr></thead>
      <tbody>
      <?php foreach ( $clicks as $click ) :
        $key = "{$click->post_id}|{$click->button_link}";
        $impressions = $impr_map[ $key ] ?? 0;
        $ctr = $impressions ? round( ( 1 / $impressions ) * 100, 2 ) : 0;
      ?>
        <tr>
          <td><?php echo esc_html( $click->button_text ); ?></td>
          <td>
            <a href="<?php echo esc_url( $click->button_link ); ?>" target="_blank">
              <?php esc_html_e('Link','plinkly-smart-cta-buttons'); ?>
            </a>
          </td>
          <td><?php echo esc_html( $click->platform ); ?></td>
          <td><?php echo esc_html( get_the_title( $click->post_id ) ); ?></td>
          <td><?php echo esc_html( $click->clicked_at ); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ( $d_total_pages > 1 ) : ?>
    <div class="tab-pagination">
      <?php
      $range = 2;
      $start = max( 1, $paged - $range );
      $end   = min( $d_total_pages, $paged + $range );

      if ( $paged > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => 1 ] ) ) . '#detailed-data-section">&laquo; ' . esc_html__('First', 'plinkly-smart-cta-buttons') . '</a> ';
      }

      if ( $start > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => 1 ] ) ) . '#detailed-data-section">1</a> ';
        if ( $start > 2 ) echo '<span class="dots">...</span> ';
      }

      for ( $i = $start; $i <= $end; $i++ ) {
        if ( $i === $paged ) {
          echo '<span class="current">' . esc_html($i) . '</span> ';
        } else {
          echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $i ] ) ) . '#detailed-data-section">' . esc_html($i) . '</a> ';
        }
      }

      if ( $end < $d_total_pages ) {
        if ( $end < $d_total_pages - 1 ) echo '<span class="dots">...</span> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $d_total_pages ] ) ) . '#detailed-data-section">' . esc_html($d_total_pages) . '</a> ';
      }

      if ( $paged < $d_total_pages ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $d_total_pages ] ) ) . '#detailed-data-section">' . esc_html__('Last', 'plinkly-smart-cta-buttons') . ' &raquo;</a>';
      }
      ?>
    </div>
    <?php endif; ?>

    <?php else : ?>
      <p><?php esc_html_e('No clicks have been recorded yet.', 'plinkly-smart-cta-buttons'); ?></p>
    <?php endif; ?>
  </div>
</div>
