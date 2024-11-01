<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/MehbubRashid
 * @since             1.0.0
 * @package           Template_Share_For_Elementor
 *
 * @wordpress-plugin
 * Plugin Name:       Template Share for Elementor
 * Plugin URI:        https://divdojo.com/template-share
 * Description:       Share your Elementor Templates. Create your own Template Library. Sell them using WooCommerce.
 * Version:           1.0.1
 * Author:            DivDojo
 * Author URI:        https://divdojo.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       template-share-for-elementor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EMSHF_VERSION', '1.0.1' );

/**
 * Define plugin path and plugin url.
 */
define( 'EMSHF_PATH', plugin_dir_path( __FILE__ ) );
define( 'EMSHF_URL', plugin_dir_url( __FILE__ ) );
define( 'EMSHF_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'EMSHF_ASSETS_VERSION', time() );

/**
 * The code that runs during plugin activation.
 */
function emshf_activate() {
	
}

/**
 * The code that runs during plugin deactivation.
 */
function emshf_deactivate() {
	
}

register_activation_hook( __FILE__, 'emshf_activate' );
register_deactivation_hook( __FILE__, 'emshf_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function emshf_run() {

	$plugin = new Emshf_Plugin();

}
emshf_run();
