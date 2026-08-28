<?php
/**
 * Slice 1 self-test. Stubs WordPress APIs so this can run with `php tests/slice1-selftest.php`.
 * On a real site: wp eval-file wp-content/plugins/artpal/tests/slice1-selftest.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['artpal_test'] = array(
	'posts'     => array(),
	'options'   => array(),
	'meta'      => array(),
	'terms'     => array(),
	'mail'      => array(),
	'actions'   => array(),
	'logs'      => array(),
);

function artpal_test_reset() {
	$GLOBALS['artpal_test'] = array(
		'posts'     => array(),
		'options'   => array(
			'ds_ap_unsoldcategory'         => 5,
			'ds_ap_soldcategory'           => 3,
			'ds_ap_saledisabledcategory'   => 6,
			'ds_ap_discountpercent'        => '0',
			'ds_ap_soldcode'               => '&lt;b&gt;SOLD!&lt;/b&gt;',
			'ds_ap_textifsaledisabled'     => 'Sorry, this item is not currently available for sale. Please check back later.',
			'ds_ap_textifunknownmetadata'  => 'Please contact me if you are interested in purchasing this piece.',
			'ds_ap_prebuttontext'          => '_PRICE_ via PayPal, _SHIPPING_ shipping within US',
			'ds_ap_paypalemail'            => 'jamiewg@aol.com',
			'ds_ap_currencysymbol'         => '$',
			'ds_ap_currencycode4217'       => 'USD',
			'ds_ap_taxrate'                => '0.00',
			'ds_ap_disableecommerce'       => '0',
			'ds_ap_usesandbox'             => '0',
			'ds_ap_paypalbutton'           => 'https://example.com/btn.gif',
			'ds_ap_thankyoupage'           => 'https://example.com/thanks',
			'ds_ap_cancelpage'             => 'https://example.com/cancel',
			'ds_ap_processed_events'       => array(),
			'siteurl'                      => 'https://example.com',
			'admin_email'                  => 'admin@example.com',
		),
		'meta'      => array(),
		'terms'     => array(),
		'mail'      => array(),
		'actions'   => array(),
		'logs'      => array(),
	);
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		$id = (int) $post_id;
		return isset( $GLOBALS['artpal_test']['posts'][ $id ] ) ? $GLOBALS['artpal_test']['posts'][ $id ] : null;
	}
	function get_option( $key, $default = false ) {
		$opts = $GLOBALS['artpal_test']['options'];
		return array_key_exists( $key, $opts ) ? $opts[ $key ] : $default;
	}
	function update_option( $key, $value, $autoload = true ) {
		unset( $autoload );
		$GLOBALS['artpal_test']['options'][ $key ] = $value;
		return true;
	}
	function add_option( $key, $value ) {
		if ( ! array_key_exists( $key, $GLOBALS['artpal_test']['options'] ) ) {
			$GLOBALS['artpal_test']['options'][ $key ] = $value;
		}
		return true;
	}
	function get_post_meta( $post_id, $key, $single = false ) {
		unset( $single );
		$meta = $GLOBALS['artpal_test']['meta'];
		if ( ! isset( $meta[ (int) $post_id ][ $key ] ) ) {
			return '';
		}
		return $meta[ (int) $post_id ][ $key ];
	}
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['artpal_test']['meta'][ (int) $post_id ][ $key ] = $value;
		return true;
	}
	function wp_get_post_categories( $post_id ) {
		$terms = $GLOBALS['artpal_test']['terms'];
		return isset( $terms[ (int) $post_id ] ) ? $terms[ (int) $post_id ] : array();
	}
	function wp_remove_object_terms( $post_id, $terms, $taxonomy ) {
		unset( $taxonomy );
		$remove = array_map( 'intval', (array) $terms );
		$have   = wp_get_post_categories( $post_id );
		$GLOBALS['artpal_test']['terms'][ (int) $post_id ] = array_values( array_diff( $have, $remove ) );
		return true;
	}
	function wp_set_object_terms( $post_id, $terms, $taxonomy, $append = false ) {
		unset( $taxonomy );
		$terms = array_map( 'intval', (array) $terms );
		$have  = wp_get_post_categories( $post_id );
		$GLOBALS['artpal_test']['terms'][ (int) $post_id ] = $append ? array_values( array_unique( array_merge( $have, $terms ) ) ) : $terms;
		return $terms;
	}
	function clean_post_cache( $post_id ) {
		unset( $post_id );
	}
	function clean_object_term_cache( $post_id, $taxonomy ) {
		unset( $post_id, $taxonomy );
	}
	function clean_term_cache( $ids, $taxonomy ) {
		unset( $ids, $taxonomy );
	}
	function do_action( $hook, ...$args ) {
		$GLOBALS['artpal_test']['actions'][] = array( $hook, $args );
	}
	function wp_mail( $to, $subject, $body ) {
		$GLOBALS['artpal_test']['mail'][] = array( $to, $subject, $body );
		return true;
	}
	function get_the_title( $post_id ) {
		$post = get_post( $post_id );
		return $post ? $post['post_title'] : '';
	}
	function get_permalink( $post_id ) {
		return 'https://example.com/?p=' . (int) $post_id;
	}
	function get_edit_post_link( $post_id, $context = 'display' ) {
		unset( $context );
		return 'https://example.com/wp-admin/post.php?post=' . (int) $post_id . '&action=edit';
	}
	function get_the_ID() {
		return isset( $GLOBALS['id'] ) ? (int) $GLOBALS['id'] : 0;
	}
	function register_activation_hook( $file, $cb ) {
		unset( $file, $cb );
	}
	function register_deactivation_hook( $file, $cb ) {
		unset( $file, $cb );
	}
	function add_action( $hook, $cb, $pri = 10, $args = 1 ) {
		unset( $hook, $cb, $pri, $args );
	}
	function add_filter( $hook, $cb, $pri = 10, $args = 1 ) {
		unset( $hook, $cb, $pri, $args );
	}
	function add_shortcode( $tag, $cb ) {
		unset( $tag, $cb );
	}
	function add_options_page() {}
	function add_management_page() {}
}

function artpal_test_seed_post( $id, $cats, $price = null, $shipping = null, $title = 'Splash!' ) {
	$GLOBALS['artpal_test']['posts'][ $id ] = array( 'ID' => $id, 'post_title' => $title );
	$GLOBALS['artpal_test']['terms'][ $id ] = $cats;
	if ( $price !== null ) {
		$GLOBALS['artpal_test']['meta'][ $id ]['artpal_price'] = $price;
	}
	if ( $shipping !== null ) {
		$GLOBALS['artpal_test']['meta'][ $id ]['artpal_shipping'] = $shipping;
	}
}

$failed = 0;
function artpal_assert( $cond, $label ) {
	global $failed;
	if ( $cond ) {
		echo "OK  $label\n";
	} else {
		$failed++;
		echo "FAIL $label\n";
	}
}

artpal_test_reset();
require dirname( __DIR__ ) . '/artpal.php';

// 1. Available + price → checkout form, not Sold
artpal_test_reset();
artpal_test_seed_post( 8358, array( 5, 12, 20 ), '150', '15' ); // Available + Landscape + size
$GLOBALS['id'] = 8358;
$html = artpal_render_buy_now( 8358 );
artpal_assert( strpos( $html, 'cmd' ) !== false && strpos( $html, '_xclick' ) !== false, 'available + price renders PayPal form' );
artpal_assert( strpos( $html, 'SOLD!' ) === false, 'available + price is not sold HTML' );
artpal_assert( strpos( $html, 'name="item_number" value="8358"' ) !== false, 'item number is the post ID' );
artpal_assert( strpos( $html, '$150.00' ) !== false, 'prebuttontext fills _PRICE_' );
artpal_assert( strpos( $html, '$15' ) !== false, 'prebuttontext fills _SHIPPING_' );

// 2. After mark_sold → soldcode, not a form; other categories remain
$ok = artpal_mark_sold( 8358, array( 'processor' => 'manual', 'event_id' => 'evt_1', 'buyer_email' => 'buyer@example.com' ) );
artpal_assert( $ok === true, 'artpal_mark_sold returns true' );
artpal_assert( artpal_is_sold( 8358 ), 'post is sold' );
artpal_assert( ! artpal_is_available( 8358 ), 'post is no longer available' );
$cats = artpal_get_category_ids( 8358 );
artpal_assert( in_array( 3, $cats, true ), 'Sold category appended' );
artpal_assert( ! in_array( 5, $cats, true ), 'Available category removed' );
artpal_assert( in_array( 12, $cats, true ) && in_array( 20, $cats, true ), 'Landscape/size categories remain' );
$html = artpal_render_buy_now( 8358 );
artpal_assert( strpos( $html, 'SOLD!' ) !== false, 'renderer shows sold HTML' );
artpal_assert( strpos( $html, '<form' ) === false, 'renderer has no checkout form after sold' );
artpal_assert( count( $GLOBALS['artpal_test']['mail'] ) === 1, 'first mark_sold sends one email' );
artpal_assert( count( $GLOBALS['artpal_test']['actions'] ) === 1 && $GLOBALS['artpal_test']['actions'][0][0] === 'artpal_marked_sold', 'artpal_marked_sold action fired once' );

// 3. Second mark_sold → still Sold, no second email
$ok2 = artpal_mark_sold( 8358, array( 'processor' => 'manual', 'event_id' => 'evt_1' ) );
artpal_assert( $ok2 === true, 'second mark_sold is idempotent' );
artpal_assert( count( $GLOBALS['artpal_test']['mail'] ) === 1, 'second mark_sold does not email' );
$cats2 = artpal_get_category_ids( 8358 );
artpal_assert( in_array( 12, $cats2, true ) && in_array( 20, $cats2, true ) && in_array( 3, $cats2, true ), 'repeat mark_sold still keeps extra categories' );

// 4. Sale-disabled → disabled text, no form
artpal_test_reset();
artpal_test_seed_post( 10, array( 5, 6 ), '150', '15' );
$html = artpal_render_buy_now( 10 );
artpal_assert( strpos( $html, 'not currently available' ) !== false, 'sale-disabled shows disabled text' );
artpal_assert( strpos( $html, '<form' ) === false, 'sale-disabled has no form' );

// 5. Missing price → inquire text
artpal_test_reset();
artpal_test_seed_post( 11, array( 5 ) );
$html = artpal_render_buy_now( 11 );
artpal_assert( strpos( $html, 'Please contact me' ) !== false, 'missing price shows inquire text' );
artpal_assert( strpos( $html, '<form' ) === false, 'missing price has no form' );

// 6. Discount 10% on $150 → effective $135.00
artpal_test_reset();
artpal_test_seed_post( 12, array( 5 ), '150', '15' );
$GLOBALS['artpal_test']['options']['ds_ap_discountpercent'] = '10';
artpal_assert( artpal_effective_price( 12 ) === 135.0, '10% off $150 is 135.00' );
$html = artpal_render_buy_now( 12 );
artpal_assert( strpos( $html, '<del>$150.00</del> $135.00' ) !== false, 'discount shows del regular + effective price' );

// 7. Shipping 0 → "free"
artpal_test_reset();
artpal_test_seed_post( 13, array( 5 ), '150', '0' );
$html = artpal_render_buy_now( 13 );
artpal_assert( strpos( $html, 'free' ) !== false, 'zero shipping renders as free' );

// Hidden if neither Available nor Sold
artpal_test_reset();
artpal_test_seed_post( 14, array( 99 ), '150', '15' );
artpal_assert( artpal_render_buy_now( 14 ) === '', 'unrelated categories hide the renderer' );

// Same event cannot mark two posts
artpal_test_reset();
artpal_test_seed_post( 21, array( 5, 12 ), '150', '15' );
artpal_test_seed_post( 22, array( 5, 12 ), '200', '15' );
artpal_mark_sold( 21, array( 'event_id' => 'txn_shared' ) );
$second = artpal_mark_sold( 22, array( 'event_id' => 'txn_shared' ) );
artpal_assert( artpal_is_sold( 21 ) && ! artpal_is_sold( 22 ) && $second === false, 'same event_id cannot mark a second post' );

// Missing post
artpal_test_reset();
artpal_assert( artpal_mark_sold( 999 ) === false, 'missing post returns false' );

// add_option does not reset existing options
artpal_test_reset();
$GLOBALS['artpal_test']['options']['ds_ap_unsoldcategory'] = 5;
ds_ap_install();
artpal_assert( get_option( 'ds_ap_unsoldcategory' ) == 5, 'activation does not reset existing options' );

// Shortcode fallback replaces leftover tag
artpal_test_reset();
artpal_test_seed_post( 8358, array( 5 ), '150', '15' );
$GLOBALS['id'] = 8358;
$out = ds_ap_parsecontent( 'Hello [artpal=insert] there' );
artpal_assert( strpos( $out, '[artpal=insert]' ) === false && strpos( $out, '_xclick' ) !== false, 'the_content fallback replaces [artpal=insert]' );

echo "\n";
if ( $failed ) {
	echo "$failed assertion(s) failed.\n";
	exit( 1 );
}
echo "All Slice 1 assertions passed.\n";
exit( 0 );
