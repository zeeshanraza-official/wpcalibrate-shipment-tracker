<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Updates;

defined('ABSPATH') || exit;

/**
 * Handles automated WordPress dashboard plugin update checks and one-click updates
 * directly from GitHub releases without third-party dependencies.
 */
class GitHubUpdater {
	/**
	 * GitHub username or organization name.
	 *
	 * @var string
	 */
	private string $username;

	/**
	 * GitHub repository slug.
	 *
	 * @var string
	 */
	private string $repository;

	/**
	 * Path to the main plugin file.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Installed plugin version.
	 *
	 * @var string
	 */
	private string $current_version;

	/**
	 * Plugin basename (e.g. wpcalibrate-shipment-tracker/wpcalibrate-shipment-tracker.php).
	 *
	 * @var string
	 */
	private string $plugin_basename;

	/**
	 * Plugin slug (e.g. wpcalibrate-shipment-tracker).
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Cache key for GitHub latest release transient.
	 */
	private const TRANSIENT_KEY = 'wpc_st_gh_latest_release';

	/**
	 * Cache lifetime in seconds (6 hours).
	 */
	private const CACHE_TTL = 21600;

	/**
	 * Constructor.
	 *
	 * @param string $username GitHub username.
	 * @param string $repository GitHub repository name.
	 * @param string $plugin_file Absolute path to main plugin file.
	 * @param string $current_version Currently installed version string.
	 */
	public function __construct(string $username, string $repository, string $plugin_file, string $current_version) {
		$this->username        = $username;
		$this->repository      = $repository;
		$this->plugin_file     = $plugin_file;
		$this->current_version = $current_version;
		$this->plugin_basename = plugin_basename($plugin_file);
		$this->plugin_slug     = dirname($this->plugin_basename);
		if ($this->plugin_slug === '.' || empty($this->plugin_slug)) {
			$this->plugin_slug = 'wpcalibrate-shipment-tracker';
		}
	}

	/**
	 * Register updater hooks with WordPress core update transients and upgrader APIs.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
		add_filter('plugins_api', [$this, 'plugin_popup_info'], 20, 3);
		add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);
	}

	/**
	 * Query GitHub Releases API to retrieve latest release metadata.
	 *
	 * @param bool $force_refresh Whether to bypass transient cache.
	 * @return array<string, mixed>|null Release data array or null on failure.
	 */
	public function get_remote_release_info(bool $force_refresh = false): ?array {
		global $pagenow;

		// Force refresh if checking on the core updates page.
		if ($pagenow === 'update-core.php' && !empty($_GET['force-check'])) {
			$force_refresh = true;
		}

		if (!$force_refresh) {
			$cached = get_site_transient(self::TRANSIENT_KEY);
			if (is_array($cached) && !empty($cached['tag_name'])) {
				return $cached;
			}
		}

		$url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository);
		$response = wp_remote_get($url, [
			'timeout'    => 10,
			'headers'    => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/WPCalibrate-Shipment-Tracker-Updater',
			],
		]);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return null;
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (!is_array($data) || empty($data['tag_name'])) {
			return null;
		}

		set_site_transient(self::TRANSIENT_KEY, $data, self::CACHE_TTL);

		return $data;
	}

	/**
	 * Hook into pre_set_site_transient_update_plugins to notify WordPress of available updates.
	 *
	 * @param mixed $transient The update_plugins transient value.
	 * @return mixed Filtered transient with our plugin update if available.
	 */
	public function check_update(mixed $transient): mixed {
		if (!is_object($transient)) {
			$transient = new \stdClass();
		}

		$release = $this->get_remote_release_info();
		if (!$release) {
			return $transient;
		}

		// Normalize tag version (e.g. "v1.0.4" -> "1.0.4").
		$new_version = ltrim((string) $release['tag_name'], 'vV');

		// Compare versions.
		if (version_compare($new_version, $this->current_version, '>')) {
			$download_url = '';

			// First look for packaged release asset ZIP.
			if (!empty($release['assets']) && is_array($release['assets'])) {
				foreach ($release['assets'] as $asset) {
					if (isset($asset['name']) && str_ends_with(strtolower((string) $asset['name']), '.zip')) {
						$download_url = (string) $asset['browser_download_url'];
						break;
					}
				}
			}

			// Fallback to release zipball.
			if (empty($download_url) && !empty($release['zipball_url'])) {
				$download_url = (string) $release['zipball_url'];
			}

			if (!empty($download_url)) {
				$plugin_update = (object) [
					'id'            => 'github.com/' . $this->username . '/' . $this->repository,
					'slug'          => $this->plugin_slug,
					'plugin'        => $this->plugin_basename,
					'new_version'   => $new_version,
					'url'           => 'https://github.com/' . $this->username . '/' . $this->repository,
					'package'       => $download_url,
					'icons'         => [
						'1x' => WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png',
						'2x' => WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png',
					],
					'banners'       => [],
					'banners_rtl'   => [],
					'tested'        => '6.7.2',
					'requires_php'  => '8.2',
					'compatibility' => new \stdClass(),
				];

				if (!isset($transient->response)) {
					$transient->response = [];
				}
				$transient->response[$this->plugin_basename] = $plugin_update;
			}
		} else {
			// Mark as checked with no update available.
			if (!isset($transient->no_update)) {
				$transient->no_update = [];
			}
			$transient->no_update[$this->plugin_basename] = (object) [
				'id'            => 'github.com/' . $this->username . '/' . $this->repository,
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $this->current_version,
				'url'           => 'https://github.com/' . $this->username . '/' . $this->repository,
				'package'       => '',
			];
		}

		return $transient;
	}

	/**
	 * Provide plugin details for the "View version details" thickbox modal.
	 *
	 * @param mixed $result The current API result.
	 * @param string $action The API action being performed.
	 * @param mixed $args Arguments object containing plugin slug.
	 * @return mixed Formatted plugin info object or original result.
	 */
	public function plugin_popup_info(mixed $result, string $action, mixed $args): mixed {
		if ($action !== 'plugin_information' || !is_object($args) || empty($args->slug) || $args->slug !== $this->plugin_slug) {
			return $result;
		}

		$release = $this->get_remote_release_info();
		$version = $release ? ltrim((string) $release['tag_name'], 'vV') : $this->current_version;
		$body    = $release ? (string) ($release['body'] ?? '') : '';

		$info = new \stdClass();
		$info->name          = 'WPCalibrate Shipment Tracker';
		$info->slug          = $this->plugin_slug;
		$info->version       = $version;
		$info->author        = '<a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">WPCalibrate</a>';
		$info->author_profile= 'https://wpcalibrate.com';
		$info->homepage      = 'https://marketplace.wpcalibrate.com/';
		$info->requires      = '6.4';
		$info->tested        = '6.7.2';
		$info->requires_php  = '8.2';
		$info->last_updated  = $release ? (string) ($release['published_at'] ?? date('Y-m-d')) : date('Y-m-d');
		$info->sections      = [
			'description'  => '<p>Enterprise-grade WooCommerce shipment tracking, partial fulfillment, multi-carrier support, and customer timelines.</p>',
			'installation' => '<ol><li>Upload the ZIP file to your WordPress plugins directory.</li><li>Activate via Plugins menu.</li><li>Configure at WPCalibrate > Shipment Tracker.</li></ol>',
			'changelog'    => !empty($body) ? nl2br(esc_html($body)) : '<p>See GitHub Releases for full details.</p>',
		];
		$info->banners       = [
			'low'  => WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png',
			'high' => WPC_SHIPMENT_TRACKER_URL . 'branding/icon-dark.png',
		];

		return $info;
	}

	/**
	 * Ensure the unpacked plugin directory matches the canonical plugin slug.
	 * Fixes GitHub's default "{user}-{repo}-{hash}" extracted directory naming.
	 *
	 * @param string $source Unpacked folder path.
	 * @param string $remote_source Remote download source path.
	 * @param mixed $upgrader Upgrader instance.
	 * @param array<string, mixed>|null $hook_extra Hook extra parameters.
	 * @return string Corrected source path or WP_Error.
	 */
	public function fix_source_dir(string $source, string $remote_source, mixed $upgrader, ?array $hook_extra = null): string {
		global $wp_filesystem;

		// Check if the update applies to this plugin.
		if (empty($hook_extra) || empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
			return $source;
		}

		$desired_dir = trailingslashit($remote_source) . $this->plugin_slug;

		if (trailingslashit($source) !== trailingslashit($desired_dir)) {
			$wp_filesystem->move($source, $desired_dir);
			return trailingslashit($desired_dir);
		}

		return $source;
	}
}
