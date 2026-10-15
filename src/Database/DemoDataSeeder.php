<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Database;

defined('ABSPATH') || exit;

/**
 * Handles creation of realistic demo products for development, testing, and store preview.
 */
class DemoDataSeeder {
	/**
	 * List of demo products to seed.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_demo_products_definitions(): array {
		return [
			[
				'name'              => 'Wireless Noise-Cancelling Headphones',
				'sku'               => 'DEMO-WNC-01',
				'price'             => '149.99',
				'stock'             => 25,
				'short_description' => 'Premium over-ear wireless headphones with active noise cancellation and 30-hour battery life.',
				'description'       => 'Experience crystal-clear audio with custom 40mm dynamic drivers, hybrid active noise cancellation, ambient sound mode, and ultra-comfortable memory foam ear cushions. Perfect for travel, home office, and daily commuting.',
				'category'          => 'Audio & Electronics',
			],
			[
				'name'              => 'Ergonomic Mechanical Keyboard',
				'sku'               => 'DEMO-EMK-02',
				'price'             => '99.50',
				'stock'             => 40,
				'short_description' => 'Tactile mechanical switches, customizable per-key RGB backlighting, and braided USB-C cable.',
				'description'       => 'Built with durable aluminum alloy casing and pre-lubricated mechanical tactile switches. Features full N-key rollover, sound-dampening silicone pads, and hot-swappable key switches for the ultimate typing and gaming experience.',
				'category'          => 'Computer Accessories',
			],
			[
				'name'              => 'Ultra-Fast USB-C Braided Cable 2M',
				'sku'               => 'DEMO-UBC-03',
				'price'             => '19.99',
				'stock'             => 150,
				'short_description' => 'Heavy-duty 100W Power Delivery USB-C to USB-C charging and high-speed sync cable.',
				'description'       => 'Engineered with double-braided ballistic nylon and reinforced aluminum connectors. Supports up to 100W (20V/5A) Power Delivery charging and 480Mbps data transfer speeds. Tested to withstand over 25,000 bend cycles.',
				'category'          => 'Cables & Adapters',
			],
			[
				'name'              => 'Smart Fitness Activity Tracker',
				'sku'               => 'DEMO-SAT-04',
				'price'             => '79.00',
				'stock'             => 30,
				'short_description' => 'Waterproof smartwatch with 24/7 heart rate monitoring, sleep tracking, and GPS.',
				'description'       => 'Track your daily wellness metrics including steps, heart rate, blood oxygen saturation (SpO2), stress levels, and sleep stages. Features a vibrant 1.4-inch AMOLED display with 5ATM water resistance and 14-day battery life.',
				'category'          => 'Wearables',
			],
			[
				'name'              => 'Minimalist Leather Laptop Sleeve',
				'sku'               => 'DEMO-MLS-05',
				'price'             => '45.00',
				'stock'             => 50,
				'short_description' => 'Slim protective vegan leather sleeve tailored for 13-15 inch laptops and ultrabooks.',
				'description'       => 'Crafted with premium water-resistant vegan leather and lined with soft scratch-free microfiber. Features a secure magnetic closure and an integrated rear slip pocket for chargers, cables, and notebooks.',
				'category'          => 'Bags & Sleeves',
			],
		];
	}

	/**
	 * Seeds the 5 demo products into WooCommerce.
	 *
	 * @return array{created: int, skipped: int, errors: array<string>}
	 */
	public static function seed(): array {
		if (!class_exists('WC_Product_Simple')) {
			return [
				'created' => 0,
				'skipped' => 0,
				'errors'  => ['WooCommerce is not active or WC_Product_Simple class is missing.'],
			];
		}

		$created = 0;
		$skipped = 0;
		$errors  = [];
		$products = self::get_demo_products_definitions();

		foreach ($products as $def) {
			try {
				// Check if product with this SKU already exists.
				$existing_id = wc_get_product_id_by_sku($def['sku']);
				if ($existing_id > 0) {
					$skipped++;
					continue;
				}

				$product = new \WC_Product_Simple();
				$product->set_name($def['name']);
				$product->set_regular_price($def['price']);
				$product->set_sku($def['sku']);
				$product->set_manage_stock(true);
				$product->set_stock_quantity((int) $def['stock']);
				$product->set_stock_status('instock');
				$product->set_short_description($def['short_description']);
				$product->set_description($def['description']);
				$product->set_status('publish');
				$product->set_catalog_visibility('visible');

				// Assign category if term exists or create term.
				if (!empty($def['category'])) {
					$term = get_term_by('name', $def['category'], 'product_cat');
					if (!$term) {
						$new_term = wp_insert_term($def['category'], 'product_cat');
						if (!is_wp_error($new_term)) {
							$product->set_category_ids([(int) $new_term['term_id']]);
						}
					} else {
						$product->set_category_ids([(int) $term->term_id]);
					}
				}

				$product_id = $product->save();
				if ($product_id) {
					$created++;
				} else {
					$errors[] = sprintf('Failed to save product %s (%s).', $def['name'], $def['sku']);
				}
			} catch (\Throwable $e) {
				$errors[] = sprintf('Error creating %s: %s', $def['sku'], $e->getMessage());
			}
		}

		return [
			'created' => $created,
			'skipped' => $skipped,
			'errors'  => $errors,
		];
	}
}
