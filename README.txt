=== Fand Pickup Points : Ultimate Edition for WCFM ===
Contributors: fandevelop
Tags: wcfm, woocommerce, pickup points, marketplace, vendor locations
Requires at least: 6.9
Tested up to:      6.9
Requires PHP:      8.2
Stable tag:        1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WCFM Pickup Points allows each store on a marketplace to individualize their own pickup locations with custom opening hours.

== Description ==

**FAND Pickup Points: Ultimate Edition for WCFM** is the most comprehensive solution for managing local collection points in a multi-vendor environment. Specifically designed as a powerful extension for the **WCFM Marketplace**, this plugin bridges the gap between online sales and physical proximity.

In a modern marketplace, logistics is key. This addon empowers your vendors by giving them total autonomy over their physical presence. No more global shipping settings that don't fit every store; each vendor becomes the master of their own delivery network.

### 🚀 Key Features for Vendors:

* **Independent Branch Management:** Vendors can create, edit, or delete multiple pickup locations directly from their WCFM Dashboard.
* **Custom Opening Hours:** For every single branch, vendors can define precise time slots for each day of the week. This is perfect for businesses with split shifts or specific collection windows.
* **Store Category Integration:** Unlike basic solutions, my addon allows for advanced sorting and filtering based on store activities and categories. This ensures customers find the right point for the right product type.
* **Visual Map Interface:** Integrated with Leaflet and OpenStreetMap, vendors can easily pinpoint their exact location for high accuracy.

### 🛠 Administrative Control & Performance:

* **Seamless WCFM Integration:** The interface feels like a native part of the WCFM ecosystem, ensuring a zero-learning curve for your vendors.

### 👥 Enhanced Customer Experience:

* **Interactive Pickup Map:** Customers can visualize all available points on a beautiful, responsive map .
* **Get Directions:** One-click integration with Google Maps directions to help customers reach the vendor's store without hassle.
* **Real-time Availability:** The custom hours management ensures customers only see pickup options when the store is actually open.

### Why choose the FAND Pickup Points?

Managing a marketplace requires tools that scale. **FAND Pickup Points** was developed to handle complex scenarios where vendors have different working hours, different locations, and different product categories. By providing a professional FAND Pickup Points experience, you increase customer trust and conversion rates.

Whether you are running a local food marketplace, a craft fair directory, or a global vendor network, this plugin provides the professional infrastructure needed to handle physical pickups at scale.

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

== Frequently Asked Questions ==

= Does this work with WCFM Free version? =
Yes, it is designed to work with both the free and ultimate versions of WCFM Marketplace.

= Can vendors set different hours for each day? =
Absolutely. Each pickup point can have its own specific opening and closing hours for every day of the week.

= Can I filter pickup points by store category? =
Yes. The plugin allows you to organize and sort pickup locations based on your WCFM store categories, making it easier for customers to find relevant points.

= Is it possible to hide specific pickup points temporarily? =
Yes, vendors can toggle the status of each branch to active or inactive directly from their dashboard without deleting the data.

= Does the plugin support custom map markers? =
The Ultimate Edition includes default markers, but it is also compatible with standard Leaflet icons for a more personalized map experience.

== Screenshots ==

1. **Back Office Activity List:** Administration interface for managing the overall list of store activities.

2. **Store Manager Dashboard:** Salesperson view for managing store activities.

3. **Schedule Configuration:** Detailed view of the custom time slot selector for each point of sale.

4. **Customer Map:** Interactive Leaflet map displaying pickup locations with activity filters.

== Changelog ==
= 1.0.4 =
* Added pickup list based on dynamic geolocation

= 1.0.3 =
* Added traduct

= 1.0.2 =
* Added search by geolocation dynamique

= 1.0.1 =
* Added search by geolocation

= 1.0.0 =
* Initial release. Fixed database security issues and added caching for performance.