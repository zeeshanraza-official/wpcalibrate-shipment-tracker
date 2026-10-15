<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Emails;

use WC_Email;
use WC_Order;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

/**
 * Dedicated WooCommerce Customer Email for Shipment Updates.
 */
class WPC_Shipment_Updated_Email extends WC_Email {
	/**
	 * Shipment ID.
	 *
	 * @var int
	 */
	public int $shipment_id = 0;

	/**
	 * Shipment data.
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $shipment = null;

	/**
	 * Assigned items.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $shipment_items = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'wpc_shipment_updated';
		$this->customer_email = true;
		$this->title          = __('Shipment Update (WPCalibrate)', 'wpcalibrate-shipment-tracker');
		$this->description    = __('Customer notification sent when a shipment is created or updated.', 'wpcalibrate-shipment-tracker');
		$this->template_html  = 'emails/customer-shipment-updated.php';
		$this->template_plain = 'emails/plain/customer-shipment-updated.php';
		$this->template_base  = WPC_SHIPMENT_TRACKER_PATH . 'templates/';

		// Default subject and heading.
		$this->subject = __('Your order #{order_number} has a shipment update', 'wpcalibrate-shipment-tracker');
		$this->heading = __('Shipment Update for Order #{order_number}', 'wpcalibrate-shipment-tracker');

		// Call parent constructor to setup form fields and settings.
		parent::__construct();
	}

	/**
	 * Trigger sending the email.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @return bool True if sent, false otherwise.
	 */
	public function trigger(int $shipment_id): bool {
		$this->setup_locale();

		$this->shipment_id = absint($shipment_id);
		$shipment_repo = new ShipmentRepository();
		$this->shipment = $shipment_repo->get_by_id($this->shipment_id);

		if (!$this->shipment) {
			$this->restore_locale();
			return false;
		}

		$order_id = (int) $this->shipment['order_id'];
		$this->object = wc_get_order($order_id);

		if (!$this->object instanceof WC_Order) {
			$this->restore_locale();
			return false;
		}

		$item_repo = new ShipmentItemRepository();
		$this->shipment_items = $item_repo->get_by_shipment_id($this->shipment_id);

		$this->recipient = $this->object->get_billing_email();

		// Replace placeholders in subject and heading.
		$this->placeholders['{order_number}'] = $this->object->get_order_number();
		$this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
		$this->placeholders['{carrier_name}'] = $this->shipment['carrier_name'];
		$this->placeholders['{tracking_number}'] = $this->shipment['tracking_number'];

		if (!$this->is_enabled() || !$this->get_recipient()) {
			$this->restore_locale();
			return false;
		}

		$sent = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		$this->restore_locale();

		return (bool) $sent;
	}

	/**
	 * Get HTML content.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			[
				'order'          => $this->object,
				'shipment'       => $this->shipment,
				'shipment_items' => $this->shipment_items,
				'email_heading'  => $this->get_heading(),
				'sent_to_admin'  => false,
				'plain_text'     => false,
				'email'          => $this,
			],
			'',
			$this->template_base
		);
	}

	/**
	 * Get plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			[
				'order'          => $this->object,
				'shipment'       => $this->shipment,
				'shipment_items' => $this->shipment_items,
				'email_heading'  => $this->get_heading(),
				'sent_to_admin'  => false,
				'plain_text'     => true,
				'email'          => $this,
			],
			'',
			$this->template_base
		);
	}

	/**
	 * Initialize settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		$this->form_fields = [
			'enabled' => [
				'title'   => __('Enable/Disable', 'wpcalibrate-shipment-tracker'),
				'type'    => 'checkbox',
				'label'   => __('Enable this email notification', 'wpcalibrate-shipment-tracker'),
				'default' => 'yes',
			],
			'subject' => [
				'title'       => __('Subject', 'wpcalibrate-shipment-tracker'),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __('Available placeholders: {site_title}, {order_number}, {order_date}, {carrier_name}, {tracking_number}', 'wpcalibrate-shipment-tracker'),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			],
			'heading' => [
				'title'       => __('Email heading', 'wpcalibrate-shipment-tracker'),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __('Available placeholders: {site_title}, {order_number}, {order_date}, {carrier_name}, {tracking_number}', 'wpcalibrate-shipment-tracker'),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			],
			'email_type' => [
				'title'       => __('Email type', 'wpcalibrate-shipment-tracker'),
				'type'        => 'select',
				'description' => __('Choose which format of email to send.', 'wpcalibrate-shipment-tracker'),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			],
		];
	}
}
