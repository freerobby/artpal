<?php
/**
 * Settings → ArtPal. Settings API. Capability: manage_options.
 *
 * Keeps every existing ds_ap_* option. Adds ds_ap_notify_email and
 * ds_ap_email_subject_prefix (both empty by default; add_option on activate).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'artpal_register_settings' );

/**
 * Register each ArtPal option individually so activation never has to rewrite them.
 *
 * @return void
 */
function artpal_register_settings() {
	$group = 'artpal';

	$category = array( 'artpal_sanitize_category_id' );
	$text     = array( 'artpal_sanitize_text_option' );
	$html     = array( 'artpal_sanitize_html_option' );
	$url      = array( 'artpal_sanitize_url_option' );
	$email    = array( 'artpal_sanitize_email_option' );
	$percent  = array( 'artpal_sanitize_percent' );
	$flag     = array( 'artpal_sanitize_flag' );

	$map = array(
		'ds_ap_unsoldcategory'        => $category,
		'ds_ap_soldcategory'          => $category,
		'ds_ap_saledisabledcategory'  => $category,
		'ds_ap_soldcode'              => $html,
		'ds_ap_prebuttontext'         => $html,
		'ds_ap_textifsaledisabled'    => $html,
		'ds_ap_textifunknownmetadata' => $html,
		'ds_ap_thankyoupage'          => $url,
		'ds_ap_cancelpage'            => $url,
		'ds_ap_paypalemail'           => $email,
		'ds_ap_currencycode4217'      => array( 'artpal_sanitize_currency_code' ),
		'ds_ap_paypalbutton'          => array( 'artpal_sanitize_paypal_button' ),
		'ds_ap_taxrate'               => $percent,
		'ds_ap_discountpercent'       => $percent,
		'ds_ap_disableecommerce'      => $flag,
		'ds_ap_usesandbox'            => $flag,
		'ds_ap_notify_email'          => $email,
		'ds_ap_email_subject_prefix'  => $text,
	);

	foreach ( $map as $key => $callback ) {
		register_setting(
			$group,
			$key,
			array(
				'type'              => 'string',
				'sanitize_callback' => $callback[0],
				'show_in_rest'      => false,
			)
		);
	}

	add_settings_section( 'artpal_categories', 'Categories', 'artpal_section_categories', 'artpal' );
	add_settings_section( 'artpal_copy', 'Copy', 'artpal_section_noop', 'artpal' );
	add_settings_section( 'artpal_pages', 'Pages', 'artpal_section_noop', 'artpal' );
	add_settings_section( 'artpal_paypal', 'PayPal', 'artpal_section_paypal', 'artpal' );
	add_settings_section( 'artpal_pricing', 'Pricing', 'artpal_section_noop', 'artpal' );
	add_settings_section( 'artpal_notifications', 'Notifications', 'artpal_section_notifications', 'artpal' );

	add_settings_field( 'ds_ap_unsoldcategory', 'Available category', 'artpal_field_category', 'artpal', 'artpal_categories', array(
		'key'         => 'ds_ap_unsoldcategory',
		'description' => 'Posts in this category can show a buy button.',
	) );
	add_settings_field( 'ds_ap_saledisabledcategory', 'Sale disabled category', 'artpal_field_category', 'artpal', 'artpal_categories', array(
		'key'         => 'ds_ap_saledisabledcategory',
		'allow_none'  => true,
		'description' => 'Not Currently Available. Choose (None) if you do not use this.',
	) );
	add_settings_field( 'ds_ap_soldcategory', 'Sold category', 'artpal_field_category', 'artpal', 'artpal_categories', array(
		'key'         => 'ds_ap_soldcategory',
		'description' => 'ArtPal appends this category and removes Available when a piece sells.',
	) );

	add_settings_field( 'ds_ap_soldcode', 'Sold HTML', 'artpal_field_text', 'artpal', 'artpal_copy', array(
		'key'         => 'ds_ap_soldcode',
		'decode'      => true,
		'description' => 'Shown instead of the buy button after the piece is sold.',
	) );
	add_settings_field( 'ds_ap_prebuttontext', 'Text before the button', 'artpal_field_text', 'artpal', 'artpal_copy', array(
		'key'         => 'ds_ap_prebuttontext',
		'decode'      => true,
		'description' => 'Use _PRICE_ and _SHIPPING_. Shipping of 0 is shown as free.',
	) );
	add_settings_field( 'ds_ap_textifsaledisabled', 'Sale disabled text', 'artpal_field_text', 'artpal', 'artpal_copy', array(
		'key'    => 'ds_ap_textifsaledisabled',
		'decode' => true,
	) );
	add_settings_field( 'ds_ap_textifunknownmetadata', 'Missing price text', 'artpal_field_text', 'artpal', 'artpal_copy', array(
		'key'         => 'ds_ap_textifunknownmetadata',
		'decode'      => true,
		'description' => 'Shown when the post is Available but artpal_price is empty.',
	) );

	add_settings_field( 'ds_ap_thankyoupage', 'Thank-you URL', 'artpal_field_text', 'artpal', 'artpal_pages', array(
		'key'  => 'ds_ap_thankyoupage',
		'type' => 'url',
	) );
	add_settings_field( 'ds_ap_cancelpage', 'Cancel URL', 'artpal_field_text', 'artpal', 'artpal_pages', array(
		'key'  => 'ds_ap_cancelpage',
		'type' => 'url',
	) );

	add_settings_field( 'ds_ap_paypalemail', 'PayPal email', 'artpal_field_text', 'artpal', 'artpal_paypal', array(
		'key'  => 'ds_ap_paypalemail',
		'type' => 'email',
	) );
	add_settings_field( 'ds_ap_currencycode4217', 'Currency', 'artpal_field_currency', 'artpal', 'artpal_paypal' );
	add_settings_field( 'ds_ap_paypalbutton', 'Button image', 'artpal_field_paypal_button', 'artpal', 'artpal_paypal' );
	add_settings_field( 'ds_ap_usesandbox', 'Sandbox', 'artpal_field_checkbox', 'artpal', 'artpal_paypal', array(
		'key'   => 'ds_ap_usesandbox',
		'label' => 'Use the PayPal sandbox. No live charges are processed.',
	) );
	add_settings_field( 'ds_ap_taxrate', 'Sales tax %', 'artpal_field_text', 'artpal', 'artpal_pricing', array(
		'key'         => 'ds_ap_taxrate',
		'class'       => 'small-text',
		'description' => 'Percent applied to the item price only, not shipping.',
	) );
	add_settings_field( 'ds_ap_discountpercent', 'Storewide discount %', 'artpal_field_text', 'artpal', 'artpal_pricing', array(
		'key'         => 'ds_ap_discountpercent',
		'class'       => 'small-text',
		'description' => 'Use 0 to turn the storewide sale off.',
	) );
	add_settings_field( 'ds_ap_disableecommerce', 'Disable checkout', 'artpal_field_checkbox', 'artpal', 'artpal_pricing', array(
		'key'   => 'ds_ap_disableecommerce',
		'label' => 'Show the price text and hide the PayPal button.',
	) );

	add_settings_field( 'ds_ap_notify_email', 'Notification email', 'artpal_field_text', 'artpal', 'artpal_notifications', array(
		'key'         => 'ds_ap_notify_email',
		'type'        => 'email',
		'description' => 'Leave blank to use the PayPal email, then the site admin email.',
	) );
	add_settings_field( 'ds_ap_email_subject_prefix', 'Email subject prefix', 'artpal_field_text', 'artpal', 'artpal_notifications', array(
		'key'         => 'ds_ap_email_subject_prefix',
		'description' => 'Leave blank to use the site title. The subject is [Prefix] Sold: title.',
	) );
}

/**
 * @return void
 */
function artpal_section_noop() {
}

/**
 * @return void
 */
function artpal_section_categories() {
	echo '<p>Inventory state is these categories. Marking a piece sold removes Available and appends Sold. Other categories on the post stay.</p>';
}

/**
 * @return void
 */
function artpal_section_paypal() {
	echo '<p>Checkout is a PayPal Buy Now button. When a payment completes, PayPal posts an Instant Payment Notification to this URL. ArtPal confirms the notification with PayPal and moves the post to Sold. The buyer does not have to return to the thank-you page.</p>';
	echo '<p><code>' . esc_html( ipn_page_url() ) . '</code></p>';
	echo '<p>In the PayPal account that receives payments, set Account Settings &rarr; Notifications &rarr; Instant payment notifications &rarr; Notification URL to exactly that address. PayPal follows a redirect to a different host, such as one with or without <code>www.</code>, but drops the payment data on the way, so the post stays Available and nothing is logged here.</p>';
	echo '<p>Recent requests to that URL and their results: <code>' . esc_html( ipn_page_url() . '?artpal_diag=1' ) . '</code></p>';
}

/**
 * @return void
 */
function artpal_section_notifications() {
	echo '<p>Sent once, the first time a post moves to Sold.</p>';
}

/**
 * @param mixed $value Posted category id.
 * @return string
 */
function artpal_sanitize_category_id( $value ) {
	return (string) intval( $value );
}

/**
 * @param mixed $value Posted text.
 * @return string
 */
function artpal_sanitize_text_option( $value ) {
	return sanitize_text_field( wp_unslash( $value ) );
}

/**
 * @param mixed $value Posted HTML.
 * @return string
 */
function artpal_sanitize_html_option( $value ) {
	return wp_kses_post( wp_unslash( $value ) );
}

/**
 * @param mixed $value Posted URL.
 * @return string
 */
function artpal_sanitize_url_option( $value ) {
	return esc_url_raw( wp_unslash( $value ) );
}

/**
 * @param mixed $value Posted email. Empty is allowed.
 * @return string
 */
function artpal_sanitize_email_option( $value ) {
	$value = trim( (string) wp_unslash( $value ) );
	if ( $value === '' ) {
		return '';
	}
	$clean = sanitize_email( $value );
	return is_email( $clean ) ? $clean : '';
}

/**
 * @param mixed $value Posted percent.
 * @return string
 */
function artpal_sanitize_percent( $value ) {
	$value = str_replace( ',', '.', trim( (string) wp_unslash( $value ) ) );
	if ( $value === '' || ! is_numeric( $value ) ) {
		return '0.00';
	}
	$number = (float) $value;
	if ( $number < 0 ) {
		$number = 0;
	}
	if ( $number > 100 ) {
		$number = 100;
	}
	return number_format( $number, 2, '.', '' );
}

/**
 * @param mixed $value Posted flag.
 * @return string
 */
function artpal_sanitize_flag( $value ) {
	return ( (string) $value === '1' ) ? '1' : '0';
}

/**
 * Save the ISO currency code and the matching symbol option.
 *
 * @param mixed $value Posted ISO code.
 * @return string
 */
function artpal_sanitize_currency_code( $value ) {
	$code = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $value ), 0, 3 ) );
	global $artpal_currencycodes;
	if ( is_array( $artpal_currencycodes ) ) {
		foreach ( $artpal_currencycodes as $row ) {
			if ( isset( $row[1] ) && $row[1] === $code ) {
				$symbol = isset( $row[2] ) ? $row[2] : '';
				update_option( 'ds_ap_currencysymbol', $symbol );
				return $code;
			}
		}
	}
	$existing = get_option( 'ds_ap_currencycode4217' );
	return is_string( $existing ) && $existing !== '' ? $existing : 'USD';
}

/**
 * Button image filenames in images/paypal/.
 *
 * @return array<string,string> Basename => URL.
 */
function artpal_paypal_button_map() {
	static $map = null;
	if ( is_array( $map ) ) {
		return $map;
	}

	$dir = dirname( __FILE__ ) . '/images/paypal';
	$map = array();
	if ( ! is_dir( $dir ) ) {
		return $map;
	}
	$files = scandir( $dir );
	if ( ! is_array( $files ) ) {
		return $map;
	}
	natcasesort( $files );
	foreach ( $files as $file ) {
		if ( $file === '.' || $file === '..' ) {
			continue;
		}
		$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'gif', 'jpg', 'jpeg', 'png' ), true ) ) {
			continue;
		}
		$map[ $file ] = plugins_url( 'images/paypal/' . $file, __FILE__ );
	}
	return $map;
}

/**
 * Bundled button filename from a stored option value.
 *
 * Accepts a bare filename or an absolute URL saved by an older settings write.
 *
 * @param string $stored Option value.
 * @return string Basename, or empty when none can be read.
 */
function artpal_paypal_button_file( $stored ) {
	$stored = trim( (string) $stored );
	if ( $stored === '' ) {
		return '';
	}
	$path = parse_url( $stored, PHP_URL_PATH );
	if ( is_string( $path ) && $path !== '' ) {
		return basename( $path );
	}
	return basename( $stored );
}

/**
 * URL to use for the PayPal button image right now.
 *
 * ds_ap_paypalbutton stores the address from the last time settings were saved.
 * Hosts such as Flywheel rewrite plugin file URLs when their cache is flushed,
 * so that saved address can 404 until the button is chosen again. Match the
 * bundled filename and ask WordPress for the current URL instead.
 *
 * @param string|null $stored Stored option value. Null reads ds_ap_paypalbutton.
 * @return string
 */
function artpal_paypal_button_url( $stored = null ) {
	if ( null === $stored ) {
		$stored = get_option( 'ds_ap_paypalbutton' );
	}
	$stored = trim( (string) $stored );
	if ( $stored === '' ) {
		return '';
	}

	$map = artpal_paypal_button_map();
	if ( isset( $map[ $stored ] ) ) {
		return $map[ $stored ];
	}
	if ( in_array( $stored, $map, true ) ) {
		return $stored;
	}

	$file = artpal_paypal_button_file( $stored );
	if ( $file !== '' && isset( $map[ $file ] ) ) {
		return $map[ $file ];
	}

	return $stored;
}

/**
 * Accept only a bundled button image. Keep the stored URL if the post is unrecognized.
 *
 * @param mixed $value Posted button URL.
 * @return string
 */
function artpal_sanitize_paypal_button( $value ) {
	$value = esc_url_raw( (string) wp_unslash( $value ) );
	$url   = artpal_paypal_button_url( $value );
	$map   = artpal_paypal_button_map();
	if ( $url !== '' && in_array( $url, $map, true ) ) {
		return $url;
	}
	$existing = artpal_paypal_button_url();
	if ( $existing !== '' && in_array( $existing, $map, true ) ) {
		return $existing;
	}
	return (string) get_option( 'ds_ap_paypalbutton' );
}

/**
 * @param array $args Field args.
 * @return void
 */
function artpal_field_category( $args ) {
	$key     = $args['key'];
	$current = (string) get_option( $key );
	$cats    = get_categories(
		array(
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
	if ( ! empty( $args['allow_none'] ) ) {
		echo '<option value="-1"' . selected( $current, '-1', false ) . '>' . esc_html( '(None)' ) . '</option>';
	}
	if ( is_array( $cats ) ) {
		foreach ( $cats as $cat ) {
			echo '<option value="' . esc_attr( (string) $cat->term_id ) . '"' . selected( $current, (string) $cat->term_id, false ) . '>';
			echo esc_html( $cat->name );
			echo '</option>';
		}
	}
	echo '</select>';
	artpal_field_description( $args );
}

/**
 * @param array $args Field args.
 * @return void
 */
function artpal_field_text( $args ) {
	$key   = $args['key'];
	$value = get_option( $key );
	if ( ! empty( $args['decode'] ) ) {
		$value = htmlspecialchars_decode( stripslashes( (string) $value ) );
	}
	$type  = isset( $args['type'] ) ? $args['type'] : 'text';
	$class = isset( $args['class'] ) ? $args['class'] : 'large-text';
	printf(
		'<input type="%1$s" class="%2$s" name="%3$s" id="%3$s" value="%4$s" />',
		esc_attr( $type ),
		esc_attr( $class ),
		esc_attr( $key ),
		esc_attr( $value )
	);
	artpal_field_description( $args );
}

/**
 * @param array $args Field args.
 * @return void
 */
function artpal_field_checkbox( $args ) {
	$key = $args['key'];
	$on  = (string) get_option( $key ) === '1' || get_option( $key ) === 1;
	echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="0" />';
	echo '<label><input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="1"' . checked( $on, true, false ) . ' /> ';
	echo esc_html( isset( $args['label'] ) ? $args['label'] : '' );
	echo '</label>';
}

/**
 * @return void
 */
function artpal_field_currency() {
	global $artpal_currencycodes;
	$current = (string) get_option( 'ds_ap_currencycode4217' );
	echo '<select name="ds_ap_currencycode4217" id="ds_ap_currencycode4217">';
	if ( is_array( $artpal_currencycodes ) ) {
		foreach ( $artpal_currencycodes as $row ) {
			$code   = $row[1];
			$symbol = isset( $row[2] ) ? html_entity_decode( $row[2], ENT_QUOTES, 'UTF-8' ) : '';
			$label  = $row[0] . ' (' . $code . ( $symbol !== '' ? '/' . $symbol : '' ) . ')';
			echo '<option value="' . esc_attr( $code ) . '"' . selected( $current, $code, false ) . '>' . esc_html( $label ) . '</option>';
		}
	}
	echo '</select>';
}

/**
 * Radio grid of images/paypal/*.
 *
 * @return void
 */
function artpal_field_paypal_button() {
	$map     = artpal_paypal_button_map();
	$current = (string) get_option( 'ds_ap_paypalbutton' );
	$base    = artpal_paypal_button_file( $current );

	if ( empty( $map ) ) {
		echo '<p>No button images found in images/paypal/.</p>';
		return;
	}

	echo '<table class="widefat" style="max-width:920px"><tr>';
	$i = 0;
	foreach ( $map as $file => $url ) {
		if ( $i > 0 && $i % 4 === 0 ) {
			echo '</tr><tr>';
		}
		$is_current = ( $current === $url || $base === $file );
		echo '<td style="text-align:center;vertical-align:bottom">';
		echo '<label><input type="radio" name="ds_ap_paypalbutton" value="' . esc_attr( $url ) . '"' . checked( $is_current, true, false ) . ' />';
		echo '<br /><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $file ) . '" /></label>';
		echo '</td>';
		$i++;
	}
	echo '</tr></table>';
}

/**
 * @param array $args Field args.
 * @return void
 */
function artpal_field_description( $args ) {
	if ( empty( $args['description'] ) ) {
		return;
	}
	echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
}

/**
 * Settings page body.
 *
 * @return void
 */
function artpal_render_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap">';
	echo '<h1>ArtPal</h1>';
	// Pages under Settings already print this notice from options-head.php.
	// Calling it again repeats the banner. The repeat only redisplays the
	// in-memory list; option writes already finished before the redirect.
	echo '<form action="options.php" method="post">';
	settings_fields( 'artpal' );
	do_settings_sections( 'artpal' );
	submit_button( 'Update Options' );
	echo '</form></div>';
}
