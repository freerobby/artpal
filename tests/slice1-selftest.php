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
			'ds_ap_paypalemail'            => 'seller@example.com',
			'ds_ap_notify_email'           => '',
			'ds_ap_email_subject_prefix'   => '',
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
		'blogname'  => 'Test Blog',
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
		$GLOBALS['artpal_registered'][] = array( 'action', $hook, $cb, $pri );
		unset( $args );
	}
	function add_filter( $hook, $cb, $pri = 10, $args = 1 ) {
		$GLOBALS['artpal_registered'][] = array( 'filter', $hook, $cb, $pri );
		unset( $args );
	}
	function add_shortcode( $tag, $cb ) {
		$GLOBALS['artpal_registered'][] = array( 'shortcode', $tag, $cb );
	}
	function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
		$GLOBALS['artpal_registered'][] = array( 'options_page', $capability, $menu_slug, $callback );
		unset( $page_title, $menu_title );
	}
	function add_management_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
		$GLOBALS['artpal_registered'][] = array( 'management_page', $capability, $menu_slug, $callback );
		unset( $page_title, $menu_title );
	}
	function get_bloginfo( $show = '', $filter = 'raw' ) {
		unset( $filter );
		if ( 'name' === $show ) {
			return isset( $GLOBALS['artpal_test']['blogname'] ) ? $GLOBALS['artpal_test']['blogname'] : 'Test Blog';
		}
		return '';
	}
	function is_email( $email ) {
		return (bool) filter_var( (string) $email, FILTER_VALIDATE_EMAIL );
	}
	function wp_unslash( $value ) {
		return $value;
	}
	function sanitize_text_field( $value ) {
		return trim( (string) $value );
	}
	function wp_verify_nonce( $nonce, $action ) {
		unset( $nonce, $action );
		return ! empty( $GLOBALS['artpal_test']['nonce_ok'] );
	}
	function current_user_can( $cap, $id = null ) {
		unset( $cap, $id );
		return ! empty( $GLOBALS['artpal_test']['can'] );
	}
	function get_post_type( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		return isset( $post['post_type'] ) ? $post['post_type'] : 'post';
	}
	function wp_is_post_autosave( $post_id ) {
		unset( $post_id );
		return false;
	}
	function wp_is_post_revision( $post_id ) {
		unset( $post_id );
		return false;
	}
	function delete_post_meta( $post_id, $key ) {
		unset( $GLOBALS['artpal_test']['meta'][ (int) $post_id ][ $key ] );
		return true;
	}
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
	function wp_nonce_field( $action, $name = '_wpnonce', $referer = true, $echo = true ) {
		unset( $action, $referer );
		$html = '<input type="hidden" name="' . $name . '" value="nonce" />';
		if ( $echo ) {
			echo $html;
		}
		return $html;
	}
	function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default' ) {
		$GLOBALS['artpal_test']['meta_boxes'][] = array( $id, $title, $callback, $screen, $context, $priority );
	}
	function plugins_url( $path = '', $plugin = '' ) {
		unset( $plugin );
		return 'https://plugins.example/' . ltrim( (string) $path, '/' );
	}
	function esc_url( $url ) {
		return (string) $url;
	}
	function esc_url_raw( $url ) {
		return (string) $url;
	}
	function delete_option( $key ) {
		unset( $GLOBALS['artpal_test']['options'][ $key ] );
		return true;
	}
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		unset( $action, $query_arg );
		if ( empty( $GLOBALS['artpal_test']['nonce_ok'] ) ) {
			throw new Exception( 'bad nonce' );
		}
		return 1;
	}
	function wp_die( $message = '', $title = '', $args = array() ) {
		unset( $title, $args );
		throw new Exception( 'wp_die: ' . $message );
	}
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( (string) $selected === (string) $current ) ? " selected='selected'" : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( $checked == $current ) ? " checked='checked'" : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
	function get_posts( $args = array() ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$statuses  = isset( $args['post_status'] ) ? (array) $args['post_status'] : array( 'publish' );
		$tax_query = ( isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) ? $args['tax_query'] : array();
		$ids       = array();
		foreach ( $GLOBALS['artpal_test']['posts'] as $id => $post ) {
			$status = isset( $post['post_status'] ) ? $post['post_status'] : 'publish';
			if ( ! in_array( 'any', $statuses, true ) && ! in_array( $status, $statuses, true ) ) {
				continue;
			}
			$terms = isset( $GLOBALS['artpal_test']['terms'][ (int) $id ] ) ? array_map( 'intval', $GLOBALS['artpal_test']['terms'][ (int) $id ] ) : array();
			if ( ! artpal_test_tax_matches( $terms, $tax_query ) ) {
				continue;
			}
			$ids[] = (int) $id;
		}
		sort( $ids );
		if ( isset( $args['fields'] ) && $args['fields'] === 'ids' ) {
			return $ids;
		}
		$out = array();
		foreach ( $ids as $id ) {
			$out[] = (object) $GLOBALS['artpal_test']['posts'][ $id ];
		}
		return $out;
	}
}

/**
 * @param int[] $terms     Assigned category ids.
 * @param array $tax_query get_posts tax_query.
 * @return bool
 */
function artpal_test_tax_matches( $terms, $tax_query ) {
	$relation = isset( $tax_query['relation'] ) ? strtoupper( (string) $tax_query['relation'] ) : 'AND';
	$clauses  = array();
	foreach ( $tax_query as $key => $clause ) {
		if ( ! is_int( $key ) || ! is_array( $clause ) ) {
			continue;
		}
		$wanted = isset( $clause['terms'] ) ? array_map( 'intval', (array) $clause['terms'] ) : array();
		$op     = isset( $clause['operator'] ) ? strtoupper( (string) $clause['operator'] ) : 'IN';
		$has    = count( array_intersect( array_map( 'intval', $terms ), $wanted ) ) > 0;
		$clauses[] = ( 'NOT IN' === $op ) ? ! $has : $has;
	}
	if ( empty( $clauses ) ) {
		return true;
	}
	if ( 'OR' === $relation ) {
		return in_array( true, $clauses, true );
	}
	return ! in_array( false, $clauses, true );
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
artpal_assert( strpos( $html, 'name="notify_url" value="https://plugins.example/ipn.php"' ) !== false, 'notify_url follows the real plugin directory' );
artpal_assert( strpos( $html, 'name="rm"' ) === false, 'checkout does not ask PayPal to POST to the thank-you page' );
artpal_assert( strpos( $html, 'name="return" value="https://example.com/thanks"' ) !== false, 'thank-you page is still the buyer return URL' );
artpal_assert( strpos( $html, '/wp-content/plugins/artpal/ipn.php' ) === false, 'checkout does not hardcode the artpal directory in notify_url' );
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
$mail_to = $GLOBALS['artpal_test']['mail'][0][0];
artpal_assert( $mail_to === array( 'seller@example.com', 'admin@example.com' ), 'sold email goes to PayPal email and admin_email when notify is empty' );
artpal_assert( $GLOBALS['artpal_test']['mail'][0][1] === '[Test Blog] Sold: Splash!', 'sold subject uses the site title when the prefix option is empty' );
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

// Ecommerce disabled: price text, no form
artpal_test_reset();
artpal_test_seed_post( 30, array( 5 ), '150', '0' );
$GLOBALS['artpal_test']['options']['ds_ap_disableecommerce'] = '1';
$html = artpal_render_buy_now( 30 );
artpal_assert( strpos( $html, '$150.00' ) !== false && strpos( $html, '<form' ) === false, 'ecommerce disabled shows price text and no form' );

// Notify email wins, and a custom prefix is used. admin_email is added when different.
artpal_test_reset();
artpal_test_seed_post( 31, array( 3 ), '150', '15', 'Ridge' );
$GLOBALS['artpal_test']['options']['ds_ap_notify_email'] = 'notify@example.com';
$GLOBALS['artpal_test']['options']['ds_ap_email_subject_prefix'] = 'Studio';
$GLOBALS['artpal_test']['mail'] = array();
artpal_send_sold_email( 31, array( 'processor' => 'paypal', 'event_id' => 'txn_x', 'buyer_email' => 'b@example.com', 'amount_total' => '150.00' ) );
artpal_assert( $GLOBALS['artpal_test']['mail'][0][0] === array( 'notify@example.com', 'admin@example.com' ), 'notify email is primary and admin is copied' );
artpal_assert( $GLOBALS['artpal_test']['mail'][0][1] === '[Studio] Sold: Ridge', 'custom subject prefix is used' );

// No duplicate when the only address is admin_email
artpal_test_reset();
artpal_test_seed_post( 32, array( 3 ), '10', '0', 'Sketch' );
$GLOBALS['artpal_test']['options']['ds_ap_notify_email'] = '';
$GLOBALS['artpal_test']['options']['ds_ap_paypalemail'] = '';
$GLOBALS['artpal_test']['mail'] = array();
artpal_send_sold_email( 32, array() );
artpal_assert( $GLOBALS['artpal_test']['mail'][0][0] === array( 'admin@example.com' ), 'admin_email is used once when notify and PayPal email are empty' );

// Metabox: empty price deletes meta; shipping is stored; status labels
artpal_assert( artpal_normalize_meta_amount( '' ) === null, 'empty amount deletes meta' );
artpal_assert( artpal_normalize_meta_amount( '$1,250.5' ) === '1250.50', 'amount normalization strips symbols' );
artpal_assert( artpal_inventory_label( 32 ) === 'Sold', 'sold status label' );
artpal_test_reset();
artpal_test_seed_post( 40, array( 5, 12 ), '150', '15', 'Draft piece' );
artpal_assert( artpal_inventory_label( 40 ) === 'Available', 'available status label' );
artpal_test_seed_post( 41, array( 5, 6 ), '150', '15' );
artpal_assert( artpal_inventory_label( 41 ) === 'Disabled', 'disabled status label' );
artpal_test_seed_post( 42, array( 99 ), '150', '15' );
artpal_assert( artpal_inventory_label( 42 ) === 'Not an ArtPal item', 'unrelated status label' );
artpal_test_seed_post( 43, array( 3, 6 ), '150', '15' );
artpal_assert( artpal_inventory_label( 43 ) === 'Sold', 'sold wins over disabled' );

$GLOBALS['artpal_test']['nonce_ok'] = true;
$GLOBALS['artpal_test']['can'] = true;
$_POST = array(
	'artpal_meta_nonce' => 'nonce',
	'artpal_price'      => '',
	'artpal_shipping'   => '15',
);
artpal_save_metabox( 40 );
artpal_assert( artpal_get_price( 40 ) === null, 'metabox empty price takes the inquire path' );
artpal_assert( artpal_get_shipping( 40 ) === 15.0, 'metabox saves shipping' );
$_POST['artpal_price'] = '25.5';
artpal_save_metabox( 40 );
artpal_assert( get_post_meta( 40, 'artpal_price', true ) === '25.50', 'metabox saves price to artpal_price' );

$GLOBALS['artpal_test']['meta_boxes'] = array();
artpal_register_metabox();
artpal_assert( $GLOBALS['artpal_test']['meta_boxes'][0][3] === 'post', 'metabox is registered for posts only' );

$sold_post = (object) array( 'ID' => 43 );
$GLOBALS['artpal_test']['meta'][43]['_artpal_last_sale'] = array(
	'processor' => 'paypal',
	'event_id'  => 'txn_43',
);
ob_start();
artpal_render_metabox( $sold_post );
$box = ob_get_clean();
artpal_assert( strpos( $box, 'Sold' ) !== false && strpos( $box, 'paypal' ) !== false && strpos( $box, 'txn_43' ) !== false, 'sold metabox shows processor and event id' );

// IPN: VERIFIED + matching receiver + real post ID marks sold, as in 1.4.
artpal_test_reset();
$_SERVER['REQUEST_METHOD']  = 'POST';
$_SERVER['REMOTE_ADDR']     = '173.0.81.1';
$_SERVER['HTTP_USER_AGENT'] = 'PayPal IPN ( https://www.paypal.com/ipn )';
$_SERVER['CONTENT_LENGTH']  = '0';
artpal_test_seed_post( 50, array( 5, 12, 20 ), '150', '15', 'IPN piece' );
$ipn_post = array(
	'payment_status' => 'Completed',
	'receiver_email' => 'Seller@Example.com',
	'item_number'    => '50',
	'txn_id'         => 'txn_ipn_1',
	'payer_email'    => 'buyer@example.com',
	'mc_gross'       => '150.00',
	'mc_currency'    => 'USD',
);
$GLOBALS['artpal_test']['ipn_postback'] = 'INVALID';
$GLOBALS['artpal_test']['ipn_requests'] = array();
artpal_assert( artpal_process_paypal_ipn( $ipn_post, 'payment_status=Completed' ) === 'not_verified', 'INVALID IPN does not mark sold' );
artpal_assert( ! artpal_is_sold( 50 ), 'post stays available after INVALID IPN' );
artpal_assert( isset( $GLOBALS['artpal_test']['ipn_requests'][0] ) && $GLOBALS['artpal_test']['ipn_requests'][0] === 'cmd=_notify-validate&payment_status=Completed', 'first IPN postback is cmd plus the original bytes' );
artpal_assert( count( $GLOBALS['artpal_test']['ipn_requests'] ) === 2, 'INVALID is retried once with the rebuilt body, then given up' );

$GLOBALS['artpal_test']['ipn_postback'] = "VERIFIED\n";
artpal_test_seed_post( 53, array( 5 ), '1', '1', 'Pending piece' );
$pending = array(
	'payment_status' => 'Pending',
	'receiver_email' => 'seller@example.com',
	'item_number'    => '53',
	'txn_id'         => 'txn_pending',
);
artpal_assert( artpal_process_paypal_ipn( $pending, 'payment_status=Pending&receiver_email=seller%40example.com&item_number=53&txn_id=txn_pending' ) === 'sold', 'Pending VERIFIED IPN marks sold, as 1.4 did' );
artpal_assert( artpal_is_sold( 53 ), 'Pending IPN post is sold' );
artpal_assert( ! artpal_is_sold( 50 ), 'Pending IPN does not touch a different post' );

artpal_test_seed_post( 54, array( 5, 12 ), '1', '1', 'Classic piece' );
artpal_assert( artpal_process_paypal_ipn( array( 'receiver_email' => 'Seller@Example.com', 'item_number' => '54' ), '' ) === 'sold', 'VERIFIED IPN marks sold without payment_status or txn_id' );
$classic_cats = artpal_get_category_ids( 54 );
artpal_assert( in_array( 3, $classic_cats, true ) && ! in_array( 5, $classic_cats, true ) && in_array( 12, $classic_cats, true ), 'classic IPN removes Available, appends Sold, keeps other categories' );

$bad_email = $ipn_post;
$bad_email['receiver_email'] = 'other@example.com';
$bad_email['business'] = 'also-other@example.com';
artpal_assert( artpal_process_paypal_ipn( $bad_email, 'raw' ) === 'email_mismatch', 'receiver_email must match the PayPal option' );
artpal_assert( ! artpal_is_sold( 50 ), 'post stays available when receiver_email mismatches' );

artpal_test_seed_post( 51, array( 5 ), '1', '1', 'Business email piece' );
$business_email = $ipn_post;
$business_email['item_number'] = '51';
$business_email['txn_id'] = 'txn_business';
$business_email['receiver_email'] = 'primary@paypal.example';
$business_email['business'] = 'Seller@Example.com';
artpal_assert( artpal_process_paypal_ipn( $business_email, 'payment_status=Completed&txn_id=txn_business&business=Seller%40Example.com&receiver_email=primary%40paypal.example&item_number=51' ) === 'sold', 'business email match marks sold when receiver_email is the account primary' );
artpal_assert( artpal_is_sold( 51 ), 'business-email IPN post is sold' );

artpal_test_seed_post( 52, array( 5, 12 ), '1', '1', 'Raw body piece' );
artpal_assert(
	artpal_process_paypal_ipn(
		array(),
		'payment_status=Completed&receiver_email=seller%40example.com&item_number=52&txn_id=txn_raw_only&payer_email=buyer%40example.com&mc_gross=1.00'
	) === 'sold',
	'IPN fields are read from the raw body when POST is empty'
);
artpal_assert( artpal_is_sold( 52 ), 'raw-body IPN post is sold' );
$raw_last = get_option( 'artpal_ipn_last' );
artpal_assert( is_array( $raw_last ) && $raw_last['result'] === 'sold' && $raw_last['item_number'] === '52' && $raw_last['txn_id'] === 'txn_raw_only', 'last IPN result is stored without requiring POST' );
artpal_assert( ! isset( $raw_last['payer_email'] ), 'stored IPN result omits the buyer email' );
artpal_assert( is_array( $raw_last['keys'] ) && in_array( 'receiver_email', $raw_last['keys'], true ) && in_array( 'payer_email', $raw_last['keys'], true ), 'stored IPN result lists the field names that arrived' );

// Request log: every call to the handler is kept, newest first, with request details.
$_SERVER['CONTENT_LENGTH'] = '0';
$_POST = array();
artpal_handle_paypal_ipn( '' );
$ipn_log = get_option( 'artpal_ipn_log' );
artpal_assert( is_array( $ipn_log ) && $ipn_log[0]['result'] === 'empty', 'a request with no fields is logged as empty' );
artpal_assert( $ipn_log[0]['method'] === 'POST' && $ipn_log[0]['remote_addr'] === '173.0.81.1' && strpos( $ipn_log[0]['user_agent'], 'PayPal IPN' ) === 0, 'log entry records method, caller address, and user agent' );
artpal_assert( $ipn_log[1]['result'] === 'sold' && $ipn_log[1]['item_number'] === '52', 'log keeps earlier results, newest first' );
$_SERVER['CONTENT_LENGTH'] = '77';
artpal_test_seed_post( 55, array( 5 ), '1', '1', 'Handler piece' );
$handler_raw = 'receiver_email=seller%40example.com&item_number=55&txn_id=txn_handler&payer_email=b%40example.com';
artpal_handle_paypal_ipn( $handler_raw );
$ipn_log = get_option( 'artpal_ipn_log' );
artpal_assert( $ipn_log[0]['result'] === 'sold' && $ipn_log[0]['raw_bytes'] === strlen( $handler_raw ) && $ipn_log[0]['content_length'] === 77, 'handler logs body size and content length with the result' );
artpal_assert( artpal_is_sold( 55 ), 'handler marks sold from the raw body alone' );
for ( $i = 0; $i < 30; $i++ ) {
	artpal_handle_paypal_ipn( '' );
}
artpal_assert( count( get_option( 'artpal_ipn_log' ) ) === 25, 'log is capped at 25 entries' );

$missing = $ipn_post;
$missing['item_number'] = '99999';
artpal_assert( artpal_process_paypal_ipn( $missing, 'raw' ) === 'missing_post', 'unknown item_number does not mark sold' );

$GLOBALS['artpal_test']['mail'] = array();
artpal_assert( artpal_process_paypal_ipn( $ipn_post, 'payment_status=Completed&txn_id=txn_ipn_1' ) === 'sold', 'VERIFIED Completed IPN marks sold' );
artpal_assert( artpal_is_sold( 50 ), 'IPN post is sold' );
$ipn_cats = artpal_get_category_ids( 50 );
artpal_assert( in_array( 3, $ipn_cats, true ) && ! in_array( 5, $ipn_cats, true ) && in_array( 12, $ipn_cats, true ) && in_array( 20, $ipn_cats, true ), 'IPN removes Available, appends Sold, keeps other categories' );
$last = get_post_meta( 50, '_artpal_last_sale', true );
artpal_assert( is_array( $last ) && $last['processor'] === 'paypal' && $last['event_id'] === 'txn_ipn_1' && $last['buyer_email'] === 'buyer@example.com', 'IPN stores paypal context' );
artpal_assert( count( $GLOBALS['artpal_test']['mail'] ) === 1, 'IPN sends one email' );
artpal_assert( artpal_process_paypal_ipn( $ipn_post, 'replay' ) === 'sold', 'replayed IPN stays sold' );
artpal_assert( count( $GLOBALS['artpal_test']['mail'] ) === 1, 'replayed IPN does not email again' );

artpal_assert( artpal_paypal_webscr_url() === 'https://ipnpb.paypal.com/cgi-bin/webscr', 'live IPN verify URL is the ipnpb host' );
$GLOBALS['artpal_test']['options']['ds_ap_usesandbox'] = '1';
artpal_assert( artpal_paypal_webscr_url() === 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr', 'sandbox IPN verify URL is the ipnpb host' );
artpal_assert( get_paypal_domain() === 'www.sandbox.paypal.com', 'sandbox checkout still uses www.sandbox.paypal.com' );
$GLOBALS['artpal_test']['options']['ds_ap_usesandbox'] = '0';
artpal_assert( get_paypal_domain() === 'www.paypal.com', 'live checkout still uses www.paypal.com' );

$GLOBALS['artpal_test']['ipn_postback'] = 'INVALID';
$GLOBALS['artpal_test']['ipn_requests'] = array();
artpal_process_paypal_ipn(
	array(
		'payment_status' => 'Pending',
	),
	'payment_date=03%3A12%3A59+Jan+13%2C+2009+PST&payment_status=Pending'
);
$kept_date = false;
foreach ( $GLOBALS['artpal_test']['ipn_requests'] as $ipn_try ) {
	if ( strpos( $ipn_try, 'payment_date=03%3A12%3A59+Jan+13%2C+2009+PST' ) !== false ) {
		$kept_date = true;
	}
}
artpal_assert( $kept_date, 'IPN postback keeps the original payment_date encoding' );
artpal_process_paypal_ipn(
	array(
		'item_name'      => 'Test post',
		'payment_status' => 'Pending',
	),
	''
);
artpal_assert( strpos( $GLOBALS['artpal_test']['ipn_request'], 'item_name=Test+post' ) !== false, 'IPN fallback encodes spaces as plus signs' );

/**
 * @param array $rows List of array( min, max, amount, mode, round ).
 * @return array
 */
function artpal_test_tier_rules( $rows ) {
	$raw = array();
	foreach ( $rows as $row ) {
		$raw[] = array(
			'min'    => $row[0],
			'max'    => $row[1],
			'amount' => $row[2],
			'mode'   => $row[3],
			'round'  => $row[4],
		);
	}
	return artpal_pricing_parse_rules( $raw );
}

// Bulk price increase: tiers, rounding, unsold only, preview fingerprint, undo.
artpal_test_reset();
artpal_assert( artpal_pricing_format_money( 1200 ) === '$1,200.00', 'money format uses the currency symbol and thousands separator' );
artpal_assert( artpal_pricing_format_diff( 15 ) === '+$15.00', 'a positive difference is marked' );
artpal_assert( artpal_pricing_format_percent( 10, 15 ) === '50.0%', 'percent increase is one decimal' );
artpal_assert( artpal_pricing_format_percent( 1000, 1200 ) === '20.0%', 'percent increase on $1,000 is exact' );
artpal_assert( artpal_pricing_format_percent( 0, 10 ) === '', 'percent increase has no baseline at zero' );

$round_up = artpal_test_tier_rules(
	array(
		array( '0', '', '30', 'percent', '5' ),
	)
);
artpal_assert( empty( $round_up['errors'] ), 'a single tier parses' );
artpal_assert( number_format( artpal_pricing_adjust( 10, $round_up['rules'][0] ), 2, '.', '' ) === '15.00', '$10 +30% rounds up to $15' );

$mid = artpal_test_tier_rules( array( array( '0', '', '20', 'percent', '5' ) ) );
artpal_assert( number_format( artpal_pricing_adjust( 95, $mid['rules'][0] ), 2, '.', '' ) === '115.00', '$95 +20% rounds up to $115' );

$high = artpal_test_tier_rules( array( array( '0', '', '20', 'percent', '100' ) ) );
artpal_assert( number_format( artpal_pricing_adjust( 1000, $high['rules'][0] ), 2, '.', '' ) === '1200.00', '$1,000 +20% rounded to $100 stays $1,200' );

$top = artpal_test_tier_rules( array( array( '0', '', '100', 'flat', '250' ) ) );
artpal_assert( number_format( artpal_pricing_adjust( 2450, $top['rules'][0] ), 2, '.', '' ) === '2750.00', '$2,450 plus $100 rounds up to $2,750' );

$dime = artpal_test_tier_rules( array( array( '0', '', '0', 'percent', '0.10' ) ) );
artpal_assert( number_format( artpal_pricing_adjust( 8.70, $dime['rules'][0] ), 2, '.', '' ) === '8.70', 'an amount already on the round step stays put' );
artpal_assert( number_format( artpal_pricing_adjust( 8.71, $dime['rules'][0] ), 2, '.', '' ) === '8.80', '$8.71 rounds up to the next $0.10' );

$round_only = artpal_test_tier_rules( array( array( '0', '', '0', 'percent', '5' ) ) );
artpal_assert( number_format( artpal_pricing_adjust( 12, $round_only['rules'][0] ), 2, '.', '' ) === '15.00', 'a zero increase still rounds up' );
artpal_assert( number_format( artpal_pricing_adjust( 15, $round_only['rules'][0] ), 2, '.', '' ) === '15.00', 'an exact multiple is not pushed to the next step' );

$adjacent = artpal_test_tier_rules(
	array(
		array( '0', '100', '10', 'percent', '' ),
		array( '100', '', '20', 'percent', '' ),
	)
);
artpal_assert( empty( $adjacent['errors'] ) && count( $adjacent['rules'] ) === 2, 'adjacent tiers do not overlap' );
artpal_assert( artpal_pricing_to_cents( artpal_pricing_adjust( 99.99, $adjacent['rules'][0] ) ) === 10999, '$99.99 is in the lower tier' );
artpal_assert( number_format( artpal_pricing_adjust( 100, $adjacent['rules'][1] ), 2, '.', '' ) === '120.00', '$100 is in the next tier, not the lower one' );

$overlap = artpal_test_tier_rules(
	array(
		array( '0', '150', '10', 'percent', '' ),
		array( '100', '200', '10', 'percent', '' ),
	)
);
artpal_assert( ! empty( $overlap['errors'] ) && empty( $overlap['rules'] ), 'overlapping tiers are rejected' );

$negative = artpal_test_tier_rules( array( array( '0', '', '-5', 'flat', '' ) ) );
artpal_assert( ! empty( $negative['errors'] ) && empty( $negative['rules'] ), 'a negative increase is rejected' );

$same_end = artpal_test_tier_rules( array( array( '100', '100', '10', 'percent', '' ) ) );
artpal_assert( ! empty( $same_end['errors'] ), 'Up to must be higher than From' );

$blank_row = artpal_pricing_parse_rules(
	array(
		array( 'min' => '', 'max' => '', 'amount' => '', 'mode' => 'percent', 'round' => '' ),
		array( 'min' => '0', 'max' => '', 'amount' => '10', 'mode' => 'flat', 'round' => '' ),
	)
);
artpal_assert( empty( $blank_row['errors'] ) && count( $blank_row['rules'] ) === 1, 'a blank tier row is ignored' );
artpal_assert( ! empty( artpal_pricing_parse_rules( array() )['errors'] ), 'no tiers is an error' );

$half = artpal_test_tier_rules(
	array(
		array( '0', '100', '50', 'percent', '' ),
		array( '100', '', '50', 'percent', '' ),
	)
);
artpal_test_reset();
artpal_test_seed_post( 90, array( 5 ), '90', '0', 'Ninety' );
artpal_test_seed_post( 100, array( 5 ), '100', '0', 'One hundred' );
$once = artpal_pricing_build_plan( 'price', $half['rules'], false );
$once_new = array();
foreach ( $once['rows'] as $row ) {
	$once_new[ $row['post_id'] ] = number_format( $row['new'], 2, '.', '' );
}
artpal_assert( $once_new[90] === '135.00', 'a price is raised by the tier it is in now, one time' );
artpal_assert( $once_new[90] !== '202.50', 'a raised price is not fed into the next tier' );
artpal_assert( $once_new[100] === '150.00', 'the boundary price uses the higher tier' );

artpal_test_reset();
artpal_test_seed_post( 101, array( 5 ), '10', '0', 'Small study' );
artpal_test_seed_post( 102, array( 5 ), '95', '0', 'Medium' );
artpal_test_seed_post( 103, array( 5 ), '1000', '0', 'Large' );
artpal_test_seed_post( 104, array( 5 ), '2450', '0', 'Huge' );
artpal_test_seed_post( 105, array( 5, 6 ), '80', '0', 'Held back' );
artpal_test_seed_post( 106, array( 5 ), '60', '0', 'Draft sketch' );
$GLOBALS['artpal_test']['posts'][106]['post_status'] = 'draft';
artpal_test_seed_post( 107, array( 5 ), '70', '0', 'Private study' );
$GLOBALS['artpal_test']['posts'][107]['post_status'] = 'private';
artpal_test_seed_post( 108, array( 5 ), '60', '0', 'Trashed' );
$GLOBALS['artpal_test']['posts'][108]['post_status'] = 'trash';
artpal_test_seed_post( 109, array( 3 ), '10', '0', 'Sold piece' );
artpal_test_seed_post( 110, array( 5, 3 ), '10', '0', 'Sold and available' );
artpal_test_seed_post( 111, array( 99 ), '10', '0', 'Not for sale' );
artpal_test_seed_post( 112, array( 5 ), null, '0', 'Ask me' );
artpal_test_seed_post( 113, array( 5 ), '0', '0', 'Zero' );

$tiers = artpal_test_tier_rules(
	array(
		array( '0', '50', '30', 'percent', '5' ),
		array( '50', '500', '20', 'percent', '5' ),
		array( '500', '2000', '20', 'percent', '100' ),
		array( '2000', '', '100', 'flat', '250' ),
	)
);
artpal_assert( empty( $tiers['errors'] ) && count( $tiers['rules'] ) === 4, 'four price tiers parse' );
$catalog = artpal_pricing_build_plan( 'price', $tiers['rules'], false );
$catalog_new = array();
$catalog_ids = array();
foreach ( $catalog['rows'] as $row ) {
	$catalog_new[ $row['post_id'] ] = number_format( $row['new'], 2, '.', '' );
	$catalog_ids[] = $row['post_id'];
}
artpal_assert( $catalog_ids === array( 101, 106, 107, 105, 102, 103, 104 ), 'changes are sorted by the current price' );
artpal_assert( $catalog_new[101] === '15.00', 'catalog $10 becomes $15' );
artpal_assert( $catalog_new[102] === '115.00', 'catalog $95 becomes $115' );
artpal_assert( $catalog_new[103] === '1200.00', 'catalog $1,000 becomes $1,200 and not $1,300' );
artpal_assert( $catalog_new[104] === '2750.00', 'catalog $2,450 becomes $2,750' );
artpal_assert( $catalog_new[105] === '100.00', 'a sale-disabled unsold piece is included' );
artpal_assert( $catalog['rows'][3]['status_label'] === 'Disabled', 'the preview names a sale-disabled piece' );
artpal_assert( $catalog_new[106] === '75.00' && $catalog['rows'][1]['post_status'] === 'draft', 'a draft in Available is included' );
artpal_assert( $catalog_new[107] === '85.00' && $catalog['rows'][2]['post_status'] === 'private', 'a private post in Available is included' );
artpal_assert( ! isset( $catalog_new[108] ) && ! isset( $catalog_new[109] ) && ! isset( $catalog_new[110] ) && ! isset( $catalog_new[111] ), 'trash, sold, and other posts are left out' );
artpal_assert( ! isset( $catalog_new[112] ) && (int) $catalog['no_value'] === 1, 'an unsold post with no price is skipped' );
artpal_assert( ! isset( $catalog_new[113] ) && (int) $catalog['unchanged'] === 1, 'a zero price that is already rounded is unchanged' );
artpal_assert( number_format( $catalog['total_before'], 2, '.', '' ) === '3765.00', 'preview total before is the sum of the changes' );
artpal_assert( number_format( $catalog['total_after'], 2, '.', '' ) === '4340.00', 'preview total after is the sum of the new prices' );
artpal_assert( artpal_pricing_format_percent( $catalog['total_before'], $catalog['total_after'] ) === '15.3%', 'average increase covers the changed items' );
$same_catalog = artpal_pricing_build_plan( 'price', $tiers['rules'], false );
artpal_assert( $same_catalog['fingerprint'] === $catalog['fingerprint'], 'the same catalog previews to the same fingerprint' );

$forged = artpal_pricing_commit_plan(
	array(
		'field'        => 'price',
		'rows'         => array(
			array( 'post_id' => 109, 'old' => 10, 'new' => 99 ),
		),
		'total_before' => 10,
		'total_after'  => 99,
	)
);
artpal_assert( null === $forged && get_post_meta( 109, 'artpal_price', true ) === '10', 'commit will not reprice a sold post' );
artpal_assert( get_option( 'artpal_last_price_run', 'missing' ) === 'missing', 'a refused commit does not store an undo snapshot' );

update_post_meta( 101, 'artpal_price', '12' );
$stale = artpal_pricing_apply( 'price', $tiers['rules'], false, $catalog['fingerprint'] );
artpal_assert( $stale['ok'] === false, 'a stale preview fingerprint does not apply' );
artpal_assert( get_post_meta( 101, 'artpal_price', true ) === '12', 'a stale apply leaves the edited price' );
artpal_assert( get_option( 'artpal_last_price_run', 'missing' ) === 'missing', 'a stale apply stores no run' );
$fresh = artpal_pricing_build_plan( 'price', $tiers['rules'], false );
artpal_assert( $fresh['fingerprint'] !== $catalog['fingerprint'], 'editing a price changes the fingerprint' );

update_post_meta( 101, 'artpal_price', '10' );
$ready  = artpal_pricing_build_plan( 'price', $tiers['rules'], false );
$saved  = artpal_pricing_apply( 'price', $tiers['rules'], false, $ready['fingerprint'] );
artpal_assert( $saved['ok'] === true && is_array( $saved['record'] ), 'a matching fingerprint applies' );
artpal_assert( get_post_meta( 101, 'artpal_price', true ) === '15.00', 'apply writes a two-decimal price' );
artpal_assert( get_post_meta( 103, 'artpal_price', true ) === '1200.00', 'apply writes $1,200.00 and not a rounded-up extra hundred' );
artpal_assert( get_post_meta( 104, 'artpal_price', true ) === '2750.00', 'apply writes the rounded top-tier price' );
artpal_assert( get_post_meta( 109, 'artpal_price', true ) === '10' && get_post_meta( 110, 'artpal_price', true ) === '10', 'apply does not change sold prices' );
artpal_assert( get_post_meta( 108, 'artpal_price', true ) === '60' && get_post_meta( 111, 'artpal_price', true ) === '10', 'apply does not change trash or unrelated posts' );
artpal_assert( artpal_get_price( 112 ) === null, 'apply does not invent a price for a post that has none' );
artpal_assert( in_array( 6, artpal_get_category_ids( 105 ), true ) && in_array( 5, artpal_get_category_ids( 105 ), true ), 'repricing does not change categories' );

update_post_meta( 102, 'artpal_price', '150.00' );
$undo = artpal_pricing_undo_last_run();
artpal_assert( $undo['missing'] === false && $undo['restored'] === 6 && $undo['skipped'] === 1, 'undo restores the run and skips a price edited since' );
artpal_assert( get_post_meta( 101, 'artpal_price', true ) === '10.00', 'undo puts the original price back' );
artpal_assert( get_post_meta( 102, 'artpal_price', true ) === '150.00', 'undo leaves the hand-edited price' );
artpal_assert( get_post_meta( 103, 'artpal_price', true ) === '1000.00', 'undo restores a large price exactly' );
artpal_assert( get_option( 'artpal_last_price_run', 'missing' ) === 'missing', 'undo removes the snapshot' );
$undo_again = artpal_pricing_undo_last_run();
artpal_assert( $undo_again['missing'] === true && $undo_again['restored'] === 0, 'a second undo has nothing to restore' );

artpal_test_reset();
artpal_test_seed_post( 301, array( 5 ), '20.40', '15', 'Cents' );
$cents_rules = artpal_test_tier_rules( array( array( '0', '', '5.10', 'flat', '' ) ) );
$cents_plan  = artpal_pricing_build_plan( 'price', $cents_rules['rules'], false );
update_post_meta( 301, 'artpal_price', '30.00' );
$cents_stale = artpal_pricing_apply( 'price', $cents_rules['rules'], false, $cents_plan['fingerprint'] );
artpal_assert( $cents_stale['ok'] === false && get_post_meta( 301, 'artpal_price', true ) === '30.00', 'fingerprint mismatch aborts before writing' );
update_post_meta( 301, 'artpal_price', '20.40' );
$cents_plan = artpal_pricing_build_plan( 'price', $cents_rules['rules'], false );
$cents_saved = artpal_pricing_apply( 'price', $cents_rules['rules'], false, $cents_plan['fingerprint'] );
artpal_assert( $cents_saved['ok'] === true && get_post_meta( 301, 'artpal_price', true ) === '25.50', 'commit writes a 25.50-style price string' );
artpal_assert( get_post_meta( 301, 'artpal_shipping', true ) === '15', 'a price increase does not change shipping' );

artpal_test_reset();
artpal_test_seed_post( 201, array( 5 ), '100', '15', 'Ships' );
artpal_test_seed_post( 202, array( 5 ), '100', '0', 'Free' );
artpal_test_seed_post( 203, array( 5 ), '100', null, 'Blank shipping' );
artpal_test_seed_post( 204, array( 5 ), '100', '40', 'Far' );
artpal_test_seed_post( 205, array( 3 ), '100', '10', 'Sold shipping' );
$ship_rules = artpal_test_tier_rules( array( array( '0', '30', '5', 'flat', '' ) ) );
$ship       = artpal_pricing_build_plan( 'shipping', $ship_rules['rules'], false );
$ship_new   = array();
foreach ( $ship['rows'] as $row ) {
	$ship_new[ $row['post_id'] ] = number_format( $row['new'], 2, '.', '' );
}
artpal_assert( $ship_new === array( 201 => '20.00' ), 'shipping increases the amounts inside the range' );
artpal_assert( (int) $ship['skipped_free'] === 2 && (int) $ship['unchanged'] === 1, 'free shipping is skipped unless asked for' );
$ship_free = artpal_pricing_build_plan( 'shipping', $ship_rules['rules'], true );
$ship_free_ids = array();
foreach ( $ship_free['rows'] as $row ) {
	$ship_free_ids[ $row['post_id'] ] = number_format( $row['new'], 2, '.', '' );
}
artpal_assert( $ship_free_ids === array( 202 => '5.00', 203 => '5.00', 201 => '20.00' ), 'free shipping can be raised when the box is checked' );
artpal_assert( (int) $ship_free['skipped_free'] === 0 && isset( $ship_free_ids[202], $ship_free_ids[203] ), 'checking the box stops skipping free shipping' );
$ship_saved = artpal_pricing_apply( 'shipping', $ship_rules['rules'], false, $ship['fingerprint'] );
artpal_assert( $ship_saved['ok'] === true && get_post_meta( 201, 'artpal_shipping', true ) === '20.00', 'shipping apply writes artpal_shipping' );
artpal_assert( get_post_meta( 202, 'artpal_shipping', true ) === '0' && get_post_meta( 203, 'artpal_shipping', true ) === '', 'free shipping meta is untouched by default' );
artpal_assert( get_post_meta( 201, 'artpal_price', true ) === '100' && get_post_meta( 205, 'artpal_shipping', true ) === '10', 'shipping apply leaves prices and sold shipping alone' );

$GLOBALS['artpal_test']['can'] = false;
$prices_refused = false;
try {
	artpal_render_prices_page();
} catch ( Exception $e ) {
	$prices_refused = ( strpos( $e->getMessage(), 'permission' ) !== false );
}
artpal_assert( $prices_refused, 'prices page refuses users who cannot manage options' );

$GLOBALS['artpal_test']['can'] = true;
$GLOBALS['artpal_test']['nonce_ok'] = false;
$_POST = array( 'artpal_prices_preview' => 'Preview' );
$prices_nonce = false;
try {
	artpal_render_prices_page();
} catch ( Exception $e ) {
	$prices_nonce = ( strpos( $e->getMessage(), 'bad nonce' ) !== false );
}
artpal_assert( $prices_nonce, 'prices page checks the preview nonce' );

artpal_test_reset();
$GLOBALS['artpal_test']['can'] = true;
$GLOBALS['artpal_test']['nonce_ok'] = true;
artpal_test_seed_post( 401, array( 5 ), '10', '0', 'Small study' );
$_POST = array(
	'artpal_prices_preview' => 'Preview',
	'artpal_field'          => 'price',
	'artpal_rules'          => array(
		array(
			'min'    => '0',
			'max'    => '100',
			'amount' => '30',
			'mode'   => 'percent',
			'round'  => '5',
		),
	),
);
ob_start();
artpal_render_prices_page();
$preview_html = ob_get_clean();
artpal_assert( strpos( $preview_html, 'Small study' ) !== false, 'preview lists the post title' );
artpal_assert( strpos( $preview_html, '$10.00' ) !== false && strpos( $preview_html, '$15.00' ) !== false, 'preview shows the old price and the new price' );
artpal_assert( strpos( $preview_html, '+$5.00' ) !== false, 'preview shows the difference' );
artpal_assert( strpos( $preview_html, 'name="artpal_prices_apply"' ) !== false, 'preview offers apply' );
artpal_assert( get_post_meta( 401, 'artpal_price', true ) === '10', 'preview does not save' );

$page_rules = artpal_test_tier_rules( array( array( '0', '100', '30', 'percent', '5' ) ) );
$page_plan  = artpal_pricing_build_plan( 'price', $page_rules['rules'], false );
$_POST['artpal_prices_apply'] = 'Apply these prices';
unset( $_POST['artpal_prices_preview'] );
$_POST['artpal_fingerprint']  = $page_plan['fingerprint'];
$_POST['artpal_include_free'] = '0';
ob_start();
artpal_render_prices_page();
$applied_html = ob_get_clean();
artpal_assert( strpos( $applied_html, 'Prices updated.' ) !== false, 'apply tells her the prices were saved' );
artpal_assert( strpos( $applied_html, 'Total before' ) !== false && strpos( $applied_html, 'Total after' ) !== false, 'apply shows the before and after totals' );
artpal_assert( get_post_meta( 401, 'artpal_price', true ) === '15.00', 'the prices page saves the previewed price' );
artpal_assert( strpos( $applied_html, 'Undo last run' ) !== false, 'the last run can be undone from the page' );

update_post_meta( 401, 'artpal_price', '12.00' );
ob_start();
artpal_render_prices_page();
$stale_html = ob_get_clean();
artpal_assert( strpos( $stale_html, 'nothing was saved' ) !== false, 'the page refuses a preview that is out of date' );
artpal_assert( get_post_meta( 401, 'artpal_price', true ) === '12.00', 'an out-of-date apply does not write' );
$kept_run = get_option( 'artpal_last_price_run' );
artpal_assert( is_array( $kept_run ) && $kept_run['rows'][0]['new'] === '15.00', 'an out-of-date apply keeps the previous undo snapshot' );

$_POST = array( 'artpal_prices_undo' => 'Undo last run' );
ob_start();
artpal_render_prices_page();
$undo_html = ob_get_clean();
artpal_assert( strpos( $undo_html, 'Left 1' ) !== false, 'the page says when undo skips an edited price' );
artpal_assert( get_post_meta( 401, 'artpal_price', true ) === '12.00', 'undo from the page leaves the edited price' );
artpal_assert( get_option( 'artpal_last_price_run', 'missing' ) === 'missing', 'undo from the page clears the snapshot' );
$_POST = array();

// Registration and source guards
artpal_register_shortcodes();
ds_ap_add_pages();
$shortcodes = array();
$content_priority = null;
$option_cap = null;
foreach ( $GLOBALS['artpal_registered'] as $reg ) {
	if ( $reg[0] === 'shortcode' ) {
		$shortcodes[] = $reg[1];
	}
	if ( $reg[0] === 'filter' && $reg[1] === 'the_content' && $reg[2] === 'ds_ap_parsecontent' ) {
		$content_priority = $reg[3];
	}
	if ( $reg[0] === 'options_page' ) {
		$option_cap = $reg[1];
	}
}
artpal_assert( in_array( 'artpal', $shortcodes, true ), '[artpal] shortcode is registered' );
artpal_assert( ! in_array( 'artpal=insert', $shortcodes, true ), '[artpal=insert] is not registered with add_shortcode' );
artpal_assert( $content_priority === 10, 'the_content fallback runs at priority 10' );
artpal_assert( $option_cap === 'manage_options', 'settings page capability is manage_options' );
$prices_cap = null;
$prices_cb  = null;
foreach ( $GLOBALS['artpal_registered'] as $reg ) {
	if ( $reg[0] === 'management_page' && $reg[2] === 'artpal-prices' ) {
		$prices_cap = $reg[1];
		$prices_cb  = $reg[3];
	}
}
artpal_assert( $prices_cap === 'manage_options', 'prices page capability is manage_options' );
artpal_assert( $prices_cb === 'artpal_render_prices_page', 'prices page callback is registered' );

global $ds_ap_options_names;
artpal_assert( in_array( 'ds_ap_notify_email', $ds_ap_options_names, true ), 'notify email option is registered for activation' );
artpal_assert( in_array( 'ds_ap_email_subject_prefix', $ds_ap_options_names, true ), 'subject prefix option is registered for activation' );
artpal_assert( ! in_array( 'ds_ap_pdt_token', $ds_ap_options_names, true ), 'PDT token option is gone' );
$GLOBALS['artpal_test']['options']['ds_ap_notify_email'] = 'keep@example.com';
ds_ap_install();
artpal_assert( get_option( 'ds_ap_notify_email' ) === 'keep@example.com', 'activation does not reset notify email' );

$plugin = file_get_contents( dirname( __DIR__ ) . '/artpal.php' );
$ipn    = file_get_contents( dirname( __DIR__ ) . '/ipn.php' );
$opts   = file_get_contents( dirname( __DIR__ ) . '/artpal-options.php' );
artpal_assert( strpos( $plugin, 'JamieWG' ) === false && strpos( $plugin, 'Hudson Valley Painter' ) === false, 'sold email has no hardcoded site name or address' );
artpal_assert( strpos( $plugin, 'edit_plugins' ) === false, 'edit_plugins capability is gone' );
artpal_assert( strpos( $plugin, 'fsockopen' ) === false && strpos( $ipn, 'fsockopen' ) === false, 'no fsockopen' );
artpal_assert( strpos( $ipn, 'wp-load.php' ) !== false && strpos( $ipn, 'wp-blog-header.php' ) === false, 'ipn.php boots through wp-load.php' );
artpal_assert( strpos( $ipn, '/../../../wp-load.php' ) === false, 'ipn.php does not probe wp-load.php through a dot-dot path' );
artpal_assert( strpos( $ipn, '.wordpress/wp-load.php' ) !== false, 'ipn.php looks for Flywheel .wordpress/wp-load.php' );
$raw_pos  = strpos( $ipn, "file_get_contents( 'php://input' )" );
$load_pos = strpos( $ipn, 'require $artpal_wp_load' );
artpal_assert( $raw_pos !== false && $load_pos !== false && $raw_pos < $load_pos, 'ipn.php reads the body before loading WordPress' );
artpal_assert( strpos( $plugin, "get_option( 'siteurl' ) . '/wp-content/plugins/artpal/ipn.php'" ) === false, 'notify URL is not hardcoded to the artpal directory' );
artpal_assert( stripos( $plugin, 'stripe' ) === false && stripos( $ipn, 'stripe' ) === false && stripos( $opts, 'stripe' ) === false, 'no Stripe code' );
artpal_assert( strpos( $plugin, 'admin-functions.php' ) === false, 'admin-functions.php require is gone' );
artpal_assert( strpos( $opts, 'settings_errors' ) === false, 'settings screen does not print a second saved notice' );

// Buy button image: a flushed host cache can leave a stale absolute URL in the option.
$button_map = artpal_paypal_button_map();
$stale_button = 'https://cdn.getflywheel.com/content/old-cache/wp-content/plugins/artpal/images/paypal/btn_buynow_LG.gif?wpe_cache=1';
artpal_assert( isset( $button_map['btn_buynow_LG.gif'] ), 'bundled large buy button is found' );
artpal_assert( artpal_paypal_button_url( $stale_button ) === $button_map['btn_buynow_LG.gif'], 'stale button URL resolves from the bundled filename' );
artpal_assert( artpal_paypal_button_url( 'btn_buynow_SM.gif' ) === $button_map['btn_buynow_SM.gif'], 'stored button filename resolves to the current URL' );
artpal_assert( artpal_paypal_button_url( $button_map['btn_buynow_LG.gif'] ) === $button_map['btn_buynow_LG.gif'], 'current button URL is left as-is' );
artpal_assert( artpal_paypal_button_url( 'https://example.com/custom-button.gif' ) === 'https://example.com/custom-button.gif', 'custom button URL is not rewritten' );
artpal_assert( artpal_sanitize_paypal_button( $stale_button ) === $button_map['btn_buynow_LG.gif'], 'saving a stale button URL stores the current URL' );
$GLOBALS['artpal_test']['options']['ds_ap_paypalbutton'] = 'https://example.com/custom-button.gif';
artpal_assert( artpal_sanitize_paypal_button( 'https://evil.example/nope.gif' ) === 'https://example.com/custom-button.gif', 'unrecognized button image keeps the saved URL' );

artpal_test_reset();
artpal_test_seed_post( 80, array( 5 ), '150', '15', 'Cache piece' );
$GLOBALS['artpal_test']['options']['ds_ap_paypalbutton'] = $stale_button;
$html = artpal_render_buy_now( 80 );
artpal_assert( strpos( $html, 'src="' . $button_map['btn_buynow_LG.gif'] . '"' ) !== false, 'checkout uses the live button URL when the option is stale' );
artpal_assert( strpos( $html, 'getflywheel.com' ) === false, 'checkout does not keep the stale button host' );
artpal_assert( strpos( $plugin, 'Version: 2.2.0' ) !== false, 'plugin version is 2.2.0' );
artpal_assert( ! function_exists( 'artpal_maybe_handle_paypal_return' ) && ! function_exists( 'artpal_paypal_pdt_fetch' ), 'thank-you return and PDT handlers are gone' );
artpal_assert( strpos( $plugin, "strcasecmp( \$status, 'Completed' )" ) === false, 'IPN does not require payment_status Completed' );
artpal_assert( strpos( $plugin, 'curl_init' ) === false, 'verification uses the WordPress HTTP API, not curl' );
artpal_assert( strpos( $ipn, 'artpal_ipn_log' ) !== false && strpos( $ipn, 'notify_url' ) !== false, 'diagnostic shows the request log and the notify_url' );
artpal_assert( strpos( $opts, 'pdt_token' ) === false && strpos( $opts, 'rm=2' ) === false, 'settings screen no longer mentions PDT or rm=2' );
$_POST = array();
$_GET = array();

if ( ! defined( 'ARTPAL_IPN_LIBRARY' ) ) {
	define( 'ARTPAL_IPN_LIBRARY', true );
}
require dirname( __DIR__ ) . '/ipn.php';

/**
 * @param string $path File to create, including parents.
 * @return void
 */
function artpal_test_touch( $path ) {
	$dir = dirname( $path );
	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0777, true );
	}
	file_put_contents( $path, "<?php\n" );
}

$artpal_ipn_root = sys_get_temp_dir() . '/artpal-ipn-' . getmypid();
$fly_plugin      = $artpal_ipn_root . '/fly/wp-content/plugins/artpal-master';
mkdir( $fly_plugin, 0777, true );
artpal_test_touch( $fly_plugin . '/wp-load.php' );
artpal_test_touch( $artpal_ipn_root . '/fly/.wordpress/wp-load.php' );
artpal_assert( artpal_find_wp_load( $fly_plugin ) === $artpal_ipn_root . '/fly/.wordpress/wp-load.php', 'Flywheel IPN bootstrap uses .wordpress/wp-load.php' );

$std_plugin = $artpal_ipn_root . '/std/wp-content/plugins/artpal';
mkdir( $std_plugin, 0777, true );
artpal_test_touch( $artpal_ipn_root . '/std/wp-load.php' );
artpal_assert( artpal_find_wp_load( $std_plugin ) === $artpal_ipn_root . '/std/wp-load.php', 'standard IPN bootstrap uses the web root wp-load.php' );

$sub_plugin = $artpal_ipn_root . '/sub/wp-content/plugins/artpal';
mkdir( $sub_plugin, 0777, true );
artpal_test_touch( $artpal_ipn_root . '/sub/wp/wp-load.php' );
artpal_assert( artpal_find_wp_load( $sub_plugin ) === $artpal_ipn_root . '/sub/wp/wp-load.php', 'subdirectory IPN bootstrap uses wp/wp-load.php' );

echo "\n";
if ( $failed ) {
	echo "$failed assertion(s) failed.\n";
	exit( 1 );
}
echo "All ArtPal assertions passed.\n";
exit( 0 );
