<?php
/**
 * Plugin Name:  Safe WP Plugins
 * Description:  Allow to install and activate WP plugins that are working on ClassicPress.
 * Version:      0.0.2
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Author:       Simone Fioravanti
 * Author URI:   https://simonefioravanti.it
 * Requires PHP: 7.4
 * Requires CP:  2.7
 */

namespace XXSimoXX\SafeWPPlugins;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class SafeWPPlugins {

	const HARDCODED_PLUGINS = array(
		'display-a-quote',
		'very-simple-contact-form',
		'very-simple-event-list',
		'very-simple-link-manager',
		'very-simple-knowledge-base',
	);

	private $plugins       = array();
	private $wp_version    = null;

	public function __construct() {
		$this->init_wp_version();
		$this->init_safe_plugins();
		add_filter( 'plugins_api_result', array( $this, 'trick_api' ), 100, 4 );
		add_filter( 'get_plugin_data', array( $this, 'trick_plugin_data' ), 100, 5 );
	}

	private function init_wp_version() {
		global $wp_version;
		$this->wp_version = preg_replace( '/^([\d]+.[\d]+).*/', '\1', $wp_version );
	}

	private function init_safe_plugins() {
		if ( ! empty( $this->plugins ) ) {
			return;
		}
		$tagged_classicpress = $this->search_classicpress_plugins();
		$safe_plugins        = array_merge( self::HARDCODED_PLUGINS, $tagged_classicpress );

		/**
		 * Filters the plugins considered safe.
		 *
		 * The slugs to be used are the ones reported by the WP API.
		 *
		 *
		 * @param array $safe_plugins Slugs of plugins considered safe.
		*/
		$this->plugins = apply_filters( 'cp_local_safe_wp_plugins', $safe_plugins );
	}

	public function get_safe_plugins() {
		return $this->plugins;
	}

	public function search_classicpress_plugins() {
		$result = get_transient( 'cp_wp_plugins_tagged_classicpress' );
		if ( $result !== false && is_array( $result ) ) {
			return $result;
		}
		$result = array();
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		$response = plugins_api(
			'query_plugins',
			array(
				'per_page' => 100,
				'tag'      => 'classicpress',
			)
		);
		if ( is_wp_error( $response ) || ! isset( $response->plugins ) ) {
			return $result;
		};
		foreach ( $response->plugins as $plugin ) {
			$result[] = $plugin['slug'];
		}
		set_transient( 'cp_wp_plugins_tagged_classicpress', $result, 48 * HOUR_IN_SECONDS );
		return $result;
	}

	public function trick_api( $res, $action, $args ) {
		if ( $action === 'plugin_information' ) {
			if ( in_array( $res->slug, $this->plugins ) ) {
				if ( version_compare( $res->requires, $this->wp_version, 'gt' ) ) {
					$res->requires = $this->wp_version;
				}
			}
		}
		if ( $action === 'query_plugins' ) {
			foreach ( $res->plugins as $index => $plugin ) {
				if ( ! in_array( $plugin['slug'], $this->plugins ) ) {
					continue;
				}
				if ( version_compare( $res->plugins[ $index ]['requires'], $this->wp_version, 'gt' ) ) {
					$res->plugins[ $index ]['requires'] = $this->wp_version;
				}
			}
		}
		return $res;
	}

	public function trick_plugin_data( $plugin_data, $plugin_file, $markup, $translate ) {
		if ( in_array( basename( dirname( plugin_basename( $plugin_file ) ) ), $this->plugins ) && isset( $plugin_data['RequiresWP'] ) ) {
			if ( version_compare( $plugin_data['RequiresWP'], $this->wp_version, 'gt' ) ) {
				$plugin_data['RequiresWP'] = $this->wp_version;
			}
		}
		return $plugin_data;
	}
}

$safe_wp_plugins = new SafeWPPlugins();
