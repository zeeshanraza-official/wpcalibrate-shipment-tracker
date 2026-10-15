<?php
/**
 * Public Guest Tracking Form Template.
 *
 * Variables available:
 * @var string $order_ref
 * @var string $email
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?>

<div class="wpc-st-form-card">
	<div class="wpc-st-form-header">
		<h2 class="wpc-st-form-title"><?php esc_html_e('Track Your Shipment', 'wpcalibrate-shipment-tracker'); ?></h2>
		<p class="wpc-st-form-desc">
			<?php esc_html_e('To track the progress of your order, please enter your Order Number and the Billing Email address used during checkout.', 'wpcalibrate-shipment-tracker'); ?>
		</p>
	</div>

	<form method="post" class="wpc-st-tracking-form" action="" novalidate>
		<?php wp_nonce_field('wpc_st_guest_track', 'wpc_st_track_nonce'); ?>

		<div class="wpc-st-form-group">
			<label for="wpc-st-order-ref" class="wpc-st-form-label">
				<?php esc_html_e('Order Number or Reference', 'wpcalibrate-shipment-tracker'); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="text" 
				name="order_ref" 
				id="wpc-st-order-ref" 
				class="input-text wpc-st-form-control" 
				value="<?php echo esc_attr($order_ref); ?>" 
				placeholder="<?php esc_attr_e('e.g. 1042', 'wpcalibrate-shipment-tracker'); ?>" 
				required 
				aria-required="true">
			<span class="wpc-st-input-hint">
				<?php esc_html_e('Found in your order confirmation email receipt.', 'wpcalibrate-shipment-tracker'); ?>
			</span>
		</div>

		<div class="wpc-st-form-group">
			<label for="wpc-st-billing-email" class="wpc-st-form-label">
				<?php esc_html_e('Billing Email Address', 'wpcalibrate-shipment-tracker'); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<input type="email" 
				name="billing_email" 
				id="wpc-st-billing-email" 
				class="input-text wpc-st-form-control" 
				value="<?php echo esc_attr($email); ?>" 
				placeholder="<?php esc_attr_e('name@example.com', 'wpcalibrate-shipment-tracker'); ?>" 
				required 
				aria-required="true">
			<span class="wpc-st-input-hint">
				<?php esc_html_e('The email address entered on the checkout billing details.', 'wpcalibrate-shipment-tracker'); ?>
			</span>
		</div>

		<div class="wpc-st-form-submit">
			<button type="submit" name="wpc_st_track_submit" value="1" class="button button-primary wpc-st-btn-track">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<?php esc_html_e('Track Shipment', 'wpcalibrate-shipment-tracker'); ?>
			</button>
		</div>
	</form>
</div>
