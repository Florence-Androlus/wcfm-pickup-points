=== Fand Pickup Points : Ultimate Edition for WCFM ===
Contributors: fandevelop
Tags: wcfm, woocommerce, pickup points, marketplace, vendor locations
Requires at least: 6.9
Tested up to:      6.9
Requires PHP:      8.2
Stable tag:        1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WCFM Pickup Points allows each store on a marketplace to individualize their own pickup locations with custom opening hours.

== Description ==

FAND Pickup Points : Ultimate Edition for WCFM is a powerful extension for WCFM Marketplace. It gives vendors the ability to create and manage their own pickup points independently. 

Each vendor can set specific locations and define custom opening hours for every branch, providing more flexibility to customers and simplifying the management of individual stores.

== External Services ==

This plugin relies on third-party services to provide full functionality:

1. Google Maps (Directions)
* Service: Provides directions and location links for pickup points.
* Data sent: The pickup point's address is sent to Google's servers when a user clicks on the "Get Directions" link. No personal user data is sent automatically.
* Terms of Service: https://www.google.com/intl/en/help/terms_maps/
* Privacy Policy: https://policies.google.com/privacy

2. OpenStreetMap (via Leaflet)
* Service: Provides the map tiles displayed on the pickup points map.
* Data sent: The user's browser requests map tiles directly from OpenStreetMap servers. The user's IP address is visible to the service during these requests.
* Privacy Policy: https://osmfoundation.org/wiki/Privacy_Policy

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/fand-pickup-points-ultimate` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure your pickup locations in the WCFM Vendor Dashboard.

== Screenshots ==

1. The vendor dashboard interface for managing pickup points.
2. The customer view of available pickup locations on the product page.

== Changelog ==

= 1.0.0 =
* Initial release. Fixed database security issues and added caching for performance.