=== Kiyoh customer review ===
Requires at least: 3.6.0
Tested up to: 6.4.3
Stable tag: kiyoh, review, customerreview, rate, send mail

The Interactivated.me developed plugin for the Kiyoh reviews network, you can easily integrate Kiyoh into your Wordpress.

== Description ==
With the newest release of the official Kiyoh reviews plugin you get a complete solution for integrating the most used review network of The Netherlands into your webstore. Gaining popularity rapidly internationally. A full overview of the capabilities of the latest plugin:

    Multistore support
    Easy widget implementation
    Automatic microdata integration enables showing the stars and your avarge score in the Google search results
    Support for the Kiyoh.nl network for your Dutch language stores
    Support for the aKiyoh.com network for your international stores
    Language selection for emails that are send for your international stores
    Support for working with customer groups (exclude certain groups)
    A-synchronised checkout process which avoids any interuption of the checkout in case of a problem on the network
    Send reviews after certain order status events, after shipment or after invoice has been generated for any order
    Possibility to configure a delay in days after which the review invite email should be send
    Possibility to configure reminder emails from your Kiyoh account

KiyOh users can now automatically collect customer reviews, publish them and share reviews in social media. When a customer places an order in your webstore, an e-mail with a invitation is being send automatically after the configured number of days, asking them to leave a review about your company and services. The e-mail is being send from your organisation name and e-mail address so it is trusted and easily recognisable. The e-mail text is fully customisable and contains a personal and secure link to leave the review.

Also see www.kiyoh.nl for more information.

== Installation ==
1. Upload or extract the kiyoh_customerreview folder to your site\'s /wp-content/plugins/ directory. You can also use the Add new option found in the Plugins menu in WordPress.
2. Enable the plugin from the Plugins menu in WordPress.
3. Install & enable WooCommerce plugin from the Plugins menu in WordPress.
4. You should install and enable Group plugin (https://wordpress.org/plugins/groups) if you want groups to use "Exclude customer groups"

== Screenshots ==
1. Settings
2. setting widget 
3. widget


== Changelog ==

= 1.0.8 =
* Fixed: email obtain problem
* Fixed: some users can't save plugin settings from admin and see error

= 1.0.20 =
* testing with wordpress 5.2.2

= 1.0.21 =
* set curl timeout 200ms

= 1.0.22 =
* set curl timeout 2s for orders

= 1.0.23 =
* fixed send incorrect email for invite klantenvertelen and kiyoh.com

= 1.0.24 =
* optimized fetching review data from klantenvertelen and kiyoh.com

= 1.0.25 =
* added invite response log

= 1.0.26 =
* support Magento 5.3

= 1.0.27 =
* fix response wp_remote_get request

= 1.0.28 =
* fix undefined get_home_path function

= 1.0.29 =
* fix structured microdata

= 1.0.30 =
* fix count() function error

= 1.0.31 =
* added block editor support

= 1.0.32 =
* fixed new woocommerce hook logic