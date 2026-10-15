<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Tracking;

use WC_Order;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentEventRepository;
use WPCalibrate\ShipmentTracker\Shipments\AllocationManager;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Handles the public guest tracking page and shortcode [wpcalibrate_shipment_tracking].
 */
class TrackingPageHandler {
	public const SHORTCODE = 'wpcalibrate_shipment_tracking';

	private ShipmentRepository $shipment_repo;
	private ShipmentItemRepository $item_repo;
	private ShipmentEventRepository $event_repo;
	private AllocationManager $allocation_manager;
	private RateLimiter $rate_limiter;
	private ?SettingsManager $settings_manager;

	/**
	 * Constructor.
	 */
	public function __construct(?SettingsManager $settings_manager = null) {
		$this->settings_manager = $settings_manager;
		$this->shipment_repo = new ShipmentRepository();
		$this->item_repo = new ShipmentItemRepository();
		$this->event_repo = new ShipmentEventRepository();
		$this->allocation_manager = new AllocationManager($this->item_repo);
		$this->rate_limiter = new RateLimiter();
	}

	/**
	 * Register shortcode and hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);
	}

	/**
	 * Render the tracking page shortcode.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string Rendered HTML output.
	 */
	public function render_shortcode($atts): string {
		ob_start();

		$error_message = '';
		$found_order = null;
		$submitted_order_ref = '';
		$submitted_email = '';

		// Check if form was submitted via POST.
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpc_st_track_submit'])) {
			$nonce = isset($_POST['wpc_st_track_nonce']) ? sanitize_text_field(wp_unslash($_POST['wpc_st_track_nonce'])) : '';
			if (!wp_verify_nonce($nonce, 'wpc_st_guest_track')) {
				$error_message = __('Your session expired or invalid request. Please try again.', 'wpcalibrate-shipment-tracker');
			} else {
				$submitted_order_ref = isset($_POST['order_ref']) ? sanitize_text_field(wp_unslash($_POST['order_ref'])) : '';
				$submitted_email     = isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : '';

				if (empty($submitted_order_ref) || empty($submitted_email)) {
					$error_message = __('Please provide both an order reference and billing email.', 'wpcalibrate-shipment-tracker');
				} elseif ($this->rate_limiter->is_rate_limited($submitted_order_ref, $submitted_email)) {
					$error_message = __('Too many failed tracking attempts. Please wait 15 minutes before trying again.', 'wpcalibrate-shipment-tracker');
				} else {
					$found_order = $this->verify_and_lookup_order($submitted_order_ref, $submitted_email);

					if (!$found_order instanceof WC_Order) {
						$this->rate_limiter->record_failure($submitted_order_ref, $submitted_email);
						// Generic message to prevent order enumeration.
						$error_message = __('No shipment found matching the provided order reference and email address.', 'wpcalibrate-shipment-tracker');
					} else {
						$this->rate_limiter->reset($submitted_order_ref, $submitted_email);
					}
				}
			}
		}

		// If user is logged in and passed an order_id in GET query param, allow direct lookup if they own it.
		if ($found_order === null && is_user_logged_in() && !empty($_GET['order_id'])) {
			$query_order_id = absint($_GET['order_id']);
			$candidate_order = wc_get_order($query_order_id);
			if ($candidate_order instanceof WC_Order && (int) $candidate_order->get_customer_id() === get_current_user_id()) {
				$found_order = $candidate_order;
			}
		}

		echo '<div class="wpc-st-public-tracking-wrap">';

		if (!empty($error_message)) {
			echo '<div class="woocommerce-error wpc-st-alert-error" role="alert">' . esc_html($error_message) . '</div>';
		}

		if ($found_order instanceof WC_Order) {
			$this->render_tracking_results($found_order);
		} else {
			$this->render_tracking_form($submitted_order_ref, $submitted_email);
		}

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Securely verify and lookup order by order reference and billing email.
	 *
	 * @param string $order_ref Order reference (ID or order number).
	 * @param string $billing_email Submitted billing email.
	 * @return WC_Order|null
	 */
	public function verify_and_lookup_order(string $order_ref, string $billing_email): ?WC_Order {
		$clean_ref = trim($order_ref);
		$clean_email = strtolower(trim($billing_email));

		if (empty($clean_ref) || empty($clean_email)) {
			return null;
		}

		$order = null;

		// 1. Try numeric order ID.
		if (is_numeric($clean_ref)) {
			$candidate = wc_get_order((int) $clean_ref);
			if ($candidate instanceof WC_Order) {
				$order = $candidate;
			}
		}

		// 2. Try looking up by custom order number if different from numeric ID.
		if (!$order instanceof WC_Order) {
			$orders = wc_get_orders([
				'limit' => 1,
				'meta_key' => '_order_number',
				'meta_value' => $clean_ref,
			]);

			if (!empty($orders[0]) && $orders[0] instanceof WC_Order) {
				$order = $orders[0];
			}
		}

		// If still not found, try order_number query.
		if (!$order instanceof WC_Order && function_exists('wc_get_order_id_by_order_key')) {
			$order_id = wc_get_order_id_by_order_key($clean_ref);
			if ($order_id) {
				$candidate = wc_get_order($order_id);
				if ($candidate instanceof WC_Order) {
					$order = $candidate;
				}
			}
		}

		if (!$order instanceof WC_Order) {
			return null;
		}

		// Verify billing email in constant time to prevent timing attacks.
		$order_email = strtolower(trim((string) $order->get_billing_email()));
		if (!hash_equals($order_email, $clean_email)) {
			return null;
		}

		/**
		 * Filter the verified order for guest tracking.
		 *
		 * @param WC_Order|null $order Verified order or null.
		 * @param string $clean_ref Submitted order reference.
		 * @param string $clean_email Submitted email.
		 */
		return apply_filters('wpcalibrate_shipment_tracker_guest_lookup_verification', $order, $clean_ref, $clean_email);
	}

	/**
	 * Render the guest lookup form.
	 *
	 * @param string $order_ref Submitted order reference.
	 * @param string $email Submitted email.
	 * @return void
	 */
	private function render_tracking_form(string $order_ref, string $email): void {
		$template_path = WPC_SHIPMENT_TRACKER_PATH . 'templates/customer/public-tracking-form.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}

	/**
	 * Render the tracking results after successful verification.
	 *
	 * @param WC_Order $order Verified order object.
	 * @return void
	 */
	private function render_tracking_results(WC_Order $order): void {
		$order_id = $order->get_id();
		$shipments = $this->shipment_repo->get_by_order_id($order_id);

		$shipment_ids = array_map(static fn($s) => (int) $s['id'], $shipments);
		$shipment_items_map = $this->item_repo->get_by_shipment_ids($shipment_ids);
		$shipment_events_map = $this->event_repo->get_by_shipment_ids($shipment_ids);
		$allocation_status = $this->allocation_manager->get_order_items_allocation_status($order);

		$template_path = WPC_SHIPMENT_TRACKER_PATH . 'templates/customer/public-tracking-results.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}
}
