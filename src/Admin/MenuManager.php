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
	public function render_parent_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'wpcalibrate-shipment-tracker'));
		}

		$settings_url = admin_url('admin.php?page=' . self::SUBMENU_SLUG);
		$orders_url   = admin_url('admin.php?page=wc-orders');
		$logo_url     = defined('WPC_SHIPMENT_TRACKER_URL')
			? WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png'
			: '';
		?>
		<div class="wrap wpcalibrate-admin-wrap">
			<div class="wpc-parent-dashboard-header">
				<?php if (!empty($logo_url)) : ?>
					<img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('WPCalibrate Logo', 'wpcalibrate-shipment-tracker'); ?>" class="wpc-parent-dashboard-logo" width="52" height="52">
				<?php endif; ?>
				<div>
					<h1 class="wpc-parent-dashboard-title"><?php esc_html_e('WPCalibrate Suite', 'wpcalibrate-shipment-tracker'); ?></h1>
					<p class="wpc-parent-dashboard-tagline"><?php esc_html_e('High-performance extensions, shipment tracking, and developer tooling for WooCommerce.', 'wpcalibrate-shipment-tracker'); ?></p>
				</div>
			</div>

			<div class="wpc-dashboard-grid">
				<!-- Plugin Card: Shipment Tracker -->
				<div class="wpc-dashboard-card wpc-card-featured">
					<div class="wpc-card-badge-row">
						<span class="wpc-badge-active"><?php esc_html_e('Active', 'wpcalibrate-shipment-tracker'); ?></span>
						<span class="wpc-badge-version">v<?php echo esc_html(defined('WPC_SHIPMENT_TRACKER_VERSION') ? WPC_SHIPMENT_TRACKER_VERSION : '1.0.0'); ?></span>
					</div>
					<h2><?php esc_html_e('Shipment & Order Tracker', 'wpcalibrate-shipment-tracker'); ?></h2>
					<p><?php esc_html_e('Enterprise shipment tracking with partial fulfillment, multi-carrier support, customer timelines, guest tracking portal, and transactional notifications.', 'wpcalibrate-shipment-tracker'); ?></p>
					
					<ul class="wpc-feature-bullets">
						<li>✓ <?php esc_html_e('Full HPOS and legacy orders compatibility', 'wpcalibrate-shipment-tracker'); ?></li>
						<li>✓ <?php esc_html_e('Multi-carrier tracking (UPS, FedEx, USPS, DHL, Royal Mail & custom couriers)', 'wpcalibrate-shipment-tracker'); ?></li>
						<li>✓ <?php esc_html_e('Partial order fulfillment and item allocation engine', 'wpcalibrate-shipment-tracker'); ?></li>
						<li>✓ <?php esc_html_e('Public rate-limited guest tracking shortcode', 'wpcalibrate-shipment-tracker'); ?></li>
					</ul>

					<div class="wpc-card-actions">
						<a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-hero">
							<?php esc_html_e('Configure Shipment Tracker', 'wpcalibrate-shipment-tracker'); ?>
						</a>
						<a href="<?php echo esc_url($orders_url); ?>" class="button button-secondary">
							<?php esc_html_e('View WooCommerce Orders', 'wpcalibrate-shipment-tracker'); ?>
						</a>
					</div>
				</div>

				<!-- Resources & Ecosystem Card -->
				<div class="wpc-dashboard-card">
					<h2><?php esc_html_e('WPCalibrate Resources & Ecosystem', 'wpcalibrate-shipment-tracker'); ?></h2>
					<p><?php esc_html_e('Explore documentation, additional plugins, professional services, and get dedicated support for your store.', 'wpcalibrate-shipment-tracker'); ?></p>
					
					<div class="wpc-resources-list">
						<div class="wpc-resource-item">
							<strong><?php esc_html_e('Marketplace', 'wpcalibrate-shipment-tracker'); ?>:</strong>
							<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">
								https://marketplace.wpcalibrate.com/
							</a>
						</div>
						<div class="wpc-resource-item">
							<strong><?php esc_html_e('Official Website', 'wpcalibrate-shipment-tracker'); ?>:</strong>
							<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">
								https://wpcalibrate.com
							</a>
						</div>
						<div class="wpc-resource-item">
							<strong><?php esc_html_e('Direct Support Email', 'wpcalibrate-shipment-tracker'); ?>:</strong>
							<a href="mailto:support@wpcalibrate.com">
								support@wpcalibrate.com
							</a>
						</div>
						<div class="wpc-resource-item">
							<strong><?php esc_html_e('Phone / WhatsApp', 'wpcalibrate-shipment-tracker'); ?>:</strong>
							<span>+44 7474 795976</span>
						</div>
					</div>

					<div class="wpc-card-footer-note">
						<p><?php esc_html_e('Need custom carrier integration or specialized workflow automation? Contact the WPCalibrate engineering team.', 'wpcalibrate-shipment-tracker'); ?></p>
					</div>
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
