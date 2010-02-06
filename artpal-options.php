<?php
/*
file:	artpal-options.php

desc:	Options page for ArtPal Wordpress Plugin

author:	Robby Grossman

*/
?>
<?php
// If the form was just submitted (options updated), ...
if ( isset ( $_POST [ 'submitted' ] ) ) {
	update_option ( 'ds_ap_unsoldcategory', htmlspecialchars ( $_POST [ 'unsold' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_soldcategory', htmlspecialchars ( $_POST [ 'sold' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_paypalemail', htmlspecialchars ( $_POST [ 'paypalemail' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_soldcode', htmlspecialchars ( $_POST [ 'soldcode' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_prebuttontext', htmlspecialchars ( $_POST [ 'prebuttontext' ] ) );
	update_option ( 'ds_ap_thankyoupage', htmlspecialchars ( $_POST [ 'thankyoupage' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_cancelpage', htmlspecialchars ( $_POST [ 'cancelpage' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_paypalbutton', htmlspecialchars ( $_POST [ 'paypalbutton' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_discountpercent', htmlspecialchars ( $_POST [ 'discountpercent' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_disableecommerce', htmlspecialchars ( $_POST [ 'disableecommerce' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_textifunknownmetadata', htmlspecialchars ( $_POST [ 'textifunknownmetadata' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_saledisabledcategory', htmlspecialchars ( $_POST [ 'saledisabledcategory' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_textifsaledisabled', htmlspecialchars ( $_POST [ 'textifsaledisabled' ], ENT_QUOTES ) );
	update_option ( 'ds_ap_currencycode4217', htmlspecialchars ( substr($_POST['currencycode4217'], 0, 3), ENT_QUOTES ) );
	update_option ( 'ds_ap_currencysymbol', htmlspecialchars ( substr($_POST['currencycode4217'], 3), ENT_QUOTES ) );
	update_option ( 'ds_ap_usesandbox', htmlspecialchars( $_POST['usesandbox'], ENT_QUOTES));
	?>
	<div id="message" class="updated fade">
		<p>
			<strong>
				Options saved!
			</strong>
		</p>
	</div>
<?php
}
?>

<div class="wrap">
<form action="<?php echo $_SERVER ['REQUEST_URI']; ?>" method="post">

<?php
// Get all category names and IDs
$category_ids = get_all_category_ids ();
$category_count = count ( $category_ids );
$category_names = array ();
for ( $i = 0; $i < $category_count; $i++ )
	$category_names [] = get_cat_name ( $category_ids [ $i ] );
?>

<h2>
	General Options
</h2>

<h3>
	ArtPal Options
</h3>

<p>
	Category that holds artwork available for sale: 
	<select name="unsold">
	<?php
	for ( $thisCat = 0; $thisCat < $category_count; $thisCat ++ ) {
		echo '<option value="';
		echo $category_ids [ $thisCat ];
		// Do we select it?
		if ( $category_ids [ $thisCat ] == get_option ( 'ds_ap_unsoldcategory' ) ) {
			echo '" selected="selected';
		}
		echo '">';
		echo $category_names [ $thisCat ];
		echo '</option>';
	}
	?>
	</select>
</p>

<p>
	Category for "available" artwork that is currently disabled:
	<select name="saledisabledcategory">
		<option value="-1">(None)</option>
	<?php
	for ( $thisCat = 0; $thisCat < $category_count; $thisCat ++ ) {
		echo '<option value="';
		echo $category_ids [ $thisCat ];
		// Do we select it?
		if ( $category_ids [ $thisCat ] == get_option ( 'ds_ap_saledisabledcategory' ) ) {
			echo '" selected="selected';
		}
		echo '">';
		echo $category_names [ $thisCat ];
		echo '</option>';
	}
	?>
	</select>
</p>

<p>
	Category into which to move artwork when sold and label accordingly:
	<select name="sold">
	<?php
	for ( $thisCat = 0; $thisCat < $category_count; $thisCat ++ ) {
		echo '<option value="';
		echo $category_ids [ $thisCat ];
		// Do we select it?
		if ( $category_ids [ $thisCat ] == get_option ( 'ds_ap_soldcategory' ) ) {
			echo '" selected="selected';
		}
		echo '">';
		echo $category_names [ $thisCat ];
		echo '</option>';
	}
	?>
	</select>
</p>

<p>
	HTML Code to display for a sold item:
	<br />
	<input type="text" name="soldcode" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_soldcode' ) ); ?>" />
</p>

<p>
	Generic text to place in every item for sale; _PRICE_ and _SHIPPING_ are metatags:
	<br />
	<input type="text" name="prebuttontext" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_prebuttontext' ) ); ?>" />
</p>

<p>
	Static text to use for "available" items that are temporarily not for sale:
	<br />
	<input type="text" name="textifsaledisabled" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_textifsaledisabled' ) ); ?>" />
</p>

<p>
	Static text to use in the event that you fail to specify pricing information for an item:
	<br />
	<input type="text" name="textifunknownmetadata" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_textifunknownmetadata' ) ); ?>" />
</p>

<p>
	URL of Thank You page:
	<br />
	<input type="text" name="thankyoupage" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_thankyoupage' ) ); ?>" />
</p>

<p>
	URL of Cancel Purchase page:
	<br />
	<input type="text" name="cancelpage" size="100" value="<?php echo stripslashes ( get_option ( 'ds_ap_cancelpage' ) ); ?>" />
</p>

<h2>
	E-commerce
</h2>
<h3>
	PayPal Options
</h3>
<p>
	Email address that is tied to your PayPal account:
	<input type="text" name="paypalemail" size="40" value="<?php echo stripslashes ( get_option ( 'ds_ap_paypalemail' ) ); ?>" />
</p>
<p>
	Currency:
	<select name="currencycode4217">
	<?php
	global $artpal_currencycodes;
	for ( $thisCurrency = 0; $thisCurrency < count($artpal_currencycodes); $thisCurrency ++ ) {
		echo '<option value="';
		// Concatenate ISO code (3 digits) with symbol
		echo $artpal_currencycodes[$thisCurrency][1] . $artpal_currencycodes[$thisCurrency][2];
		// Do we select it?
		if ( $artpal_currencycodes[$thisCurrency][1] == get_option ( 'ds_ap_currencycode4217' ) ) {
			echo '" selected="selected';
		}
		echo '">';
		echo $artpal_currencycodes[$thisCurrency][0] . ' (' . $artpal_currencycodes[$thisCurrency][1] . '/' . $artpal_currencycodes[$thisCurrency][2] . ')';
		echo '</option>';
	}
	?>
	</select>
</p>

<p>
	Please choose the graphic that you would like to use as a PayPal button.
	<br />
	<?php
	// Get all files in our PayPal button directory.
	$buttons = array ();
	$handler = opendir ( ABSPATH . '/wp-content/plugins/artpal/images/paypal' );
    // keep going until all files in directory have been read
    while ($file = readdir($handler)) {
        // if $file isn't this directory or its parent, 
        // add it to the results array
        if ($file != '.' && $file != '..') {
        	// Make sure it's an image!
        	$file_ext = substr ( $file, strlen ( $file ) - 4, 4 );
        	if ( $file_ext == '.jpg' || $file_ext == '.gif' || $file_ext == '.png' )
        	    $buttons[] = $file;
		}
    }
    // tidy up: close the handler
    closedir($handler);
	
	// The number of images we have to choose from
	$numFiles = count ( $buttons );
	// Begin HTML table
	echo '<table border="0" width="100%">';
	// Number of columns to generate in our table
	$numCols = 4;
	for ( $i = 0; $i < $numFiles; $i ++ ) {
		// Start new row if necessary
		if ( $i % $numCols == 0 ) {
			echo '<tr>';
		}
		// Start new column
		echo '<td>';
		$url = get_option ( 'siteurl' ) . '/wp-content/plugins/artpal/images/paypal/' . basename ( $buttons [ $i ] );
		echo '<input type="radio" name="paypalbutton" value="' . $url . '"';
		if ( $url == get_option ( 'ds_ap_paypalbutton' ) ) {
			echo ' checked="checked"';
		}
		echo ' />';
		echo '<img src="' . $url . '" />';
		// Close column
		echo '</td>';
		// Close row if necessary.
		if ( $i % $numCols == $numCols - 1 ) {
			echo '</tr>';
		}
	}
	echo '</table>';
	?>
</p>

<h3>
	Store-wide Sale
</h3>
<p>
	You may create a store-wide sale by specifying a <b>percent-based discount</b> on <b>all items in your store</b>:
	<br />
	Use 0 &#37; to disable a store-wide sale.
	<br />
	Enter discount: 
	<input type="text" name="discountpercent" maxlength="2" size="2" value="<?php echo stripslashes ( get_option ( 'ds_ap_discountpercent' ) ) ?>" /> &#37;
</p>

<h3>
	E-commerce Status
</h3>
<p>
	You may turn off e-commerce at any time. This will disable your online store. Features relating to general options will still be in place, but your visitors will not be able to purchase your items through PayPal.
	<br />
	Check to disable ecommerce:
	<input type="checkbox" name="disableecommerce" value="1" <?php if ( stripslashes ( get_option ( 'ds_ap_disableecommerce' ))) echo 'checked'; ?> />
</p>

<h3>
  Sandbox Mode
</h3>
<p>
  If you'd like to test your plugin, you may use sandbox mode. This will use the PayPal sandbox API (note: you will need to use a sandbox API account). No charges will be processed.
  <br />
  Check to use sandbox mode:
  <input type="checkbox" name="usesandbox" value="1" <?php if ( stripslashes ( get_option ( 'ds_ap_usesandbox' ))) echo 'checked'; ?> />
</p>

<input type="submit" name="submitted" value="Update Options &raquo;" />

<!-- End of Options Page -->
	</form>
</div>
<!-- End of End of Options Page -->
