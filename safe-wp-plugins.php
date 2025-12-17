<?php
/**
 * Plugin Name:  Safe WordPress Plugins
 * Description:  Allow to install and activate WP plugins that are working on ClassicPress.
 * Version:      0.0.1
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Author:       Simone Fioravanti
 * Author URI:   https://simonefioravanti.it
 * Requires PHP: 7.4
 * Requires CP:  2.6
 */

namespace XXSimoXX\SafeWPPlugins;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class SafeWPPlugins {

	private $plugins = array(
		'very-simple-event-list',
	);

	private $wp_version = null;

	public function __construct( $use_tags = true ) {
		$this->init_wp_version();
		add_filter( 'plugins_api_result', array( $this, 'trick_api' ), 100, 4 );
		add_filter( 'get_plugin_data', array( $this, 'trick_plugin_data' ), 100, 5 );
	}

	private function init_wp_version() {
		global $wp_version;
		$this->wp_version = preg_replace( '/^([\d]+.[\d]+).*/', '\1', $wp_version );
	}

	public function get_safe_plugins() {
		return $this->plugins;
	}

	public function search_plugins() {
		$result   = array();
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
		return $result;
	}

	public function trick_api( $res, $action, $args ) {
		if ( $action === 'plugin_information' ) {
			if ( in_array( $res->slug, $this->plugins ) ) {
				$res->requires = $this->wp_version;
			}
		}
		if ( $action === 'query_plugins' ) {
			foreach ( $res->plugins as $index => $plugin ) {
				if ( ! in_array( $plugin['slug'], $this->plugins ) ) {
					continue;
				}
				$res->plugins[ $index ]['requires'] = $this->wp_version;
			}
		}
		return $res;
	}

	public function trick_plugin_data( $plugin_data, $plugin_file, $markup, $translate ) {
		if ( in_array( basename( dirname( plugin_basename( $plugin_file ) ) ), $this->plugins ) && isset( $plugin_data['RequiresWP'] ) ) {
			$plugin_data['RequiresWP'] = $this->wp_version;
		}
		return $plugin_data;
	}

}

$safe_wp_plugins = new SafeWPPlugins();
