/**
 * Giant Scroll
 *
 * Replicates the jQuery full-page scroll demo using vanilla JS + CSS transforms.
 * Works with Gutenberg Cover blocks whose id starts with "section"
 * (e.g. section, section-1, section-2 …).
 *
 * Settings are injected by wp_localize_script as window.giantScroll:
 *   disableMobile {boolean}
 *   breakpoint    {number}   px
 *   duration      {number}   ms
 */
( function () {
	'use strict';

	// ── Config ────────────────────────────────────────────────────────────────

	var cfg = window.giantScroll || {};
	var DISABLE_MOBILE = !! cfg.disableMobile;
	var BREAKPOINT     = cfg.breakpoint  || 768;
	var DURATION       = cfg.duration    || 800;

	// ── State ─────────────────────────────────────────────────────────────────

	var sections   = [];   // ordered list of section elements
	var wrapper    = null; // direct parent of sections (or synthetic wrapper)
	var position   = 0;   // 0-based index of the current visible section
	var animating  = false;
	var active     = false; // full-page mode is running

	// ── Helpers ───────────────────────────────────────────────────────────────

	function vh() {
		return window.innerHeight;
	}

	function isMobileViewport() {
		return DISABLE_MOBILE && window.innerWidth < BREAKPOINT;
	}

	// ── Init / Teardown ───────────────────────────────────────────────────────

	function init() {
		// Collect all Cover blocks whose id starts with "section"
		sections = Array.prototype.slice.call(
			document.querySelectorAll( '[id^="section"]' )
		);

		if ( sections.length === 0 ) return;

		// Sort by DOM order to be safe
		sections.sort( function ( a, b ) {
			return a.compareDocumentPosition( b ) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
		} );

		// Wrap sections in a single translateY-able container if they aren't
		// already inside one. We look for a common parent; if all sections share
		// the same parent we use that, otherwise we inject a <div>.
		var commonParent = sections[ 0 ].parentElement;
		var allSameParent = sections.every( function ( s ) {
			return s.parentElement === commonParent;
		} );

		if ( allSameParent ) {
			wrapper = commonParent;
		} else {
			// Inject a wrapper around the sections
			wrapper = document.createElement( 'div' );
			wrapper.className = 'giant-scroll-wrapper';
			sections[ 0 ].parentNode.insertBefore( wrapper, sections[ 0 ] );
			sections.forEach( function ( s ) {
				wrapper.appendChild( s );
			} );
		}

		wrapper.classList.add( 'giant-scroll-wrapper' );

		enable();
	}

	function enable() {
		active = true;
		position = 0;
		animating = false;

		// Expose CSS custom property for transition duration
		document.documentElement.style.setProperty(
			'--giant-scroll-duration', DURATION + 'ms'
		);

		document.body.classList.remove( 'giant-scroll-mobile-off' );
		document.body.classList.add( 'giant-scroll-active' );

		// Force wrapper height so it scrolls correctly
		wrapper.style.height = ( sections.length * vh() ) + 'px';

		goTo( 0, false );
	}

	function disable() {
		active = false;
		animating = false;

		document.body.classList.remove( 'giant-scroll-active' );
		document.body.classList.add( 'giant-scroll-mobile-off' );

		// Reset any inline transform
		wrapper.style.transform = '';
		wrapper.style.height    = '';
	}

	// ── Navigation ────────────────────────────────────────────────────────────

	function goTo( index, animate ) {
		if ( index < 0 || index >= sections.length ) return;

		var offset = index * vh();

		if ( animate === false ) {
			// Instant — temporarily suppress transition
			wrapper.style.transition = 'none';
			wrapper.style.transform  = 'translateY(-' + offset + 'px)';
			// Re-enable transition after a paint
			requestAnimationFrame( function () {
				requestAnimationFrame( function () {
					wrapper.style.transition = '';
				} );
			} );
		} else {
			animating = true;
			wrapper.style.transform = 'translateY(-' + offset + 'px)';

			// Listen for transitionend to clear animating flag
			var onEnd = function () {
				animating = false;
				wrapper.removeEventListener( 'transitionend', onEnd );
			};
			wrapper.addEventListener( 'transitionend', onEnd );

			// Safety fallback in case transitionend doesn't fire
			setTimeout( function () {
				animating = false;
			}, DURATION + 100 );
		}

		position = index;
	}

	function scrollUp() {
		if ( !animating && position > 0 ) {
			goTo( position - 1, true );
		}
	}

	function scrollDown() {
		if ( !animating && position < sections.length - 1 ) {
			goTo( position + 1, true );
		}
	}

	// ── Touch tracking ────────────────────────────────────────────────────────

	var touchStartY = 0;

	// ── Event Listeners ───────────────────────────────────────────────────────

	window.addEventListener( 'wheel', function ( e ) {
		if ( !active ) return;

		var delta = e.deltaY || -e.wheelDeltaY || 0;

		if ( delta < 0 ) {
			scrollUp();
		} else if ( delta > 0 ) {
			scrollDown();
		}

		// Prevent native scroll while our animation is in control
		e.preventDefault();
	}, { passive: false } );

	window.addEventListener( 'touchstart', function ( e ) {
		touchStartY = e.touches[ 0 ].clientY;
	}, { passive: true } );

	window.addEventListener( 'touchend', function ( e ) {
		if ( !active ) return;

		var diff = touchStartY - e.changedTouches[ 0 ].clientY;

		if ( Math.abs( diff ) < 30 ) return; // ignore tiny swipes

		if ( diff < 0 ) {
			scrollUp();
		} else {
			scrollDown();
		}
	}, { passive: true } );

	// Keyboard support
	window.addEventListener( 'keydown', function ( e ) {
		if ( !active ) return;

		if ( e.key === 'ArrowDown' || e.key === 'PageDown' ) {
			e.preventDefault();
			scrollDown();
		} else if ( e.key === 'ArrowUp' || e.key === 'PageUp' ) {
			e.preventDefault();
			scrollUp();
		}
	} );

	// Recalculate on resize (covers orientation changes on mobile)
	var resizeTimer;
	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			if ( isMobileViewport() ) {
				if ( active ) disable();
				return;
			}
			if ( !active ) {
				enable();
				return;
			}
			// Recalculate wrapper height and current offset
			wrapper.style.height = ( sections.length * vh() ) + 'px';
			goTo( position, false );
		}, 150 );
	} );

	// ── Boot ──────────────────────────────────────────────────────────────────

	function boot() {
		if ( isMobileViewport() ) {
			// Still collect sections and mark body for CSS, but don't enable
			sections = Array.prototype.slice.call(
				document.querySelectorAll( '[id^="section"]' )
			);
			if ( sections.length ) {
				document.body.classList.add( 'giant-scroll-mobile-off' );
			}
			return;
		}

		init();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

}() );
