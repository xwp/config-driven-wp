<?php
/**
 * WordPress.com-specific functions and definitions
 *
 * @package _s
 */

/**
 * Set a default theme color array for WP.com.
 *
 * @global array $themecolors
 */
function _s_wpcom_globalize_themecolors() {
	$GLOBALS['themecolors'] = WP_Config_Drivers::$site_config->get( 'wpcom/themecolors', array() );
}
add_action( 'init', '_s_wpcom_globalize_themecolors' );
