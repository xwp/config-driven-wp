<?php
/**
 * _s functions and definitions
 *
 * @package _s
 */


function radio_setup() {
	/**
	 * Make theme available for translation
	 * Translations can be filed in the /languages/ directory
	 * If you're building a theme based on _s, use a find and replace
	 * to change 'radio' to the name of your theme in all the template files
	 * We need to load the translations before loading the config, because
	 * there be text translations in the config. Child themes which have
	 * configs with translations in them should load their textdomains
	 * at the radio_load_text_domains action.
	 */
	load_theme_textdomain( 'radio', get_template_directory() . '/languages' );
	do_action( 'radio_load_text_domains' );
}
add_action( 'after_theme_setup', 'radio_setup' );


function radio_filter_wp_site_config_file( $site_config_file ) {
	$theme_config_file = locate_template( 'config.php' );
	if ( $theme_config_file ) {
		$site_config_file = $theme_config_file;
	}
	return $site_config_file;
}
add_filter( 'wp_site_config_file', 'radio_filter_wp_site_config_file' );

/**
 * Functions for the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * WordPress.com-specific functions and definitions
 */
require get_template_directory() . '/inc/wpcom.php';
