<?php

/**
 * @todo The post types should be in plugins
 * @action init
 */
function _s_setup_post_types() {
	global $theme_config;
	foreach ( $theme_config->post_types as $post_type => $options ) {
		if ( empty( $options['include_path'] ) ) {
			$options['include_path'] = get_template_directory() . '/post_types/' . $post_type . '.php';
		}
		$setup_func = require( $options['include_path'] );
		call_user_func( $setup_func, $options );
	}
}
add_action( 'init', '_s_setup_post_types' );


/**
 * Widgets for the theme
 * @see inc/sidebars-widgets.php
 */
//'widgets' => array(),
//'taxonomies' => array(),
//'post_types' => array(),
//'shortcodes' => array()
