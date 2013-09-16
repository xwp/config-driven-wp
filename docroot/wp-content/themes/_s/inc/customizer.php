<?php
/**
 * _s Theme Customizer
 *
 * @package _s
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function _s_customize_register( $wp_customize ) {
	global $theme_config;
	foreach ( array_filter( $theme_config->get( 'customizer/settings', array() ) ) as $id => $args ) {
		$setting = $wp_customize->get_setting( $id );
		if ( $setting ) {
			foreach ( $args as $key => $value ) {
				$setting->$key = $value;
			}
		}
		else {
			$wp_customize->add_setting( $id, $args );
		}
	}
}
add_action( 'customize_register', '_s_customize_register' );
