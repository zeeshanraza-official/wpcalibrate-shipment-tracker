<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker;

defined('ABSPATH') || exit;

/**
 * Autoloader for WPCalibrate Shipment Tracker classes.
 * Maps the namespace WPCalibrate\ShipmentTracker\ to the src/ directory (PSR-4 compliant).
 */
final class Autoloader {
	/**
	 * Prefix to register.
	 */
	private const NAMESPACE_PREFIX = 'WPCalibrate\\ShipmentTracker\\';

	/**
	 * Base directory for the namespace prefix.
	 *
	 * @var string
	 */
	private static string $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Base directory path (points to src/).
	 * @return void
	 */
	public static function register(string $base_dir): void {
		self::$base_dir = rtrim($base_dir, '/\\') . DIRECTORY_SEPARATOR;
		spl_autoload_register([self::class, 'autoload']);
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return void
	 */
	public static function autoload(string $class): void {
		// Does the class use the namespace prefix?
		$len = strlen(self::NAMESPACE_PREFIX);
		if (strncmp(self::NAMESPACE_PREFIX, $class, $len) !== 0) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr($class, $len);

		// Replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append with .php.
		$file = self::$base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

		// If the file exists, require it.
		if (file_exists($file)) {
			require_once $file;
		}
	}
}
