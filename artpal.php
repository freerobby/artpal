<?php
/*
Plugin Name: ArtPal
Plugin URI: http://freerobby.com/artpal
Description: ArtPal allows artists to use WordPress to sell one-of-a-kind originals. When a piece sells, ArtPal stops showing a buy button, shows the sold HTML, and moves the post from the Available category to the Sold category.
Author: Robby Grossman
Version: 2.0.4
Requires at least: 6.0
Requires PHP: 7.4
Author URI: http://freerobby.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

////////////////////////////////////////////////////////////////////////////////
// Global Constant Declarations
////////////////////////////////////////////////////////////////////////////////
define( 'ds_ap_CFPRICE', 'artpal_price' );  // Custom Field Names
define( 'ds_ap_CFSHIPPING', 'artpal_shipping' );
define( 'ds_ap_TAGINSERT', '[artpal=insert]' ); // ArtPal Insert Button Tag
define( 'ARTPAL_LAST_SALE_META', '_artpal_last_sale' );
define( 'ARTPAL_PROCESSED_EVENTS_OPTION', 'ds_ap_processed_events' );

////////////////////////////////////////////////////////////////////////////////
// Wordpress Hook Declarations
////////////////////////////////////////////////////////////////////////////////

// Plugin activation — add_option only; never overwrites existing values.
register_activation_hook( __FILE__, 'ds_ap_install' );
// Plugin deactivation
register_deactivation_hook( __FILE__, 'ds_ap_uninstall' );
// Add our page to the administration menu
add_action( 'admin_menu', 'ds_ap_add_pages' );
// [artpal] and [artpal insert]. [artpal=insert] is not a valid shortcode tag on WP 4.4+.
add_action( 'init', 'artpal_register_shortcodes' );
// Buyer return hits the thank-you page from their browser. PayPal's own IPN
// POST has not been reaching this host, so the return is what marks the post sold.
add_action( 'init', 'artpal_maybe_handle_paypal_return', 1 );
// String-replace [artpal=insert] before do_shortcode (priority 11).
add_filter( 'the_content', 'ds_ap_parsecontent', 10 );
add_action( 'add_meta_boxes', 'artpal_register_metabox' );
add_action( 'save_post_post', 'artpal_save_metabox' );
add_action( 'cli_init', 'artpal_register_cli' );
require_once __DIR__ . '/artpal-options.php';

////////////////////////////////////////////////////////////////////////////////
// Plugin Options Definitions
////////////////////////////////////////////////////////////////////////////////

global $artpal_currencycodes;
// English spelling, ISO 4217 code, symbol
$artpal_currencycodes = array(
	array( 'Australian Dollar', 'AUD', '$' ),
	array( 'Canadian Dollar', 'CAD', '$' ),
	array( 'Swiss Franc', 'CHF', '&#8355;' ),
	array( 'Czech Koruna', 'CZK', 'K&#269;' ),
	array( 'Danish Krone', 'DKK' ),
	array( 'Euro', 'EUR', '&euro;' ),
	array( 'Pound Sterling', 'GBP', '&pound;' ),
	array( 'Hong Kong Dollar', 'HKD', '$' ),
	array( 'Hungarian Forint', 'HUF', 'Ft' ),
	array( 'Japanese Yen', 'JPY', '&yen;' ),
	array( 'Norwegian Krone', 'NOK', 'kr' ),
	array( 'New Zealand Dollar', 'NZD', '$' ),
	array( 'Polish Zloty', 'PLN', 'pln' ),
	array( 'Swedish Krona', 'SEK', 'kr' ),
	array( 'Singapore Dollar', 'SGD', '$' ),
	array( 'U.S. Dollar', 'USD', '$' ),
);

// Option keys
global $ds_ap_options_names;
$ds_ap_options_names = array(
	'ds_ap_unsoldcategory',
	'ds_ap_soldcategory',
	'ds_ap_taxrate',
	'ds_ap_paypalemail',
	'ds_ap_soldcode',
	'ds_ap_prebuttontext',
	'ds_ap_thankyoupage',
	'ds_ap_cancelpage',
	'ds_ap_paypalbutton',
	'ds_ap_discountpercent',
	'ds_ap_disableecommerce',
	'ds_ap_textifunknownmetadata',
	'ds_ap_saledisabledcategory',
	'ds_ap_textifsaledisabled',
	'ds_ap_currencycode4217',
	'ds_ap_currencysymbol',
	'ds_ap_usesandbox',
	'ds_ap_notify_email',
	'ds_ap_email_subject_prefix',
	'ds_ap_pdt_token',
);

global $ds_ap_options_vals;
$ds_ap_options_vals = array(
	null,
	null,
	'0.00',
	stripslashes( 'ValidPaypalEmail@Goes.Here' ),
	stripslashes( '<b>Sold!</b>' ),
	stripslashes( '_PRICE_ via PayPal, _SHIPPING_ shipping within US' ),
	null,
	null,
	'',
	'0',
	'0',
	stripslashes( 'Please contact me if you are interested in purchasing this piece.' ),
	'-1',
	stripslashes( 'Sorry, this item is not currently available for sale. Please check back later.' ),
	$artpal_currencycodes[15][1], // USD
	$artpal_currencycodes[15][2], // $
	'0',
	'', // ds_ap_notify_email — empty means fall back to PayPal email, then admin_email
	'', // ds_ap_email_subject_prefix — empty means the site title
	'', // ds_ap_pdt_token — Payment Data Transfer identity token, optional
);

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function get_paypal_domain() {
	if ( get_option( 'ds_ap_usesandbox' ) == true ) {
		return 'www.sandbox.paypal.com';
	}
	return 'www.paypal.com';
}

function ipn_page_url() {
	// Query string keeps this off a cached empty response for the bare ipn.php URL.
	return plugins_url( 'ipn.php', __FILE__ ) . '?artpal-ipn=1';
}

// Define our configuration pages
function ds_ap_add_pages() {
	add_options_page( 'ArtPal', 'ArtPal', 'manage_options', 'artpal', 'ds_ap_options_page' );
	add_management_page( 'ArtPal Items', 'ArtPal Items', 'edit_posts', 'artpal-items', 'ds_ap_manage_page' );
}

function artpal_register_shortcodes() {
	// WordPress rejects shortcode names that contain "=" (WP 4.4+).
	// Classic posts use the the_content fallback for the literal [artpal=insert].
	add_shortcode( 'artpal', 'artpal_shortcode' );
}

/**
 * Shortcode callback for [artpal=insert], [artpal], and [artpal insert].
 *
 * @param array|string $atts    Shortcode attributes (ignored; inventory comes from the current post).
 * @param string|null  $content Enclosed content (unused).
 * @param string       $tag     Matched shortcode tag.
 * @return string
 */
function artpal_shortcode( $atts = array(), $content = null, $tag = '' ) {
	unset( $atts, $content, $tag );
	return artpal_render_buy_now();
}

////////////////////////////////////////////////////////////////////////////////
// Inventory API
////////////////////////////////////////////////////////////////////////////////

/**
 * Category term IDs assigned to a post.
 *
 * @param int $post_id Post ID (WordPress item number).
 * @return int[]
 */
function artpal_get_category_ids( $post_id ) {
	$cats = wp_get_post_categories( (int) $post_id );
	if ( ! is_array( $cats ) ) {
		return array();
	}
	return array_map( 'intval', $cats );
}

/**
 * True if the post has the Sold category. Sold wins over Available.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function artpal_is_sold( $post_id ) {
	$sold = (int) get_option( 'ds_ap_soldcategory' );
	if ( $sold <= 0 ) {
		return false;
	}
	return in_array( $sold, artpal_get_category_ids( $post_id ), true );
}

/**
 * True if the post has Available and does not have Sold.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function artpal_is_available( $post_id ) {
	if ( artpal_is_sold( $post_id ) ) {
		return false;
	}
	$available = (int) get_option( 'ds_ap_unsoldcategory' );
	if ( $available <= 0 ) {
		return false;
	}
	return in_array( $available, artpal_get_category_ids( $post_id ), true );
}

/**
 * True if the post has the Not Currently Available (sale-disabled) category.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function artpal_is_sale_disabled( $post_id ) {
	$disabled = (int) get_option( 'ds_ap_saledisabledcategory' );
	if ( $disabled <= 0 ) {
		return false;
	}
	return in_array( $disabled, artpal_get_category_ids( $post_id ), true );
}

/**
 * Unit price from artpal_price, or null if missing.
 *
 * @param int $post_id Post ID.
 * @return float|null
 */
function artpal_get_price( $post_id ) {
	$raw = get_post_meta( (int) $post_id, ds_ap_CFPRICE, true );
	if ( $raw === '' || $raw === null || false === $raw ) {
		return null;
	}
	return (float) $raw;
}

/**
 * Shipping from artpal_shipping. Missing meta is 0 (free).
 *
 * @param int $post_id Post ID.
 * @return float
 */
function artpal_get_shipping( $post_id ) {
	$raw = get_post_meta( (int) $post_id, ds_ap_CFSHIPPING, true );
	if ( $raw === '' || $raw === null || false === $raw ) {
		return 0.0;
	}
	return (float) $raw;
}

/**
 * Unit price after the storewide ds_ap_discountpercent. Null if price meta is missing.
 *
 * @param int $post_id Post ID.
 * @return float|null
 */
function artpal_effective_price( $post_id ) {
	$price = artpal_get_price( $post_id );
	if ( $price === null ) {
		return null;
	}
	$discount = (float) get_option( 'ds_ap_discountpercent' );
	if ( $discount <= 0 ) {
		return round( $price, 2 );
	}
	return round( $price - ( $price * ( $discount / 100 ) ), 2 );
}

/**
 * Move post from Available → Sold.
 *
 * Idempotent. Safe to call twice. Safe if already Sold.
 * Removes Available and appends Sold so Landscape / size / location categories remain.
 *
 * @param int   $post_id Post ID (item number).
 * @param array $context Sale context stored in _artpal_last_sale (not used for rendering).
 * @return bool True if the post is Sold when the function returns.
 */
function artpal_mark_sold( $post_id, $context = array() ) {
	$post_id = (int) $post_id;
	$context = is_array( $context ) ? $context : array();

	if ( $post_id <= 0 || ! get_post( $post_id ) ) {
		error_log( 'ArtPal: artpal_mark_sold: post ' . $post_id . ' does not exist.' );
		return false;
	}

	$event_id = isset( $context['event_id'] ) ? (string) $context['event_id'] : '';
	if ( $event_id !== '' ) {
		$existing = artpal_event_post_id( $event_id );
		if ( $existing && $existing !== $post_id ) {
			error_log( 'ArtPal: event ' . $event_id . ' already applied to post ' . $existing . ', refusing to mark ' . $post_id . '.' );
			return artpal_is_sold( $post_id );
		}
	}

	if ( artpal_is_sold( $post_id ) ) {
		artpal_store_sale_context_if_new( $post_id, $context );
		if ( $event_id !== '' ) {
			artpal_remember_event( $event_id, $post_id );
		}
		return true;
	}

	$available_term_id = (int) get_option( 'ds_ap_unsoldcategory' );
	$sold_term_id      = (int) get_option( 'ds_ap_soldcategory' );

	if ( $sold_term_id <= 0 ) {
		error_log( 'ArtPal: artpal_mark_sold: ds_ap_soldcategory is not set.' );
		return false;
	}

	if ( $available_term_id > 0 ) {
		wp_remove_object_terms( $post_id, $available_term_id, 'category' );
	}

	// Append Sold. Do not replace the whole category set.
	wp_set_object_terms( $post_id, array( $sold_term_id ), 'category', true );

	clean_post_cache( $post_id );
	clean_object_term_cache( $post_id, 'category' );
	if ( function_exists( 'clean_term_cache' ) ) {
		$to_clean = array( $sold_term_id );
		if ( $available_term_id > 0 ) {
			$to_clean[] = $available_term_id;
		}
		clean_term_cache( $to_clean, 'category' );
	}
	if ( defined( 'WP_CACHE' ) && WP_CACHE && function_exists( 'wp_cache_no_postid' ) ) {
		wp_cache_no_postid( $post_id );
	}
	if ( function_exists( 'wp_cache_post_change' ) ) {
		wp_cache_post_change( $post_id );
	}

	if ( empty( $context['sold_at'] ) ) {
		$context['sold_at'] = gmdate( 'c' );
	}
	update_post_meta( $post_id, ARTPAL_LAST_SALE_META, $context );

	if ( $event_id !== '' ) {
		artpal_remember_event( $event_id, $post_id );
	}

	do_action( 'artpal_marked_sold', $post_id, $context );

	if ( empty( $context['skip_email'] ) ) {
		artpal_send_sold_email( $post_id, $context );
	}

	return artpal_is_sold( $post_id );
}

/**
 * @param string $event_id Processor event id (PayPal txn_id).
 * @return int 0 if unseen.
 */
function artpal_event_post_id( $event_id ) {
	$events = get_option( ARTPAL_PROCESSED_EVENTS_OPTION, array() );
	if ( ! is_array( $events ) || $event_id === '' || ! isset( $events[ $event_id ] ) ) {
		return 0;
	}
	return (int) $events[ $event_id ];
}

/**
 * Remember a processor event so the same txn cannot mark two posts.
 *
 * @param string $event_id Event id.
 * @param int    $post_id  Post that consumed it.
 * @return void
 */
function artpal_remember_event( $event_id, $post_id ) {
	$events = get_option( ARTPAL_PROCESSED_EVENTS_OPTION, array() );
	if ( ! is_array( $events ) ) {
		$events = array();
	}
	if ( isset( $events[ $event_id ] ) && (int) $events[ $event_id ] === (int) $post_id ) {
		return;
	}
	$events[ $event_id ] = (int) $post_id;
	update_option( ARTPAL_PROCESSED_EVENTS_OPTION, $events, false );
}

/**
 * Write _artpal_last_sale when this is a new event_id (or first context).
 *
 * @param int   $post_id Post ID.
 * @param array $context Sale context.
 * @return void
 */
function artpal_store_sale_context_if_new( $post_id, $context ) {
	if ( empty( $context ) ) {
		return;
	}
	$event_id = isset( $context['event_id'] ) ? (string) $context['event_id'] : '';
	$last     = get_post_meta( $post_id, ARTPAL_LAST_SALE_META, true );
	if ( is_array( $last ) && $event_id !== '' && isset( $last['event_id'] ) && (string) $last['event_id'] === $event_id ) {
		return;
	}
	if ( empty( $context['sold_at'] ) ) {
		$context['sold_at'] = gmdate( 'c' );
	}
	update_post_meta( $post_id, ARTPAL_LAST_SALE_META, $context );
}

/**
 * Email the artist once on the first successful Available → Sold transition.
 *
 * @param int   $post_id Post ID.
 * @param array $context Sale context.
 * @return void
 */
function artpal_send_sold_email( $post_id, $context ) {
	$title   = get_the_title( $post_id );
	$public  = get_permalink( $post_id );
	$edit    = get_edit_post_link( $post_id, 'raw' );
	$amount  = isset( $context['amount_total'] ) ? $context['amount_total'] : artpal_effective_price( $post_id );
	$ship    = artpal_get_shipping( $post_id );
	$buyer   = isset( $context['buyer_email'] ) ? $context['buyer_email'] : '';
	$proc    = isset( $context['processor'] ) ? $context['processor'] : '';
	$event   = isset( $context['event_id'] ) ? $context['event_id'] : '';

	$body  = "A painting sold.\n\n";
	$body .= 'Title: ' . $title . "\n";
	$body .= 'Edit: ' . $edit . "\n";
	$body .= 'Public: ' . $public . "\n";
	$body .= 'Amount: ' . $amount . "\n";
	$body .= 'Shipping: ' . $ship . "\n";
	$body .= 'Buyer email: ' . $buyer . "\n";
	$body .= 'Processor: ' . $proc . "\n";
	$body .= 'Event id: ' . $event . "\n\n";
	$body .= "Category is now Sold; the buy button is off.\n";

	$prefix = artpal_sold_subject_prefix();
	$title  = str_replace( array( "\r", "\n" ), '', (string) $title );
	$subject = '[' . $prefix . '] Sold: ' . $title;

	$recipients = artpal_sold_email_recipients();
	if ( empty( $recipients ) ) {
		return;
	}

	wp_mail( $recipients, $subject, $body );
}

/**
 * Subject prefix from ds_ap_email_subject_prefix, otherwise the site title.
 *
 * @return string
 */
function artpal_sold_subject_prefix() {
	$prefix = trim( (string) get_option( 'ds_ap_email_subject_prefix' ) );
	$prefix = str_replace( array( "\r", "\n" ), '', $prefix );
	if ( $prefix === '' ) {
		$prefix = str_replace( array( "\r", "\n" ), '', trim( (string) get_bloginfo( 'name' ) ) );
	}
	return $prefix;
}

/**
 * Sold notice recipients.
 *
 * Primary: ds_ap_notify_email, else ds_ap_paypalemail, else admin_email.
 * admin_email is also included when it is different from the primary address.
 *
 * @return string[]
 */
function artpal_sold_email_recipients() {
	$notify = trim( (string) get_option( 'ds_ap_notify_email' ) );
	$paypal = trim( (string) get_option( 'ds_ap_paypalemail' ) );
	$admin  = trim( (string) get_option( 'admin_email' ) );

	$primary = '';
	if ( $notify !== '' && is_email( $notify ) ) {
		$primary = $notify;
	} elseif ( $paypal !== '' && is_email( $paypal ) ) {
		$primary = $paypal;
	} elseif ( $admin !== '' && is_email( $admin ) ) {
		$primary = $admin;
	}

	$recipients = array();
	if ( $primary !== '' ) {
		$recipients[] = $primary;
	}
	if ( $admin !== '' && is_email( $admin ) ) {
		$seen = false;
		foreach ( $recipients as $have ) {
			if ( strtolower( $have ) === strtolower( $admin ) ) {
				$seen = true;
				break;
			}
		}
		if ( ! $seen ) {
			$recipients[] = $admin;
		}
	}
	return $recipients;
}

////////////////////////////////////////////////////////////////////////////////
// Renderer
////////////////////////////////////////////////////////////////////////////////

/**
 * Resolve the post being rendered.
 *
 * @param int $post_id Optional explicit ID.
 * @return int
 */
function artpal_current_post_id( $post_id = 0 ) {
	if ( $post_id ) {
		return (int) $post_id;
	}
	$qid = get_the_ID();
	if ( $qid ) {
		return (int) $qid;
	}
	global $id, $post;
	if ( ! empty( $id ) ) {
		return (int) $id;
	}
	if ( $post && isset( $post->ID ) ) {
		return (int) $post->ID;
	}
	return 0;
}

/**
 * Same decision tree as ds_ap_constructbuynow:
 * hidden / sold HTML / disabled text / missing-price text / prebuttontext + buy control.
 *
 * @param int $post_id Optional post ID. Defaults to the current post.
 * @return string
 */
function artpal_render_buy_now( $post_id = 0 ) {
	$post_id = artpal_current_post_id( $post_id );
	if ( $post_id <= 0 ) {
		return '';
	}

	$in_available = in_array( (int) get_option( 'ds_ap_unsoldcategory' ), artpal_get_category_ids( $post_id ), true );
	$in_sold      = artpal_is_sold( $post_id );

	// Hidden from ArtPal unless the post is in Available and/or Sold.
	if ( ! $in_available && ! $in_sold ) {
		return '';
	}

	if ( $in_sold ) {
		return ds_ap_buynow_sold();
	}

	if ( artpal_is_sale_disabled( $post_id ) ) {
		return stripslashes( htmlspecialchars_decode( (string) get_option( 'ds_ap_textifsaledisabled' ) ) );
	}

	return ds_ap_buynow_button_for_post( $post_id );
}

/**
 * Price text + PayPal buy control, or the missing-price inquire text.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ds_ap_buynow_button_for_post( $post_id ) {
	$regularprice = artpal_get_price( $post_id );
	if ( $regularprice === null ) {
		return stripslashes( htmlspecialchars_decode( (string) get_option( 'ds_ap_textifunknownmetadata' ) ) );
	}

	$price    = artpal_effective_price( $post_id );
	$shipping = get_post_meta( $post_id, ds_ap_CFSHIPPING, true );
	if ( $shipping === '' || $shipping === false ) {
		$shipping = 0;
	}
	$name = get_the_title( $post_id );

	return ds_ap_generatepaypalbutton(
		stripslashes( (string) get_option( 'ds_ap_paypalemail' ) ),
		$name,
		$post_id,
		number_format( (float) $price, 2, '.', '' ),
		$shipping,
		number_format( $regularprice, 2, '.', '' )
	);
}

function ds_ap_buynow_button() {
	global $id;
	return ds_ap_buynow_button_for_post( artpal_current_post_id( $id ) );
}

// Replace the buy-now tag with a button or "SOLD" text.
function ds_ap_buynow_sold() {
	return stripslashes( htmlspecialchars_decode( (string) get_option( 'ds_ap_soldcode' ) ) );
}

/**
 * Legacy wrapper. Inventory writes go through artpal_mark_sold() — no raw SQL.
 *
 * @param int $objid   Post ID.
 * @param int $old_tid Unused (Available is read from options).
 * @param int $new_tid Unused (Sold is read from options).
 * @return bool
 */
function ds_ap_change_taxonomy_of_object( $objid, $old_tid, $new_tid ) {
	unset( $old_tid, $new_tid );
	return artpal_mark_sold( (int) $objid );
}

// Figure out if this button has sold and act accordingly.
function ds_ap_constructbuynow() {
	return artpal_render_buy_now();
}

// Generate a PayPal button to purchase a particular item
function ds_ap_generatepaypalbutton( $selleremail, $itemname, $itemnumber, $price, $shipping, $regularprice = null ) {
	$cSymbol = get_option( 'ds_ap_currencysymbol' );
	if ( $regularprice == $price ) {
		$regularprice = null;
	}
	$pretext   = htmlspecialchars_decode( stripslashes( (string) get_option( 'ds_ap_prebuttontext' ) ) );
	$pricetext = $cSymbol . $price;
	// If regular price isn't the same as the current price, ...
	if ( $regularprice != null ) {
		// ... show the savings!
		$pricetext = '<del>' . $cSymbol . $regularprice . '</del> ' . $pricetext;
	}
	$pretext      = str_replace( '_PRICE_', $pricetext, $pretext );
	$shippingtext = $shipping;
	if ( $shippingtext == 0 ) {
		$shippingtext = 'free';
	} else {
		$shippingtext = $cSymbol . $shippingtext;
	}
	$pretext      = str_replace( '_SHIPPING_', $shippingtext, $pretext );
	$button_html  = $pretext . '<br />';
	// Don't create the PayPal button if ecommerce is disabled.
	if ( ! get_option( 'ds_ap_disableecommerce' ) ) {
		$button_html .= '<form method="post" action="https://' . get_paypal_domain() . '/cgi-bin/webscr" target="paypal">'
		. '<input type="hidden" name="cmd" value="_xclick">'
		. '<input type="hidden" name="business" value="' . $selleremail . '">' // email account to send money to
		. '<input type="hidden" name="item_name" value="' . $itemname . '">' // name of item to appear at checkout
		. '<input type="hidden" name="item_number" value="' . $itemnumber . '">' // item number = WordPress post ID
		//	. '<input type="hidden" name="invoice" value="' . $itemnumber . '">' // invoice # mandated unique by paypal
		// be careful! if you uncomment the above line, you can't "reset" sold
		// paintings to make them available again!
		. '<input type="hidden" name="amount" value="' . $price . '">' // price of item
		. '<input type="hidden" name="tax" value="' . round( ( $price * ( get_option( 'ds_ap_taxrate' ) / 100 ) ), 2 ) . '">' // price of item
		. '<input type="hidden" name="currency_code" value="' . get_option( 'ds_ap_currencycode4217' ) . '">' // us dollars only
		. '<input type="hidden" name="quantity" value="1">' // default 1 item
		. '<input type="hidden" name="shipping" value="' . $shipping . '">' // shipping price of item
		. '<input type="hidden" name="notify_url" value="' . esc_url( ipn_page_url() ) . '">'
		. '<input type="hidden" name="rm" value="2">'
		. '<input type="hidden" name="return" value="' . get_option( 'ds_ap_thankyoupage' ) . '">'
		. '<input type="hidden" name="cancel_return" value="' . get_option( 'ds_ap_cancelpage' ) . '">'
		. '<input type="image" name="add" src="' . esc_url( artpal_paypal_button_url() ) . '">' // button graphic
		. '</form>';
	}
	return $button_html;
}

function ds_ap_install() {
	// Add our options with their default values as defined.
	// add_option() does not overwrite values that already exist.
	global $ds_ap_options_names;
	global $ds_ap_options_vals;
	$num_opts = count( $ds_ap_options_names );
	for ( $i = 0; $i < $num_opts; $i++ ) {
		add_option( $ds_ap_options_names[ $i ], $ds_ap_options_vals[ $i ] );
	}
}

function ds_ap_manage_page() {
	include __DIR__ . '/artpal-manage.php';
}

function ds_ap_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to manage ArtPal settings.' );
	}
	artpal_render_options_page();
}

/**
 * Fallback: string-replace raw [artpal=insert].
 *
 * A shortcode name containing "=" is rejected on WordPress 4.4+.
 * This runs on the_content at priority 10, before do_shortcode at 11.
 *
 * @param string $content Post content.
 * @return string
 */
function ds_ap_parsecontent( $content ) {
	if ( ! is_string( $content ) || false === strpos( $content, ds_ap_TAGINSERT ) ) {
		return $content;
	}
	return str_replace( ds_ap_TAGINSERT, artpal_render_buy_now(), $content );
}

function ds_ap_uninstall() {
	// Do not delete options on deactivation. Live category IDs and copy must survive.
}

////////////////////////////////////////////////////////////////////////////////
// Post metabox (posts only)
////////////////////////////////////////////////////////////////////////////////

/**
 * Read-only inventory label for the metabox.
 *
 * Sold wins. Then Disabled, then Available, otherwise not an ArtPal item.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function artpal_inventory_label( $post_id ) {
	if ( artpal_is_sold( $post_id ) ) {
		return 'Sold';
	}
	if ( artpal_is_sale_disabled( $post_id ) ) {
		return 'Disabled';
	}
	if ( artpal_is_available( $post_id ) ) {
		return 'Available';
	}
	return 'Not an ArtPal item';
}

/**
 * Normalize a price or shipping field.
 *
 * @param mixed $raw Raw form value.
 * @return string|null|false Decimal string, null if empty (delete meta), false if not numeric.
 */
function artpal_normalize_meta_amount( $raw ) {
	$raw = trim( str_replace( array( '$', ',', ' ' ), '', (string) $raw ) );
	if ( $raw === '' ) {
		return null;
	}
	if ( ! is_numeric( $raw ) ) {
		return false;
	}
	return number_format( (float) $raw, 2, '.', '' );
}

/**
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param mixed  $raw     Raw form value.
 * @return void
 */
function artpal_write_amount_meta( $post_id, $key, $raw ) {
	$norm = artpal_normalize_meta_amount( $raw );
	if ( null === $norm ) {
		delete_post_meta( $post_id, $key );
		return;
	}
	if ( false === $norm ) {
		return;
	}
	update_post_meta( $post_id, $key, $norm );
}

function artpal_register_metabox() {
	add_meta_box(
		'artpal_sale',
		'ArtPal',
		'artpal_render_metabox',
		'post',
		'side',
		'high'
	);
}

/**
 * @param WP_Post $post Post object.
 * @return void
 */
function artpal_render_metabox( $post ) {
	wp_nonce_field( 'artpal_save_meta', 'artpal_meta_nonce' );
	$price    = get_post_meta( $post->ID, ds_ap_CFPRICE, true );
	$shipping = get_post_meta( $post->ID, ds_ap_CFSHIPPING, true );
	$label    = artpal_inventory_label( $post->ID );

	echo '<p><label for="artpal_price"><strong>Price</strong></label><br />';
	echo '<input type="number" step="0.01" min="0" class="widefat" name="artpal_price" id="artpal_price" value="' . esc_attr( $price ) . '" />';
	echo '<br /><span class="description">Leave blank when the price is not set. The inquire text is shown instead of a buy button.</span></p>';

	echo '<p><label for="artpal_shipping"><strong>Shipping</strong></label><br />';
	echo '<input type="number" step="0.01" min="0" class="widefat" name="artpal_shipping" id="artpal_shipping" value="' . esc_attr( $shipping ) . '" /></p>';

	echo '<p><strong>Status:</strong> ' . esc_html( $label ) . '</p>';

	if ( 'Sold' === $label ) {
		$last = get_post_meta( $post->ID, ARTPAL_LAST_SALE_META, true );
		if ( is_array( $last ) ) {
			$proc  = isset( $last['processor'] ) ? (string) $last['processor'] : '';
			$event = isset( $last['event_id'] ) ? (string) $last['event_id'] : '';
			echo '<p><strong>Processor:</strong> ' . esc_html( $proc ) . '<br />';
			echo '<strong>Event id:</strong> ' . esc_html( $event ) . '</p>';
		}
	}
}

/**
 * Save price and shipping from the post metabox.
 *
 * Empty price deletes artpal_price so the inquire path stays available.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function artpal_save_metabox( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['artpal_meta_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['artpal_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'artpal_save_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( get_post_type( $post_id ) !== 'post' ) {
		return;
	}

	if ( isset( $_POST['artpal_price'] ) ) {
		artpal_write_amount_meta( $post_id, ds_ap_CFPRICE, wp_unslash( $_POST['artpal_price'] ) );
	}
	if ( isset( $_POST['artpal_shipping'] ) ) {
		artpal_write_amount_meta( $post_id, ds_ap_CFSHIPPING, wp_unslash( $_POST['artpal_shipping'] ) );
	}
}

////////////////////////////////////////////////////////////////////////////////
// PayPal IPN
////////////////////////////////////////////////////////////////////////////////

/**
 * PayPal IPN verification endpoint. Sandbox when ds_ap_usesandbox is on.
 *
 * Checkout buttons still post to www.paypal.com. Verification has to go to
 * the ipnpb host from PayPal's IPN listener protocol.
 *
 * @return string
 */
function artpal_paypal_webscr_url() {
	if ( get_option( 'ds_ap_usesandbox' ) == true ) {
		return 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
	}
	return 'https://ipnpb.paypal.com/cgi-bin/webscr';
}

/**
 * POST the IPN payload back to PayPal over HTTPS for _notify-validate.
 *
 * Tests may set $GLOBALS['artpal_test']['ipn_postback'] to skip the network.
 *
 * @param string $request_body application/x-www-form-urlencoded body including cmd=_notify-validate.
 * @return string|WP_Error VERIFIED, INVALID, or an error.
 */
function artpal_paypal_ipn_postback( $request_body ) {
	if ( isset( $GLOBALS['artpal_test'] ) && is_array( $GLOBALS['artpal_test'] ) && array_key_exists( 'ipn_postback', $GLOBALS['artpal_test'] ) ) {
		$GLOBALS['artpal_test']['ipn_request'] = $request_body;
		if ( ! isset( $GLOBALS['artpal_test']['ipn_requests'] ) || ! is_array( $GLOBALS['artpal_test']['ipn_requests'] ) ) {
			$GLOBALS['artpal_test']['ipn_requests'] = array();
		}
		$GLOBALS['artpal_test']['ipn_requests'][] = $request_body;
		return $GLOBALS['artpal_test']['ipn_postback'];
	}

	$response = wp_remote_post(
		artpal_paypal_webscr_url(),
		array(
			'timeout'     => 45,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'headers'     => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Connection'   => 'Close',
				'User-Agent'   => 'ArtPal/' . artpal_version(),
			),
			'body'        => $request_body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'artpal_ipn_http', 'PayPal IPN verification returned HTTP ' . $code );
	}

	return trim( (string) wp_remote_retrieve_body( $response ) );
}

/**
 * Plugin header version, used as the IPN verification User-Agent.
 *
 * @return string
 */
function artpal_version() {
	if ( ! function_exists( 'get_file_data' ) ) {
		return '2.0.4';
	}
	$data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	if ( ! empty( $data['Version'] ) ) {
		return (string) $data['Version'];
	}
	return '2.0.4';
}

/**
 * True when PayPal's verification body is the single word VERIFIED.
 *
 * @param mixed $body Response body.
 * @return bool
 */
function artpal_ipn_body_is_verified( $body ) {
	$body = trim( (string) $body );
	$body = preg_replace( '/^\xEF\xBB\xBF/', '', $body );
	return strcasecmp( trim( (string) $body ), 'VERIFIED' ) === 0;
}

/**
 * Parse an application/x-www-form-urlencoded body. "+" is a space.
 *
 * @param string $raw Raw body.
 * @return array<string,string>
 */
function artpal_ipn_parse_raw( $raw ) {
	$fields = array();
	if ( ! is_string( $raw ) || $raw === '' || strpos( $raw, '=' ) === false ) {
		return $fields;
	}
	foreach ( explode( '&', $raw ) as $pair ) {
		if ( $pair === '' ) {
			continue;
		}
		$parts = explode( '=', $pair, 2 );
		$key   = urldecode( str_replace( '+', ' ', $parts[0] ) );
		$value = isset( $parts[1] ) ? str_replace( '+', ' ', $parts[1] ) : '';
		$fields[ $key ] = urldecode( $value );
	}
	return $fields;
}

/**
 * Rebuild a form body from already-decoded fields. Spaces become "+".
 *
 * @param array $fields Field map.
 * @return string
 */
function artpal_ipn_encode_fields( $fields ) {
	$parts = array();
	foreach ( $fields as $key => $value ) {
		if ( is_array( $value ) ) {
			continue;
		}
		$parts[] = urlencode( (string) $key ) . '=' . urlencode( (string) $value );
	}
	return implode( '&', $parts );
}

/**
 * PayPal account emails on this notification.
 *
 * receiver_email is the account's primary address. business is the address
 * on the button, which is what ArtPal stores. Either one is this seller.
 *
 * @param array $fields IPN fields.
 * @return bool
 */
function artpal_ipn_email_matches( $fields ) {
	$expected = strtolower( trim( html_entity_decode( (string) get_option( 'ds_ap_paypalemail' ), ENT_QUOTES ) ) );
	if ( $expected === '' ) {
		return false;
	}
	foreach ( array( 'receiver_email', 'business' ) as $key ) {
		if ( empty( $fields[ $key ] ) ) {
			continue;
		}
		$candidate = strtolower( trim( html_entity_decode( (string) $fields[ $key ], ENT_QUOTES ) ) );
		if ( $candidate === $expected ) {
			return true;
		}
	}
	return false;
}

/**
 * Remember the last IPN outcome without the buyer's address or email.
 *
 * Read it at ipn.php?artpal_diag=1 after a notification.
 *
 * @param string $code   Result code.
 * @param array  $fields IPN fields.
 * @param string $detail Short verification note.
 * @return void
 */
function artpal_ipn_remember_result( $code, $fields, $detail = '' ) {
	$fields = is_array( $fields ) ? $fields : array();
	update_option(
		'artpal_ipn_last',
		array(
			'at'              => gmdate( 'c' ),
			'result'          => (string) $code,
			'payment_status'  => isset( $fields['payment_status'] ) ? (string) $fields['payment_status'] : '',
			'item_number'     => isset( $fields['item_number'] ) ? (string) $fields['item_number'] : '',
			'txn_id'          => isset( $fields['txn_id'] ) ? (string) $fields['txn_id'] : '',
			'receiver_email'  => isset( $fields['receiver_email'] ) ? (string) $fields['receiver_email'] : '',
			'business'        => isset( $fields['business'] ) ? (string) $fields['business'] : '',
			'expected_email'  => (string) get_option( 'ds_ap_paypalemail' ),
			'detail'          => substr( preg_replace( '/\s+/', ' ', (string) $detail ), 0, 180 ),
		),
		false
	);
}

/**
 * Bodies to post back, in the order PayPal is most likely to VERIFIED.
 *
 * The original bytes go first. A rebuilt body covers a raw stream that was
 * empty or already decoded. cmd is tried at the front and at the end.
 *
 * @param string $raw    Original body.
 * @param array  $fields Decoded fields.
 * @return string[]
 */
function artpal_ipn_validation_bodies( $raw, $fields ) {
	$bodies = array();
	$raw    = is_string( $raw ) ? $raw : '';
	if ( $raw !== '' && strpos( $raw, '=' ) !== false ) {
		$bodies[] = 'cmd=_notify-validate&' . $raw;
		$bodies[] = $raw . '&cmd=_notify-validate';
	}
	$encoded = artpal_ipn_encode_fields( $fields );
	if ( $encoded !== '' ) {
		$bodies[] = 'cmd=_notify-validate&' . $encoded;
		$bodies[] = $encoded . '&cmd=_notify-validate';
	}
	return array_values( array_unique( $bodies ) );
}

/**
 * Entry from ipn.php. Uses the raw body for verification and field checks.
 *
 * ipn.php reads php://input before WordPress boots and passes that string in.
 * Reading it again here would see an empty stream.
 *
 * @param string|null $raw Original request body, when the front controller already read it.
 * @return void
 */
function artpal_handle_paypal_ipn( $raw = null ) {
	if ( function_exists( 'nocache_headers' ) ) {
		nocache_headers();
	}
	if ( ! is_string( $raw ) ) {
		$raw = file_get_contents( 'php://input' );
	}
	if ( ! is_string( $raw ) ) {
		$raw = '';
	}
	$post = ( isset( $_POST ) && is_array( $_POST ) ) ? wp_unslash( $_POST ) : array();
	$result = artpal_process_paypal_ipn( $post, $raw );
	if ( 'transport_error' === $result ) {
		status_header( 500 );
		return;
	}
	status_header( 200 );
}

/**
 * Verify a PayPal IPN and mark the post sold when the checks pass.
 *
 * @param array  $post Parsed IPN fields.
 * @param string $raw  Raw request body, when available.
 * @return string Result code: empty, transport_error, not_verified, not_completed, email_mismatch, missing_post, missing_txn, sold.
 */
function artpal_process_paypal_ipn( $post, $raw = '' ) {
	$post = is_array( $post ) ? $post : array();
	$raw  = is_string( $raw ) ? $raw : '';

	if ( $raw === '' && empty( $post ) ) {
		return 'empty';
	}

	// The raw body is what PayPal signed. $_POST can be empty or missing keys
	// after the stream is read, so decoded raw fields win when they exist.
	$fields = $post;
	$from_raw = artpal_ipn_parse_raw( $raw );
	if ( ! empty( $from_raw ) ) {
		$fields = array_merge( $fields, $from_raw );
	}

	$bodies = artpal_ipn_validation_bodies( $raw, $fields );
	$saw_http = false;
	$saw_transport = false;
	$verified_ok = false;
	$detail = '';
	foreach ( $bodies as $body ) {
		$verified = artpal_paypal_ipn_postback( $body );
		if ( is_object( $verified ) ) {
			$saw_transport = true;
			$detail = 'transport';
			continue;
		}
		$saw_http = true;
		$detail = trim( (string) $verified );
		if ( artpal_ipn_body_is_verified( $verified ) ) {
			$verified_ok = true;
			break;
		}
	}

	if ( ! $verified_ok ) {
		if ( ! $saw_http && $saw_transport ) {
			error_log( 'ArtPal IPN: PayPal verification request failed.' );
			artpal_ipn_remember_result( 'transport_error', $fields, $detail );
			return 'transport_error';
		}
		error_log( 'ArtPal IPN: payload was not VERIFIED.' );
		artpal_ipn_remember_result( 'not_verified', $fields, $detail );
		return 'not_verified';
	}

	return artpal_apply_verified_paypal_payment( $fields, 'VERIFIED' );
}

/**
 * Mark the post sold from fields PayPal has already verified.
 *
 * @param array  $fields IPN or PDT fields.
 * @param string $detail Note stored with the last result.
 * @return string Result code.
 */
function artpal_apply_verified_paypal_payment( $fields, $detail = 'VERIFIED' ) {
	$fields = is_array( $fields ) ? $fields : array();

	$status = isset( $fields['payment_status'] ) ? trim( (string) $fields['payment_status'] ) : '';
	if ( strcasecmp( $status, 'Completed' ) !== 0 ) {
		error_log( 'ArtPal IPN: payment_status is not Completed.' );
		artpal_ipn_remember_result( 'not_completed', $fields, $status );
		return 'not_completed';
	}

	if ( ! artpal_ipn_email_matches( $fields ) ) {
		error_log( 'ArtPal IPN: receiver_email and business do not match the PayPal email option.' );
		artpal_ipn_remember_result( 'email_mismatch', $fields, '' );
		return 'email_mismatch';
	}

	$item = isset( $fields['item_number'] ) ? trim( (string) $fields['item_number'] ) : '';
	if ( $item === '' || ! ctype_digit( $item ) || ! get_post( (int) $item ) ) {
		error_log( 'ArtPal IPN: item_number is not an existing post ID.' );
		artpal_ipn_remember_result( 'missing_post', $fields, $item );
		return 'missing_post';
	}

	$txn = isset( $fields['txn_id'] ) ? trim( (string) $fields['txn_id'] ) : '';
	if ( $txn === '' ) {
		error_log( 'ArtPal IPN: txn_id is missing.' );
		artpal_ipn_remember_result( 'missing_txn', $fields, '' );
		return 'missing_txn';
	}

	$marked = artpal_mark_sold(
		(int) $item,
		array(
			'processor'    => 'paypal',
			'event_id'     => $txn,
			'buyer_email'  => isset( $fields['payer_email'] ) ? (string) $fields['payer_email'] : '',
			'amount_total' => isset( $fields['mc_gross'] ) ? (string) $fields['mc_gross'] : '',
			'currency'     => isset( $fields['mc_currency'] ) ? (string) $fields['mc_currency'] : '',
			'raw_ref'      => $txn,
			'sold_at'      => gmdate( 'c' ),
		)
	);

	if ( ! $marked ) {
		error_log( 'ArtPal IPN: post ' . (int) $item . ' was not Sold after a verified payment.' );
		artpal_ipn_remember_result( 'not_sold', $fields, '' );
		return 'not_sold';
	}

	artpal_ipn_remember_result( 'sold', $fields, $detail );
	return 'sold';
}

/**
 * Path component of a URL or request, without a trailing slash.
 *
 * @param string $url URL or path.
 * @return string
 */
function artpal_url_path( $url ) {
	$path = parse_url( (string) $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || $path === '' ) {
		return '';
	}
	return rtrim( $path, '/' );
}

/**
 * @return string
 */
function artpal_request_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return artpal_url_path( $uri );
}

/**
 * True when this request is the standalone ipn.php script.
 *
 * wp-load.php runs init, so the thank-you handler must not also treat an IPN POST.
 *
 * @return bool
 */
function artpal_is_direct_ipn_request() {
	$script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? (string) $_SERVER['SCRIPT_FILENAME'] : '';
	if ( $script !== '' && substr( $script, -7 ) === 'ipn.php' ) {
		return true;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return strpos( $uri, 'ipn.php' ) !== false;
}

/**
 * Ask PayPal to confirm a Payment Data Transfer token from the return URL.
 *
 * @param string $tx PDT transaction token (tx), not txn_id.
 * @return array{ok:bool,fields?:array,error?:string}|null Null when no identity token is saved.
 */
function artpal_paypal_pdt_fetch( $tx ) {
	$token = trim( (string) get_option( 'ds_ap_pdt_token' ) );
	$tx    = trim( (string) $tx );
	if ( $token === '' || $tx === '' ) {
		return null;
	}

	if ( isset( $GLOBALS['artpal_test'] ) && is_array( $GLOBALS['artpal_test'] ) && array_key_exists( 'pdt_response', $GLOBALS['artpal_test'] ) ) {
		$text = (string) $GLOBALS['artpal_test']['pdt_response'];
	} else {
		$response = wp_remote_post(
			'https://' . get_paypal_domain() . '/cgi-bin/webscr',
			array(
				'timeout'     => 45,
				'httpversion' => '1.1',
				'headers'     => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Connection'   => 'Close',
					'User-Agent'   => 'ArtPal/' . artpal_version(),
				),
				'body'        => 'cmd=_notify-synch&tx=' . rawurlencode( $tx ) . '&at=' . rawurlencode( $token ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'error' => 'transport' );
		}
		$text = (string) wp_remote_retrieve_body( $response );
	}

	$lines = preg_split( "/\r\n|\n|\r/", trim( $text ) );
	if ( ! is_array( $lines ) || ! isset( $lines[0] ) || strcasecmp( trim( $lines[0] ), 'SUCCESS' ) !== 0 ) {
		$snippet = trim( preg_replace( '/\s+/', ' ', $text ) );
		return array( 'ok' => false, 'error' => substr( $snippet, 0, 180 ) );
	}

	$fields = array();
	$count  = count( $lines );
	for ( $i = 1; $i < $count; $i++ ) {
		$parts = explode( '=', $lines[ $i ], 2 );
		if ( count( $parts ) !== 2 ) {
			continue;
		}
		$fields[ urldecode( $parts[0] ) ] = urldecode( $parts[1] );
	}
	return array( 'ok' => true, 'fields' => $fields );
}

/**
 * Confirm a buyer landing on the thank-you page and mark the post sold.
 *
 * PayPal posts the payment variables here when the button sends rm=2. That
 * request comes from the buyer's browser. The IPN request comes from PayPal
 * and has not been arriving on this host.
 *
 * @param array $fields Return fields from POST and GET.
 * @return string Result code, or empty when there is nothing to confirm.
 */
function artpal_handle_paypal_return_fields( $fields ) {
	$fields = is_array( $fields ) ? $fields : array();
	if ( empty( $fields['payment_status'] ) && ! empty( $fields['st'] ) ) {
		$fields['payment_status'] = (string) $fields['st'];
	}

	$tx = isset( $fields['tx'] ) ? trim( (string) $fields['tx'] ) : '';
	if ( $tx !== '' && trim( (string) get_option( 'ds_ap_pdt_token' ) ) !== '' ) {
		$synced = artpal_paypal_pdt_fetch( $tx );
		if ( is_array( $synced ) && ! empty( $synced['ok'] ) && ! empty( $synced['fields'] ) ) {
			return artpal_apply_verified_paypal_payment( $synced['fields'], 'PDT' );
		}
		$pdt_error = is_array( $synced ) && isset( $synced['error'] ) ? (string) $synced['error'] : 'pdt';
		artpal_ipn_remember_result( 'pdt_failed', $fields, $pdt_error );
	}

	if ( empty( $fields['item_number'] ) || empty( $fields['txn_id'] ) || empty( $fields['payment_status'] ) ) {
		if ( $tx !== '' || ! empty( $fields['item_number'] ) || ! empty( $fields['st'] ) ) {
			artpal_ipn_remember_result( 'return_incomplete', $fields, 'thank-you return was missing payment fields' );
			return 'return_incomplete';
		}
		return 'empty';
	}

	return artpal_process_paypal_ipn( $fields, '' );
}

/**
 * init callback. No-op except on the configured thank-you URL with PayPal fields.
 *
 * @return void
 */
function artpal_maybe_handle_paypal_return() {
	if ( artpal_is_direct_ipn_request() ) {
		return;
	}
	$thank = artpal_url_path( (string) get_option( 'ds_ap_thankyoupage' ) );
	$path  = artpal_request_path();
	if ( $thank === '' || $path === '' || $thank !== $path ) {
		return;
	}

	$fields = array();
	if ( isset( $_POST ) && is_array( $_POST ) && ! empty( $_POST ) ) {
		$fields = wp_unslash( $_POST );
	}
	if ( isset( $_GET ) && is_array( $_GET ) ) {
		foreach ( wp_unslash( $_GET ) as $key => $value ) {
			if ( ! is_string( $key ) || is_array( $value ) ) {
				continue;
			}
			if ( ! isset( $fields[ $key ] ) || $fields[ $key ] === '' ) {
				$fields[ $key ] = $value;
			}
		}
	}

	$interesting = array( 'item_number', 'txn_id', 'tx', 'payment_status', 'st', 'verify_sign' );
	$any         = false;
	foreach ( $interesting as $key ) {
		if ( ! empty( $fields[ $key ] ) ) {
			$any = true;
			break;
		}
	}
	if ( ! $any ) {
		return;
	}

	artpal_handle_paypal_return_fields( $fields );
}

////////////////////////////////////////////////////////////////////////////////
// WP-CLI (staging helper)
////////////////////////////////////////////////////////////////////////////////

function artpal_register_cli() {
	if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) || ! class_exists( 'ArtPal_CLI_Command' ) ) {
		return;
	}
	WP_CLI::add_command( 'artpal', 'ArtPal_CLI_Command' );
}

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI_Command' ) ) {
	/**
	 * Staging helpers: status and mark-sold.
	 */
	class ArtPal_CLI_Command extends WP_CLI_Command {
		/**
		 * Show ArtPal inventory state for a post.
		 *
		 * ## OPTIONS
		 *
		 * <post_id>
		 * : WordPress post ID (item number).
		 *
		 * ## EXAMPLES
		 *
		 *     wp artpal status 8358
		 *
		 * @param array $args Positional arguments.
		 * @return void
		 */
		public function status( $args ) {
			$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
			if ( $post_id <= 0 || ! get_post( $post_id ) ) {
				WP_CLI::error( 'Post not found.' );
			}
			$terms = array();
			foreach ( artpal_get_category_ids( $post_id ) as $tid ) {
				$term = get_term( $tid, 'category' );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term->slug . ' (' . $tid . ')';
				} else {
					$terms[] = (string) $tid;
				}
			}
			WP_CLI::log( 'post_id:     ' . $post_id );
			WP_CLI::log( 'available:   ' . ( artpal_is_available( $post_id ) ? 'yes' : 'no' ) );
			WP_CLI::log( 'sold:        ' . ( artpal_is_sold( $post_id ) ? 'yes' : 'no' ) );
			WP_CLI::log( 'disabled:    ' . ( artpal_is_sale_disabled( $post_id ) ? 'yes' : 'no' ) );
			WP_CLI::log( 'price:       ' . var_export( artpal_get_price( $post_id ), true ) );
			WP_CLI::log( 'shipping:    ' . var_export( artpal_get_shipping( $post_id ), true ) );
			WP_CLI::log( 'effective:   ' . var_export( artpal_effective_price( $post_id ), true ) );
			WP_CLI::log( 'categories:  ' . implode( ', ', $terms ) );
		}

		/**
		 * Mark a post Sold (Available removed, Sold appended).
		 *
		 * ## OPTIONS
		 *
		 * <post_id>
		 * : WordPress post ID (item number).
		 *
		 * [--skip-email]
		 * : Do not send the sold notification email.
		 *
		 * [--event-id=<id>]
		 * : Optional processor event id for idempotency testing.
		 *
		 * ## EXAMPLES
		 *
		 *     wp artpal mark-sold 8358 --skip-email
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 * @return void
		 */
		public function mark_sold( $args, $assoc_args ) {
			$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
			$context = array(
				'processor' => 'manual',
				'event_id'  => isset( $assoc_args['event-id'] ) ? (string) $assoc_args['event-id'] : ( 'cli-' . time() ),
				'sold_at'   => gmdate( 'c' ),
			);
			if ( isset( $assoc_args['skip-email'] ) ) {
				$context['skip_email'] = true;
			}
			$ok = artpal_mark_sold( $post_id, $context );
			if ( $ok ) {
				WP_CLI::success( 'Post ' . $post_id . ' is Sold.' );
				$this->status( array( $post_id ) );
			} else {
				WP_CLI::error( 'Could not mark post ' . $post_id . ' as Sold.' );
			}
		}
	}
}
