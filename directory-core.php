<?php
/**
 * Plugin Name: Directory Core
 * Plugin URI:  https://mostlywanted.com/
 * Description: Beta shared artist and venue records for directory and map products by Mostly Wanted.
 * Version:     0.1.4-beta.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author:      Mostly Wanted
 * License:     GPL2+
 * Text Domain: directory-core
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'DIRECTORY_CORE_VERSION', '0.1.4-beta.1' );
define( 'DIRECTORY_CORE_TEXT_DOMAIN', 'directory-core' );
define( 'DIRECTORY_CORE_PLUGIN_FILE', __FILE__ );
define( 'DIRECTORY_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once DIRECTORY_CORE_PLUGIN_DIR . 'src/Infrastructure/Autoloader.php';

DirectoryCore\Infrastructure\Autoloader::register( 'DirectoryCore', DIRECTORY_CORE_PLUGIN_DIR . 'src' );

DirectoryCore\Plugin::boot(
	new DirectoryCore\Infrastructure\PluginContext(
		DIRECTORY_CORE_PLUGIN_FILE,
		DIRECTORY_CORE_VERSION,
		DIRECTORY_CORE_TEXT_DOMAIN
	)
);

register_activation_hook(
	__FILE__,
	static function (): void {
		DirectoryCore\Plugin::activate(
			new DirectoryCore\Infrastructure\PluginContext(
				DIRECTORY_CORE_PLUGIN_FILE,
				DIRECTORY_CORE_VERSION,
				DIRECTORY_CORE_TEXT_DOMAIN
			)
		);
	}
);
