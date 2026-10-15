=== WPCalibrate Shipment Tracker ===
Contributors: wpcalibrate
Tags: woocommerce, shipment tracking, order tracking, order fulfillment, shipping
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.2
WC requires at least: 8.5
WC tested up to: 11.2.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce shipment and order tracking plugin supporting multiple carriers, partial fulfillment item allocations, and customer tracking.

== Description ==

WPCalibrate Shipment Tracker adds shipment management and order tracking to WooCommerce. It allows store administrators to record shipments, assign carrier tracking codes, allocate specific quantities of order items across multiple shipments, and provide tracking updates for both registered customers and guest shoppers.

In version 1.0.0, shipment statuses are controlled manually by store administrators. Direct carrier API polling, automatic status synchronization, and label purchasing are not part of version 1.0.0 and are planned for future versions.

= Key Features =

* **Multiple Shipments per Order**: Assign multiple packages and tracking numbers to a single WooCommerce order.
* **Partial Order Fulfillment**: Allocate exact item quantities to individual shipments. The system prevents over-allocation by tracking purchased, refunded, and previously assigned quantities.
* **Predefined & Custom Carriers**: Includes templates for UPS, FedEx, USPS, DHL Express, Royal Mail, Canada Post, and Australia Post, plus an interface to create custom carriers.
* **High-Performance Order Storage (HPOS)**: Compatible with WooCommerce HPOS (Custom Order Tables) and traditional post storage.
* **Customer Tracking Experience**: Tracking information is accessible in WooCommerce My Account, individual order details, and on a dedicated public tracking page via shortcode.
* **Rate-Limited Guest Tracking**: Allows guest customers to track their orders securely using their Order Number and Billing Email address.
* **Transactional Email Notifications**: Integrates with WooCommerce emails and includes a dedicated customer shipment update notification.
* **Auditable Shipment Timeline**: Visual timeline displaying recorded shipment status events.
* **Zero N+1 Query Impact**: Optimized batch database queries for order administration list tables.

== Installation ==

1. Upload the `wpcalibrate-shipment-tracker` folder to your WordPress plugins directory (typically `/wp-content/plugins/`).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce (version 8.5 or higher) is installed and activated.
4. Navigate to **WPCalibrate > Shipment Tracker** to configure your tracking preferences and review shipping carriers.
5. Create a new WordPress page and insert the shortcode `[wpcalibrate_shipment_tracking]` to offer a dedicated tracking page for your customers.

== Frequently Asked Questions ==

= Does this plugin automatically sync tracking status from carriers in version 1.0? =
No. Version 1.0 provides manual shipment status management. Carrier APIs are not polled automatically in this release.

= Can I split an order into multiple shipments? =
Yes. You can create as many shipments as necessary for an order. When creating each shipment, you select which order line items and how many units of each item are included in that shipment.

= What happens if an item is refunded? =
The plugin accounts for WooCommerce refunds. Refunded quantities are deducted from the shippable quantity, preventing refunded items from being assigned to shipments.

= How does guest tracking work? =
Guests can enter their Order Number and Billing Email on the public tracking page. If both match, tracking information for that order is displayed. Failed lookups return a generic response to prevent order enumeration, and lookups are throttled using hashed request identifiers.

= How is data handled when uninstalling? =
By default, all shipment records and settings are preserved if the plugin is uninstalled. If you wish to permanently remove all shipment tables and options upon deletion, you can enable the "Delete plugin data when the plugin is uninstalled" option under **WPCalibrate > Shipment Tracker > Advanced**.

== Privacy & Data Protection ==

WPCalibrate Shipment Tracker processes order IDs, carrier names, tracking numbers, and order item allocations. It does not send customer data to external carrier APIs in version 1.0. Public tracking requests verify the order reference and billing email using constant-time comparisons. Rate limiting uses one-way hashed identifiers without persisting raw IP addresses.

== Developer Hooks ==

* `wpcalibrate_shipment_tracker_statuses` (filter): Modify or extend the list of allowed shipment statuses.
* `wpcalibrate_shipment_tracker_carrier_registry` (filter): Modify the list of predefined and custom shipping carriers.
* `wpcalibrate_shipment_tracker_generated_url` (filter): Filter generated carrier tracking URLs.
* `wpcalibrate_shipment_tracker_shipment_created` (action): Fired when a shipment is created.
* `wpcalibrate_shipment_tracker_shipment_updated` (action): Fired when a shipment is updated.
* `wpcalibrate_shipment_tracker_shipment_deleted` (action): Fired when a shipment is deleted.
* `wpcalibrate_shipment_tracker_status_changed` (action): Fired when a shipment status changes.
* `wpcalibrate_shipment_tracker_guest_lookup_verification` (filter): Filter the verified order during guest tracking lookup.

== Changelog ==

= 1.0.4 =
* Fix: Resolved fatal error on Suite Overview page by adding get_enabled_carriers() alias in CarrierRegistry.
* Fix: Hardened database querying and carrier registry access with defensive try-catch error handling to prevent critical errors.

= 1.0.3 =
* Redesign: Enterprise WPCalibrate Suite Overview hub (admin.php?page=wpcalibrate) with live consignment metrics, operational health cards, and modern SaaS styling.
* Enhancement: Restored settings_errors transient handling so success/error banners display reliably on all settings tabs.
* Enhancement: Dynamic filemtime asset versioning to eliminate stale browser cache on plugin upgrades.
* Enhancement: Added Suite Overview navigation link in Shipment Tracker settings header.

= 1.0.2 =
* Fix: Eliminated duplicate plugin folders on remote WordPress servers. Enforced single canonical installation path at wpcalibrate-shipment-tracker.
* Enhancement: Complete card-based redesign across all 8 settings tabs (Overview, General, Tracking, Carriers, Emails, Advanced, License, Support).
* Feature: Animated custom toggle switches and segmented Dashicons navigation bar.

= 1.0.1 =
* Feature: Comprehensive Overview tab with logistics pipeline diagram and operator guides.
* Feature: Built-in 5 demo products seeder under Advanced tab.
* Fix: Admin menu branding icon sizing and high-resolution dark branding dashboard header.

= 1.0.0 =
* Initial release.
* Multi-shipment and partial fulfillment support for WooCommerce orders.
* HPOS (High-Performance Order Storage) and Cart/Checkout Blocks compatibility.
* Predefined carrier registry (UPS, FedEx, USPS, DHL Express, Royal Mail, Canada Post, Australia Post) and custom carrier management.
* Dedicated customer email notification and email integration hooks.
* Rate-limited public tracking page shortcode [wpcalibrate_shipment_tracking].
* Event-based shipment progress timeline.

