<?php
// File: admin/dashboard/sections/dashboard-top-buttons.php
// Lists the best-performing buttons (clicks & CTR) + filters (date + variant).

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ── 1) Read filters ───────────────────────────────────────*/
$top_from     = isset( $_GET['top_from'] )     ? sanitize_text_field( $_GET['top_from'] )     : '';
$top_to       = isset( $_GET['top_to'] )       ? sanitize_text_field( $_GET['top_to'] )       : '';
$top_variant  = isset( $_GET['top_variant'] )  ? strtoupper( sanitize_key( $_GET['top_variant'] ) ) : '';
if ( ! in_array( $top_variant, [ 'A', 'B' ], true ) ) {
	$top_variant = ''; // All variants
}

$top_nonce = wp_create_nonce( 'plinkly_export_csv' );
?>
<div id="top-clicked-buttons-section" class="card top-clicked-buttons-card detailed-data-card">

  <!-- ─── Header ─────────────────────────────────────────-->
  <div class="top-clicked-buttons-header">
    <h3><?php esc_html_e( 'Top-Clicked Buttons', 'plinkly-smart-cta-buttons' ); ?></h3>

    <div class="top-buttons-menu-wrapper">
      <button class="button">⋮</button>
      <div id="topButtonsMenu" class="top-buttons-dropdown">

        <!-- Export CSV -->
        <a class="button export-button" href="<?php
          echo esc_url(
            admin_url(
              'admin-ajax.php?action=plinkly_export_top_buttons_csv' .
              ( $top_from    ? '&top_from='    . rawurlencode( $top_from )    : '' ) .
              ( $top_to      ? '&top_to='      . rawurlencode( $top_to )      : '' ) .
              ( $top_variant ? '&top_variant=' . rawurlencode( $top_variant ) : '' ) .
              '&nonce=' . $top_nonce
            )
          );
        ?>">
          <?php esc_html_e( 'Export as CSV', 'plinkly-smart-cta-buttons' ); ?>
        </a>

        <!-- Reset -->
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=plinkly-cta-dashboard' ) ); ?>#top-clicked-buttons-section" class="button">
          <?php esc_html_e( 'Reset Filters', 'plinkly-smart-cta-buttons' ); ?>
        </a>
      </div>
    </div>
  </div><!-- /header -->

  <!-- ─── Filters ────────────────────────────────────────-->
  <div class="detailed-date-filter">
    <div class="detailed-date-filter-controls">
      <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>#top-clicked-buttons-section" class="detailed-date-form">

        <input type="hidden" name="page" value="plinkly-cta-dashboard" />

        <label class="date-label">
          <span class="label-text"><?php esc_html_e( 'From:', 'plinkly-smart-cta-buttons' ); ?></span>
          <input type="date" name="top_from" value="<?php echo esc_attr( $top_from ); ?>" />
        </label>

        <label class="date-label">
          <span class="label-text"><?php esc_html_e( 'To:', 'plinkly-smart-cta-buttons' ); ?></span>
          <input type="date" name="top_to" value="<?php echo esc_attr( $top_to ?: gmdate( 'Y-m-d' ) ); ?>" />
        </label>

        <label class="date-label">
          <span class="label-text"><?php esc_html_e( 'Variant:', 'plinkly' ); ?></span>
          <select name="top_variant">
            <option value=""  <?php selected( $top_variant, '' );  ?>><?php esc_html_e( 'All', 'plinkly' ); ?></option>
            <option value="A" <?php selected( $top_variant, 'A' ); ?>>A</option>
            <option value="B" <?php selected( $top_variant, 'B' ); ?>>B</option>
          </select>
        </label>

        <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'plinkly-smart-cta-buttons' ); ?>" />
      </form>
    </div>
  </div><!-- /filters -->

  <!-- ─── Table or “No data” message ─────────────────────-->
  <?php if ( ! empty( $top_buttons ) ) : ?>
    <table class="widefat fixed striped sortable">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Text',        'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Link',        'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Variant',     'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Clicks',      'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'Impressions', 'plinkly-smart-cta-buttons' ); ?></th>
          <th><?php esc_html_e( 'CTR %',       'plinkly-smart-cta-buttons' ); ?></th>
        </tr>
      </thead>

      <tbody>
      <?php foreach ( $top_buttons as $btn ) : ?>
        <tr class="variant-<?php echo esc_attr( strtolower( $btn->variant ?: 'A' ) ); ?>">
          <td><?php echo esc_html( $btn->text ); ?></td>

          <td>
            <a href="<?php echo esc_url( $btn->link ); ?>" target="_blank" rel="noopener noreferrer">
              <?php
                $parts = wp_parse_url( $btn->link );
                echo esc_html( ! empty( $parts['host'] ) ? $parts['host'] : '' );
              ?>
            </a>
          </td>

          <td><?php echo esc_html( $btn->variant ?: 'A' ); ?></td>
          <td><?php echo esc_html( number_format_i18n( (int) $btn->clicks ) ); ?></td>
          <td><?php echo esc_html( number_format_i18n( (int) $btn->impressions ) ); ?></td>
          <td><?php echo esc_html( $btn->ctr ); ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- ─── Pagination ─────────────────────────────────────-->
    <?php if ( $top_total_pages > 1 ) : ?>
    <div class="tab-pagination">
      <?php
      $range = 2;
      $start = max( 1, $top_paged - $range );
      $end   = min( $top_total_pages, $top_paged + $range );

      if ( $top_paged > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => 1 ] ) ) . '#top-clicked-buttons-section">&laquo; ' . esc_html__( 'First', 'plinkly-smart-cta-buttons' ) . '</a> ';
      }

      if ( $start > 1 ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => 1 ] ) ) . '#top-clicked-buttons-section">1</a> ';
        if ( $start > 2 ) echo '<span class="dots">…</span> ';
      }

      for ( $i = $start; $i <= $end; $i++ ) {
        if ( $i === $top_paged ) {
          echo '<span class="current">' . esc_html( $i ) . '</span> ';
        } else {
          echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $i ] ) ) . '#top-clicked-buttons-section">' . esc_html( $i ) . '</a> ';
        }
      }

      if ( $end < $top_total_pages ) {
        if ( $end < $top_total_pages - 1 ) echo '<span class="dots">…</span> ';
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $top_total_pages ] ) ) . '#top-clicked-buttons-section">' . esc_html( $top_total_pages ) . '</a> ';
      }

      if ( $top_paged < $top_total_pages ) {
        echo '<a class="button" href="' . esc_url( add_query_arg( [ 'top_paged' => $top_total_pages ] ) ) . '#top-clicked-buttons-section">' . esc_html__( 'Last', 'plinkly-smart-cta-buttons' ) . ' &raquo;</a>';
      }
      ?>
    </div>
    <?php endif; ?>

  <?php else : ?>
    <p><?php esc_html_e( 'No button data found yet.', 'plinkly-smart-cta-buttons' ); ?></p>
  <?php endif; ?>

</div><!-- /card -->
