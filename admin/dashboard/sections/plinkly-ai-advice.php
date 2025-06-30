<?php
// File: admin/dashboard/plinkly-ai-advice.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Prevent duplicate inclusion */
if ( isset( $GLOBALS['plinkly_ai_section_rendered'] ) ) {
	return;
}
$GLOBALS['plinkly_ai_section_rendered'] = true;
?>

<!-- Toolbar: Toggle AI section -->
<div class="plinkly-ai-toolbar" data-plinkly-ai="1">
	<button id="show-ai-dashboard"
	        class="button button-primary"
	        type="button">
		<?php echo esc_html__( '🤖 Show Plinkly AI Advice', 'plinkly' ); ?>
	</button>

	<button id="hide-ai-dashboard"
	        class="button"
	        type="button"
	        style="display:none;">
		<?php echo esc_html__( '← Hide Plinkly AI', 'plinkly' ); ?>
	</button>
</div>

<!-- AI analytics container (hidden by default) -->
<section id="ai-dashboard-analytics-section"
         class="plinkly-ai-card card"
         style="display:none;">
	<header class="plinkly-ai-header">
		<h2><?php echo esc_html__( '🤖 Plinkly AI Analytics', 'plinkly' ); ?></h2>

		<button id="ai-dashboard-refresh"
		        class="button"
		        type="button"
		        aria-label="<?php esc_attr_e( 'Refresh AI insights', 'plinkly' ); ?>">
			<?php echo esc_html__( '🔄 Refresh', 'plinkly' ); ?>
		</button>
	</header>

	<div id="ai-dashboard-insights">
		<em><?php esc_html_e( 'Click 🔄 Refresh', 'plinkly' ); ?></em>
	</div>
</section>
