<?php
/**
 * Plugin Name:  IconAgency WordPress Vite
 * Plugin URI:   https://bitbucket.org/iconagency/iconagency-wordpress-vite
 * Description:  Autoload manifest entries foom Vite.
 * Version:      1.0.0
 * Author:       IconAgency
 * Author URI:   https://iconagency.com.au/
 * License:      MIT License
 *
 * @package wordpress_vite
 */

/**
 * Load vite assets base on HMR and manifest.json
 */
function vite_add_assets(): void {
	require_once 'class-vitemanifest.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$theme_style = get_stylesheet_directory() . '/style.css';

	if ( ! file_exists( $theme_style ) ) {
		return;
	}

	// Get path to HMR and entries from style.css.
	$theme_headers = array(
		'Version'    => 'Version',
		'ViteClient' => 'Vite Client',
		'ViteEntry'  => 'Vite Entry',
		'ViteDist'   => 'Vite Dist',
	);

	$theme_data = get_file_data( $theme_style, $theme_headers, 'theme' );

	$version = $theme_data['Version'] ?? null;
	$client  = $theme_data['ViteClient'] ?? null;
	$entry   = $theme_data['ViteEntry'] ?? null;
	$dist    = $theme_data['ViteDist'] ?? 'dist/';

	if ( empty( $client ) || empty( $version ) || empty( $entry ) ) {
		return;
	}

	$vite = new ViteManifest( $dist, $client );

	// Add the HMR client.
	if ( $vite->dev() ) {
		// phpcs:ignore
		wp_register_script( 'vite-client', $client, array(), null, false );
		wp_enqueue_script( 'vite-client' );
	}

	$base     = $vite->path();
	$manifest = $vite->manifest();

	$entries = array_map( 'trim', explode( ',', $entry ) );

	// Add the entrypoint and any css.
	foreach ( $entries as $i => $file ) {
		$app_path = $base . ( $manifest[ $file ]['file'] ?? $file );
		wp_register_script( 'vite-' . $i, $app_path, array(), $version, true );
		wp_enqueue_script( 'vite-' . $i );

		$styles = $manifest[ $file ]['css'] ?? array();
		foreach ( $styles as $j => $css ) {
			wp_register_style( 'vite-' . $i . '-' . $j, $base . $css, array(), $version );
			wp_enqueue_style( 'vite-' . $i . '-' . $j );
		}
	}
}

/**
 * Modify script tage to be a module.
 *
 * @param string $tag HTML output.
 * @param string $handle Registered handle.
 * @param string $src URL to file.
 *
 * @return string Modified HTML.
 */
function vite_module_entries( $tag, $handle, $src ): ?string {
	if ( preg_match( '/^vite-/', $handle ) ) {
		// phpcs:ignore
		return '<script type="module" src="' . esc_url( $src ) . '"></script>';
	}
	return $tag;
}


if ( is_blog_installed() ) {
	add_action( 'wp_enqueue_scripts', 'vite_add_assets' );
	add_filter( 'script_loader_tag', 'vite_module_entries', 10, 3 );
}
