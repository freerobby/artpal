<?php
/**
 * PayPal Instant Payment Notification endpoint.
 *
 * This file stays in the plugin root so existing notify_url values keep working:
 * {site}/wp-content/plugins/artpal/ipn.php
 *
 * Verification is an HTTPS POST back to PayPal with cmd=_notify-validate.
 */

define( 'WP_USE_THEMES', false );

$artpal_wp_load = dirname( __FILE__ ) . '/../../../wp-load.php';
if ( ! is_readable( $artpal_wp_load ) ) {
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

artpal_handle_paypal_ipn();
