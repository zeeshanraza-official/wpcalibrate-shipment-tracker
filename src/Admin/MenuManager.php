<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Admin;

defined('ABSPATH') || exit;

/**
 * Handles registration and management of the WPCalibrate admin menus.
 * Implements a collision-resistant shared parent menu strategy so multiple
 * WPCalibrate plugins can seamlessly attach without duplication.
 */
class MenuManager {
	/**
	 * Shared parent slug for all WPCalibrate plugins.
	 */
	public const PARENT_SLUG = 'wpcalibrate';

	/**
	 * Submenu page slug for this plugin.
	 */
	public const SUBMENU_SLUG = 'wpcalibrate-shipment-tracker';

	/**
	 * Settings callback reference.
	 *
	 * @var callable|null
	 */
	private $settings_render_callback = null;

	/**
	 * Constructor.
	 *
	 * @param callable|null $settings_render_callback Callback to render the settings page.
	 */
	public function __construct(?callable $settings_render_callback = null) {
		$this->settings_render_callback = $settings_render_callback;
	}

	/**
	 * Register WordPress admin menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Priority 9: Ensure parent menu exists early.
		add_action('admin_menu', [$this, 'register_parent_menu'], 9);

		// Priority 10: Register plugin submenu.
		add_action('admin_menu', [$this, 'register_submenu'], 10);

		// Admin head styles for custom branding menu icon.
		add_action('admin_head', [$this, 'render_admin_menu_styles']);
	}

	/**
	 * Render CSS for the WPCalibrate top-level admin menu icon.
	 * Ensures high-resolution branding PNG is constrained to standard 20x20px WP menu icon dimensions.
	 *
	 * @return void
	 */
	public function render_admin_menu_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-style">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 7px 0 0 0 !important;
				object-fit: contain !important;
				box-sizing: content-box;
			}
		</style>
		<?php
	}

	/**
	 * Register or reuse the top-level WPCalibrate parent menu.
	 *
	 * @return void
	 */
	public function register_parent_menu(): void {
		// Capability check for administrative access.
		if (!current_user_can('manage_options')) {
			return;
		}

		global $admin_page_hooks;

		// Check if the parent menu has already been registered by another WPCalibrate plugin.
		if (!empty($admin_page_hooks[self::PARENT_SLUG])) {
			return;
		}

		// Icon for WordPress admin menu sidebar (white icon on dark admin bar).
		$icon_url = defined('WPC_SHIPMENT_TRACKER_URL')
			? WPC_SHIPMENT_TRACKER_URL . 'branding/icon-white.png'
			: 'dashicons-chart-line';

		add_menu_page(
			__('WPCalibrate', 'wpcalibrate-shipment-tracker'),
			__('WPCalibrate', 'wpcalibrate-shipment-tracker'),
			'manage_options',
			self::PARENT_SLUG,
			[$this, 'render_parent_page'],
			$icon_url,
			56 // Position just below WooCommerce and Products
		);
	}

	/**
	 * Register the WPCalibrate Shipment Tracker submenu page.
	 *
	 * @return void
	 */
	public function register_submenu(): void {
		if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
			return;
		}

		add_submenu_page(
			self::PARENT_SLUG,
			__('Shipment Tracker', 'wpcalibrate-shipment-tracker'),
			__('Shipment Tracker', 'wpcalibrate-shipment-tracker'),
			'manage_woocommerce',
			self::SUBMENU_SLUG,
			[$this, 'render_settings_page']
		);

		// If this plugin created the parent menu and has the default first submenu item matching parent,
		// rename the first item to "Overview" or "Shipment Tracker".
		global $submenu;
		if (isset($submenu[self::PARENT_SLUG][0]) && $submenu[self::PARENT_SLUG][0][2] === self::PARENT_SLUG) {
			$submenu[self::PARENT_SLUG][0][0] = __('Overview', 'wpcalibrate-shipment-tracker');
		}
	}

	/**
	 * Render callback for the shared parent page if accessed directly.
	 *
	 * @return void
	 */
	/**
	 * Render callback for the shared parent page if accessed directly.
	 *
	 * @return void
	 */
	public function render_parent_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wpcalibrate-shipment-tracker'));
		}

		global $wpdb;

		$settings_url  = admin_url('admin.php?page=' . self::SUBMENU_SLUG);
		$carriers_url  = admin_url('admin.php?page=' . self::SUBMENU_SLUG . '&tab=carriers');
		$tracking_url  = admin_url('admin.php?page=' . self::SUBMENU_SLUG . '&tab=tracking');
		$support_url   = admin_url('admin.php?page=' . self::SUBMENU_SLUG . '&tab=support');
		$orders_url    = admin_url('admin.php?page=wc-orders');
		$logo_url      = defined('WPC_SHIPMENT_TRACKER_URL')
			? WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png'
			: '';

		$is_hpos_active = class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		// Live count of shipments recorded in database (safe with try-catch).
		$total_shipments = 0;
		try {
			if (isset($wpdb) && is_object($wpdb)) {
				$shipments_table = $wpdb->prefix . 'wpcalibrate_shipments';
				if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $shipments_table)) === $shipments_table) {
					$total_shipments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$shipments_table}");
				}
			}
		} catch (\Throwable $e) {
			$total_shipments = 0;
		}

		// Count of active carriers (safe with try-catch and fallback).
		$carrier_count = 7;
		try {
			if (class_exists(\WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry::class)) {
				$registry = new \WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry();
				$carrier_count = count($registry->get_enabled());
			}
		} catch (\Throwable $e) {
			$carrier_count = 7;
		}
		?>
		<div class="wrap wpcalibrate-admin-wrap wpc-suite-wrap">
			<!-- Suite Top Header Banner -->
			<header class="wpc-suite-header">
				<div class="wpc-suite-header-main">
					<div class="wpc-suite-logo-box">
						<?php if (!empty($logo_url)) : ?>
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('WPCalibrate Logo', 'wpcalibrate-shipment-tracker'); ?>" class="wpc-suite-logo" width="48" height="48">
						<?php else : ?>
							<span class="dashicons dashicons-admin-generic" style="font-size: 32px; color: #3b82f6;"></span>
						<?php endif; ?>
					</div>
					<div class="wpc-suite-header-info">
						<div class="wpc-suite-title-row">
							<h1 class="wpc-suite-title"><?php esc_html_e('WPCalibrate Suite', 'wpcalibrate-shipment-tracker'); ?></h1>
							<span class="wpc-suite-badge-status">
								<span class="wpc-status-dot"></span>
								<?php esc_html_e('System Operational', 'wpcalibrate-shipment-tracker'); ?>
							</span>
							<span class="wpc-suite-badge-hpos">
								<?php echo $is_hpos_active ? esc_html__('HPOS Engine Active', 'wpcalibrate-shipment-tracker') : esc_html__('Classic Storage Ready', 'wpcalibrate-shipment-tracker'); ?>
							</span>
							<span class="wpc-suite-badge-version">v<?php echo esc_html(defined('WPC_SHIPMENT_TRACKER_VERSION') ? WPC_SHIPMENT_TRACKER_VERSION : '1.0.3'); ?></span>
						</div>
						<p class="wpc-suite-tagline"><?php esc_html_e('Enterprise shipment tracking, inventory allocation, and operations hub for WooCommerce.', 'wpcalibrate-shipment-tracker'); ?></p>
					</div>
				</div>
				<div class="wpc-suite-header-actions">
					<a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-hero wpc-btn-primary">
						<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span>
						<?php esc_html_e('Shipment Tracker Settings', 'wpcalibrate-shipment-tracker'); ?>
					</a>
					<a href="<?php echo esc_url($orders_url); ?>" class="button button-secondary wpc-btn-light">
						<span class="dashicons dashicons-cart" style="margin-top: 3px;"></span>
						<?php esc_html_e('WooCommerce Orders', 'wpcalibrate-shipment-tracker'); ?>
					</a>
				</div>
			</header>

			<!-- Suite Navigation Tabs -->
			<nav class="wpc-suite-nav" aria-label="<?php esc_attr_e('WPCalibrate Suite Navigation', 'wpcalibrate-shipment-tracker'); ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=' . self::PARENT_SLUG)); ?>" class="wpc-suite-nav-item active">
					<span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
					<span><?php esc_html_e('Suite Overview', 'wpcalibrate-shipment-tracker'); ?></span>
				</a>
				<a href="<?php echo esc_url($settings_url); ?>" class="wpc-suite-nav-item">
					<span class="dashicons dashicons-car" aria-hidden="true"></span>
					<span><?php esc_html_e('Shipment Tracker Settings', 'wpcalibrate-shipment-tracker'); ?></span>
				</a>
				<a href="<?php echo esc_url($carriers_url); ?>" class="wpc-suite-nav-item">
					<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
					<span><?php esc_html_e('Carriers Registry', 'wpcalibrate-shipment-tracker'); ?></span>
				</a>
				<a href="<?php echo esc_url($orders_url); ?>" class="wpc-suite-nav-item">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<span><?php esc_html_e('WooCommerce Orders', 'wpcalibrate-shipment-tracker'); ?></span>
				</a>
				<a href="<?php echo esc_url($support_url); ?>" class="wpc-suite-nav-item">
					<span class="dashicons dashicons-sos" aria-hidden="true"></span>
					<span><?php esc_html_e('Support & Help', 'wpcalibrate-shipment-tracker'); ?></span>
				</a>
			</nav>

			<!-- Operational Statistics Strip -->
			<section class="wpc-suite-stats-grid">
				<div class="wpc-stat-card">
					<div class="wpc-stat-icon-wrap" style="background: rgba(59, 130, 246, 0.1); color: #2563eb;">📦</div>
					<div class="wpc-stat-content">
						<span class="wpc-stat-label"><?php esc_html_e('Total Consignments', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-val"><?php echo esc_html((string) $total_shipments); ?></span>
						<span class="wpc-stat-sub"><?php esc_html_e('Recorded in database', 'wpcalibrate-shipment-tracker'); ?></span>
					</div>
				</div>
				<div class="wpc-stat-card">
					<div class="wpc-stat-icon-wrap" style="background: rgba(16, 185, 129, 0.1); color: #059669;">🚚</div>
					<div class="wpc-stat-content">
						<span class="wpc-stat-label"><?php esc_html_e('Active Couriers', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-val"><?php echo esc_html((string) $carrier_count); ?></span>
						<span class="wpc-stat-sub"><?php esc_html_e('Built-in + Custom templates', 'wpcalibrate-shipment-tracker'); ?></span>
					</div>
				</div>
				<div class="wpc-stat-card">
					<div class="wpc-stat-icon-wrap" style="background: rgba(245, 158, 11, 0.1); color: #d97706;">⚡</div>
					<div class="wpc-stat-content">
						<span class="wpc-stat-label"><?php esc_html_e('Order Data Store', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-val"><?php echo $is_hpos_active ? esc_html__('HPOS Table', 'wpcalibrate-shipment-tracker') : esc_html__('Classic Posts', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-sub"><?php esc_html_e('Zero query overhead', 'wpcalibrate-shipment-tracker'); ?></span>
					</div>
				</div>
				<div class="wpc-stat-card">
					<div class="wpc-stat-icon-wrap" style="background: rgba(99, 102, 241, 0.1); color: #4f46e5;">🛡️</div>
					<div class="wpc-stat-content">
						<span class="wpc-stat-label"><?php esc_html_e('Portal Protection', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-val"><?php esc_html_e('Rate Limited', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-stat-sub"><?php esc_html_e('Brute-force defended', 'wpcalibrate-shipment-tracker'); ?></span>
					</div>
				</div>
			</section>

			<!-- Main Featured Extension Card -->
			<div class="wpc-suite-main-grid">
				<section class="wpc-suite-card wpc-suite-featured-card">
					<div class="wpc-featured-badge-row">
						<span class="wpc-pill-active">✓ <?php esc_html_e('Active Extension', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-pill-version">v<?php echo esc_html(defined('WPC_SHIPMENT_TRACKER_VERSION') ? WPC_SHIPMENT_TRACKER_VERSION : '1.0.3'); ?></span>
					</div>

					<div class="wpc-featured-head">
						<h2 class="wpc-featured-title"><?php esc_html_e('WPCalibrate Shipment & Order Tracker', 'wpcalibrate-shipment-tracker'); ?></h2>
						<p class="wpc-featured-desc">
							<?php esc_html_e('Enterprise shipment tracking engine supporting partial order fulfillment, multi-package dispatches, automatic carrier tracking links, customer timeline visualizations, and protected guest tracking lookups.', 'wpcalibrate-shipment-tracker'); ?>
						</p>
					</div>

					<div class="wpc-capabilities-grid">
						<div class="wpc-cap-item">
							<span class="wpc-cap-icon">📦</span>
							<div>
								<h3 class="wpc-cap-title"><?php esc_html_e('Partial Fulfillment Math', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-cap-text"><?php esc_html_e('Split line items across shipments with strict mathematical allocation that prevents accidental over-fulfillment.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-cap-item">
							<span class="wpc-cap-icon">🚚</span>
							<div>
								<h3 class="wpc-cap-title"><?php esc_html_e('Global Carrier Network', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-cap-text"><?php esc_html_e('Preconfigured for UPS, FedEx, USPS, DHL Express, Royal Mail, Canada Post, Australia Post, plus unlimited custom templates.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-cap-item">
							<span class="wpc-cap-icon">⏱️</span>
							<div>
								<h3 class="wpc-cap-title"><?php esc_html_e('Customer Self-Service', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-cap-text"><?php esc_html_e('Visual milestone timeline on My Account, order completion emails, and rate-limited public guest search.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
						<div class="wpc-cap-item">
							<span class="wpc-cap-icon">⚡</span>
							<div>
								<h3 class="wpc-cap-title"><?php esc_html_e('Dedicated Database Tables', 'wpcalibrate-shipment-tracker'); ?></h3>
								<p class="wpc-cap-text"><?php esc_html_e('Relational tables keep your WooCommerce postmeta lean, fast, and fully compliant with HPOS High-Performance storage.', 'wpcalibrate-shipment-tracker'); ?></p>
							</div>
						</div>
					</div>

					<div class="wpc-featured-actions">
						<a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-hero">
							<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span>
							<?php esc_html_e('Configure Shipment Tracker', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url($orders_url); ?>" class="button button-secondary">
							<span class="dashicons dashicons-cart" style="margin-top: 3px;"></span>
							<?php esc_html_e('Fulfill Orders in WooCommerce', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url($carriers_url); ?>" class="button button-secondary">
							<span class="dashicons dashicons-location-alt" style="margin-top: 3px;"></span>
							<?php esc_html_e('Manage Shipping Couriers', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>
				</section>

				<!-- Quick Setup & Shortcode Integration Box -->
				<section class="wpc-suite-card wpc-integration-card">
					<div class="wpc-card-header-simple">
						<span class="wpc-card-icon-round"><span class="dashicons dashicons-shortcode"></span></span>
						<div>
							<h3 class="wpc-card-title-simple"><?php esc_html_e('Public Guest Tracking Setup', 'wpcalibrate-shipment-tracker'); ?></h3>
							<p class="wpc-card-sub-simple"><?php esc_html_e('Provide a 24/7 self-service order tracking page for shoppers.', 'wpcalibrate-shipment-tracker'); ?></p>
						</div>
					</div>

					<p style="font-size: 13px; color: #475569; margin: 0 0 12px 0;">
						<?php esc_html_e('Copy this shortcode and paste it into any WordPress page to enable your public tracking portal:', 'wpcalibrate-shipment-tracker'); ?>
					</p>

					<div class="wpc-shortcode-box">
						<span class="wpc-shortcode-code">[wpcalibrate_shipment_tracking]</span>
						<button type="button" class="wpc-btn-copy-shortcode" data-clipboard-text="[wpcalibrate_shipment_tracking]">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e('Copy Shortcode', 'wpcalibrate-shipment-tracker'); ?>
						</button>
					</div>

					<div class="wpc-steps-mini">
						<div class="wpc-step-mini-item">
							<span class="wpc-step-mini-num">1</span>
							<span><?php esc_html_e('Create a Page titled "Track Your Order"', 'wpcalibrate-shipment-tracker'); ?></span>
						</div>
						<div class="wpc-step-mini-item">
							<span class="wpc-step-mini-num">2</span>
							<span><?php esc_html_e('Paste the [wpcalibrate_shipment_tracking] shortcode & publish', 'wpcalibrate-shipment-tracker'); ?></span>
						</div>
						<div class="wpc-step-mini-item">
							<span class="wpc-step-mini-num">3</span>
							<span><?php esc_html_e('Select the page in Shipment Tracker > General tab', 'wpcalibrate-shipment-tracker'); ?></span>
						</div>
					</div>

					<div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
						<a href="<?php echo esc_url($tracking_url); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
							<?php esc_html_e('Customize Tracking Portal Display Options &rarr;', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>
				</section>
			</div>

			<!-- Ecosystem, Marketplace & Dedicated Support Grid -->
			<div class="wpc-ecosystem-header">
				<h2><?php esc_html_e('WPCalibrate Ecosystem & Professional Support', 'wpcalibrate-shipment-tracker'); ?></h2>
				<p><?php esc_html_e('Enterprise tools, technical documentation, and priority engineering support for high-volume WooCommerce stores.', 'wpcalibrate-shipment-tracker'); ?></p>
			</div>

			<div class="wpc-ecosystem-grid">
				<div class="wpc-eco-card">
					<div class="wpc-eco-icon">🛍️</div>
					<h3 class="wpc-eco-title"><?php esc_html_e('WPCalibrate Marketplace', 'wpcalibrate-shipment-tracker'); ?></h3>
					<p class="wpc-eco-text"><?php esc_html_e('Discover extensions, warehouse integrations, and developer plugins crafted specifically for WooCommerce.', 'wpcalibrate-shipment-tracker'); ?></p>
					<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php esc_html_e('Visit Marketplace &rarr;', 'wpcalibrate-shipment-tracker'); ?>
					</a>
				</div>

				<div class="wpc-eco-card">
					<div class="wpc-eco-icon">📖</div>
					<h3 class="wpc-eco-title"><?php esc_html_e('Official Documentation', 'wpcalibrate-shipment-tracker'); ?></h3>
					<p class="wpc-eco-text"><?php esc_html_e('Comprehensive operational guides, carrier setup tutorials, and PHP action/filter hook documentation.', 'wpcalibrate-shipment-tracker'); ?></p>
					<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php esc_html_e('Read Documentation &rarr;', 'wpcalibrate-shipment-tracker'); ?>
					</a>
				</div>

				<div class="wpc-eco-card">
					<div class="wpc-eco-icon">✉️</div>
					<h3 class="wpc-eco-title"><?php esc_html_e('Engineering Support', 'wpcalibrate-shipment-tracker'); ?></h3>
					<p class="wpc-eco-text"><?php esc_html_e('Direct assistance from senior WordPress and WooCommerce architects. Guaranteed rapid ticket resolution.', 'wpcalibrate-shipment-tracker'); ?></p>
					<a href="mailto:support@wpcalibrate.com" class="button button-secondary">
						support@wpcalibrate.com
					</a>
				</div>

				<div class="wpc-eco-card">
					<div class="wpc-eco-icon">💬</div>
					<h3 class="wpc-eco-title"><?php esc_html_e('WhatsApp Direct Line', 'wpcalibrate-shipment-tracker'); ?></h3>
					<p class="wpc-eco-text"><?php esc_html_e('Fast-response messaging for urgent store delivery inquiries, bespoke custom courier coding, or audits.', 'wpcalibrate-shipment-tracker'); ?></p>
					<a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer" class="button button-secondary wpc-btn-whatsapp">
						+44 7474 795976
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render callback for the plugin settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wpcalibrate-shipment-tracker'));
		}

		if (is_callable($this->settings_render_callback)) {
			call_user_func($this->settings_render_callback);
		} else {
			?>
			<div class="wrap">
				<h1><?php esc_html_e('WPCalibrate Shipment Tracker', 'wpcalibrate-shipment-tracker'); ?></h1>
			</div>
			<?php
		}
	}
}
