<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\ShipmentTracker\Tracking\TrackingPageHandler;

defined('ABSPATH') || exit;

/**
 * Manages conditional asset enqueuing for admin and frontend views.
 */
class AssetManager {
	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
	}

	/**
	 * Conditionally enqueue admin stylesheets and scripts.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public function enqueue_admin_assets(string $hook_suffix): void {
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		$screen_id = $screen ? (string) $screen->id : '';

		$is_suite_screen = str_contains($hook_suffix, MenuManager::SUBMENU_SLUG) 
			|| str_contains($hook_suffix, MenuManager::PARENT_SLUG)
			|| str_contains($hook_suffix, 'wpcalibrate')
			|| str_contains($screen_id, 'wpcalibrate');

		$is_order_screen = false;
		if ($screen) {
			if ($screen->id === 'shop_order') {
				$is_order_screen = true;
			} elseif (class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled()) {
				if ($screen->id === wc_get_page_screen_id('shop-order')) {
					$is_order_screen = true;
				}
			}
		}

		if (!$is_suite_screen && !$is_order_screen) {
			return;
		}

		// Cache-busting asset version based on file modification timestamp to bypass browser caching.
		$css_file = defined('WPC_SHIPMENT_TRACKER_PATH') ? WPC_SHIPMENT_TRACKER_PATH . 'assets/css/admin.css' : '';
		$css_version = (!empty($css_file) && file_exists($css_file)) ? (string) filemtime($css_file) : WPC_SHIPMENT_TRACKER_VERSION;

		$js_file = defined('WPC_SHIPMENT_TRACKER_PATH') ? WPC_SHIPMENT_TRACKER_PATH . 'assets/js/admin.js' : '';
		$js_version = (!empty($js_file) && file_exists($js_file)) ? (string) filemtime($js_file) : WPC_SHIPMENT_TRACKER_VERSION;

		// Enqueue admin CSS.
		wp_enqueue_style(
			'wpcalibrate-shipment-tracker-admin',
			WPC_SHIPMENT_TRACKER_URL . 'assets/css/admin.css',
			[],
			$css_version
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'wpcalibrate-shipment-tracker-admin',
			WPC_SHIPMENT_TRACKER_URL . 'assets/js/admin.js',
			['jquery'],
			$js_version,
			true
		);

		// Localize script data.
		wp_localize_script(
			'wpcalibrate-shipment-tracker-admin',
			'wpc_st_admin_params',
			[
				'ajax_url' => admin_url('admin-ajax.php'),
				'security' => wp_create_nonce('wpcalibrate_shipment_tracker_admin'),
				'i18n'     => [
					'confirm_delete' => __('Are you sure you want to delete this shipment? Allocated items will be released back to the order.', 'wpcalibrate-shipment-tracker'),
					'delete_failed'  => __('Failed to delete shipment.', 'wpcalibrate-shipment-tracker'),
					'save_failed'    => __('Failed to save shipment.', 'wpcalibrate-shipment-tracker'),
					'edit_title'     => __('Edit Shipment', 'wpcalibrate-shipment-tracker'),
					'add_title'      => __('Add New Shipment', 'wpcalibrate-shipment-tracker'),
					'loading'        => __('Loading...', 'wpcalibrate-shipment-tracker'),
				],
			]
		);
	}

	/**
	 * Conditionally enqueue frontend stylesheets and scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		global $post;

		$is_account = function_exists('is_account_page') && is_account_page();
		$has_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, TrackingPageHandler::SHORTCODE);

		if (!$is_account && !$has_shortcode) {
			return;
		}

		wp_enqueue_style(
			'wpcalibrate-shipment-tracker-frontend',
			WPC_SHIPMENT_TRACKER_URL . 'assets/css/frontend.css',
			[],
			WPC_SHIPMENT_TRACKER_VERSION
		);
	}
}
