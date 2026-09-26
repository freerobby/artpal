=== ArtPal ===
Contributors: freerobby
Donate link: http://freerobby.com/donate
Tags: paypal, ecommerce, artists, sell
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.0.1

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
