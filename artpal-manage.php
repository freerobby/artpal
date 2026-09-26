<?php
/**
 * Tools → ArtPal Items.
 *
 * Post ID lookup stays available. The 2009 [paypal=title;price;shipping]
 * upgrader is shown only when WP_DEBUG is on.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( 'You do not have permission to manage ArtPal items.' );
}

$lookup_message = '';
$lookup_class   = 'updated';

if ( isset( $_POST['getitembynumber'] ) ) {
	check_admin_referer( 'artpal_lookup' );
	$id   = isset( $_POST['itemnumber'] ) ? absint( wp_unslash( $_POST['itemnumber'] ) ) : 0;
	$post = $id ? get_post( $id ) : null;
	if ( ! $post ) {
		$lookup_class   = 'error';
		$lookup_message = 'Item not found.';
	} else {
		$url            = get_permalink( $post );
		$lookup_message = '<a href="' . esc_url( $url ) . '">Here is a link to item number ' . esc_html( (string) $id ) . '</a>.';
	}
}
?>
<div class="wrap">
	<h1>ArtPal Items</h1>

	<?php if ( $lookup_message !== '' ) : ?>
		<div id="message" class="<?php echo esc_attr( $lookup_class ); ?> notice is-dismissible">
			<p><strong><?php echo wp_kses_post( $lookup_message ); ?></strong></p>
		</div>
	<?php endif; ?>

	<h2>Find an Item (Post)</h2>
	<p>The item number is the WordPress post ID.</p>
	<form method="post" action="">
		<?php wp_nonce_field( 'artpal_lookup' ); ?>
		<p>
			<label for="artpal-itemnumber">Item number</label><br />
			<input type="number" min="1" step="1" name="itemnumber" id="artpal-itemnumber" value="" />
		</p>
		<p>
			<input type="submit" name="getitembynumber" class="button button-primary" value="Get Link to Item" />
		</p>
	</form>

	<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
		<h2>Upgrade Items</h2>
		<p>Debug only. Converts leftover 2009 <code>[paypal=title;price;shipping]</code> tags into <code>[artpal=insert]</code> and post meta. Do not run this against a site that was already upgraded.</p>
		<form method="post" action="">
			<?php wp_nonce_field( 'artpal_upgrade' ); ?>
			<input type="submit" name="testupgrade" class="button" value="Test Upgrade" />
			<input type="submit" name="upgrade" class="button" value="Perform Upgrade" />
		</form>
		<?php
		if ( isset( $_POST['upgrade'] ) || isset( $_POST['testupgrade'] ) ) {
			check_admin_referer( 'artpal_upgrade' );
			$commit = isset( $_POST['upgrade'] );

			global $wpdb;
			$sql            = 'SELECT DISTINCT ID, post_content FROM ' . $wpdb->posts . ' WHERE post_content LIKE "%[paypal=%;%;%]%"';
			$poststoconvert = $wpdb->get_results( $sql, ARRAY_A );
			$return_ids     = array();
			$return_content = array();
			if ( is_array( $poststoconvert ) ) {
				foreach ( $poststoconvert as $this_post ) {
					$return_ids[]     = $this_post['ID'];
					$return_content[] = $this_post['post_content'];
				}
			}
			echo '<p>Converting the following posts: ';
			foreach ( $return_ids as $this_id ) {
				echo esc_html( (string) $this_id ) . ', ';
			}
			echo ' ...</p>';

			for ( $this_post = 0; $this_post < count( $return_ids ); $this_post++ ) {
				echo '<p>Reading post ' . esc_html( (string) $return_ids[ $this_post ] ) . '...<br />';
				$content = $return_content[ $this_post ];
				if ( preg_match( '/\[paypal=(.*);(.*);(.*)\]/iU', $content, $matches ) ) {
					echo 'Tag: <strong>' . esc_html( $matches[0] ) . '</strong><br />';
					echo 'Title (ignored): <strong>' . esc_html( $matches[1] ) . '</strong><br />';
					echo 'Price: <strong>' . esc_html( $matches[2] ) . '</strong><br />';
					echo 'Shipping: <strong>' . esc_html( $matches[3] ) . '</strong><br />';

					if ( $commit ) {
						update_post_meta( (int) $return_ids[ $this_post ], ds_ap_CFPRICE, sanitize_text_field( $matches[2] ) );
						update_post_meta( (int) $return_ids[ $this_post ], ds_ap_CFSHIPPING, sanitize_text_field( $matches[3] ) );
						$new_content = str_replace( $matches[0], ds_ap_TAGINSERT, $content );
						wp_update_post(
							array(
								'ID'           => (int) $return_ids[ $this_post ],
								'post_content' => $new_content,
							)
						);
					}
				}
				echo '</p>';
			}
			if ( $commit ) {
				echo '<p><strong>Changes committed.</strong></p>';
			} else {
				echo '<p><strong>Changes not committed.</strong></p>';
			}
		}
		?>
	<?php endif; ?>
</div>
