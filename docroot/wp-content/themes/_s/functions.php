<?php
/**
 * _s functions and definitions
 *
 * @package _s
 */

/**
 * @var WP_Config_Array|array Theme configuration merged from parent theme, child theme, and other sources
 * @global
 */
$theme_config = array();

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 */
function _s_setup() {

	/**
	 * Make theme available for translation
	 * Translations can be filed in the /languages/ directory
	 * If you're building a theme based on _s, use a find and replace
	 * to change '_s' to the name of your theme in all the template files
	 * We need to load the translations before loading the config, because
	 * there be text translations in the config. Child themes which have
	 * configs with translations in them should load their textdomains
	 * at the _s_load_text_domains action.
	 */
	load_theme_textdomain( '_s', get_template_directory() . '/languages' );
	do_action( '_s_load_text_domains' );

	/**
	 * Load the configs
	 */
	global $theme_config;
	$theme_config = new WP_Config_Array( include( TEMPLATEPATH . '/config.php' ) );
	if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/config.php' ) ) {
		$theme_config->extend( include( STYLESHEETPATH . '/config.php' ) );
	}
	// @todo The configs should indicate which configs they extend
	do_action( '_s_theme_config_loaded', $theme_config );

	$GLOBALS['content_width'] = $theme_config->get( 'content_width' );

	foreach ( $theme_config->get('theme_support', array() ) as $feature => $options ) {
		if ( $options === false ) {
			remove_theme_support( $feature );
		}
		else if (is_array($options)) {
			if ( ! isset($options[0]) && in_array( $feature, array( 'post-formats' ) ) ) {
				$options = array_keys( array_filter( $options ) );
			}
			add_theme_support($feature, $options);
		}
		else {
			add_theme_support($feature);
		}
	}

	register_nav_menus( array_filter( $theme_config->get( 'menus', array() ) ) );

	foreach ( array_filter( $theme_config->get( 'image_sizes', array() ) ) as $name => $size_info ) {
		extract( array_merge(
			compact( 'name' ),
			array(
				'crop' => false,
				'width' => 9999,
				'height' => 9999,
			),
			$size_info
		));
		add_image_size( $name, $width, $height, $crop );
	}
}
add_action( 'after_setup_theme', '_s_setup' );

/**
 * Functions for the script and style dependencies.
 */
require get_template_directory() . '/inc/sidebars-widgets.php';

/**
 * Functions for the script and style dependencies.
 */
require get_template_directory() . '/inc/dependencies.php';

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
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * WordPress.com-specific functions and definitions
 */
require get_template_directory() . '/inc/wpcom.php';
