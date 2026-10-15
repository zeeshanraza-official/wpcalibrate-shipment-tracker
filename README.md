# WPCalibrate Shipment Tracker

[![WordPress Compatibility](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce Compatibility](https://img.shields.io/badge/WooCommerce-8.5%20--%2011.2%2B-purple.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-8892BF.svg)](https://php.net/)
[![HPOS Compatibility](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com/document/high-performance-order-storage/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Enterprise-grade physical shipment tracking and partial fulfillment management plugin for WooCommerce. Developed by [WPCalibrate](https://wpcalibrate.com).

---

## 📖 About

**WPCalibrate Shipment Tracker** bridges the gap between physical supply chain operations and customer post-purchase experience. 

Standard WooCommerce orders only provide high-level statuses (*Processing*, *Completed*), leaving customers in the dark about package dispatch, transit milestones, and partial shipments. WPCalibrate introduces real-time consignment management with dedicated relational tables, multi-carrier support, item-level allocation math, transactional email notifications, and a brute-force-proof guest tracking portal.

---

## ✨ Features

- **⚡ High-Performance Order Storage (HPOS)**: Fully compatible with WooCommerce Custom Order Tables (HPOS) and classic post storage without performance degradation.
- **📦 Partial Fulfillment & Allocation Engine**: Split large orders across multiple packages or consignments. The allocation manager prevents accidental over-allocation (`Available = Ordered - Refunded - Assigned`).
- **🚚 Multi-Carrier Registry**: 7+ built-in global carriers (UPS, FedEx, USPS, DHL Express, Royal Mail, Australia Post, Canada Post) with pre-verified URL templates.
- **🌐 Custom Couriers**: Register custom regional shipping couriers using dynamic `{tracking_number}` placeholders.
- **🔒 Secure Public Guest Tracking**: Allow guests to track orders using Order Number and Billing Email with constant-time verification and IP-based rate limiting to prevent enumeration attacks.
- **⏱️ Chronological Event History**: Immutable audit log of status transitions (*Preparing*, *Shipped*, *In Transit*, *Out for Delivery*, *Delivered*, *Delivery Exception*, *Returned*).
- **✉️ Transactional Email Updates**: Integrated with WooCommerce core emails (`WPC_Shipment_Updated_Email`) and order completion notifications.
- **🛍️ 1-Click Demo Products Seeder**: Built-in testing seeder providing 5 realistic demo WooCommerce products with SKUs, stock quantities, and descriptions.
- **🎨 WCAG 2.2 AA & RTL Ready**: Scoped CSS, logical properties, responsive layout from mobile to wide screens, and accessibility-compliant modals.

---

## 📋 System Requirements

| Requirement | Supported Version |
|---|---|
| **PHP** | 8.2 or 8.3+ (Strict typing enforced) |
| **WordPress** | 6.4 to 6.7+ |
| **WooCommerce** | 8.5 to 11.2+ |
| **MySQL / MariaDB** | 5.7+ / 10.3+ |

---

## 🚀 Installation

### Option 1: WordPress Dashboard (ZIP Upload)
1. Download the latest `wpcalibrate-shipment-tracker-1.0.0.zip` release.
2. In your WordPress admin, navigate to **Plugins > Add New > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Option 2: Manual FTP / SFTP Installation
1. Extract `wpcalibrate-shipment-tracker-1.0.0.zip` to your computer.
2. Upload the `wpcalibrate-shipment-tracker` folder to your server's `/wp-content/plugins/` directory.
3. Activate **WPCalibrate Shipment Tracker** in **Plugins > Installed Plugins**.

### Option 3: Git Installation
```bash
cd wp-content/plugins/
git clone https://github.com/zeeshanraza-official/wpcalibrate-shipment-tracker.git wpcalibrate-shipment-tracker
```

---

## 📚 Documentation & How to Use

### 1. Fulfilling an Order and Adding Shipments
1. Navigate to **WooCommerce > Orders** and open an order in *Processing* status.
2. Locate the **Shipments & Tracking** meta box.
3. Click **Add Shipment** to open the modal.
4. Select the carrier, enter the tracking number, select status (*Shipped*), and adjust the quantity of each line item packed in this consignment.
5. Click **Save Shipment**. The customer is instantly notified via email.

### 2. Setting Up Public Guest Tracking
1. Go to **Pages > Add New** and create a page titled **Track Your Order**.
2. Insert the tracking shortcode block:
   ```text
   [wpcalibrate_shipment_tracking]
   ```
3. Publish the page.
4. Navigate to **WPCalibrate > Shipment Tracker > General** tab, select the published page under **Public Tracking Page**, and save.

### 3. Adding a Custom Courier
1. Navigate to **WPCalibrate > Shipment Tracker > Carriers** tab.
2. Scroll to the **Add Custom Carrier** section.
3. Enter the carrier name (e.g. *FastCourier*) and the tracking URL template:
   ```text
   https://track.example.com/status?code={tracking_number}
   ```
4. Check **Enable carrier immediately** and save.

### 4. Automatic Order Completion
In **Settings > General**, check **Mark order Completed when all shippable items are Delivered**. Orders will automatically transition to *Completed* once every non-refunded item is marked *Delivered*.

---

## 🗄️ Database Schema

The plugin maintains three dedicated, relational, indexed database tables created via `dbDelta`:

- `wp_wpcalibrate_shipments`: Primary shipment records (Order ID, carrier, tracking number, status, delivery dates).
- `wp_wpcalibrate_shipment_items`: Relational mapping of line item IDs and allocated quantities per shipment.
- `wp_wpcalibrate_shipment_events`: Immutable audit trail of lifecycle timestamp events and actors.

---

## 📝 Changelog

### [1.0.2] - 2026-10-15
- **Fixed**: Eliminated duplicate plugin folders on remote WordPress servers. Enforced single canonical installation at `wpcalibrate-shipment-tracker`.
- **Enhanced**: Complete card-based redesign across all 8 settings tabs (Overview, General, Tracking, Carriers, Emails, Advanced, License, Support).
- **Added**: Animated `.wpc-switch` toggle switches and segmented Dashicons navigation bar.

### [1.0.1] - 2026-10-15
- **Added**: Comprehensive Overview tab with logistics pipeline diagram and operator guides.
- **Added**: Built-in 5 demo products seeder (`DemoDataSeeder`) under Advanced tab.
- **Fixed**: Admin menu branding icon sizing and high-resolution dark branding dashboard header.

### [1.0.0] - 2026-10-15
- Initial production release.
- HPOS compatibility and custom relational database architecture.
- Partial fulfillment allocation engine.
- 7+ built-in carriers, guest tracking shortcode, and WooCommerce transactional email integration.

---

## 🔒 Security & Privacy

- Full capability checks (`manage_woocommerce` / `edit_shop_orders`) on all endpoints.
- CSRF protection with nonces on all forms and AJAX actions.
- Brute-force protection on guest tracking with SHA-256 IP rate limiting.
- Constant-time cryptographic comparison (`hash_equals`) for guest order verification.
- Prepared SQL statements via `$wpdb->prepare()`.

---

## 📄 License & Credits

- **Author**: [WPCalibrate](https://wpcalibrate.com)
- **Marketplace**: [https://marketplace.wpcalibrate.com/](https://marketplace.wpcalibrate.com/)
- **License**: [GNU General Public License v2.0 or later (GPL-2.0-or-later)](https://www.gnu.org/licenses/gpl-2.0.html)
- **Support**: support@wpcalibrate.com | WhatsApp: +44 7474 795976
