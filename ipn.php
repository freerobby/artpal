<?php
/**
 * PayPal Instant Payment Notification endpoint.
 *
 * notify_url is plugins_url( 'ipn.php' ), so PayPal posts here even when the
 * plugin directory is not named artpal.
 *
 * The body is read before WordPress loads. Bootstrapping can consume
 * php://input, and PayPal rejects a verification post that is not the
 * original bytes.
 *
 * Core is not always three levels above this file. Flywheel keeps it in
 * .wordpress/ beside the web root. Paths are built with dirname() so
 * is_readable() is not handed a ".." segment (open_basedir rejects those).
 */

if ( ! function_exists( 'artpal_find_wp_load' ) ) {
	/**
	 * Locate wp-load.php by walking up from the plugin directory.
	 *
	 * The plugin directory itself is skipped, so a stray wp-load.php next
	 * to this file cannot shadow WordPress.
	 *
	 * @param string|null $start Directory that contains ipn.php. Defaults to this file's directory.
	 * @return string Absolute path, or '' when WordPress cannot be found.
	 */
	function artpal_find_wp_load( $start = null ) {
		$dir = is_string( $start ) && $start !== '' ? $start : __DIR__;
		$dir = rtrim( $dir, '/\\' );
		$parent = dirname( $dir );
		if ( $parent === $dir ) {
			return '';
		}
		$dir = $parent;

		for ( $i = 0; $i < 8; $i++ ) {
			$candidates = array(
				$dir . '/wp-load.php',
				$dir . '/.wordpress/wp-load.php',
				$dir . '/wp/wp-load.php',
			);
			foreach ( $candidates as $candidate ) {
				if ( is_file( $candidate ) && is_readable( $candidate ) ) {
					return $candidate;
				}
			}
			$parent = dirname( $dir );
			if ( $parent === $dir ) {
				break;
			}
			$dir = $parent;
		}

		return '';
	}
}

if ( defined( 'ARTPAL_IPN_LIBRARY' ) && ARTPAL_IPN_LIBRARY ) {
	return;
}

if ( ! defined( 'WP_USE_THEMES' ) ) {
	define( 'WP_USE_THEMES', false );
}

$artpal_ipn_raw = file_get_contents( 'php://input' );
if ( ! is_string( $artpal_ipn_raw ) ) {
	$artpal_ipn_raw = '';
}

$artpal_wp_load = artpal_find_wp_load();
if ( $artpal_wp_load === '' ) {
	header( 'HTTP/1.1 500 Internal Server Error' );
	echo 'WordPress bootstrap not found.';
	exit;
}

require $artpal_wp_load;

if ( ! function_exists( 'artpal_handle_paypal_ipn' ) ) {
	if ( function_exists( 'status_header' ) ) {
		status_header( 500 );
	} else {
		header( 'HTTP/1.1 500 Internal Server Error' );
	}
	echo 'ArtPal is not active.';
	exit;
}

artpal_handle_paypal_ipn( $artpal_ipn_raw );
