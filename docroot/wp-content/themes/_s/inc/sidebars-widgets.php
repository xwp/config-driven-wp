<?php
/**
 * _s Sidebars and Widgets
 *
 * @package _s
 */

/**
 * Register widgetized area and update sidebar with default widgets
 */
function _s_widgets_init() {
	global $theme_config;
	foreach ( array_filter( $theme_config->get( 'sidebars' ) ) as $id => $options ) {
		register_sidebar( array_merge( compact( 'id' ), $options ) );
	}
	foreach ( array_filter( $theme_config->get( 'widgets', array() ) ) as $id => $options ) {
		if ( $options === true ) {
			$options = array();
		}
		if ( empty( $options['include_path'] ) ) {
			$options['include_path'] = TEMPLATEPATH . '/widgets/' . $id . '.php';
		}
		require_once( $options['include_path'] );
	}
}
add_action( 'widgets_init', '_s_widgets_init' );
