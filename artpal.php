<?php
/*
Plugin Name: ArtPal
Plugin URI: http://freerobby.com/artpal
Description: ArtPal allows artists to use WordPress to sell one-of-a-kind originals. When a piece sells, ArtPal stops showing a buy button, shows the sold HTML, and moves the post from the Available category to the Sold category.
Author: Robby Grossman
Version: 2.0.0-dev
Requires at least: 6.0
Requires PHP: 7.4
Author URI: http://freerobby.com
*/

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
// Shortcodes: [artpal=insert], [artpal], and [artpal insert]
add_action( 'init', 'artpal_register_shortcodes' );
// Fallback for leftover raw [artpal=insert] if a theme skipped do_shortcode.
add_filter( 'the_content', 'ds_ap_parsecontent', 12 );
add_action( 'cli_init', 'artpal_register_cli' );

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
	return get_option( 'siteurl' ) . '/wp-content/plugins/artpal/ipn.php';
}

// Define our configuration pages
function ds_ap_add_pages() {
	// Add our menu under "options"
	add_options_page( 'ArtPal', 'ArtPal', 'edit_plugins', __FILE__, 'ds_ap_options_page' );
	// Add our management menu under "Manage"
	add_management_page( 'ArtPal Items', 'ArtPal Items', 'edit_posts', __FILE__, 'ds_ap_manage_page' );
}

function artpal_register_shortcodes() {
	add_shortcode( 'artpal=insert', 'artpal_shortcode' );
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
 * @param string $event_id Processor event id (Stripe evt_… or PayPal txn_id).
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

	$subject = '[Hudson Valley Painter] Sold: ' . $title;
	$notify  = get_option( 'ds_ap_notify_email' );
	if ( ! $notify ) {
		$notify = 'JamieWG@aol.com';
	}

	$recipients = array( $notify );
	$admin      = get_option( 'admin_email' );
	if ( $admin && strtolower( $admin ) !== strtolower( $notify ) ) {
		$recipients[] = $admin;
	}

	wp_mail( $recipients, $subject, $body );
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
		. '<input type="hidden" name="notify_url" value="' . ipn_page_url() . '">'
		. '<input type="hidden" name="return" value="' . get_option( 'ds_ap_thankyoupage' ) . '">'
		. '<input type="hidden" name="cancel_return" value="' . get_option( 'ds_ap_cancelpage' ) . '">'
		. '<input type="image" name="add" src="' . get_option( 'ds_ap_paypalbutton' ) . '">' // button graphic
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

// Define our options page
function ds_ap_manage_page() {
	include( 'artpal-manage.php' );
}

// Define our options page
function ds_ap_options_page() {
	include( 'artpal-options.php' );
}

/**
 * Fallback: replace leftover raw [artpal=insert] after shortcodes have run.
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
