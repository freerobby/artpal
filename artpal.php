<?php
/*
Plugin Name: ArtPal

Plugin URI: http://www.freerobby.com/artpal

Description: ArtPal allows artists to use Wordpress to sell their work through PayPal. It automates the entire selling process. Normally an artist has to make edits to a post once his artwork sells (i.e. he'll write "sold" under the picture, take away the PayPal button, and perhaps change the category in which the post is filed). ArtPal takes care of all these things automatically - in real time!

Author: Robby Grossman

Version: 1.1.1

Author URI: http://www.freerobby.com
*/

////////////////////////////////////////////////////////////////////////////////
// Dependences
////////////////////////////////////////////////////////////////////////////////

// Wordpress Administrative Functions
require_once ( ABSPATH . '/wp-admin/admin-functions.php' );

////////////////////////////////////////////////////////////////////////////////
// Global Constant Declarations
////////////////////////////////////////////////////////////////////////////////
define ( 'ds_ap_CFPRICE', 'artpal_price' );  // Custom Field Names
define ( 'ds_ap_CFSHIPPING', 'artpal_shipping' );
define ( 'ds_ap_TAGINSERT', '[artpal=insert]' ); // ArtPal Insert Button Tag
define ( 'ds_ap_TAGIPN', '[artpal=ipnpage]' ); // Create IPN Receve page

////////////////////////////////////////////////////////////////////////////////
// Wordpress Hook Declarations
////////////////////////////////////////////////////////////////////////////////

// Plugin activation
register_activation_hook ( __FILE__ , 'ds_ap_install' );
// Plugin deactivation
register_deactivation_hook ( __FILE__ , 'ds_ap_uninstall' );
// Add our page to the administration menu
add_action('admin_menu', 'ds_ap_add_pages');
// Before content is displayed, let us look through it and make changes as necessary.
add_filter ( 'the_content', 'ds_ap_parsecontent' ) ;

////////////////////////////////////////////////////////////////////////////////
// Plugin Options Definitions
////////////////////////////////////////////////////////////////////////////////

// Option keys
global $ds_ap_options_names;
$ds_ap_options_names = array (
	'ds_ap_unsoldcategory',
	'ds_ap_soldcategory',
	'ds_ap_paypalemail',
	'ds_ap_soldcode',
	'ds_ap_prebuttontext',
	'ds_ap_thankyoupage',
	'ds_ap_ipnpage',
	'ds_ap_cancelpage',
	'ds_ap_paypalbutton',
	'ds_ap_discountpercent',
	'ds_ap_disableecommerce',
	'ds_ap_textifunknownmetadata',
	'ds_ap_saledisabledcategory',
	'ds_ap_textifsaledisabled'
	);

global $ds_ap_options_vals;
$ds_ap_options_vals = array (
	NULL,
	NULL,
	stripslashes('ValidPaypalEmail@Goes.Here'),
	stripslashes('<b>Sold!</b>'),
	stripslashes('_PRICE_ via PayPal, _SHIPPING_ shipping within US'),
	NULL,
	NULL,
	NULL,
	'',
	'0',
	'0',
	stripslashes('Please contact me if you are interested in purchasing this piece.'),
	'-1',
	stripslashes('Sorry, this item is not currently available for sale. Please check back later.')
);

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

// Define our configuration pages
function ds_ap_add_pages () {
	// Add our menu under "options"
	add_options_page ( 'ArtPal', 'ArtPal', 'edit_plugins', __FILE__, 'ds_ap_options_page');
	// Add our management menu under "Manage"
	add_management_page ( 'ArtPal Items', 'ArtPal Items', 'edit_posts', __FILE__, 'ds_ap_manage_page');
}

function ds_ap_buynow_button () {
	// Get post ID
	global $id;
	// Get price of item
	$regularprice = get_post_meta ($id, ds_ap_CFPRICE, $single = true );
	// If the price is not set, ...
	if ($regularprice == null)
	{
		// Return pre-defined static text if dynamic info is not available.
		$textToDisplay = htmlspecialchars_decode ( stripslashes ( get_option ( 'ds_ap_textifunknownmetadata' ) ) );
		return $textToDisplay;
	}
	else {
		// Apply discount to item if applicable.
		$discount = get_option ( 'ds_ap_discountpercent' );
		$price = $regularprice - ( $regularprice * ( $discount / 100 ) );
		// Get shipping cost of item
		$shipping = get_post_meta ($id, ds_ap_CFSHIPPING, $single = true );
		// Get name of item
		$name = get_the_title ( $id );
		// Generate the button
		$button_html = ds_ap_generatepaypalbutton (
			stripslashes ( get_option ( 'ds_ap_paypalemail' ) ),
			$name,
			$id,
			$price,
			$shipping,
			$regularprice
		);
		return $button_html; // Return the HTML code for the button.
	}
}

// Replace the buy-now tag with a button or "SOLD" text.
function ds_ap_buynow_sold () {
	return stripslashes ( htmlspecialchars_decode ( get_option ( 'ds_ap_soldcode' ) ) );
}

// Add category reference to post
function ds_ap_AddTaxonomyToObject ( $oid, $tid ) {
	global $wpdb;
	$sql = 'INSERT INTO ' . $wpdb -> term_relationships . ' ( object_id, term_taxonomy_id ) VALUES ( ' . $oid . ', ' . $tid . ' )';
	$wpdb -> query ( $sql );
}

// Remove category reference from postq
function ds_ap_RemoveTaxonomyFromObject ( $oid, $tid ) {
	global $wpdb;
	$sql = 'DELETE FROM ' . $wpdb -> term_relationships . ' WHERE object_id = ' . $oid . ' AND term_taxonomy_id = ' . $tid;
	$wpdb -> query ( $sql );
}

// Change the category of post from old to new
// DOES NOT CHECK TO VERIFY THAT CATEGORY WAS OLD
// This now changes the taxonomy rather than the category; variables need to be changed at some point accordingly.
function ds_ap_change_taxonomy_of_object ( $objid, $old_tid, $new_tid ) {
	// apply new category
	ds_ap_AddTaxonomyToObject ( $objid, $new_tid );
	// remove old category
	ds_ap_RemoveTaxonomyFromObject ( $objid, $old_tid );
}

// Figure out if this button has sold and act accordingly
function ds_ap_constructbuynow () {
	// Find out if we've sold
	// Get post id
	global $id;
	// Get post category
	$cats = wp_get_post_cats ( 1, $id );
	// Get category of for-sale artwork.
	$cat_forsale = get_option ('ds_ap_unsoldcategory' );
	$cat_forsold = get_option ('ds_ap_soldcategory' );
	$cat_notsale = get_option ('ds_ap_saledisabledcategory'); // Category indicating that an item is temporarily not for sale.
	// Sold unless it's in the category that's for sale.
	$sold = false;
	// Do we show anything at all (i.e. is it in the sold/unsold category, or a completely different one?)
	$show = false;
	// By default, an item is eligible to be on sale. This will be set to false if the item is found to belong to the category of items that are explicitly off sale (temporarily disabled).
	$allowSale = true;
	foreach ( $cats as $cat ) {
		if ( $cat == $cat_forsale ) {
			$show = true;
			$sold = false;
		}
		if ( $cat == $cat_forsold ) {
			$show = true;
			$sold = true;
		}
		if ( $cat == $cat_notsale) {
			$allowSale = false;
		}
	}
	// If sold, tell user
	if ( $sold && $show ) {
		return ds_ap_buynow_sold ();
	}
	// If unsold and sale is allowed, draw button
	else if ($show && $allowSale) {
		return ds_ap_buynow_button ();
	}
	// If unsold and sale is not allowed, show not allowed text.
	else if ($show && !$allowSale) {
		return stripslashes ( htmlspecialchars_decode ( get_option('ds_ap_textifsaledisabled') ) );
	}
	else {
		return NULL;
	}
}

// Process IPN request
function ds_ap_doipn ($logging = false) {
	if ($logging) {
		$myFile = "./ipnoutput.txt";
		$fh = fopen($myFile, 'w');
		fwrite ( $fh, "--------------------------------------------------\n" );
		fwrite ( $fh, "Begin Instant Payment Notification\n" );
	}
	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';
	// Get each element of IPN request
	foreach ($_POST as $key => $value) {
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
		if ($logging) {
			fwrite ( $fh, "$key = $value \n" );
		}
	}
	// post back to PayPal system to validate
	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
	$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);
	// assign posted variables to local variables
	$item_name = $_POST['item_name'];
	$item_number = $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
	$txn_id = $_POST['txn_id'];
	$receiver_email = $_POST['receiver_email'];
	$payer_email = $_POST['payer_email'];
	
	if (!$fp) {
		if ($logging) {
			fwrite ( $fh, "HTTP ERROR\n" );
		}
		// HTTP ERROR
	}
	else {
		if ($logging) {
			fwrite ( $fh, "NO HTTP ERROR\n" );
		}
		fputs ($fp, $header . $req);
		while (!feof($fp)) {
			$res = fgets ($fp, 1024);
			if (strcmp ($res, "VERIFIED") == 0) {
				if ($logging) {
					fwrite ( $fh, "VERIFIED = 0\n" );
				}
				// check the payment_status is Completed
				// check that txn_id has not been previously processed
				// check that receiver_email is your Primary PayPal email
				if ( strtolower ( urldecode ( $receiver_email ) ) != strtolower ( get_option ( 'ds_ap_paypalemail' ) ) ) {
					if ($logging) {
						fwrite ( $fh, "RECEIVER EMAILS DONT MATCH\n" );
					}
					exit;
				}
				// check that payment_amount/payment_currency are correct
				// process payment
				// Mark as sold!
				$post_id = $item_number;
				$old_cat = get_option ( 'ds_ap_unsoldcategory' );
				$new_cat = get_option ( 'ds_ap_soldcategory' );
				if ($logging) {
					fwrite ( $fh, "Changing Category of Post $post_id from $old_cat to $new_cat..." );
				}
				ds_ap_change_taxonomy_of_object ( $post_id, $old_cat, $new_cat );
				if ($logging) {
					fwrite ( $fh, "done\n" );
				}
				// Flush the cache on the item in question.
				if (defined('WP_CACHE') && WP_CACHE == true) {
					wp_cache_no_postid($item_number);
				}
				
			}
			else if (strcmp ($res, "INVALID") == 0) {
				if ($logging) {
					fwrite ( $fh, "INVALID = 0\n" );
				}
				// log for manual investigation
				echo stripslashes ( get_option ( 'ds_ap_suspiciousactivitymsg' ) );
				echo '<br />';
				echo '<a href="' . get_option ( 'siteurl' ) . '">Click to return to my site.</a><br />';
			}
		}
		fclose ($fp);
	}
	if ($logging) {
		fwrite ( $fh, "End Instant Payment Notification\n" );
		fwrite ( $fh, "--------------------------------------------------\n" );
		fclose ( $fh );
	}
	$item_number = $_GET [ 'itempurchased' ];
	return '';
}

// Generate a PayPal button to purchase a particular item
function ds_ap_generatepaypalbutton ( $selleremail, $itemname, $itemnumber, $price, $shipping, $regularprice = NULL) {
	if ( $regularprice == $price )
		$regularprice = NULL;
	$pretext = htmlspecialchars_decode ( stripslashes ( get_option ( 'ds_ap_prebuttontext' ) ) );
	$pricetext = '$' . $price;
	// If regular price isn't the same as the current price, ...
	if ( $regularprice != NULL ) {
		// ... show the savings!
		$pricetext = '<del>$' . $regularprice . '</del> ' . $pricetext;
	}
	$pretext = str_replace ( '_PRICE_', $pricetext, $pretext );
	if ( $shipping == 0 )
		$shipping = 'free';
	else
		$shipping = '$' . $shipping;
	$pretext = str_replace ( '_SHIPPING_', $shipping, $pretext );
	if ( $shipping == 'free' )
		$shipping = 0;
	$button_html = $pretext . '<br />';
	// Don't create the PayPal button if ecommerce is disabled.
	if ( ! get_option ( 'ds_ap_disableecommerce' ) ) {
		$button_html .= '<form method="post" action="https://www.paypal.com/cgi-bin/webscr" target="paypal">'
		. '<input type="hidden" name="cmd" value="_xclick">'
		. '<input type="hidden" name="business" value="' . $selleremail . '">' // email account to send money to
		. '<input type="hidden" name="item_name" value="' . $itemname . '">' // name of item to appear at checkout
		. '<input type="hidden" name="item_number" value="' . $itemnumber . '">' // item number
	//	. '<input type="hidden" name="invoice" value="' . $itemnumber . '">' // invoice # mandated unique by paypal
																			// be careful! if you uncomment the above line, you can't "reset" sold 
																			// paintings to make them available again!
		. '<input type="hidden" name="amount" value="' . $price . '">' // price of item
		. '<input type="hidden" name="currency_code" value="USD">' // us dollars only
		. '<input type="hidden" name="quantity" value="1">' // default 1 item
		. '<input type="hidden" name="shipping" value="' . $shipping . '">' // shipping price of item
		. '<input type="hidden" name="notify_url" value="' . get_option ( 'ds_ap_ipnpage' ) . '">'
		. '<input type="hidden" name="return" value="' . get_option ( 'ds_ap_thankyoupage' ) . '">'
	 	. '<input type="hidden" name="cancel_return" value="' . get_option ( 'ds_ap_cancelpage' ) . '">'
		. '<input type="image" name="add" src="' . get_option ( 'ds_ap_paypalbutton' ) . '">' // button graphic
		. '</form>';
	}
	return $button_html;
}

function ds_ap_install () {
	// Add our options with their default values as defined.
	global $ds_ap_options_names;
	global $ds_ap_options_vals;
	$num_opts = count($ds_ap_options_names);
	for ($i = 0; $i < $num_opts; $i++) {
		add_option($ds_ap_options_names[$i], $ds_ap_options_vals[$i]);
	}
}

// Define our options page
function ds_ap_manage_page () {
	include  ( 'artpal-manage.php' );
}

// Define our options page
function ds_ap_options_page () {
	include  ( 'artpal-options.php' );
}

// Determine if content has ipninsert
function ds_ap_hasipninsert ($content) {
	$pos = strpos ($content, ds_ap_TAGINSERT);
	if ($pos !== false)
		return true;
	else
		return false;
}

function ds_ap_hasipnpage ($content) {
	$pos = strpos ($content, ds_ap_TAGIPN);
	if ($pos !== false)
		return true;
	else
		return false;
}

// Break into: pre-tag, tag, post-tag
function ds_ap_parsecontent ( $content ) {
	// Find start of [artpal=insert] tag
	$tag_startpos = strpos ( $content, ds_ap_TAGINSERT );
	if ( $tag_startpos !== false ) {
		$tag_endpos = $tag_startpos + strlen ( ds_ap_TAGINSERT );
		$content_pre = substr ( $content, 0, $tag_startpos ); // all content before tag
		$content_post = substr ( $content, $tag_endpos ); // all content after tag
		$newcontent_tag = ds_ap_constructbuynow ( );
		$content = $content_pre . $newcontent_tag . $content_post;
	}
	// Find start of [artpal=ipnpage] tag
	else {
		$tag_startpos = strpos ( $content, ds_ap_TAGIPN );
		if ( $tag_startpos !== false ) {
			$tag_endpos = $tag_startpos + strlen ( ds_ap_TAGIPN );
			$content_pre = substr ( $content, 0, $tag_startpos );
			$content_post = substr ( $content, $tag_endpos );
			$newcontent_tag = ds_ap_doipn ();
			$content = $content_pre . $newcontent_tag . $content_post;
		}
	}
	return $content;
}

function ds_ap_uninstall () {
	// Remove options from database on uninstall.
	//global $ds_ap_options_names;
	//$num_opts = count($ds_ap_options_names);
	//for ($i = 0; $i < $num_opts; $i++) {
	//	delete_option($ds_ap_options_names[$i], $ds_ap_options_vals[$i]);
	//}
}
	
?>
