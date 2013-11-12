<?php
/**
 * Plugin Name: Config Drivers
 * Description: Given the WP_Config_Array, set up a theme along with post types, shortcodes, widgets, etc
 * Author: Weston Ruter, X-Team
 * Author URI: http://x-team.com/wordpress/
 * Version: 0.1
 * License: GPLv2+
 */

class WP_Config_Drivers {

	static $site_config;

	static function setup() {
		add_action( 'after_setup_theme', array( __CLASS__, 'after_setup_theme' ), 999 );
	}

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which runs
	 * before the init hook. The init hook is too late for some features, such as indicating
	 * support post thumbnails.
	 */
	static function after_setup_theme() {

		$site_config_file = apply_filters( 'wp_site_config_file', locate_template( 'config.php' ) );
		if ( empty( $site_config_file ) ) {
			return;
		}

		self::$site_config = WP_Config_Array::load_config( $site_config_file );

		$GLOBALS['content_width'] = self::$site_config->get( 'content_width' );

		foreach ( self::$site_config->get( 'theme_support', array() ) as $feature => $options ) {
			if ( $options === false ) {
				remove_theme_support( $feature );
			}
			else if ( is_array($options) ) {
				if ( ! isset($options[0]) && in_array( $feature, array( 'post-formats' ) ) ) {
					$options = array_keys( array_filter( $options ) );
				}
				add_theme_support($feature, $options);
			}
			else {
				add_theme_support($feature);
			}
		}

		register_nav_menus( array_filter( self::$site_config->get( 'menus', array() ) ) );

		foreach ( array_filter( self::$site_config->get( 'image_sizes', array() ) ) as $name => $size_info ) {
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

		add_action( 'widgets_init', array( __CLASS__, 'sidebars_widgets_init' ) );
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'create_template_tags' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );
	}


	/**
	 * Register widgetized area and update sidebar with default widgets
	 */
	static function sidebars_widgets_init() {
		foreach ( array_filter( self::$site_config->get( 'sidebars' ) ) as $id => $options ) {
			register_sidebar( array_merge( compact( 'id' ), $options ) );
		}
		foreach ( array_filter( self::$site_config->get( 'widgets', array() ) ) as $id => $options ) {
			if ( empty( $options['include_path'] ) || ! file_exists( $options['include_path'] ) ) {
				trigger_error( sprintf( 'Incorrect include_path supplied for widget "%s"', $id ), E_USER_WARNING );
			}
			else {
				require_once $options['include_path'];
			}
		}
	}

	/**
	 * @todo The post types should be in plugins
	 * @action init
	 */
	static function register_post_types() {
		$post_types = array_filter( self::$site_config->get( 'post_types', array() ) );
		foreach ( $post_types as $post_type => $options ) {
			if ( empty( $options['include_path'] ) || ! file_exists( $options['include_path'] ) ) {
				trigger_error( sprintf( 'Incorrect include_path supplied or post_type "%s"', $post_type ), E_USER_WARNING );
			}
			else {
				require_once $options['include_path'];
			}
		}
	}

	/**
	 * @param string {styles,scripts}
	 */
	static function enqueue_deps( $type ) {
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
		foreach ( array_filter( self::$site_config->get( $type, array() ) ) as $handle => $args ) {
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
	static function enqueue_scripts() {
		self::enqueue_deps( 'scripts' );
	}

	/**
	 * Register and enqueue styles
	 */
	static function enqueue_styles() {
		self::enqueue_deps( 'styles' );
	}

	/**
	 * Allow child themes to replace parent theme template tags without the original functions
	 * EXPERIMENTAL! This requires the use of evil eval. Template tags would really better rely on partial templates.
	 */
	static function create_template_tags() {
		$function_tpl = '
			function %s(){
				return call_user_func_array( %s, func_get_args() );
			}
		';
		foreach( self::$site_config->get( 'template_tags' ) as $template_tag => $function ) {
			$ok = (
				is_string( $template_tag )
				&&
				is_callable( $template_tag, true )
				&&
				is_callable( $function, true )
			);
			if ( $ok ) {
				$function_code = sprintf( $function_tpl, $template_tag, var_export( $function, true ) );
				eval( $function_code );
			}
		}
	}

	/**
	 * Add postMessage support for site title and description for the Theme Customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	static function customize_register( $wp_customize ) {
		foreach ( array_filter( self::$site_config->get( 'customizer/settings', array() ) ) as $id => $args ) {
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

	/**
	 * @todo Taxonomies, shortcodes, etc
	 */

}

add_action( 'muplugins_loaded', array( 'WP_Config_Drivers', 'setup' ) );
