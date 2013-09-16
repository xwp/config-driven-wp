<?php
/**
 * _s Script and Styles
 *
 * @package _s
 */

/**
 * @param string {styles,scripts}
 */
function _s_enqueue_deps( $type ) {
	global $theme_config;
	if ( 'scripts' === $type ) {
		$default_args = array(
			'deps' => array(),
			'ver' => null,
			'in_footer' => false,
			'enqueue' => false,
		);
	}
	else {
		$default_args = array(
			'deps' => array(),
			'ver' => null,
			'enqueue' => false,
			'media' => 'all',
		);
	}
	$home_host = parse_url( home_url(), PHP_URL_HOST );
	foreach ( array_filter( $theme_config->get( $type, array() ) ) as $handle => $args ) {
		$args = array_merge( $default_args, $args );
		if ( ! empty( $args['src'] ) ) {
			if ( empty( $args['ver'] ) && parse_url( $args['src'], PHP_URL_HOST ) === $home_host ) {
				$path = parse_url( $args['src'], PHP_URL_PATH );
				$path = preg_replace( '#^.*?(?=/wp-content)#', '', $path ); // for subdirectory multisite installs
				$args['ver'] = filemtime( ABSPATH . $path );
			}
			if ( 'scripts' === $type ) {
				wp_register_script(
					$handle,
					$args['src'],
					$args['deps'],
					$args['ver'],
					$args['in_footer']
				);
			}
			else {
				wp_register_style(
					$handle,
					$args['src'],
					$args['deps'],
					$args['ver'],
					$args['media']
				);
			}
		}
		if ( is_callable( $args['enqueue'] ) ) {
			$args['enqueue'] = call_user_func( $args['enqueue'] );
		}
		if ( $args['enqueue'] ) {
			if ( 'scripts' === $type ) {
				wp_enqueue_script( $handle );
			}
			else {
				wp_enqueue_style( $handle );
			}
		}
	}

}

/**
 * Register and enqueue scripts
 */
function _s_enqueue_scripts() {
	_s_enqueue_deps( 'scripts' );
}
add_action( 'wp_enqueue_scripts', '_s_enqueue_scripts' );

/**
 * Register and enqueue styles
 */
function _s_enqueue_styles() {
	_s_enqueue_deps( 'styles' );
}
add_action( 'wp_enqueue_scripts', '_s_enqueue_styles' );
