<?php
/**
 * Plugin Name: Back To The Theme
 * Description: Capture screenshots of a page with different themes, just like that!
 * Version: 1.2.0
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Author URI:  https://www.mikaelkorpela.fi
 */

defined( 'ABSPATH' ) or die();

require_once( plugin_dir_path( __FILE__ ) . 'class.back-to-the-theme.php' );

add_action( 'plugins_loaded', array( 'BackToTheTheme', 'init' ) );
