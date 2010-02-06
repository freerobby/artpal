<?php

define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');

$logging = false;

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
$fp = fsockopen (get_paypal_domain(), 80, $errno, $errstr, 30);
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
   }
 }
 fclose ($fp);
}
if ($logging) {
 fwrite ( $fh, "End Instant Payment Notification\n" );
 fwrite ( $fh, "--------------------------------------------------\n" );
 fclose ( $fh );
}

?>