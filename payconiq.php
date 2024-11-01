<?php
/*
Plugin Name: Payconiq
Plugin URI:
Description: Accept payments by scanning a QR-code through the Payconiq app.
Version: 1.0.2
Author: Payconiq
Author URI: https://www.payconiq.lu/
License: GPLv3
Text Domain: payconiq
Domain Path: /languages
*/

namespace payconiq;

use payconiq\lib\Container;
use payconiq\lib\Container_Interface;

define( 'PAYCONIQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAYCONIQ_URL', plugin_dir_url( __FILE__ ) );
define( 'PAYCONIQ_FILE', __FILE__ );
define( 'PAYCONIQ_VERSION', '1.0.2' );

/**
 * Register autoloader to load files/classes dynamically
 */
require_once PAYCONIQ_DIR . 'lib/autoloader.php';

/**
 * Load composer/PHP-DI container
 *
 * FYI vendor files are moved from /vendor to /lib/ioc/ directory
 *
 * "php-di/php-di": "5.0"
 *
 * @version 1.0.0
 * @since 1.0.0
 */
require_once PAYCONIQ_DIR . 'lib/ioc/autoload.php';

class Payconiq {

	/**
	 * Plugin_Boilerplate constructor.
	 *
	 * @param Container_Interface $container
	 *
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function __construct( Container_Interface $container ) {
		/**
		 * Load init config
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		$container->container->get( 'init_config' );
	}
}

/**
 * Start the plugin
 *
 * @version 1.0.0
 * @since 1.0.0
 */
new Payconiq( Container::getInstance() );
