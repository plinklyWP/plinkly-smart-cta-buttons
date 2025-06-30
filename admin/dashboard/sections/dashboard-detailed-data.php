<?php
// File: admin/dashboard/sections/dashboard-detailed-data.php
// Renders the “Detailed Data” section in the PlinkLy dashboard.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ──────────────────────────────────────────────────────────
 * 0) Read filters (date range + variant)
 * ──────────────────────────────────────────────────────── */
$filter_from    = isset( $_GET['filter_from'] )    ? sanitize_text_field( $_GET['filter_from'] )    : '';
$filter_to      = isset( $_GET['filter_to'] )      ? sanitize_text_field( $_GET['filter_to'] )      : '';
$filter_variant = isset( $_GET['filter_variant'] ) ? strtoupper( sanitize_key( $_GET['filter_variant'] ) ) : '';

if ( ! in_array( $filter_variant, [ 'A', 'B' ], true ) ) {
	$filter_variant = ''; // All variants
}

$nonce = wp_create_nonce( 'plinkly_export_csv' );
?>
<div id="detailed-data-section">
  <div class="card detailed-data-card">

    <!-- ─── Header ───────────────────────────────────────-->
    <div class="detailed-data-header">
      <h3><?php esc_html_e( 'Detailed Data', 'plinkly-smart-cta-buttons' ); ?></h3>

      <div class="top-buttons-menu-wrapper">
        <button class="button">⋮</button>
        <div id="detailedDataMenu" class="top-buttons-dropdown">

          <!-- Export CSV -->
          <a class="button export-button" href="<?php
            echo esc_url(
              admin_url(
                'admin-ajax.php?action=plinkly_export_csv' .
                ( $filter_from    ? '&filter_from='    . rawurlencode( $filter_from )    : '' ) .
                ( $filter_to      ? '&filter_to='      . rawurlencode( $filter_to )      : '' ) .
                ( $filter_variant ? '&filter_variant=' . rawurlencode( $filter_variant ) : '' ) .
                '&nonce=' . $nonce
              )
            );
          ?>">
            <?php esc_html_e( 'Export as CSV', 'plinkly-smart-cta-buttons' ); ?>
          </a>

          <!-- Reset -->
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard' ) ); ?>#detailed-data-section" class="button">
            <?php esc_html_e( 'Reset Filters', 'plinkly-smart-cta-buttons' ); ?>
          </a>
        </div>
      </div>
    </div><!-- /header -->

    <!-- ─── Filters ──────────────────────────────────────-->
    <div class="detailed-date-filter">
      <div class="detailed-date-filter-controls">
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>#detailed-data-section" class="detailed-date-form">
          <input type="hidden" name="page" value="plinkly-cta-dashboard" />

          <label class="date-label">
            <span class="label-text"><?php esc_html_e( 'From:', 'plinkly-smart-cta-buttons' ); ?></span>
            <input type="date" name="filter_from" value="<?php echo esc_attr( $filter_from ); ?>" />
          </label>

          <label class="date-label">
            <span class="label-text"><?php esc_html_e( 'To:', 'plinkly-smart-cta-buttons' ); ?></span>
            <input type="date" name="filter_to" value="<?php echo esc_attr( $filter_to ?: gmdate( 'Y-m-d' ) ); ?>" />
          </label>

          <label class="date-label">
            <span class="label-text"><?php esc_html_e( 'Variant:', 'plinkly-smart-cta-buttons' ); ?></span>
            <select name="filter_variant">
              <option value=""  <?php selected( $filter_variant, '' );  ?>><?php esc_html_e( 'All', 'plinkly' ); ?></option>
              <option value="A" <?php selected( $filter_variant, 'A' ); ?>>A</option>
              <option value="B" <?php selected( $filter_variant, 'B' ); ?>>B</option>
            </select>
          </label>

          <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'plinkly-smart-cta-buttons' ); ?>" />
        </form>
      </div>
    </div><!-- /filters -->

    <!-- ─── Data Table ───────────────────────────────────-->
    <?php if ( $clicks ) : ?>
    <table class="widefat fixed striped sortable">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Text',      'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Link',      'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Platform',  'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Variant',   'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Post',      'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Time',      'plinkly-smart-cta-buttons' ); ?></th>
        </tr>
      </thead>

      <tbody>
      <?php
      foreach ( $clicks as $click ) :
        $key        = "{$click->post_id}|{$click->button_link}";
        $impr       = $impr_map[ $key ] ?? 0;
        //$ctr        = $impr ? round( ( 1 / $impr ) * 100, 2 ) : 0;
        $row_class  = 'variant-' . strtolower( $click->variant ?: 'A' );
      ?>
        <tr class="<?php echo esc_attr( $row_class ); ?>">
          <td><?php echo esc_html( $click->button_text ); ?></td>

          <td>
            <a href="<?php echo esc_url( $click->button_link ); ?>" target="_blank" rel="noopener noreferrer">
              <?php esc_html_e( 'Open', 'plinkly-smart-cta-buttons' ); ?>
            </a>
          </td>

          <td><?php echo esc_html( $click->platform ); ?></td>
          <td><?php echo esc_html( $click->variant ?: 'A' ); ?></td>
          <td><?php echo esc_html( get_the_title( $click->post_id ) ); ?></td>
          <td><?php echo esc_html( $click->clicked_at ); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ─── Pagination ───────────────────────────────────-->
    <?php if ( $d_total_pages > 1 ) : ?>
    <div class="tab-pagination">
      <?php
      $range = 2;
      $start = max( 1, $paged - $range );
      $end   = min( $d_total_pages, $paged + $range );

      if ( $paged > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => 1 ] ) ) . '#detailed-data-section">&laquo; ' . esc_html__( 'First', 'plinkly-smart-cta-buttons' ) . '</a> ';
      }

      if ( $start > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => 1 ] ) ) . '#detailed-data-section">1</a> ';
        if ( $start > 2 ) echo '<span class="dots">…</span> ';
      }

      for ( $i = $start; $i <= $end; $i++ ) {
        if ( $i === $paged ) {
          echo '<span class="current">' . esc_html( $i ) . '</span> ';
        } else {
          echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $i ] ) ) . '#detailed-data-section">' . esc_html( $i ) . '</a> ';
        }
      }

      if ( $end < $d_total_pages ) {
        if ( $end < $d_total_pages - 1 ) echo '<span class="dots">…</span> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $d_total_pages ] ) ) . '#detailed-data-section">' . esc_html( $d_total_pages ) . '</a> ';
      }

      if ( $paged < $d_total_pages ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'paged' => $d_total_pages ] ) ) . '#detailed-data-section">' . esc_html__( 'Last', 'plinkly-smart-cta-buttons' ) . ' &raquo;</a>';
      }
      ?>
    </div>
    <?php endif; ?>

    <?php else : ?>
      <p><?php esc_html_e( 'No clicks have been recorded yet.', 'plinkly-smart-cta-buttons' ); ?></p>
    <?php endif; ?>

  </div><!-- /.card -->
</div><!-- /#detailed-data-section -->
