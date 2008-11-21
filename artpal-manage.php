<?php
/*
file:	artpal-manage.php

desc:	Manage Items page for ArtPal Wordpress Plugin

author:	Robby Grossman <http://www.freerobby.com>

legal:	Copyright 2007 Digital Sublimity <http://www.digitalsublimity.com>
		All rights reserved.
		Unauthorized reuse of the code contained herein is strictly prohibited.

notes:	
*/
?>
<?php
// If the user was searching for an item, ...
if ( isset ( $_POST [ 'getitembynumber' ] ) ) {
	$id = $_POST [ 'itemnumber' ];
	$url = get_permalink ( $id );
	// Check for invalid permalink
	// There is no documented behavior for an invalid permalink, so we're going
	// to cheat. We're going to generate a separate permalink that we know to
	// be bad, and compare the actual permalink to that one.
	if ( $url == get_permalink ( -1 ) ) {
?>
	<div id="message" class="updated fade">
		<p>
			<strong>
				Item not found!
			</strong>
		</p>
	</div>
<?php
	}
	else {
?>
	<div id="message" class="updated fade">
		<p>
			<strong>
				<a href="<?php echo $url; ?>">Here is a link to item number <?php echo $id; ?></a>.
			</strong>
		</p>
	</div>
<?php
	}
}
?>
<div class="wrap">
<h2>
	Item Lookup
</h2>

<h3>
	Find an Item (Post)
</h3>
<form action="<?php echo $_SERVER ['REQUEST_URI']; ?>" method="post">
<p>
	Enter item number (object id):
	<input type="text" name="itemnumber" size="5" />
	<br />
	<input type="submit" name="getitembynumber" value="Get Link to Item..." />
</p>
</form>

<h2>
	Upgrade Items
</h2>
<p>Use this feature to upgrade old posts into the new ArtPal framework. This will do the following:</p>

<ol>
	<li>Search through all posts</li>
	<li>Create custom fields based on tagged content.</li>
	<li>Replace old tags with new ones</li>
</ol>
<form action="<?php echo $_SERVER ['REQUEST_URI']; ?>" method="post">
<input type="submit" name="testupgrade" value="Test Upgrade &gt;" />
<input type="submit" name="upgrade" value="Perform Upgrade &gt;" />
</form>
<?php
	if ( isset ( $_POST [ 'upgrade' ] ) || isset ( $_POST [ 'testupgrade' ] ) ) {
		$commit = false;
		if ( isset ( $_POST [ 'upgrade' ] ) ) {
			$commit = true;
		}
		
		// Get all posts with ArtPal-like tags.
		global $wpdb;
		$sql = 'SELECT DISTINCT ID, post_content FROM ' . $wpdb -> posts . ' WHERE post_content LIKE "%[paypal=%;%;%]%"';
		$poststoconvert = $wpdb -> get_results ( $sql, ARRAY_A );
		$return_ids = array ();
		$return_content = array ();
		// Merge ID and content into respective arrays.
		foreach ( $poststoconvert as $thisPost ) {
			$return_ids [] = $thisPost [ 'ID' ];
			$return_content [] = $thisPost [ 'post_content' ];
		}
		echo '<p>Converting the following posts: ';
		foreach ( $return_ids as $thisID ) {
			echo $thisID . ', ';
		}
		echo ' ...</p>';
		
		// Go through each post
		for ( $thisPost = 0; $thisPost < count ( $return_ids ); $thisPost++ ) {
			echo '<p>Reading post ' . $return_ids [ $thisPost ] . '...<br />';
			$content = $return_content [ $thisPost ];
			if ( preg_match ('/\[paypal=(.*);(.*);(.*)\]/iU', $content, $matches) ) {
				echo 'Tag: <strong>' . $matches [ 0 ] . '</strong><br />';
				echo 'Title (ignored): <strong>' . $matches [ 1 ] . '</strong><br />';
				echo 'Price: <strong>' . $matches [ 2 ] . '</strong><br />';
				echo 'Shipping: <strong>' . $matches [ 3 ] . '</strong><br />';
				
				// Insert price metadata
				$sql = 'INSERT INTO ' . $wpdb -> postmeta . ' ( post_id, meta_key, meta_value ) VALUES ( ' . $return_ids [ $thisPost ] . ', "' . ds_ap_CFPRICE . '", "' . $matches [ 2 ] . '" )';
				echo $sql . '<br />';
				if ( $commit )
					$wpdb -> query ( $sql );
				$sql = 'INSERT INTO ' . $wpdb -> postmeta . ' ( post_id, meta_key, meta_value ) VALUES ( ' . $return_ids [ $thisPost ] . ', "' . ds_ap_CFSHIPPING . '", "' . $matches [ 3 ] . '" )';
				echo $sql . '<br />';
				if ( $commit )
					$wpdb -> query ( $sql );
				
				// Replace old data with new.
				$content = str_replace  ( $matches [ 0 ], ds_ap_TAGINSERT, $content );
				$sql = 'UPDATE ' . $wpdb -> posts . ' SET post_content = "' . addslashes ( $content ) . '" WHERE  ID = ' . $return_ids [ $thisPost ];
				if ( $commit )
					$wpdb -> query ( $sql );
			}
			echo '</p>';
		}
		if ( $commit ) 
			echo '<p><strong>Changes committed.</strong></p>';
		else
			echo '<p><strong>Changes not committed.</strong></p>';
	}
?>
<p>
</p>
</div>