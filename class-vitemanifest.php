<?php
/**
 * IconAgency Vite Manifest Helper
 *
 * @package wordpress_vite
 */

/**
 * Vite asset helper.
 */
class ViteManifest {

	/**
	 * Production path for assets
	 *
	 * @var string
	 */
	private string $prod_path;

	/**
	 * HMR http/https.
	 *
	 * @var string
	 */
	private string $scheme;

	/**
	 * HMR external host.
	 *
	 * @var string
	 */
	private string $localhost;

	/**
	 * HMR external port.
	 *
	 * @var int
	 */
	private int $port;

	/**
	 * HTTP client.
	 *
	 * @var \WP_Http
	 */
	private \WP_Http $client;

	/**
	 * Check dev server is running.
	 *
	 * @var bool|null
	 */
	private ?bool $dev_running = null;

	/**
	 * Build vite information based off HMR path.
	 *
	 * @param string $prod_path Path to production assets.
	 * @param string $hmr_url HMR url.
	 */
	public function __construct( $prod_path, $hmr_url ) {
		$this->prod_path = trailingslashit( $prod_path );
		$this->scheme    = wp_parse_url( $hmr_url, PHP_URL_SCHEME );
		$this->localhost = wp_parse_url( $hmr_url, PHP_URL_HOST );
		$this->port      = (int) wp_parse_url( $hmr_url, PHP_URL_PORT );
		$this->client    = new \WP_Http();
	}

	/**
	 * Confirm dev mode and server running.
	 */
	public function dev(): bool {
		if ( ! WP_DEBUG ) {
			return false;
		}

		if ( ! is_null( $this->dev_running ) ) {
			return $this->dev_running;
		}

		$this->dev_running = $this->dev_online();

		return $this->dev_running;
	}

	/**
	 * Check if the dev server is running.
	 */
	private function dev_online(): bool {
		$enabled = $this->client->head(
			$this->dev_host(),
			array(
				'timeout'            => 0.25,
				'sslverify'          => false,
				'reject_unsafe_urls' => false,
			)
		);

		return is_wp_error( $enabled ) ? false : ! empty( $enabled );
	}

	/**
	 * Internal host path for docker.
	 */
	private function dev_host(): string {
		global $wp_filesystem;

		$lando  = getenv( 'LANDO_HOST_IP' );
		$docker = $wp_filesystem->exists( '/.dockerenv' );

		if ( $docker ) {
			$host = $lando ? $lando : 'host.docker.internal';
		}

		return $this->scheme . '://' . ( $docker ? $host : $this->localhost ) . ':' . $this->port;
	}

	/**
	 * Internal host path for lando.
	 */
	private function dev_path(): string {
		return $this->scheme . '://' . $this->localhost . ':' . $this->port . '/';
	}

	/**
	 * External path to the vite dist assets.
	 */
	public function path(): string {
		return $this->dev() ? $this->dev_path() : get_theme_file_uri( $this->prod_path );
	}

	/**
	 * Path to the manifets file.
	 *
	 * @param string $dir Path to theme.
	 */
	private function manifest_path( $dir ): string {
		return $dir . '/' . $this->prod_path . 'manifest.json';
	}

	/**
	 * Manifest content.
	 *
	 * @param string $dir Path to theme.
	 */
	public function manifest( $dir ): array {
		global $wp_filesystem;

		$path = $this->manifest_path( $dir );

		if ( ! $this->dev() && $wp_filesystem->exists( $path ) ) {
			return json_decode( $wp_filesystem->get_contents( $path ), true );
		}

		return array();
	}

}
