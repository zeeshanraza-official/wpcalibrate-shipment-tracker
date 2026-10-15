<?php
/**
 * Admin Shipment Modal Form Template.
 *
 * Variables available from parent scope:
 * @var WC_Order $order
 * @var array<int, array<string, mixed>> $allocation_status
 * @var array<string, array<string, mixed>> $carriers
 */

declare(strict_types=1);

use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

$statuses = ShipmentStatus::get_all();
?>

<div class="wpc-st-modal-backdrop" id="wpc-st-modal-backdrop" style="display: none;" aria-hidden="true">
	<div class="wpc-st-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="wpc-st-modal-title">
		<div class="wpc-st-modal-header">
			<h3 class="wpc-st-modal-title" id="wpc-st-modal-title">
				<?php esc_html_e('Add New Shipment', 'wpcalibrate-shipment-tracker'); ?>
			</h3>
			<button type="button" class="wpc-st-modal-close" id="wpc-st-modal-close-btn" aria-label="<?php esc_attr_e('Close dialog', 'wpcalibrate-shipment-tracker'); ?>">
				&times;
			</button>
		</div>

		<form id="wpc-st-shipment-form" novalidate>
			<input type="hidden" name="action" value="wpc_st_save_shipment">
			<input type="hidden" name="order_id" id="wpc-st-form-order-id" value="<?php echo esc_attr((string) $order->get_id()); ?>">
			<input type="hidden" name="shipment_id" id="wpc-st-form-shipment-id" value="0">
			<?php wp_nonce_field('wpcalibrate_shipment_tracker_admin', 'security'); ?>

			<div class="wpc-st-modal-body">
				<!-- Form error message banner -->
				<div class="wpc-st-form-alert" id="wpc-st-form-alert" style="display: none;" role="alert"></div>

				<!-- Section: Carrier & Tracking -->
				<div class="wpc-st-form-row wpc-st-form-row-split">
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-carrier" class="wpc-st-label">
							<?php esc_html_e('Shipping Carrier', 'wpcalibrate-shipment-tracker'); ?> <span class="required">*</span>
						</label>
						<select name="carrier_key" id="wpc-st-field-carrier" class="wpc-st-select" required>
							<?php foreach ($carriers as $key => $c) : ?>
								<option value="<?php echo esc_attr($key); ?>" data-template="<?php echo esc_attr($c['url_template'] ?? ''); ?>">
									<?php echo esc_html($c['name']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="wpc-st-form-col" id="wpc-st-custom-carrier-name-wrap" style="display: none;">
						<label for="wpc-st-field-carrier-name" class="wpc-st-label">
							<?php esc_html_e('Custom Carrier Name', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<input type="text" name="carrier_name" id="wpc-st-field-carrier-name" class="wpc-st-input" placeholder="<?php esc_attr_e('e.g. Local Courier', 'wpcalibrate-shipment-tracker'); ?>">
					</div>
				</div>

				<div class="wpc-st-form-row wpc-st-form-row-split">
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-tracking-number" class="wpc-st-label">
							<?php esc_html_e('Tracking Number', 'wpcalibrate-shipment-tracker'); ?> <span class="required">*</span>
						</label>
						<input type="text" name="tracking_number" id="wpc-st-field-tracking-number" class="wpc-st-input" required placeholder="<?php esc_attr_e('Enter tracking code', 'wpcalibrate-shipment-tracker'); ?>">
					</div>

					<div class="wpc-st-form-col">
						<label for="wpc-st-field-status" class="wpc-st-label">
							<?php esc_html_e('Shipment Status', 'wpcalibrate-shipment-tracker'); ?> <span class="required">*</span>
						</label>
						<select name="status" id="wpc-st-field-status" class="wpc-st-select">
							<?php foreach ($statuses as $val => $lbl) : ?>
								<option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="wpc-st-form-row">
					<label for="wpc-st-field-tracking-url" class="wpc-st-label">
						<?php esc_html_e('Tracking URL (Optional Override)', 'wpcalibrate-shipment-tracker'); ?>
					</label>
					<input type="url" name="tracking_url" id="wpc-st-field-tracking-url" class="wpc-st-input" placeholder="https://example.com/track/...">
					<span class="wpc-st-field-hint">
						<?php esc_html_e('Leave blank to use the carrier default URL template.', 'wpcalibrate-shipment-tracker'); ?>
					</span>
				</div>

				<!-- Section: Dates -->
				<div class="wpc-st-form-row wpc-st-form-row-dates">
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-shipped-at" class="wpc-st-label">
							<?php esc_html_e('Shipped Date / Time', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<input type="datetime-local" name="shipped_at" id="wpc-st-field-shipped-at" class="wpc-st-input">
					</div>
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-est-delivery" class="wpc-st-label">
							<?php esc_html_e('Estimated Delivery', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<input type="date" name="estimated_delivery_at" id="wpc-st-field-est-delivery" class="wpc-st-input">
					</div>
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-delivered-at" class="wpc-st-label">
							<?php esc_html_e('Delivered Date / Time', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<input type="datetime-local" name="delivered_at" id="wpc-st-field-delivered-at" class="wpc-st-input">
					</div>
				</div>

				<!-- Section: Item Allocation -->
				<div class="wpc-st-form-section">
					<div class="wpc-st-section-header-flex">
						<h4 class="wpc-st-section-subheading">
							<?php esc_html_e('Assign Order Items to this Shipment', 'wpcalibrate-shipment-tracker'); ?> <span class="required">*</span>
						</h4>
						<button type="button" class="button button-secondary button-small" id="wpc-st-btn-fill-all">
							<?php esc_html_e('Fill All Available', 'wpcalibrate-shipment-tracker'); ?>
						</button>
					</div>

					<table class="widefat fixed striped wpc-st-modal-items-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Product', 'wpcalibrate-shipment-tracker'); ?></th>
								<th class="wpc-st-col-qty"><?php esc_html_e('Ordered', 'wpcalibrate-shipment-tracker'); ?></th>
								<th class="wpc-st-col-qty"><?php esc_html_e('Available', 'wpcalibrate-shipment-tracker'); ?></th>
								<th class="wpc-st-col-action"><?php esc_html_e('Qty to Ship', 'wpcalibrate-shipment-tracker'); ?></th>
							</tr>
						</thead>
						<tbody id="wpc-st-modal-items-tbody">
							<?php foreach ($allocation_status as $item) : 
								$item_id = $item['item_id'];
								$avail = $item['available_qty'];
							?>
								<tr data-item-id="<?php echo esc_attr((string) $item_id); ?>" data-available="<?php echo esc_attr((string) $avail); ?>">
									<td>
										<strong><?php echo esc_html($item['name']); ?></strong>
										<?php if (!empty($item['sku'])) : ?>
											<span class="wpc-st-sku"><?php echo esc_html('(' . $item['sku'] . ')'); ?></span>
										<?php endif; ?>
									</td>
									<td class="wpc-st-col-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $item['ordered_qty']), '0'), '.')); ?></td>
									<td class="wpc-st-col-qty">
										<span class="wpc-st-avail-badge" id="wpc-st-avail-<?php echo esc_attr((string) $item_id); ?>">
											<?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $avail), '0'), '.')); ?>
										</span>
									</td>
									<td class="wpc-st-col-action">
										<div class="wpc-st-qty-input-group">
											<input type="number" 
												name="items[<?php echo esc_attr((string) $item_id); ?>]" 
												id="wpc-st-item-qty-<?php echo esc_attr((string) $item_id); ?>" 
												class="wpc-st-input-qty" 
												min="0" 
												max="<?php echo esc_attr((string) $avail); ?>" 
												step="any" 
												value="0"
												aria-label="<?php echo esc_attr(sprintf(__('Quantity to ship for %s', 'wpcalibrate-shipment-tracker'), $item['name'])); ?>">
											<button type="button" class="button button-small wpc-st-btn-fill-single" data-item-id="<?php echo esc_attr((string) $item_id); ?>" title="<?php esc_attr_e('Fill all available quantity', 'wpcalibrate-shipment-tracker'); ?>">
												<?php esc_html_e('Max', 'wpcalibrate-shipment-tracker'); ?>
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Section: Notes -->
				<div class="wpc-st-form-row wpc-st-form-row-split">
					<div class="wpc-st-form-col">
						<label for="wpc-st-field-customer-note" class="wpc-st-label">
							<?php esc_html_e('Customer Note (Visible in customer emails & portal)', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<textarea name="customer_note" id="wpc-st-field-customer-note" class="wpc-st-textarea" rows="2" placeholder="<?php esc_attr_e('e.g. Left with neighbor at #14', 'wpcalibrate-shipment-tracker'); ?>"></textarea>
					</div>

					<div class="wpc-st-form-col">
						<label for="wpc-st-field-internal-note" class="wpc-st-label">
							<?php esc_html_e('Internal Note (Private to store managers)', 'wpcalibrate-shipment-tracker'); ?>
						</label>
						<textarea name="internal_note" id="wpc-st-field-internal-note" class="wpc-st-textarea" rows="2" placeholder="<?php esc_attr_e('e.g. Packed by warehouse team B', 'wpcalibrate-shipment-tracker'); ?>"></textarea>
					</div>
				</div>

				<!-- Notification checkbox -->
				<div class="wpc-st-form-row">
					<label class="wpc-st-checkbox-label">
						<input type="checkbox" name="notify_customer" id="wpc-st-field-notify-customer" value="yes" checked>
						<span><?php esc_html_e('Send customer notification email about this shipment update', 'wpcalibrate-shipment-tracker'); ?></span>
					</label>
				</div>
			</div>

			<div class="wpc-st-modal-footer">
				<button type="button" class="button button-secondary" id="wpc-st-btn-cancel-modal">
					<?php esc_html_e('Cancel', 'wpcalibrate-shipment-tracker'); ?>
				</button>
				<button type="submit" class="button button-primary" id="wpc-st-btn-save-modal">
					<span class="wpc-st-btn-text"><?php esc_html_e('Save Shipment', 'wpcalibrate-shipment-tracker'); ?></span>
					<span class="spinner wpc-st-spinner" style="float: none; margin: 0 0 0 5px;" aria-hidden="true"></span>
				</button>
			</div>
		</form>
	</div>
</div>
