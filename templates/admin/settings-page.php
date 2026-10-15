<?php
/**
 * WPCalibrate Shipment Tracker Settings Page Template.
 *
 * Variables available from caller:
 * @var string $current_tab
 * @var array<string, mixed> $settings
 * @var array<string, array<string, mixed>> $carriers
 * @var \WPCalibrate\ShipmentTracker\Licensing\LicenseProviderInterface $license_provider
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$tabs = [
	'overview' => [
		'label' => __('Overview', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-chart-pie',
	],
	'general'  => [
		'label' => __('General', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-admin-generic',
	],
	'tracking' => [
		'label' => __('Tracking Portal', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-search',
	],
	'carriers' => [
		'label' => __('Carriers Registry', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-car',
	],
	'emails'   => [
		'label' => __('Email Alerts', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-email-alt',
	],
	'advanced' => [
		'label' => __('Advanced & Data', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-admin-tools',
	],
	'license'  => [
		'label' => __('License', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-admin-network',
	],
	'support'  => [
		'label' => __('Support & Help', 'wpcalibrate-shipment-tracker'),
		'icon'  => 'dashicons-format-chat',
	],
];

if (!array_key_exists($current_tab, $tabs)) {
	$current_tab = 'overview';
}

$logo_url = defined('WPC_SHIPMENT_TRACKER_URL')
	? WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png'
	: '';
$orders_url = admin_url('admin.php?page=wc-orders');
$is_hpos_active = class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
?>

<div class="wrap wpc-st-settings-wrap">
	<!-- Plugin Branding Header -->
	<header class="wpc-st-header-banner">
		<div class="wpc-st-header-logo-group">
			<?php if (!empty($logo_url)) : ?>
				<img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('WPCalibrate Logo', 'wpcalibrate-shipment-tracker'); ?>" class="wpc-st-brand-icon" width="48" height="48">
			<?php endif; ?>
			<div class="wpc-st-header-text">
				<h1 class="wpc-st-title"><?php esc_html_e('WPCalibrate Shipment Tracker', 'wpcalibrate-shipment-tracker'); ?></h1>
				<span class="wpc-st-version-tag">v<?php echo esc_html(WPC_SHIPMENT_TRACKER_VERSION); ?></span>
				<span class="wpc-st-badge-status">
					<?php echo $is_hpos_active ? esc_html__('HPOS Active', 'wpcalibrate-shipment-tracker') : esc_html__('Classic Storage Ready', 'wpcalibrate-shipment-tracker'); ?>
				</span>
			</div>
		</div>
		<div class="wpc-st-header-actions">
			<a href="<?php echo esc_url($orders_url); ?>" class="button button-secondary">
				<span class="dashicons dashicons-cart" style="margin-top: 3px;"></span>
				<?php esc_html_e('WooCommerce Orders', 'wpcalibrate-shipment-tracker'); ?>
			</a>
			<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer" class="button button-secondary">
				<span class="dashicons dashicons-book" style="margin-top: 3px;"></span>
				<?php esc_html_e('Docs', 'wpcalibrate-shipment-tracker'); ?>
			</a>
			<a href="<?php echo esc_url(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=support')); ?>" class="button button-primary">
				<span class="dashicons dashicons-sos" style="margin-top: 3px;"></span>
				<?php esc_html_e('Support', 'wpcalibrate-shipment-tracker'); ?>
			</a>
		</div>
	</header>

	<!-- Admin Notices -->
	<?php settings_errors(); ?>

	<!-- Navigation Tabs -->
	<nav class="wpc-st-nav-tabs" aria-label="<?php esc_attr_e('Settings Tabs', 'wpcalibrate-shipment-tracker'); ?>">
		<?php foreach ($tabs as $tab_key => $tab_data) : 
			$active_class = ($current_tab === $tab_key) ? 'nav-tab-active' : '';
			$tab_url = admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=' . $tab_key);
		?>
			<a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo esc_attr($active_class); ?>">
				<span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>" aria-hidden="true"></span>
				<span><?php echo esc_html($tab_data['label']); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<main class="wpc-st-tab-content">
		<?php if ($current_tab === 'overview') : ?>
			<!-- Overview Tab -->
			<div class="wpc-overview-wrap">
				<!-- Hero Section -->
				<section class="wpc-overview-hero">
					<span class="wpc-hero-badge">⚡ <?php esc_html_e('Enterprise WooCommerce Logistics', 'wpcalibrate-shipment-tracker'); ?></span>
					<h2><?php esc_html_e('Shipment & Order Tracking for WooCommerce', 'wpcalibrate-shipment-tracker'); ?></h2>
					<p class="wpc-hero-desc"><?php esc_html_e('Bridge the gap between physical logistics operations and customer delivery satisfaction. Split orders into multiple shipments, integrate 7+ global carriers, and empower customers with self-service real-time timelines.', 'wpcalibrate-shipment-tracker'); ?></p>
					
					<div class="wpc-overview-metrics">
						<div class="wpc-metric-card">
							<div class="wpc-metric-icon-wrap" aria-hidden="true">📦</div>
							<div>
								<h3 class="wpc-metric-label"><?php esc_html_e('Partial Fulfillment', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-metric-sub"><?php esc_html_e('Split line items across shipments', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-metric-card">
							<div class="wpc-metric-icon-wrap" aria-hidden="true">🚚</div>
							<div>
								<h3 class="wpc-metric-label"><?php esc_html_e('Multi-Carrier Ready', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-metric-sub"><?php esc_html_e('7+ Built-in + Custom Couriers', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-metric-card">
							<div class="wpc-metric-icon-wrap" aria-hidden="true">⚡</div>
							<div>
								<h3 class="wpc-metric-label"><?php esc_html_e('HPOS Compatible', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-metric-sub"><?php esc_html_e('High Performance Order Storage', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-metric-card">
							<div class="wpc-metric-icon-wrap" aria-hidden="true">🔒</div>
							<div>
								<h3 class="wpc-metric-label"><?php esc_html_e('Protected Portal', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-metric-sub"><?php esc_html_e('Rate-limited guest lookups', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>
				</section>

				<!-- Purpose & Value Pillars -->
				<section class="wpc-feature-grid">
					<article class="wpc-feature-card">
						<div class="wpc-feature-header">
							<span class="wpc-feature-icon">🎯</span>
							<h4><?php esc_html_e('Reduce WISMO Support Tickets', 'wpcalibrate-shipment-tracker'); ?></h4>
						</div>
						<p><?php esc_html_e('Delivery inquiries make up over 70% of e-commerce support tickets. Providing direct carrier tracking links and customer dashboard timeline views allows shoppers to self-serve their parcel status 24/7 without opening support tickets.', 'wpcalibrate-shipment-tracker'); ?></p>
					</article>
					<article class="wpc-feature-card">
						<div class="wpc-feature-header">
							<span class="wpc-feature-icon">📦</span>
							<h4><?php esc_html_e('Multi-Package Partial Fulfillment', 'wpcalibrate-shipment-tracker'); ?></h4>
						</div>
						<p><?php esc_html_e('When orders ship from multiple warehouses or contain backordered products, our mathematical allocation engine prevents accidental over-allocation and clearly shows customers which products are packed in each consignment.', 'wpcalibrate-shipment-tracker'); ?></p>
					</article>
					<article class="wpc-feature-card">
						<div class="wpc-feature-header">
							<span class="wpc-feature-icon">🛡️</span>
							<h4><?php esc_html_e('Protect Store & Shopper Privacy', 'wpcalibrate-shipment-tracker'); ?></h4>
						</div>
						<p><?php esc_html_e('Unlike basic tracking plugins vulnerable to automated scraping, WPCalibrate enforces constant-time cryptographic verification (Order ID + Billing Email) and intelligent rate limiting to block malicious enumeration attacks.', 'wpcalibrate-shipment-tracker'); ?></p>
					</article>
				</section>

				<!-- The Method: 4-Step Fulfillment Lifecycle -->
				<div class="wpc-panel">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon"><span class="dashicons dashicons-networking"></span></span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('The Method: 4-Step Fulfillment Lifecycle', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('How packages flow through your warehouse dispatch pipeline to final customer delivery.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<div class="wpc-workflow-grid">
						<div class="wpc-workflow-card">
							<span class="wpc-workflow-number">1</span>
							<h4><?php esc_html_e('Carrier Setup', 'wpcalibrate-shipment-tracker'); ?></h4>
							<p><?php esc_html_e('Enable built-in carriers (UPS, FedEx, USPS, DHL, Royal Mail, Australia Post, Canada Post) or define custom courier URL templates with dynamic tokens.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
						<div class="wpc-workflow-card">
							<span class="wpc-workflow-number">2</span>
							<h4><?php esc_html_e('Order Allocation', 'wpcalibrate-shipment-tracker'); ?></h4>
							<p><?php esc_html_e('On the order screen, click "Add Shipment". Select carrier, enter tracking code, and assign item quantities for this parcel with automatic validation.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
						<div class="wpc-workflow-card">
							<span class="wpc-workflow-number">3</span>
							<h4><?php esc_html_e('Status Progression', 'wpcalibrate-shipment-tracker'); ?></h4>
							<p><?php esc_html_e('Advance parcels through Preparing, Shipped, In Transit, Out for Delivery, and Delivered. Each transition logs an immutable audit event in the database.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
						<div class="wpc-workflow-card">
							<span class="wpc-workflow-number">4</span>
							<h4><?php esc_html_e('Customer Experience', 'wpcalibrate-shipment-tracker'); ?></h4>
							<p><?php esc_html_e('Shoppers receive transactional email alerts, check parcel timelines on My Account, and lookup tracking on your public portal 24/7.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<!-- Step-by-Step Operator Guide -->
				<div class="wpc-panel">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon"><span class="dashicons dashicons-welcome-learn-more"></span></span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('Operator Quick Guides', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('Actionable step-by-step instructions for everyday store fulfillment workflows.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<div class="wpc-guide-card">
						<h4><span class="dashicons dashicons-cart"></span> <?php esc_html_e('1. Fulfilling an Order with Tracking Number', 'wpcalibrate-shipment-tracker'); ?></h4>
						<ol>
							<li><?php esc_html_e('Go to WooCommerce > Orders in your admin sidebar.', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Open any processing order to access the order details screen.', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Locate the "Shipments & Tracking" panel (compatible with HPOS and classic views).', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Click "Add Shipment", select your shipping carrier, enter the tracking number, and set status to "Shipped".', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Adjust the quantity of each line item packed into this parcel and click "Save Shipment".', 'wpcalibrate-shipment-tracker'); ?></li>
						</ol>
					</div>

					<div class="wpc-guide-card">
						<h4><span class="dashicons dashicons-search"></span> <?php esc_html_e('2. Setting Up Public Guest Order Tracking', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p style="margin: 0 0 10px 0; font-size: 13px; color: #475569;">
							<?php esc_html_e('Enable guest customers and mobile shoppers to check their tracking status without signing in:', 'wpcalibrate-shipment-tracker'); ?>
						</p>
						<div class="wpc-shortcode-box">
							<span class="wpc-shortcode-code">[wpcalibrate_shipment_tracking]</span>
							<button type="button" class="wpc-btn-copy-shortcode" data-clipboard-text="[wpcalibrate_shipment_tracking]">
								<span class="dashicons dashicons-admin-page"></span>
								<?php esc_html_e('Copy Shortcode', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
						<ol>
							<li><?php esc_html_e('Create a new WordPress Page (e.g., "Track Your Order").', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Paste the shortcode [wpcalibrate_shipment_tracking] and publish the page.', 'wpcalibrate-shipment-tracker'); ?></li>
							<li><?php esc_html_e('Under Shipment Tracker > General tab, select your new page under "Public Tracking Page" and save.', 'wpcalibrate-shipment-tracker'); ?></li>
						</ol>
					</div>

					<div class="wpc-guide-card">
						<h4><span class="dashicons dashicons-car"></span> <?php esc_html_e('3. Adding Custom Courier URLs', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p style="margin: 0 0 8px 0; font-size: 13px; color: #475569;">
							<?php esc_html_e('In the Carriers tab, click "Add Custom Carrier" and use the dynamic token {tracking_number}:', 'wpcalibrate-shipment-tracker'); ?>
						</p>
						<div class="wpc-shortcode-box">
							<span class="wpc-shortcode-code">https://track.mycourier.com/status?code={tracking_number}</span>
							<button type="button" class="wpc-btn-copy-shortcode" data-clipboard-text="https://track.mycourier.com/status?code={tracking_number}">
								<span class="dashicons dashicons-admin-page"></span>
								<?php esc_html_e('Copy Example', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Quick Setup Shortcuts -->
				<div class="wpc-panel">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon"><span class="dashicons dashicons-dashboard"></span></span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('Quick Setup Shortcuts', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('Jump directly to key configuration panels.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>
					<div class="wpc-card-actions" style="padding-top: 0;">
						<a href="<?php echo esc_url(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=general')); ?>" class="button button-primary">
							<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span>
							<?php esc_html_e('General Settings', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=carriers')); ?>" class="button button-secondary">
							<span class="dashicons dashicons-car" style="margin-top: 3px;"></span>
							<?php esc_html_e('Shipping Carriers', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=tracking')); ?>" class="button button-secondary">
							<span class="dashicons dashicons-search" style="margin-top: 3px;"></span>
							<?php esc_html_e('Tracking Portal Options', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=emails')); ?>" class="button button-secondary">
							<span class="dashicons dashicons-email-alt" style="margin-top: 3px;"></span>
							<?php esc_html_e('Email Notifications', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>
				</div>
			</div>

		<?php elseif ($current_tab === 'general') : ?>
			<!-- General Settings Tab -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-admin-generic"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('General Fulfillment Settings', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Configure public tracking endpoints and order completion automation rules.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wpc_st_save_settings">
					<input type="hidden" name="current_tab" value="general">
					<?php wp_nonce_field('wpc_st_save_settings', 'wpc_st_nonce'); ?>

					<div class="wpc-setting-card">
						<div class="wpc-setting-info">
							<label for="wpc-st-tracking-page" class="wpc-setting-label">
								<?php esc_html_e('Public Tracking Page', 'wpcalibrate-shipment-tracker'); ?>
							</label>
							<p class="wpc-setting-desc">
								<?php esc_html_e('Select the published WordPress page containing the [wpcalibrate_shipment_tracking] shortcode for guest shoppers.', 'wpcalibrate-shipment-tracker'); ?>
							</p>
						</div>
						<div class="wpc-select-group" style="min-width: 260px;">
							<?php
							wp_dropdown_pages([
								'name'              => 'tracking_page_id',
								'id'                => 'wpc-st-tracking-page',
								'selected'          => absint($settings['tracking_page_id'] ?? 0),
								'show_option_none'  => __('— Select Tracking Page —', 'wpcalibrate-shipment-tracker'),
								'option_none_value' => '0',
							]);
							?>
						</div>
					</div>

					<div class="wpc-setting-card">
						<div class="wpc-setting-info">
							<label for="wpc-st-auto-complete" class="wpc-setting-label">
								<?php esc_html_e('Automatic Order Completion', 'wpcalibrate-shipment-tracker'); ?>
							</label>
							<p class="wpc-setting-desc">
								<?php esc_html_e('Automatically transition order status to "Completed" when every ordered item is delivered across all consignments.', 'wpcalibrate-shipment-tracker'); ?>
							</p>
						</div>
						<label class="wpc-switch" for="wpc-st-auto-complete">
							<input type="checkbox" name="auto_complete_order" id="wpc-st-auto-complete" value="yes" <?php checked($settings['auto_complete_order'] ?? 'no', 'yes'); ?>>
							<span class="wpc-slider"></span>
						</label>
					</div>

					<div class="wpc-save-bar">
						<button type="submit" class="button button-primary button-hero">
							<?php esc_html_e('Save General Settings', 'wpcalibrate-shipment-tracker'); ?>
						</button>
					</div>
				</form>
			</div>

		<?php elseif ($current_tab === 'tracking') : ?>
			<!-- Tracking Portal Display Tab -->
			<div class="wpc-preview-wrap">
				<!-- Settings Form Column -->
				<div class="wpc-panel" style="margin-bottom: 0;">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon"><span class="dashicons dashicons-search"></span></span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('Tracking Portal Display Options', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('Control what information is visible on My Account and public guest tracking views.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="wpc_st_save_settings">
						<input type="hidden" name="current_tab" value="tracking">
						<?php wp_nonce_field('wpc_st_save_settings', 'wpc_st_nonce'); ?>

						<div class="wpc-setting-card">
							<div class="wpc-setting-info">
								<label for="wpc-st-enable-guest" class="wpc-setting-label">
									<?php esc_html_e('Guest Order Tracking', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<p class="wpc-setting-desc">
									<?php esc_html_e('Allow guest customers to verify delivery status using Order Number and Billing Email with brute-force rate limiting.', 'wpcalibrate-shipment-tracker'); ?>
								</p>
							</div>
							<label class="wpc-switch" for="wpc-st-enable-guest">
								<input type="checkbox" name="enable_guest_tracking" id="wpc-st-enable-guest" value="yes" <?php checked($settings['enable_guest_tracking'] ?? 'yes', 'yes'); ?>>
								<span class="wpc-slider"></span>
							</label>
						</div>

						<div class="wpc-setting-card">
							<div class="wpc-setting-info">
								<label for="wpc-st-show-carrier-link" class="wpc-setting-label">
									<?php esc_html_e('External Carrier Tracking Link', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<p class="wpc-setting-desc">
									<?php esc_html_e('Display direct outbound links to track parcels directly on the official courier portal.', 'wpcalibrate-shipment-tracker'); ?>
								</p>
							</div>
							<label class="wpc-switch" for="wpc-st-show-carrier-link">
								<input type="checkbox" name="show_carrier_link" id="wpc-st-show-carrier-link" value="yes" <?php checked($settings['show_carrier_link'] ?? 'yes', 'yes'); ?>>
								<span class="wpc-slider"></span>
							</label>
						</div>

						<div class="wpc-setting-card">
							<div class="wpc-setting-info">
								<label for="wpc-st-show-items" class="wpc-setting-label">
									<?php esc_html_e('Item Allocation Breakdown', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<p class="wpc-setting-desc">
									<?php esc_html_e('Show the list of products and quantities packed inside each individual shipment box.', 'wpcalibrate-shipment-tracker'); ?>
								</p>
							</div>
							<label class="wpc-switch" for="wpc-st-show-items">
								<input type="checkbox" name="show_items_in_tracking" id="wpc-st-show-items" value="yes" <?php checked($settings['show_items_in_tracking'] ?? 'yes', 'yes'); ?>>
								<span class="wpc-slider"></span>
							</label>
						</div>

						<div class="wpc-setting-card">
							<div class="wpc-setting-info">
								<label for="wpc-st-show-timeline" class="wpc-setting-label">
									<?php esc_html_e('Chronological Shipment Timeline', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<p class="wpc-setting-desc">
									<?php esc_html_e('Display date and time milestone progression (Preparing, Shipped, In Transit, Delivered) on tracking cards.', 'wpcalibrate-shipment-tracker'); ?>
								</p>
							</div>
							<label class="wpc-switch" for="wpc-st-show-timeline">
								<input type="checkbox" name="show_timeline" id="wpc-st-show-timeline" value="yes" <?php checked($settings['show_timeline'] ?? 'yes', 'yes'); ?>>
								<span class="wpc-slider"></span>
							</label>
						</div>

						<div class="wpc-save-bar">
							<button type="submit" class="button button-primary button-hero">
								<?php esc_html_e('Save Tracking Preferences', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- Live Simulation Preview Column -->
				<div class="wpc-sim-card">
					<span class="wpc-sim-badge"><?php esc_html_e('Live Customer Preview', 'wpcalibrate-shipment-tracker'); ?></span>
					<div class="wpc-sim-content">
						<div class="wpc-sim-header">
							<div>
								<strong style="font-size: 14px; color: #0f172a; display: block;"><?php esc_html_e('Package 1 of 1', 'wpcalibrate-shipment-tracker'); ?></strong>
								<span style="font-size: 12px; color: #64748b;">UPS Express &bull; <code>1Z9999999999999999</code></span>
							</div>
							<span class="wpc-carrier-badge-active">✓ In Transit</span>
						</div>

						<!-- Progress Timeline Bar -->
						<div class="wpc-sim-progress-bar">
							<div class="wpc-sim-progress-line"></div>
							<div class="wpc-sim-progress-active-line"></div>
							<div class="wpc-sim-step wpc-step-complete">
								<div class="wpc-sim-step-dot">✓</div>
								<span class="wpc-sim-step-label"><?php esc_html_e('Ordered', 'wpcalibrate-shipment-tracker'); ?></span>
							</div>
							<div class="wpc-sim-step wpc-step-complete">
								<div class="wpc-sim-step-dot">✓</div>
								<span class="wpc-sim-step-label"><?php esc_html_e('Preparing', 'wpcalibrate-shipment-tracker'); ?></span>
							</div>
							<div class="wpc-sim-step wpc-step-active">
								<div class="wpc-sim-step-dot">●</div>
								<span class="wpc-sim-step-label"><?php esc_html_e('Shipped', 'wpcalibrate-shipment-tracker'); ?></span>
							</div>
							<div class="wpc-sim-step">
								<div class="wpc-sim-step-dot">4</div>
								<span class="wpc-sim-step-label"><?php esc_html_e('Delivered', 'wpcalibrate-shipment-tracker'); ?></span>
							</div>
						</div>

						<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
							<span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e('Contents in this consignment:', 'wpcalibrate-shipment-tracker'); ?></span>
							<p style="margin: 4px 0 0 0; font-size: 13px; font-weight: 600; color: #0f172a;">
								Wireless Noise-Cancelling Headphones &times; 1
							</p>
						</div>

						<a href="#" class="button button-secondary" style="width: 100%; text-align: center; justify-content: center; pointer-events: none;">
							<span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
							<?php esc_html_e('Track Directly on Carrier Site &rarr;', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>
				</div>
			</div>

		<?php elseif ($current_tab === 'carriers') : ?>
			<!-- Carriers Registry Tab -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-car"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('Shipping Carriers Registry', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Manage predefined global couriers or register custom shipping companies with dynamic URL templates.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<table class="widefat fixed striped wpc-carriers-table" role="presentation">
					<thead>
						<tr>
							<th style="width: 25%;"><?php esc_html_e('Carrier Name', 'wpcalibrate-shipment-tracker'); ?></th>
							<th style="width: 15%;"><?php esc_html_e('Key', 'wpcalibrate-shipment-tracker'); ?></th>
							<th style="width: 38%;"><?php esc_html_e('Tracking URL Template', 'wpcalibrate-shipment-tracker'); ?></th>
							<th style="width: 10%;"><?php esc_html_e('Status', 'wpcalibrate-shipment-tracker'); ?></th>
							<th style="width: 12%; text-align: right;"><?php esc_html_e('Actions', 'wpcalibrate-shipment-tracker'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($carriers as $key => $c) : 
							$is_enabled = !empty($c['enabled']);
							$is_custom = !empty($c['is_custom']);
							$badge_class = 'wpc-carrier-icon-' . sanitize_html_class(str_replace('_', '-', $key));
							$sample_test_url = !empty($c['url_template']) ? str_replace('{tracking_number}', '1Z9999999999999999', $c['url_template']) : '';
						?>
							<tr>
								<td>
									<div class="wpc-carrier-title-cell">
										<span class="wpc-carrier-icon-badge <?php echo esc_attr($badge_class); ?>">
											<?php echo esc_html(strtoupper(substr($c['name'], 0, 1))); ?>
										</span>
										<div>
											<strong><?php echo esc_html($c['name']); ?></strong>
											<?php if ($is_custom) : ?>
												<span class="wpc-st-version-tag" style="background:#fef3c7; color:#b45309; border-color:#fde68a;"><?php esc_html_e('Custom', 'wpcalibrate-shipment-tracker'); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</td>
								<td><code><?php echo esc_html($key); ?></code></td>
								<td>
									<?php if (!empty($c['url_template'])) : ?>
										<code style="word-break: break-all; font-size: 11px;"><?php echo esc_html($c['url_template']); ?></code>
									<?php else : ?>
										<span style="color: #94a3b8;">&mdash;</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($is_enabled) : ?>
										<span class="wpc-carrier-badge-active"><?php esc_html_e('Active', 'wpcalibrate-shipment-tracker'); ?></span>
									<?php else : ?>
										<span class="wpc-carrier-badge-disabled"><?php esc_html_e('Disabled', 'wpcalibrate-shipment-tracker'); ?></span>
									<?php endif; ?>
								</td>
								<td style="text-align: right;">
									<div style="display: inline-flex; align-items: center; gap: 6px;">
										<?php if (!empty($sample_test_url)) : ?>
											<a href="<?php echo esc_url($sample_test_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" title="<?php esc_attr_e('Test tracking template URL with sample number', 'wpcalibrate-shipment-tracker'); ?>">
												<span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px; line-height: 13px;"></span>
											</a>
										<?php endif; ?>

										<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
											<input type="hidden" name="action" value="wpc_st_carrier_action">
											<input type="hidden" name="carrier_action" value="toggle_status">
											<input type="hidden" name="carrier_key" value="<?php echo esc_attr($key); ?>">
											<input type="hidden" name="carrier_enabled" value="<?php echo $is_enabled ? '0' : '1'; ?>">
											<?php wp_nonce_field('wpc_st_carrier_nonce', 'wpc_st_carrier_security'); ?>
											<button type="submit" class="button button-small">
												<?php echo $is_enabled ? esc_html__('Disable', 'wpcalibrate-shipment-tracker') : esc_html__('Enable', 'wpcalibrate-shipment-tracker'); ?>
											</button>
										</form>

										<?php if ($is_custom) : ?>
											<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to delete this custom carrier?', 'wpcalibrate-shipment-tracker')); ?>');">
												<input type="hidden" name="action" value="wpc_st_carrier_action">
												<input type="hidden" name="carrier_action" value="delete_custom">
												<input type="hidden" name="carrier_key" value="<?php echo esc_attr($key); ?>">
												<?php wp_nonce_field('wpc_st_carrier_nonce', 'wpc_st_carrier_security'); ?>
												<button type="submit" class="button button-small button-link-delete">
													<?php esc_html_e('Delete', 'wpcalibrate-shipment-tracker'); ?>
												</button>
											</form>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Add Custom Carrier Card -->
				<div class="wpc-panel" style="background: #f8fafc; border-color: #cbd5e1; margin-bottom: 0;">
					<h4 style="margin: 0 0 14px 0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
						<span class="dashicons dashicons-plus-alt2" style="color: #2563eb;"></span>
						<?php esc_html_e('Add Custom Carrier', 'wpcalibrate-shipment-tracker'); ?>
					</h4>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="wpc_st_carrier_action">
						<input type="hidden" name="carrier_action" value="add_custom">
						<?php wp_nonce_field('wpc_st_carrier_nonce', 'wpc_st_carrier_security'); ?>

						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 14px;">
							<div>
								<label for="wpc-st-custom-name" style="font-weight: 600; font-size: 13px; color: #0f172a; display: block; margin-bottom: 6px;">
									<?php esc_html_e('Carrier Name', 'wpcalibrate-shipment-tracker'); ?> <span class="required">*</span>
								</label>
								<input type="text" name="custom_carrier_name" id="wpc-st-custom-name" class="regular-text" style="width: 100%;" required placeholder="<?php esc_attr_e('e.g. DPD Germany', 'wpcalibrate-shipment-tracker'); ?>">
							</div>
							<div>
								<label for="wpc-st-custom-template" style="font-weight: 600; font-size: 13px; color: #0f172a; display: block; margin-bottom: 6px;">
									<?php esc_html_e('Tracking URL Template', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<input type="url" name="custom_url_template" id="wpc-st-custom-template" class="large-text" style="width: 100%;" placeholder="https://example.com/track?num={tracking_number}">
								<div id="wpc-token-preview-hint" style="font-size: 12px; margin-top: 4px;"></div>
							</div>
						</div>

						<p class="description" style="margin: 0 0 16px 0;">
							<?php esc_html_e('Use {tracking_number} as the dynamic token where the tracking code is placed. Must begin with http:// or https://.', 'wpcalibrate-shipment-tracker'); ?>
						</p>

						<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
							<label for="wpc-st-custom-enabled" style="font-size: 13px; font-weight: 600; color: #334155;">
								<input type="checkbox" name="custom_carrier_enabled" id="wpc-st-custom-enabled" value="1" checked>
								<?php esc_html_e('Enable carrier immediately upon saving', 'wpcalibrate-shipment-tracker'); ?>
							</label>

							<button type="submit" class="button button-primary">
								<?php esc_html_e('Add Carrier to Registry', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

		<?php elseif ($current_tab === 'emails') : ?>
			<!-- Emails Tab -->
			<div class="wpc-preview-wrap">
				<!-- Settings Column -->
				<div class="wpc-panel" style="margin-bottom: 0;">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon"><span class="dashicons dashicons-email-alt"></span></span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('Shipment Email Notifications', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('Manage transactional email notifications sent to customers when shipments are created or updated.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="wpc_st_save_settings">
						<input type="hidden" name="current_tab" value="emails">
						<?php wp_nonce_field('wpc_st_save_settings', 'wpc_st_nonce'); ?>

						<div class="wpc-setting-card">
							<div class="wpc-setting-info">
								<label for="wpc-st-email-notif-default" class="wpc-setting-label">
									<?php esc_html_e('Default Customer Notification Checkbox', 'wpcalibrate-shipment-tracker'); ?>
								</label>
								<p class="wpc-setting-desc">
									<?php esc_html_e('Pre-check the "Send customer notification email" checkbox when creating or modifying shipments on the order screen.', 'wpcalibrate-shipment-tracker'); ?>
								</p>
							</div>
							<label class="wpc-switch" for="wpc-st-email-notif-default">
								<input type="checkbox" name="customer_email_notification" id="wpc-st-email-notif-default" value="yes" <?php checked($settings['customer_email_notification'] ?? 'yes', 'yes'); ?>>
								<span class="wpc-slider"></span>
							</label>
						</div>

						<div class="wpc-save-bar">
							<button type="submit" class="button button-primary button-hero">
								<?php esc_html_e('Save Email Preferences', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- WooCommerce Email Customizer Link Column -->
				<div class="wpc-panel" style="margin-bottom: 0;">
					<div class="wpc-panel-header">
						<div class="wpc-panel-title-group">
							<span class="wpc-panel-icon">✉️</span>
							<div>
								<h3 class="wpc-panel-title"><?php esc_html_e('WooCommerce Template Customizer', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-panel-description"><?php esc_html_e('Standard core email customization settings.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
						<?php esc_html_e('The "Shipment Tracking Updated" transactional email is fully registered in WooCommerce core. You can configure recipients, subject lines, heading text, and preview HTML/plain-text templates directly from WooCommerce > Settings > Emails.', 'wpcalibrate-shipment-tracker'); ?>
					</p>

					<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
							<strong style="font-size: 13px; color: #0f172a;"><?php esc_html_e('Shipment Tracking Updated', 'wpcalibrate-shipment-tracker'); ?></strong>
							<span class="wpc-carrier-badge-active">Active</span>
						</div>
						<p style="margin: 0; font-size: 12px; color: #64748b;">
							<?php esc_html_e('Class: WPC_Shipment_Updated_Email &bull; Recipient: Customer', 'wpcalibrate-shipment-tracker'); ?>
						</p>
					</div>

					<a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=email&section=wpc_shipment_updated')); ?>" class="button button-secondary button-hero" style="width: 100%; text-align: center; justify-content: center;">
						<span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
						<?php esc_html_e('Configure Email in WooCommerce &rarr;', 'wpcalibrate-shipment-tracker'); ?>
					</a>
				</div>
			</div>

		<?php elseif ($current_tab === 'advanced') : ?>
			<!-- Advanced & Data Management Tab -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-admin-tools"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('Advanced Configuration & Data Lifecycle', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Manage uninstall data retention, diagnostic technical logging, and developer utilities.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wpc_st_save_settings">
					<input type="hidden" name="current_tab" value="advanced">
					<?php wp_nonce_field('wpc_st_save_settings', 'wpc_st_nonce'); ?>

					<div class="wpc-setting-card">
						<div class="wpc-setting-info">
							<label for="wpc-st-logging" class="wpc-setting-label">
								<?php esc_html_e('Diagnostic Technical Logging', 'wpcalibrate-shipment-tracker'); ?>
							</label>
							<p class="wpc-setting-desc">
								<?php esc_html_e('Log technical shipment lifecycle events under source wpcalibrate-shipment-tracker to WooCommerce > Status > Logs.', 'wpcalibrate-shipment-tracker'); ?>
							</p>
						</div>
						<label class="wpc-switch" for="wpc-st-logging">
							<input type="checkbox" name="enable_logging" id="wpc-st-logging" value="yes" <?php checked($settings['enable_logging'] ?? 'no', 'yes'); ?>>
							<span class="wpc-slider"></span>
						</label>
					</div>

					<div class="wpc-setting-card" style="border-left: 4px solid #ef4444; background: #fff5f5;">
						<div class="wpc-setting-info">
							<label for="wpc-st-delete-data" class="wpc-setting-label" style="color: #b91c1c;">
								⚠️ <?php esc_html_e('Data Retention on Plugin Uninstall', 'wpcalibrate-shipment-tracker'); ?>
							</label>
							<p class="wpc-setting-desc" style="color: #7f1d1d;">
								<?php esc_html_e('CAUTION: If enabled, deleting this plugin will permanently drop custom shipment database tables and remove all tracking history. Default is OFF (data safely retained).', 'wpcalibrate-shipment-tracker'); ?>
							</p>
						</div>
						<label class="wpc-switch" for="wpc-st-delete-data">
							<input type="checkbox" name="delete_data_on_uninstall" id="wpc-st-delete-data" value="yes" <?php checked($settings['delete_data_on_uninstall'] ?? 'no', 'yes'); ?>>
							<span class="wpc-slider"></span>
						</label>
					</div>

					<div class="wpc-save-bar">
						<button type="submit" class="button button-primary button-hero">
							<?php esc_html_e('Save Advanced Settings', 'wpcalibrate-shipment-tracker'); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- Demo Products Seeder Card -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon">🛍️</span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('Demo Products & Testing Catalog', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('One-click seeding of 5 realistic e-commerce products with SKUs, prices, stock, and descriptions for fulfillment testing.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<div class="wpc-demo-products-grid">
					<div class="wpc-demo-item-pill">
						<span class="wpc-demo-item-title">🎧 Wireless Noise-Cancelling Headphones</span>
						<span class="wpc-demo-item-meta">$149.99 &bull; SKU: DEMO-WNC-01</span>
					</div>
					<div class="wpc-demo-item-pill">
						<span class="wpc-demo-item-title">⌨️ Ergonomic Mechanical Keyboard</span>
						<span class="wpc-demo-item-meta">$99.50 &bull; SKU: DEMO-EMK-02</span>
					</div>
					<div class="wpc-demo-item-pill">
						<span class="wpc-demo-item-title">🔌 Ultra-Fast USB-C Braided Cable 2M</span>
						<span class="wpc-demo-item-meta">$19.99 &bull; SKU: DEMO-UBC-03</span>
					</div>
					<div class="wpc-demo-item-pill">
						<span class="wpc-demo-item-title">⌚ Smart Fitness Activity Tracker</span>
						<span class="wpc-demo-item-meta">$79.00 &bull; SKU: DEMO-SAT-04</span>
					</div>
					<div class="wpc-demo-item-pill">
						<span class="wpc-demo-item-title">💼 Minimalist Leather Laptop Sleeve</span>
						<span class="wpc-demo-item-meta">$45.00 &bull; SKU: DEMO-MLS-05</span>
					</div>
				</div>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="wpc_st_seed_demo_products">
					<?php wp_nonce_field('wpc_st_seed_demo_nonce', 'wpc_st_seed_security'); ?>
					<button type="submit" class="button button-secondary">
						<span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
						<?php esc_html_e('Seed / Verify 5 Demo Products', 'wpcalibrate-shipment-tracker'); ?>
					</button>
				</form>
			</div>

			<!-- Environment Diagnostics Card -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-dashboard"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('System Diagnostics & Platform Status', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Current server environment and WooCommerce compatibility metrics.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<div class="wpc-diag-grid">
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('Plugin Version', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val"><?php echo esc_html(WPC_SHIPMENT_TRACKER_VERSION); ?></p>
					</div>
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('Database Schema', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val"><?php echo esc_html((string) get_option(\WPCalibrate\ShipmentTracker\Database\MigrationManager::SCHEMA_VERSION_OPTION, '1.0.0')); ?></p>
					</div>
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('PHP Environment', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val"><?php echo esc_html(PHP_VERSION); ?></p>
					</div>
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('WordPress Core', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val"><?php echo esc_html(get_bloginfo('version')); ?></p>
					</div>
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('WooCommerce Core', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val"><?php echo esc_html(defined('WC_VERSION') ? WC_VERSION : 'N/A'); ?></p>
					</div>
					<div class="wpc-diag-card">
						<p class="wpc-diag-label"><?php esc_html_e('HPOS Status', 'wpcalibrate-shipment-tracker'); ?></p>
						<p class="wpc-diag-val">
							<?php 
								if ($is_hpos_active) {
									echo '<span style="color: #059669;">' . esc_html__('Active (HPOS)', 'wpcalibrate-shipment-tracker') . '</span>';
								} else {
									echo esc_html__('Legacy Post Storage', 'wpcalibrate-shipment-tracker');
								}
							?>
						</p>
					</div>
				</div>
			</div>

		<?php elseif ($current_tab === 'license') : ?>
			<!-- Licensing Tab -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-admin-network"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('License & Edition Status', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Manage your WPCalibrate license key and update entitlements.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
					<span class="wpc-badge-active" style="background:#ecfdf5; color:#047857; border-color:#a7f3d0;">
						✓ <?php esc_html_e('Unrestricted Edition', 'wpcalibrate-shipment-tracker'); ?>
					</span>
				</div>

				<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 24px;">
					<p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.6;">
						<strong><?php esc_html_e('License Status: Community / Neutral Provider', 'wpcalibrate-shipment-tracker'); ?></strong> &mdash; 
						<?php esc_html_e('All core shipment tracking, partial allocations, carrier registries, and email notification functions are fully active and unrestricted in this release.', 'wpcalibrate-shipment-tracker'); ?>
					</p>
				</div>

				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 560px;">
					<input type="hidden" name="action" value="wpc_st_license_action">
					<input type="hidden" name="license_action" value="activate">
					<?php wp_nonce_field('wpc_st_license_nonce', 'wpc_st_license_security'); ?>

					<div style="margin-bottom: 16px;">
						<label for="wpc-st-license-key" style="font-weight: 600; font-size: 13px; color: #0f172a; display: block; margin-bottom: 6px;">
							<?php esc_html_e('Marketplace License Key', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<input type="password" name="license_key" id="wpc-st-license-key" class="regular-text" style="width: 100%;" placeholder="••••••••-••••-••••-••••••••••••" value="<?php echo esc_attr($license_provider->get_masked_key()); ?>" autocomplete="off">
						<span class="description" style="display: block; margin-top: 6px; font-size: 12px;">
							<?php esc_html_e('Enter your license key obtained from marketplace.wpcalibrate.com to receive automatic updates.', 'wpcalibrate-shipment-tracker'); ?>
						</span>
					</div>

					<button type="submit" class="button button-primary">
						<?php esc_html_e('Activate License Key', 'wpcalibrate-shipment-tracker'); ?>
					</button>
				</form>
			</div>

		<?php elseif ($current_tab === 'support') : ?>
			<!-- Support & Resources Tab -->
			<div class="wpc-panel">
				<div class="wpc-panel-header">
					<div class="wpc-panel-title-group">
						<span class="wpc-panel-icon"><span class="dashicons dashicons-format-chat"></span></span>
						<div>
							<h3 class="wpc-panel-title"><?php esc_html_e('WPCalibrate Support & Ecosystem', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-panel-description"><?php esc_html_e('Official resources, direct engineering support, and documentation.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>
				</div>

				<div class="wpc-support-grid">
					<div class="wpc-support-card">
						<span class="wpc-support-icon">🛍️</span>
						<h4><?php esc_html_e('Marketplace', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p><?php esc_html_e('Browse add-ons, premium extensions, and developer licenses.', 'wpcalibrate-shipment-tracker'); ?></p>
						<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e('Visit Marketplace &rarr;', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>

					<div class="wpc-support-card">
						<span class="wpc-support-icon">📖</span>
						<h4><?php esc_html_e('Documentation', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p><?php esc_html_e('Step-by-step developer guides, hooks reference, and FAQs.', 'wpcalibrate-shipment-tracker'); ?></p>
						<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e('Read Documentation &rarr;', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>

					<div class="wpc-support-card">
						<span class="wpc-support-icon">✉️</span>
						<h4><?php esc_html_e('Direct Email Support', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p><?php esc_html_e('Need assistance or custom courier integration? Contact us.', 'wpcalibrate-shipment-tracker'); ?></p>
						<a href="mailto:support@wpcalibrate.com" class="button button-secondary">
							support@wpcalibrate.com
						</a>
					</div>

					<div class="wpc-support-card">
						<span class="wpc-support-icon">💬</span>
						<h4><?php esc_html_e('Phone / WhatsApp', 'wpcalibrate-shipment-tracker'); ?></h4>
						<p><?php esc_html_e('Direct telephone and WhatsApp messaging support line.', 'wpcalibrate-shipment-tracker'); ?></p>
						<a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer" class="button button-secondary wpc-btn-whatsapp">
							+44 7474 795976
						</a>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</main>
</div>
