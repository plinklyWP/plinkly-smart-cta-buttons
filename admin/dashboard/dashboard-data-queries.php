<?php
// File: admin/dashboard-data-queries.php
// Collect all statistics needed by the dashboard.


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* -----------------------------------------------------------
 * (0) Aggregate Impressions (needed for CTR calculations)
 * -------------------------------------------------------- */
$impr_rows = $wpdb->get_results(
	"SELECT post_id, button_link, COUNT(*) AS total
	 FROM {$table_impressions}
	 GROUP BY post_id, button_link",
	OBJECT_K
);
$impr_map = [];
foreach ( $impr_rows as $r ) {
	$impr_map["{$r->post_id}|{$r->button_link}"] = (int) $r->total;
}

/* -----------------------------------------------------------
 * (1) Summary Metrics (totals & quick insights)
 * -------------------------------------------------------- */
$total_clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_clicks}" );

$avg_clicks = (int) $wpdb->get_var( "
	SELECT AVG(daily.total)
	FROM (
		SELECT COUNT(*) AS total
		FROM {$table_clicks}
		GROUP BY DATE(clicked_at)
	) AS daily
" );

/* top platform by total clicks */
$top_platform = $wpdb->get_var( "
	SELECT platform
	FROM {$table_clicks}
	GROUP BY platform
	ORDER BY COUNT(*) DESC
	LIMIT 1
" );

/* single most-clicked link */
$top_link = $wpdb->get_row( "
	SELECT button_link, COUNT(*) AS total
	FROM {$table_clicks}
	GROUP BY button_link
	ORDER BY total DESC
	LIMIT 1
" );

/* most-clicked posts (top 5) */
$top_posts = $wpdb->get_results( "
	SELECT post_id, COUNT(*) AS total
	FROM {$table_clicks}
	GROUP BY post_id
	ORDER BY total DESC
	LIMIT 5
" );

/* (1.1) Weekly Click Change – “smart insight” */
$this_week_clicks = (int) $wpdb->get_var( "
	SELECT COUNT(*)
	FROM {$table_clicks}
	WHERE YEARWEEK(clicked_at, 1) = YEARWEEK(CURDATE(), 1)
" );

$last_week_clicks = (int) $wpdb->get_var( "
	SELECT COUNT(*)
	FROM {$table_clicks}
	WHERE YEARWEEK(clicked_at, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)
" );

$weekly_change = 0;
if ( $last_week_clicks > 0 ) {
	$weekly_change = round( ( ( $this_week_clicks - $last_week_clicks ) / $last_week_clicks ) * 100, 1 );
}

/* -----------------------------------------------------------
 * (2) Periodic Counts (today, last-7, etc.)
 * -------------------------------------------------------- */
$quick_counts = [
	'today'       => [ $today ],
	'yesterday'   => [ date( 'Y-m-d', strtotime( '-1 day' ) ) ],
	'last_7'      => [ date( 'Y-m-d', strtotime( '-7 day' ) ),  $today ],
	'last_30'     => [ date( 'Y-m-d', strtotime( '-30 day' ) ), $today ],
	'last_60'     => [ date( 'Y-m-d', strtotime( '-60 day' ) ), $today ],
	'last_90'     => [ date( 'Y-m-d', strtotime( '-90 day' ) ), $today ],
	'this_month'  => [ date( 'Y-m-01' ),                        date( 'Y-m-t' ) ],
	'last_month'  => [ date( 'Y-m-01', strtotime( 'first day of last month' ) ),
	                   date( 'Y-m-t', strtotime( 'last day of last month' ) ) ],
	'this_year'   => [ date( 'Y-01-01' ),                       $today ],
	'last_year'   => [ date( 'Y-01-01', strtotime( 'last year' ) ),
	                   date( 'Y-12-31', strtotime( 'last year' ) ) ],
];

$period_counts = [];
foreach ( $quick_counts as $key => $params ) {
	$period_counts[ $key ] = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_clicks}
		 WHERE DATE(clicked_at) " .
		( count( $params ) === 1 ? '= %s' : 'BETWEEN %s AND %s' ),
		...$params
	) );
}

/* -----------------------------------------------------------
 * (3) Daily Line-Chart
 * -------------------------------------------------------- */
$daily_counts = $wpdb->get_results( "
	SELECT DATE(clicked_at) AS date,
	       COUNT(*)          AS total
	FROM {$table_clicks}
	GROUP BY DATE(clicked_at)
	ORDER BY date ASC
" );

/* -----------------------------------------------------------
 * (4) Platform Doughnut
 * -------------------------------------------------------- */
$platform_counts = $wpdb->get_results( "
	SELECT platform,
	       COUNT(*) AS total
	FROM {$table_clicks}
	GROUP BY platform
" );

/* -----------------------------------------------------------
 * (5) Top Buttons – CTR Table
 * -------------------------------------------------------- */
/* filter by date range (optional) */
$top_where = '';
if ( $top_from && $top_to ) {
	$top_where = $wpdb->prepare(
		'WHERE DATE(clicked_at) BETWEEN %s AND %s',
		$top_from, $top_to
	);
}
$top_limit  = 10;
$top_offset = ( $top_paged - 1 ) * $top_limit;

$top_total_rows  = (int) $wpdb->get_var(
	"SELECT COUNT(DISTINCT CONCAT(button_text, button_link))
	 FROM {$table_clicks} {$top_where}"
);
$top_total_pages = max( 1, ceil( $top_total_rows / $top_limit ) );

$top_buttons_raw = $wpdb->get_results( "
	SELECT button_text,
	       button_link,
	       COUNT(*) AS total_clicks
	FROM {$table_clicks}
	{$top_where}
	GROUP BY button_text, button_link
	ORDER BY total_clicks DESC
	LIMIT {$top_limit} OFFSET {$top_offset}
" );

$top_buttons = [];
foreach ( $top_buttons_raw as $btn ) {
	$impressions = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_impressions}
		 WHERE button_link = %s",
		$btn->button_link
	) );
	$ctr = $impressions ? round( ( $btn->total_clicks / $impressions ) * 100, 2 ) : 0;

	$top_buttons[] = (object) [
		'text'        => $btn->button_text,
		'link'        => $btn->button_link,
		'clicks'      => (int) $btn->total_clicks,
		'impressions' => $impressions,
		'ctr'         => $ctr,
	];
}

/* -----------------------------------------------------------
 * (6) Detailed Clicks Table (paginated)
 * -------------------------------------------------------- */
$d_where = '';
if ( $filter_from && $filter_to ) {
	$d_where = $wpdb->prepare(
		'WHERE DATE(clicked_at) BETWEEN %s AND %s',
		$filter_from, $filter_to
	);
}
$limit  = 10;
$offset = ( $paged - 1 ) * $limit;

$d_total_rows  = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$table_clicks} {$d_where}"
);
$d_total_pages = max( 1, ceil( $d_total_rows / $limit ) );

$clicks = $wpdb->get_results( "
	SELECT *
	FROM {$table_clicks}
	{$d_where}
	ORDER BY clicked_at DESC
	LIMIT {$limit} OFFSET {$offset}
" );

/* ===========================================================
 * (7) NORMALISE VARIABLE NAMES FOR THE DASHBOARD
 *      → provide both legacy and new `$plinkly_*` aliases
 * ======================================================== */

/* 7.1  Build full & short titles for Top-Posts chart */
$top_post_titles_full  = [];
$top_post_titles_short = [];
foreach ( $top_posts as $row ) {
	$title = get_the_title( $row->post_id );
	$top_post_titles_full[]  = $title;
	$top_post_titles_short[] = ( mb_strlen( $title ) > 24 )
		? mb_substr( $title, 0, 24 ) . '…'
		: $title;
}

/* 7.2  Build full & short names for Platform chart */
$platform_names_full  = [];
$platform_names_short = [];
foreach ( $platform_counts as $row ) {
	$name = ucfirst( $row->platform );               // e.g. "amazon"
	$platform_names_full[]  = $name;
	$platform_names_short[] = ( mb_strlen( $name ) > 12 )
		? mb_substr( $name, 0, 12 ) . '…'
		: $name;
}

/* 7.3  Expose `$plinkly_*` aliases so dashboard-page.php
 *      can rely on uniform naming whatever include order */
$plinkly_daily_counts           = $daily_counts;
$plinkly_top_post_titles_full   = $top_post_titles_full;
$plinkly_top_post_titles_short  = $top_post_titles_short;
$plinkly_top_posts              = $top_posts;
$plinkly_platform_names_full    = $platform_names_full;
$plinkly_platform_names_short   = $platform_names_short;
$plinkly_platform_counts        = $platform_counts;
