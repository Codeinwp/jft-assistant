<?php
/**
 * Plugin Name: JustFreeThemes Assistant
 * Contributors: codeinwp,themeisle, rozroz
 * Plugin URI: https://github.com/Codeinwp/jft-assistant
 * Description: Allows access to more than 600 awesome, pixel perfect & free WordPress Themes
 * Version: 1.0.0
 * Author: JustFreeThemes
 * Author URI: https://justfreethemes.com
 * License: GPL2
 * Text-Domain: jft-assistant
 * Domain Path: /languages
 * WordPress Available:  no
 * Requires License:    no
 */

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'JFT_ASSISTANT_NAME__', 'JFT Assistant' );
define( 'JFT_ASSISTANT_SLUG__', '__jft_assistant_' );
define( 'JFT_ASSISTANT_VERSION__', '1.0.0' );
define( 'JFT_ASSISTANT_DIR__', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'JFT_ASSISTANT_URL__', plugin_dir_url( __FILE__ ) );
define( 'JFT_ASSISTANT_ROOT__', trailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'JFT_ASSISTANT_RESOURCES__', JFT_ASSISTANT_ROOT__ . 'resources/' );
define( 'JFT_ASSISTANT_IMAGES__', JFT_ASSISTANT_RESOURCES__ . 'images/' );
define( 'JFT_ASSISTANT_DEBUG__', false );
define( 'JFT_ASSISTANT_THEMES_PERPAGE__', 48 );
define( 'JFT_ASSISTANT_THEMES_ENDPOINT__', 'https://justfreethemes.com/wp-json/wp/v2/posts?context=jft-assistant&per_page=' . JFT_ASSISTANT_THEMES_PERPAGE__ . '&page=#' );
define( 'JFT_ASSISTANT_THEMES_CACHE_DAYS__', 1 );
define( 'JFT_ASSISTANT_THEMES_DISABLE_CACHE__', false );
define( 'JFT_THEO_TRACK_ENDPOINT__', 'https://justfreethemes.com/wp-json/theo/v1/track/2/#id#/' );

if ( JFT_ASSISTANT_DEBUG__ ) {
	// @codingStandardsIgnoreStart
	@error_reporting( E_ALL );
	@ini_set( 'display_errors', '1' );
	// @codingStandardsIgnoreEnd
}

/**
 * Abort loading if WordPress is upgrading
 */
if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

/**
 * The entry point of the plugin
 */
function jft_assistant_init() {
	require_once JFT_ASSISTANT_DIR__ . '/classes/JftAssistant/Autoloader.php';
	JftAssistant_Autoloader::register();
	JftAssistant_Plugin::get_instance()->load();
}

// hook to load plugin
add_action( 'plugins_loaded', 'jft_assistant_init', 0 );

register_activation_hook( __FILE__, 'jft_assistant_activate' );
register_deactivation_hook( __FILE__, 'jft_assistant_deactivate' );

/**
 * Called when plugin is activated
 */
function jft_assistant_activate() {
	require_once JFT_ASSISTANT_DIR__ . '/classes/JftAssistant/Autoloader.php';
	JftAssistant_Autoloader::register();
	JftAssistant_Plugin::get_instance()->activate();
}

/**
 * Called when plugin is activated
 */
function jft_assistant_deactivate() {
	require_once JFT_ASSISTANT_DIR__ . '/classes/JftAssistant/Autoloader.php';
	JftAssistant_Autoloader::register();
	JftAssistant_Plugin::get_instance()->deactivate();
}
