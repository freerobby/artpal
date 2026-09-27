=== ArtPal ===
Contributors: freerobby
Donate link: http://freerobby.com/donate
Tags: paypal, ecommerce, artists, sell
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.0.5

ArtPal is a free (GPL) Wordpress plugin, originally written for Artists, to seemlessly integrate PayPal with their Wordpress blogs so that they can sell their work online.
== Description ==

ArtPal is a free (GPL) Wordpress plugin, originally written for Artists, to seemlessly integrate PayPal with their Wordpress blogs so that they can sell their work online.

Artists' online stores tend to be simple, but also unconventional. The items for sale are one of a kind, and thus the overhead that deals with keeping stock is unnecessary. I created ArtPal so that artists would have a simple, easy-to-use solution for their unique needs.

ArtPal's most important features are:

* Easy PayPal integration - all you need to supply is your PayPal email address!
* Real-time sales updates - as soon as your item sells, ArtPal will disable it from being sold. You'll never worry about your item selling twice!
* Professionally supported - businesses mean business. Digital Sublimity provides commercial support, so you can be rest assured that your critical application will stay up and running when you need it.

== Installation ==

1. Upload ./artpal directory to wp-content/plugins directory.
2. Activate the plugin through the 'Plugins' menu in Wordpress.
3. Configure as specified at [http://freerobby.com/artpal](http://freerobby.com/artpal).

== Changelog ==

= 2.0.5 =
* PayPal answered INVALID for a completed payment, and the saved notification had an empty receiver_email and business. The postback is the original message again: ipn.php copies $_POST and the raw body before WordPress loads, then posts that message back with curl. A plugin can no longer drop those fields before the check, and WordPress HTTP filters can no longer rewrite the body.
* The diagnostic at ipn.php?artpal_diag=1 also lists the field names that arrived, the raw body size, and the PayPal host used for the check.

= 2.0.4 =
* A verified PayPal IPN moves the post from Available to Sold the way 1.4 did. The listener no longer waits for payment_status Completed or a txn_id. It still requires PayPal to answer VERIFIED and the receiver address to match the PayPal email in the settings.
* The check with PayPal is HTTPS to the current ipnpb endpoint. PayPal no longer answers the plain HTTP connection 1.4 opened. The validation message is the same urlencoded field list 1.4 posted, and the original notification bytes if PayPal requires those.
* notify_url is ipn.php in the installed plugin directory, with no extra query string. The response is still sent with no-store headers so a cached empty page cannot swallow the notification.

= 2.0.3 =
* IPN responses send no-store cache headers, and notify_url adds ?artpal-ipn=1, so a cached empty 200 cannot swallow PayPal's POST.
* Verification tries the original body and a rebuilt body, with cmd=_notify-validate at either end. A Completed notice counts when business or receiver_email matches the PayPal email option.
* Field values are taken from the raw notification when PHP's $_POST is empty. The last result is stored for ipn.php?artpal_diag=1 and omits the buyer email.

= 2.0.2 =
* PayPal notify_url follows the plugin directory. A button was posting IPN to /wp-content/plugins/artpal/ipn.php, which 404s when the folder has another name. The buyer still landed on the thank-you page, and the post stayed Available.
* ipn.php finds wp-load.php when WordPress core is outside the web root, including Flywheel's .wordpress directory. The old relative path stopped with "WordPress bootstrap not found." before the category could change.
* IPN verification posts to ipnpb.paypal.com (or the sandbox ipnpb host). The original request body is captured before WordPress loads, and a rebuilt body encodes spaces as "+".

= 2.0.1 =
* Settings → ArtPal shows one "Settings saved" notice. WordPress already prints that notice on screens under Settings, and the plugin was printing it again. The second print did not write options a second time.
* The PayPal button image is resolved from the bundled file when the page renders, so a flushed host cache cannot leave the buy button pointing at a stale image address.

= 2.0.0 =
* Inventory API: artpal_mark_sold() is idempotent and uses wp_remove_object_terms / wp_set_object_terms (no raw SQL).
* Helpers: artpal_is_sold, artpal_is_available, artpal_is_sale_disabled, artpal_get_price, artpal_get_shipping, artpal_effective_price.
* [artpal=insert] is replaced on the_content. [artpal] and [artpal insert] are real shortcodes. "=" is not a valid shortcode name on current WordPress.
* Post metabox for price, shipping, and read-only status.
* Settings → ArtPal uses the Settings API (manage_options) and keeps every existing option.
* Sold email uses the notification email, then the PayPal email, then the admin email. Subject prefix defaults to the site title.
* PayPal IPN verifies over HTTPS and marks sold only after VERIFIED + Completed.
* PayPal _xclick remains the checkout. The 2009 tag upgrader is hidden unless WP_DEBUG is on.

== Frequently Asked Questions ==

= Is ArtPal awesome? =

Yes (this is a placeholder--ask questions and I'll answer them here)
`<?php code(); // goes in backticks ?>`
