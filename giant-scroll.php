<?php
/**
 * Plugin Name: Giant Scroll
 * Description: Smooth full-page scroll snap for Gutenberg Cover blocks with id="section-*". Includes a mobile disable toggle.
 * Version: 1.0.0
 * Author: Marius Cautis
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GIANT_SCROLL_VERSION', '1.0.0' );
define( 'GIANT_SCROLL_URL', plugin_dir_url( __FILE__ ) );
define( 'GIANT_SCROLL_PATH', plugin_dir_path( __FILE__ ) );

// ── Admin menu ────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
	add_menu_page(
		'Giant Scroll',
		'Giant Scroll',
		'manage_options',
		'giant-scroll',
		'giant_scroll_settings_page',
		'dashicons-move',
		80
	);
} );

// ── Admin styles ──────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'toplevel_page_giant-scroll' ) return;
	wp_enqueue_style(
		'giant-scroll-admin',
		GIANT_SCROLL_URL . 'assets/giant-scroll-admin.css',
		[],
		GIANT_SCROLL_VERSION
	);
} );

// ── Settings registration ─────────────────────────────────────────────────────

add_action( 'admin_init', function () {
	register_setting( 'giant_scroll_group', 'giant_scroll_disable_mobile', [
		'type'              => 'boolean',
		'default'           => false,
		'sanitize_callback' => 'rest_sanitize_boolean',
	] );
	register_setting( 'giant_scroll_group', 'giant_scroll_mobile_breakpoint', [
		'type'              => 'integer',
		'default'           => 768,
		'sanitize_callback' => 'absint',
	] );
	register_setting( 'giant_scroll_group', 'giant_scroll_duration', [
		'type'              => 'integer',
		'default'           => 800,
		'sanitize_callback' => 'absint',
	] );
} );

// ── Settings page ─────────────────────────────────────────────────────────────

function giant_scroll_settings_page() {
	$disable_mobile = get_option( 'giant_scroll_disable_mobile', false );
	$breakpoint     = get_option( 'giant_scroll_mobile_breakpoint', 768 );
	$duration       = get_option( 'giant_scroll_duration', 800 );

	$saved = isset( $_GET['settings-updated'] ) && $_GET['settings-updated'];
	?>
	<div class="gs-wrap">

		<!-- ── Header ── -->
		<div class="gs-header">
			<div class="gs-header__inner">
				<span class="gs-header__icon dashicons dashicons-move"></span>
				<div>
					<h1 class="gs-header__title">Giant Scroll</h1>
					<p class="gs-header__sub">Full-page scroll snap for Gutenberg Cover blocks</p>
				</div>
			</div>
			<span class="gs-badge">v<?php echo esc_html( GIANT_SCROLL_VERSION ); ?></span>
		</div>

		<?php if ( $saved ) : ?>
		<div class="gs-notice gs-notice--success">
			<span class="dashicons dashicons-yes-alt"></span>
			Settings saved successfully!
		</div>
		<?php endif; ?>

		<div class="gs-layout">

			<!-- ── Settings panel ── -->
			<div class="gs-card gs-card--settings">
				<h2 class="gs-card__title">
					<span class="dashicons dashicons-admin-settings"></span>
					Settings
				</h2>

				<form method="post" action="options.php">
					<?php settings_fields( 'giant_scroll_group' ); ?>

					<!-- Mobile toggle -->
					<div class="gs-field">
						<div class="gs-field__header">
							<label class="gs-field__label" for="gs-disable-mobile">Disable on mobile</label>
							<label class="gs-toggle">
								<input
									type="checkbox"
									id="gs-disable-mobile"
									name="giant_scroll_disable_mobile"
									value="1"
									<?php checked( 1, $disable_mobile ); ?>
								/>
								<span class="gs-toggle__track">
									<span class="gs-toggle__thumb"></span>
								</span>
								<span class="gs-toggle__label"><?php echo $disable_mobile ? 'On' : 'Off'; ?></span>
							</label>
						</div>
						<p class="gs-field__desc">
							When enabled, the full-page scroll effect is turned off on devices narrower than the breakpoint below, letting visitors scroll normally on phones.
						</p>
					</div>

					<!-- Breakpoint -->
					<div class="gs-field gs-field--indent <?php echo $disable_mobile ? '' : 'gs-field--dimmed'; ?>" id="gs-breakpoint-row">
						<label class="gs-field__label" for="gs-breakpoint">Mobile breakpoint</label>
						<div class="gs-input-group">
							<input
								type="number"
								id="gs-breakpoint"
								name="giant_scroll_mobile_breakpoint"
								value="<?php echo esc_attr( $breakpoint ); ?>"
								min="320"
								max="1200"
								step="1"
							/>
							<span class="gs-input-group__unit">px</span>
						</div>
						<p class="gs-field__desc">Viewports narrower than this width will use normal scrolling (only applies when the toggle above is on).</p>
					</div>

					<!-- Duration -->
					<div class="gs-field">
						<label class="gs-field__label" for="gs-duration">Animation duration</label>
						<div class="gs-input-group">
							<input
								type="number"
								id="gs-duration"
								name="giant_scroll_duration"
								value="<?php echo esc_attr( $duration ); ?>"
								min="200"
								max="3000"
								step="50"
							/>
							<span class="gs-input-group__unit">ms</span>
						</div>
						<p class="gs-field__desc">How long the slide transition takes. 600–900 ms feels natural; go lower for snappier, higher for cinematic.</p>
					</div>

					<div class="gs-actions">
						<?php submit_button( 'Save Settings', 'primary', 'submit', false ); ?>
					</div>

				</form>
			</div>

			<!-- ── How to use panel ── -->
			<div class="gs-card gs-card--howto">
				<h2 class="gs-card__title">
					<span class="dashicons dashicons-info-outline"></span>
					How to use
				</h2>

				<ol class="gs-steps">
					<li class="gs-step">
						<span class="gs-step__num">1</span>
						<div>
							<strong>Add Cover blocks to your page</strong>
							<p>In the Gutenberg editor, insert a <em>Cover</em> block for each full-screen section you want.</p>
						</div>
					</li>
					<li class="gs-step">
						<span class="gs-step__num">2</span>
						<div>
							<strong>Set the HTML anchor</strong>
							<p>Select a Cover block → open the <em>Advanced</em> panel in the right sidebar → set the <strong>HTML anchor</strong> to <code>section</code>, <code>section-1</code>, <code>section-2</code>, etc.</p>
						</div>
					</li>
					<li class="gs-step">
						<span class="gs-step__num">3</span>
						<div>
							<strong>Make each Cover full-width &amp; full-height</strong>
							<p>In the Cover block settings set <em>Minimum height</em> to <code>100vh</code> and alignment to <em>Full width</em>.</p>
						</div>
					</li>
					<li class="gs-step">
						<span class="gs-step__num">4</span>
						<div>
							<strong>Publish and enjoy</strong>
							<p>That's it! Giant Scroll automatically detects all <code>section-*</code> blocks and enables the full-page snap effect.</p>
						</div>
					</li>
				</ol>

				<div class="gs-tip">
					<span class="dashicons dashicons-lightbulb"></span>
					<div>
						<strong>Tip — navigation</strong>
						<p>Visitors can navigate between sections using the <strong>mouse wheel</strong>, <strong>touch swipe</strong> (up/down), or the <strong>arrow keys</strong> on a keyboard.</p>
					</div>
				</div>

				<div class="gs-tip gs-tip--warn">
					<span class="dashicons dashicons-smartphone"></span>
					<div>
						<strong>Mobile behaviour</strong>
						<p>Enable <em>Disable on mobile</em> above if you'd prefer phones to scroll freely through your sections rather than snap between them.</p>
					</div>
				</div>

				<div class="gs-anchor-guide">
					<p class="gs-anchor-guide__label">Valid HTML anchors</p>
					<div class="gs-anchor-guide__tags">
						<code>section</code>
						<code>section-1</code>
						<code>section-2</code>
						<code>section-3</code>
						<code>section-hero</code>
						<code>section-about</code>
						<span class="gs-anchor-guide__etc">…anything starting with <code>section</code></span>
					</div>
				</div>
			</div>

		</div><!-- .gs-layout -->
	</div><!-- .gs-wrap -->

	<script>
	(function(){
		var cb = document.getElementById('gs-disable-mobile');
		var row = document.getElementById('gs-breakpoint-row');
		var lbl = cb.closest('.gs-field').querySelector('.gs-toggle__label');
		function sync(){
			lbl.textContent = cb.checked ? 'On' : 'Off';
			row.classList.toggle('gs-field--dimmed', !cb.checked);
		}
		cb.addEventListener('change', sync);
	}());
	</script>
	<?php
}

// ── Front-end assets ──────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'giant-scroll',
		GIANT_SCROLL_URL . 'assets/giant-scroll.css',
		[],
		GIANT_SCROLL_VERSION
	);

	wp_enqueue_script(
		'giant-scroll',
		GIANT_SCROLL_URL . 'assets/giant-scroll.js',
		[],
		GIANT_SCROLL_VERSION,
		true
	);

	wp_localize_script( 'giant-scroll', 'giantScroll', [
		'disableMobile' => (bool) get_option( 'giant_scroll_disable_mobile', false ),
		'breakpoint'    => (int)  get_option( 'giant_scroll_mobile_breakpoint', 768 ),
		'duration'      => (int)  get_option( 'giant_scroll_duration', 800 ),
	] );
} );
