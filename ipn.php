<?php
/**
 * PayPal Instant Payment Notification endpoint.
 *
 * notify_url is plugins_url( 'ipn.php' ), so PayPal posts here even when the
 * plugin directory is not named artpal. Every request is logged; see
 * ipn.php?artpal_diag=1.
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

// Flywheel/Fastly will cache a bare 200 from this URL and can replay that
// empty response to PayPal's POST, so the category never changes.
header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );
header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
header( 'X-Robots-Tag: noindex, nofollow' );

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

if ( function_exists( 'nocache_headers' ) ) {
	nocache_headers();
}

// Diagnostic view. Shows the installed version, the notify_url the buy button
// sends to PayPal, the last result, and the last 25 requests to this script.
if ( isset( $_GET['artpal_diag'] ) && (string) $_GET['artpal_diag'] === '1' ) {
	header( 'Content-Type: application/json; charset=utf-8' );
	$artpal_diag = array(
		'version'    => function_exists( 'artpal_version' ) ? artpal_version() : '',
		'notify_url' => function_exists( 'ipn_page_url' ) ? ipn_page_url() : '',
		'last'       => get_option( 'artpal_ipn_last', array() ),
		'requests'   => get_option( 'artpal_ipn_log', array() ),
	);
	echo function_exists( 'wp_json_encode' ) ? wp_json_encode( $artpal_diag, JSON_PRETTY_PRINT ) : json_encode( $artpal_diag, JSON_PRETTY_PRINT );
	exit;
}

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
