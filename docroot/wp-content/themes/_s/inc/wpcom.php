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
	global $theme_config;
	$GLOBALS['themecolors'] = $theme_config->get( 'wpcom/themecolors', array() );
}
add_action( '_s_theme_config_loaded', '_s_wpcom_globalize_themecolors' );
