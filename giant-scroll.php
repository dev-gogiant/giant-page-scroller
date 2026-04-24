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

// ── Settings ─────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
	add_options_page(
		'Giant Scroll',
		'Giant Scroll',
		'manage_options',
		'giant-scroll',
		'giant_scroll_settings_page'
	);
} );

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

function giant_scroll_settings_page() {
	$disable_mobile = get_option( 'giant_scroll_disable_mobile', false );
	$breakpoint     = get_option( 'giant_scroll_mobile_breakpoint', 768 );
	$duration       = get_option( 'giant_scroll_duration', 800 );
	?>
	<div class="wrap">
		<h1>Giant Scroll Settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'giant_scroll_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Disable on mobile</th>
					<td>
						<label>
							<input type="checkbox" name="giant_scroll_disable_mobile" value="1" <?php checked( 1, $disable_mobile ); ?> />
							Allow normal scrolling below the breakpoint width
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Mobile breakpoint (px)</th>
					<td>
						<input type="number" name="giant_scroll_mobile_breakpoint" value="<?php echo esc_attr( $breakpoint ); ?>" min="320" max="1200" step="1" class="small-text" />
						<p class="description">Full-page scroll is disabled when the viewport is narrower than this value (only applies when the checkbox above is ticked).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Scroll animation duration (ms)</th>
					<td>
						<input type="number" name="giant_scroll_duration" value="<?php echo esc_attr( $duration ); ?>" min="200" max="3000" step="50" class="small-text" />
						<p class="description">Duration of the slide transition in milliseconds (e.g. 800).</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
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
		true   // footer
	);

	wp_localize_script( 'giant-scroll', 'giantScroll', [
		'disableMobile' => (bool) get_option( 'giant_scroll_disable_mobile', false ),
		'breakpoint'    => (int)  get_option( 'giant_scroll_mobile_breakpoint', 768 ),
		'duration'      => (int)  get_option( 'giant_scroll_duration', 800 ),
	] );
} );
