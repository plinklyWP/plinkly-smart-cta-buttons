<?php
// File: admin/dashboard-data-queries.php
// Collect all statistics needed by the dashboard.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* =======================================================================
 * (-1) Filters passed from UI (date range + A/B variant)
 * ==================================================================== */
$filter_from    = isset( $_GET['filter_from'] )    ? sanitize_text_field( $_GET['filter_from'] )    : '';
$filter_to      = isset( $_GET['filter_to'] )      ? sanitize_text_field( $_GET['filter_to'] )      : '';
$filter_variant = isset( $_GET['filter_variant'] ) ? strtoupper( sanitize_key( $_GET['filter_variant'] ) ) : '';
if ( ! in_array( $filter_variant, [ 'A', 'B' ], true ) ) {
	$filter_variant = '';
}
$variant_where        = $filter_variant ? $wpdb->prepare( ' AND variant = %s', $filter_variant )        : '';
$variant_where_impr   = $filter_variant ? $wpdb->prepare( 'WHERE variant = %s', $filter_variant )       : '';

/* -----------------------------------------------------------
 * (0) Aggregate Impressions (needed for CTR calculations)
 * -------------------------------------------------------- */
$impr_rows = $wpdb->get_results(
	"SELECT post_id,
	        button_link,
	        COALESCE(variant,'A') AS variant,
	        COUNT(*)              AS total
	 FROM {$table_impressions}
	 {$variant_where_impr}
	 GROUP BY post_id, button_link, variant",
	OBJECT_K
);

$impr_map = [];
foreach ( $impr_rows as $r ) {
	$key = "{$r->post_id}|{$r->button_link}|{$r->variant}";
	$impr_map[ $key ] = (int) $r->total;
}

/* -----------------------------------------------------------
 * (1) Summary Metrics (totals & quick insights)
 * -------------------------------------------------------- */
$total_clicks = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$table_clicks} WHERE 1=1 {$variant_where}"
);

$avg_clicks = (int) $wpdb->get_var( "
	SELECT AVG(daily.total) FROM (
		SELECT COUNT(*) AS total
		FROM {$table_clicks}
		WHERE 1=1 {$variant_where}
		GROUP BY DATE(clicked_at)
	) AS daily
" );

/* top platform by total clicks */
$top_platform = $wpdb->get_var( "
	SELECT platform
	FROM {$table_clicks}
	WHERE 1=1 {$variant_where}
	GROUP BY platform
	ORDER BY COUNT(*) DESC
	LIMIT 1
" );

/* single most-clicked link */
$top_link = $wpdb->get_row( "
	SELECT button_link, COUNT(*) AS total
	FROM {$table_clicks}
	WHERE 1=1 {$variant_where}
	GROUP BY button_link
	ORDER BY total DESC
	LIMIT 1
" );

/* most-clicked posts (top 5) */
$top_posts = $wpdb->get_results( "
	SELECT post_id, COUNT(*) AS total
	FROM {$table_clicks}
	WHERE 1=1 {$variant_where}
	GROUP BY post_id
	ORDER BY total DESC
	LIMIT 5
" );

/* (1.1) Weekly Click Change */
$this_week_clicks = (int) $wpdb->get_var( "
	SELECT COUNT(*)
	FROM {$table_clicks}
	WHERE YEARWEEK(clicked_at, 1) = YEARWEEK(CURDATE(), 1)
	{$variant_where}
" );

$last_week_clicks = (int) $wpdb->get_var( "
	SELECT COUNT(*)
	FROM {$table_clicks}
	WHERE YEARWEEK(clicked_at, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)
	{$variant_where}
" );

$weekly_change = $last_week_clicks ? round( ( ( $this_week_clicks - $last_week_clicks ) / $last_week_clicks ) * 100, 1 ) : 0;

/* -----------------------------------------------------------
 * (1.A) Smart-Insight helper values
 * -------------------------------------------------------- */

/* Top Post (Last 7 Days) */
$smart_top_post = null;
if ( ! empty( $top_posts[0] ) ) {
	$smart_top_post = [
		'title'  => get_the_title( $top_posts[0]->post_id ),
		'clicks' => (int) $top_posts[0]->total,
	];
}

/* A/B Winner (CTR over the last 7 days) */
$ab_rows = $wpdb->get_results( "
	SELECT COALESCE(variant,'A') AS variant,
	       SUM(clicks)      AS clicks,
	       SUM(impressions) AS imps
	FROM (
	    /* CLICKs */
	    SELECT COALESCE(variant,'A') AS variant,
	           COUNT(*)              AS clicks,
	           0                     AS impressions
	    FROM {$table_clicks}
	    WHERE clicked_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY
	          {$variant_where}
	    GROUP BY variant

	    UNION ALL

	    /* IMPRESSIONS */
	    SELECT COALESCE(variant,'A') AS variant,
	           0                     AS clicks,
	           COUNT(*)              AS impressions
	    FROM {$table_impressions}
	    WHERE showed_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY
	          {$variant_where}
	    GROUP BY variant
	) t
	GROUP BY variant
	HAVING imps > 20
" , ARRAY_A );

$smart_ab_winner = null;
if ( count( $ab_rows ) === 2 ) {
	// Calculate CTR for each variant
	$ctrs = array_map(
		fn ( $r ) => [
			'variant' => $r['variant'],
			'ctr'     => $r['imps'] ? $r['clicks'] / $r['imps'] : 0,
		],
		$ab_rows
	);
	usort( $ctrs, fn ( $a, $b ) => $b['ctr'] <=> $a['ctr'] );

	$lift = ( $ctrs[0]['ctr'] - $ctrs[1]['ctr'] )
	      / max( 0.0001, $ctrs[1]['ctr'] ) * 100;

	$smart_ab_winner = [
		'variant' => $ctrs[0]['variant'],
		'lift'    => round( $lift ),
	];
}

/* Peak Hour (Last 14 Days) */
$peak_row = $wpdb->get_row( "
	SELECT HOUR(clicked_at) AS hr, COUNT(*) AS cnt
	FROM {$table_clicks}
	WHERE clicked_at >= UTC_TIMESTAMP() - INTERVAL 14 DAY
	      {$variant_where}
	GROUP BY hr
	ORDER BY cnt DESC
	LIMIT 1
" );
$smart_best_hour = $peak_row ? (int) $peak_row->hr : null;

/* Merge data for dashboard output */
$plinkly_smart_insight = [
	'top_post'      => $smart_top_post,
	'ab_winner'     => $smart_ab_winner,
	'best_hour'     => $smart_best_hour,
];


/* -----------------------------------------------------------
 * (2) Periodic Counts (today, last-7, …)
 * -------------------------------------------------------- */
$today = gmdate( 'Y-m-d' );
$quick_counts = [
	'today'       => [ $today ],
	'yesterday'   => [ gmdate( 'Y-m-d', strtotime( '-1 day' ) ) ],
	'last_7'      => [ gmdate( 'Y-m-d', strtotime( '-7 day' ) ),  $today ],
	'last_30'     => [ gmdate( 'Y-m-d', strtotime( '-30 day' ) ), $today ],
	'last_60'     => [ gmdate( 'Y-m-d', strtotime( '-60 day' ) ), $today ],
	'last_90'     => [ gmdate( 'Y-m-d', strtotime( '-90 day' ) ), $today ],
	'this_month'  => [ gmdate( 'Y-m-01' ),                        gmdate( 'Y-m-t' ) ],
	'last_month'  => [ gmdate( 'Y-m-01', strtotime( 'first day of last month' ) ),
	                   gmdate( 'Y-m-t', strtotime( 'last day of last month' ) ) ],
	'this_year'   => [ gmdate( 'Y-01-01' ),                       $today ],
	'last_year'   => [ gmdate( 'Y-01-01', strtotime( 'last year' ) ),
	                   gmdate( 'Y-12-31', strtotime( 'last year' ) ) ],
];

$period_counts = [];
foreach ( $quick_counts as $key => $params ) {
	$period_counts[ $key ] = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		 FROM {$table_clicks}
		 WHERE DATE(clicked_at) " .
		 ( count( $params ) === 1 ? '= %s' : 'BETWEEN %s AND %s' ) .
		 $variant_where,
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
	WHERE 1=1 {$variant_where}
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
	WHERE 1=1 {$variant_where}
	GROUP BY platform
" );

/* -----------------------------------------------------------
 * (5) Top Buttons – CTR Table
 * -------------------------------------------------------- */
$top_where = '';
if ( $top_from && $top_to ) {
	$top_where = $wpdb->prepare(
		'WHERE DATE(clicked_at) BETWEEN %s AND %s',
		$top_from, $top_to
	);
}
$top_where .= $variant_where;

$top_limit  = 10;
$top_offset = ( $top_paged - 1 ) * $top_limit;

$top_total_rows = (int) $wpdb->get_var(
	"SELECT COUNT(DISTINCT CONCAT(button_text,button_link,COALESCE(variant,'A')))
	 FROM {$table_clicks} {$top_where}"
);

$top_buttons_raw = $wpdb->get_results( "
	SELECT button_text,
	       button_link,
	       COALESCE(variant,'A') AS variant,
	       COUNT(*)              AS total_clicks
	FROM {$table_clicks}
	{$top_where}
	GROUP BY button_text, button_link, variant
	ORDER BY total_clicks DESC
	LIMIT {$top_limit} OFFSET {$top_offset}
" );

$top_buttons = [];
foreach ( $top_buttons_raw as $btn ) {
	$impressions = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table_impressions}
		 WHERE button_link = %s AND COALESCE(variant,'A') = %s {$variant_where}",
		$btn->button_link,
		$btn->variant
	) );

	$ctr = $impressions ? round( ( $btn->total_clicks / $impressions ) * 100, 2 ) : 0;

	$top_buttons[] = (object) [
		'text'        => $btn->button_text,
		'link'        => $btn->button_link,
		'variant'     => $btn->variant,
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
$d_where .= $variant_where;

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
	$name = ucfirst( $row->platform );
	$platform_names_full[]  = $name;
	$platform_names_short[] = ( mb_strlen( $name ) > 12 )
		? mb_substr( $name, 0, 12 ) . '…'
		: $name;
}

/* 7.3  Expose `$plinkly_*` aliases */
$plinkly_daily_counts           = $daily_counts;
$plinkly_top_post_titles_full   = $top_post_titles_full;
$plinkly_top_post_titles_short  = $top_post_titles_short;
$plinkly_top_posts              = $top_posts;
$plinkly_platform_names_full    = $platform_names_full;
$plinkly_platform_names_short   = $platform_names_short;
$plinkly_platform_counts        = $platform_counts;
