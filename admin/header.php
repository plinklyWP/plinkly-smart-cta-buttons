<?php
// File: admin/header.php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─────────────── Util URLs ─────────────── */
function plinkly_get_logo_url()   { return plugin_dir_url( __DIR__ ) . 'assets/img/plinkly-logo.png'; }
function plinkly_get_upgrade_url(){ return 'https://plink.ly/#price-box'; }
function plinkly_get_support_url(){ return 'https://plink.ly/support/'; }
function plinkly_get_add_on_url(){ return 'https://plink.ly/#add-on'; }

/**
 * Render the unified PlinkLy header inside wp-admin.
 *
 * @param string $page_title  Custom title shown next to the logo.
 */
function plinkly_render_header( $page_title = 'PlinkLy CTA' ) {

	$current_page = $_GET['page'] ?? ''; // نستخدم وسيط الـ GET لتحديد الشاشة
	?>
	<div class="plinkly-header">
		<!-- ── Top Bar ── -->
		<div class="plinkly-header-top">
			<div class="plinkly-header-left">
				<img
					src="<?php echo esc_url( plinkly_get_logo_url() ); ?>"
					alt="<?php esc_attr_e( 'PlinkLy Logo', 'plinkly-smart-cta-buttons' ); ?>"
					class="plinkly-logo"
				/>
			</div>

			<div class="plinkly-header-right">
				<?php if ( ! plinkly_is_pro_active() ) : ?>
					<div class="plinkly-upgrade-wrapper">
						<a
							class="plinkly-upgrade-button"
							href="<?php echo esc_url( plinkly_get_upgrade_url() ); ?>"
							target="_blank"
						>🚀 <?php esc_html_e( 'Upgrade to PRO', 'plinkly-smart-cta-buttons' ); ?></a>
						<span class="plinkly-badge">20% OFF</span>
					</div>
				<?php endif; ?>

				<div class="plinkly-upgrade-wrapper">
					<a
						class="plinkly-upgrade-button"
						href="<?php echo esc_url( plinkly_get_add_on_url() ); ?>"
						target="_blank"
					>🧩 <?php esc_html_e( 'Add-ons Library', 'plinkly-smart-cta-buttons' ); ?></a>
				</div>

				<a
					class="plinkly-support-link pulse"
					href="<?php echo esc_url( plinkly_get_support_url() ); ?>"
					title="<?php esc_attr_e( 'Support', 'plinkly-smart-cta-buttons' ); ?>"
					target="_blank"
				>🛟</a>
			</div>
		</div>

		<!-- ── Middle (title) ── -->
		<div class="plinkly-header-middle">
			<h2 class="plinkly-page-title"><?php echo esc_html( $page_title ); ?></h2>
		</div>

		<?php /* ── Bottom / In-page navigation – Dashboard-only ── */
		if ( $current_page === 'plinkly-cta-dashboard' ) : ?>
			<div class="plinkly-header-bottom">
				<nav class="plinkly-nav">
					<a href=""                              class="plinkly-nav-link">Home</a>
					<a href="#top-clicked-buttons-section"  class="plinkly-nav-link">Top Buttons</a>
					<a href="#ab-summary-section"           class="plinkly-nav-link">A/B Testing</a>
					<a href="#detailed-data-section"        class="plinkly-nav-link">Detailed Data</a>
				</nav>
			</div>

			<!-- Smooth-scroll + active-tab highlight (Dashboard only) -->
			<script>
			document.addEventListener('DOMContentLoaded', () => {
				const navLinks = document.querySelectorAll('.plinkly-nav-link');
				if (!navLinks.length) return;

				/* 1) Smooth scroll */
				navLinks.forEach(link => link.addEventListener('click', e => {
					e.preventDefault();
					const target = document.querySelector(link.getAttribute('href'));
					if (target) target.scrollIntoView({behavior: 'smooth', block: 'start'});
				}));

				/* 2) Highlight while scrolling */
				const sections = [...navLinks].map(l => document.querySelector(l.getAttribute('href')));
				const observer = new IntersectionObserver(entries => {
					entries.forEach(entry => {
						if (entry.isIntersecting) {
							navLinks.forEach(l => {
								l.classList.toggle(
									'is-active',
									l.getAttribute('href').substring(1) === entry.target.id
								);
							});
						}
					});
				}, {rootMargin: '-80px 0px -70% 0px'}); // adjust for header height

				sections.forEach(s => s && observer.observe(s));
			});
			</script>
		<?php endif; /* end Dashboard-only */ ?>
	</div>
	<?php
}
