<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Licensing;

defined('ABSPATH') || exit;

/**
 * Value object representing the outcome of a licensing operation.
 */
class LicenseResult {
	private bool $success;
	private string $status;
	private string $message;
	private ?string $expiry;
	private ?string $renewal_url;

	/**
	 * Constructor.
	 *
	 * @param bool $success Operation success flag.
	 * @param string $status Machine-readable status code ('not_configured', 'active', 'invalid', 'expired').
	 * @param string $message User-facing message.
	 * @param string|null $expiry Expiry date string if applicable.
	 * @param string|null $renewal_url Renewal URL if provided.
	 */
	public function __construct(
		bool $success,
		string $status,
		string $message,
		?string $expiry = null,
		?string $renewal_url = null
	) {
		$this->success = $success;
		$this->status = $status;
		$this->message = $message;
		$this->expiry = $expiry;
		$this->renewal_url = $renewal_url;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_expiry(): ?string {
		return $this->expiry;
	}

	public function get_renewal_url(): ?string {
		return $this->renewal_url;
	}
}
