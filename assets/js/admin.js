/**
 * WPCalibrate Shipment Tracker - Admin Scripts
 * Handles modal dialog, allocation calculator, AJAX CRUD operations, and keyboard accessibility.
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		var $metaBox = $('.wpc-st-meta-box-wrap');
		if (!$metaBox.length) {
			return;
		}

		var orderId = $metaBox.data('order-id');
		var $modal = $('#wpc-st-modal-backdrop');
		var $form = $('#wpc-st-shipment-form');
		var $alert = $('#wpc-st-form-alert');
		var $modalTitle = $('#wpc-st-modal-title');
		var $carrierSelect = $('#wpc-st-field-carrier');
		var $customCarrierWrap = $('#wpc-st-custom-carrier-name-wrap');
		var $itemsTbody = $('#wpc-st-modal-items-tbody');
		var params = window.wpc_st_admin_params || {};

		// Helper to open modal
		function openModal(title) {
			$modalTitle.text(title || params.i18n.add_title);
			$alert.hide().empty();
			$modal.show().attr('aria-hidden', 'false');
			$carrierSelect.focus();
			$(document).on('keydown.wpcst', handleKeydown);
		}

		// Helper to close modal
		function closeModal() {
			$modal.hide().attr('aria-hidden', 'true');
			$form[0].reset();
			$('#wpc-st-form-shipment-id').val('0');
			$alert.hide().empty();
			$(document).off('keydown.wpcst');
		}

		// Close modal on ESC key
		function handleKeydown(e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				closeModal();
			}
		}

		// Open Modal on "Add Shipment" button
		$('#wpc-st-add-shipment-btn').on('click', function(e) {
			e.preventDefault();
			$form[0].reset();
			$('#wpc-st-form-shipment-id').val('0');
			$customCarrierWrap.hide();

			// Fetch fresh allocations
			$.post(params.ajax_url, {
				action: 'wpc_st_get_allocations',
				security: params.security,
				order_id: orderId
			}, function(response) {
				if (response.success && response.data.allocations) {
					updateModalItems(response.data.allocations, {});
				}
				openModal(params.i18n.add_title);
			});
		});

		// Close modal button & backdrop
		$('#wpc-st-modal-close-btn, #wpc-st-btn-cancel-modal').on('click', function(e) {
			e.preventDefault();
			closeModal();
		});

		$modal.on('click', function(e) {
			if ($(e.target).is('#wpc-st-modal-backdrop')) {
				closeModal();
			}
		});

		// Carrier select change
		$carrierSelect.on('change', function() {
			var val = $(this).val();
			if (val === 'custom') {
				$customCarrierWrap.show();
			} else {
				$customCarrierWrap.hide();
			}
		});

		// Edit Shipment handler
		$metaBox.on('click', '.wpc-st-btn-edit', function(e) {
			e.preventDefault();
			var shipmentId = $(this).data('id');

			$.post(params.ajax_url, {
				action: 'wpc_st_get_shipment',
				security: params.security,
				shipment_id: shipmentId
			}, function(response) {
				if (!response.success || !response.data.shipment) {
					alert(response.data.message || 'Error loading shipment details.');
					return;
				}

				var s = response.data.shipment;
				$('#wpc-st-form-shipment-id').val(s.id);
				$carrierSelect.val(s.carrier_key).trigger('change');
				$('#wpc-st-field-carrier-name').val(s.carrier_name);
				$('#wpc-st-field-tracking-number').val(s.tracking_number);
				$('#wpc-st-field-tracking-url').val(s.tracking_url);
				$('#wpc-st-field-status').val(s.status);
				$('#wpc-st-field-shipped-at').val(s.shipped_at ? s.shipped_at.replace(' ', 'T') : '');
				$('#wpc-st-field-est-delivery').val(s.estimated_delivery_at || '');
				$('#wpc-st-field-delivered-at').val(s.delivered_at ? s.delivered_at.replace(' ', 'T') : '');
				$('#wpc-st-field-customer-note').val(s.customer_note || '');
				$('#wpc-st-field-internal-note').val(s.internal_note || '');

				var assignedItems = {};
				if (s.items && s.items.length) {
					$.each(s.items, function(i, itm) {
						assignedItems[itm.order_item_id] = itm.quantity;
					});
				}

				// Fetch allocations excluding this shipment
				$.post(params.ajax_url, {
					action: 'wpc_st_get_allocations',
					security: params.security,
					order_id: orderId,
					exclude_shipment_id: s.id
				}, function(allocResponse) {
					if (allocResponse.success && allocResponse.data.allocations) {
						updateModalItems(allocResponse.data.allocations, assignedItems);
					}
					openModal(params.i18n.edit_title);
				});
			});
		});

		// Update items table inside modal
		function updateModalItems(allocations, assignedMap) {
			$itemsTbody.empty();

			$.each(allocations, function(itemId, info) {
				var assignedQty = assignedMap[itemId] || 0;
				var maxAvail = parseFloat(info.available_qty) + parseFloat(assignedQty);

				var rowHtml = '<tr data-item-id="' + itemId + '" data-available="' + maxAvail + '">' +
					'<td><strong>' + escapeHtml(info.name) + '</strong>' +
					(info.sku ? ' <span class="wpc-st-sku">(' + escapeHtml(info.sku) + ')</span>' : '') +
					'</td>' +
					'<td class="wpc-st-col-qty">' + info.ordered_qty + '</td>' +
					'<td class="wpc-st-col-qty"><span class="wpc-st-avail-badge">' + maxAvail.toFixed(2).replace(/\.?0+$/, '') + '</span></td>' +
					'<td class="wpc-st-col-action">' +
						'<div class="wpc-st-qty-input-group">' +
							'<input type="number" name="items[' + itemId + ']" class="wpc-st-input-qty" min="0" max="' + maxAvail + '" step="any" value="' + (assignedQty > 0 ? assignedQty : 0) + '">' +
							'<button type="button" class="button button-small wpc-st-btn-fill-single" data-item-id="' + itemId + '">Max</button>' +
						'</div>' +
					'</td>' +
				'</tr>';

				$itemsTbody.append(rowHtml);
			});
		}

		// Fill Max for single item
		$itemsTbody.on('click', '.wpc-st-btn-fill-single', function(e) {
			e.preventDefault();
			var $tr = $(this).closest('tr');
			var avail = parseFloat($tr.data('available')) || 0;
			$tr.find('.wpc-st-input-qty').val(avail);
		});

		// Fill All Available button
		$('#wpc-st-btn-fill-all').on('click', function(e) {
			e.preventDefault();
			$itemsTbody.find('tr').each(function() {
				var avail = parseFloat($(this).data('available')) || 0;
				$(this).find('.wpc-st-input-qty').val(avail);
			});
		});

		// Delete Shipment handler
		$metaBox.on('click', '.wpc-st-btn-delete', function(e) {
			e.preventDefault();
			var shipmentId = $(this).data('id');

			if (!confirm(params.i18n.confirm_delete)) {
				return;
			}

			$.post(params.ajax_url, {
				action: 'wpc_st_delete_shipment',
				security: params.security,
				shipment_id: shipmentId
			}, function(response) {
				if (response.success) {
					window.location.reload();
				} else {
					alert(response.data.message || params.i18n.delete_failed);
				}
			});
		});

		// Submit shipment form
		$form.on('submit', function(e) {
			e.preventDefault();

			var $submitBtn = $('#wpc-st-btn-save-modal');
			var $spinner = $submitBtn.find('.spinner');

			// Basic client validation
			var trackingNum = $.trim($('#wpc-st-field-tracking-number').val());
			if (!trackingNum) {
				$alert.text('Please enter a tracking number.').show();
				$('#wpc-st-field-tracking-number').focus();
				return;
			}

			var hasQty = false;
			$itemsTbody.find('.wpc-st-input-qty').each(function() {
				if (parseFloat($(this).val()) > 0) {
					hasQty = true;
				}
			});

			if (!hasQty) {
				$alert.text('Please allocate at least one product quantity to this shipment.').show();
				return;
			}

			$submitBtn.prop('disabled', true);
			$spinner.addClass('is-active');
			$alert.hide().empty();

			var formData = $form.serialize();

			$.post(params.ajax_url, formData, function(response) {
				$submitBtn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					closeModal();
					window.location.reload();
				} else {
					$alert.text(response.data.message || params.i18n.save_failed).show();
				}
			}).fail(function(xhr) {
				$submitBtn.prop('disabled', false);
				$spinner.removeClass('is-active');
				var msg = params.i18n.save_failed;
				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					msg = xhr.responseJSON.data.message;
				}
				$alert.text(msg).show();
			});
		});

		function escapeHtml(text) {
			return $('<div>').text(text).html();
		}
	});
})(jQuery);
