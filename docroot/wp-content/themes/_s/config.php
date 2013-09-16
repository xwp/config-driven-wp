<?php
return array(
	/**
	 * Set the content width based on the theme's design and stylesheet.
	 * @see _s_setup()
	 */
	'content_width' => 640, // px

	/**
	 * Theme support
	 * @see _s_setup()
	 */
	'theme_support' => array(
		/**
		 * Add default posts and comments RSS feed links to head
		 */
		'automatic-feed-links' => true,

		/**
		 * Enable support for Post Thumbnails on posts and pages
		 *
		 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
		 */
		'post-thumbnails' => true,

		/**
		 * Enable support for Post Formats
		 */
		'post-formats' => array_fill_keys( array( 'aside', 'image', 'video', 'quote', 'link' ), true ),

		/**
		 * Setup the WordPress core custom background feature.
		 */
		'custom-background' => apply_filters( '_s_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ),

		/**
		 * Setup the WordPress core custom header feature.
		 */
		'custom-header' => apply_filters( '_s_custom_header_args', array(
			'default-image'          => '',
			'default-text-color'     => '000',
			'width'                  => 1000,
			'height'                 => 250,
			'flex-height'            => true,
			'wp-head-callback'       => '_s_header_style',
			'admin-head-callback'    => '_s_admin_header_style',
			'admin-preview-callback' => '_s_admin_header_image',
		) ),

		/**
		 * Add theme support for Infinite Scroll.
		 * See: http://jetpack.me/support/infinite-scroll/
		 */
		'infinite-scroll' => array(
			'container' => 'main',
			'footer'    => 'page',
		),
	),

	/**
	 * Allow child themes to replace parent theme template tags without the original functions
	 * EXPERIMENTAL! This requires the use of evil eval. Template tags would really better rely on partial templates.
	 * @see inc/template-tags.php
	 */
	'template_tags' => array(
		'_s_content_nav' => '_s_content_nav__',
		'_s_comment' => '_s_comment__',
		'_s_the_attached_image' => '_s_the_attached_image__',
		'_s_posted_on' => '_s_posted_on__',
	),

	/**
	 * Menus the theme
	 * @see _s_setup()
	 */
	'menus' => array(
		'primary' => __( 'Primary Menu', '_s' ),
	),

	/**
	 * Sidebar areas for the theme
	 * @see inc/sidebars-widgets.php
	 */
	'sidebars' => array(
		'sidebar-1' => array(
			'name'          => __( 'Sidebar', '_s' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		)
	),

	/**
	 * Image sizes that get added
	 * @see _s_setup()
	 */
	'image_sizes' => array(
		'post_thumbnail' => array(
			'width' => 800,
			'height' => 320,
			'crop' => true,
		),
	),

	/**
	 * WordPress.com-specific configs
	 * @see inc/wpcom.php
	 */
	'wpcom' => array(

		/**
		 * Set a default theme color array for WP.com.
		 */
		'themecolors' => array(
			'bg'     => '',
			'border' => '',
			'text'   => '',
			'link'   => '',
			'url'    => '',
		),

	),

	/**
	 * Frontend scripts which get registered and enqueued
	 * @see inc/dependencies.php
	 */
	'scripts' => array(
		'_s-navigation' => array(
			'src' => get_template_directory_uri() . '/js/navigation.js',
			'deps' => array(),
			'enqueue' => true,
			'ver' => '20120206',
			'in_footer' => true,
		),
		'_s-skip-link-focus-fix' => array(
			'src' => get_template_directory_uri() . '/js/skip-link-focus-fix.js',
			'deps' => array( 'jquery' ),
			'enqueue' => true,
			'ver' => '20130115',
			'in_footer' => true,
		),
		'_s-keyboard-image-navigation' => array(
			'src' => get_template_directory_uri() . '/js/keyboard-image-navigation.js',
			'deps' => array( 'jquery', ),
			'enqueue' => function () {
				return is_singular() && wp_attachment_is_image();
			},
			'ver' => '20120202',
			'in_footer' => true,
		),
		'_s_customizer' => array(
			'src' => get_template_directory_uri() . '/js/customizer.js',
			'deps' => array( 'customize-preview' ),
			'enqueue' => function () {
				return did_action( 'customize_preview_init' );
			},
			'ver' => '20130508',
			'in_footer' => true,
		),
		'comment-reply' => array(
			'enqueue' => function () {
				return is_singular() && comments_open() && get_option( 'thread_comments' );
			},
		),
	),

	/**
	 * Frontend stylesheets which get registered and enqueued
	 * @see inc/dependencies.php
	 */
	'styles' => array(
		'_s-style' => array(
			'src' => get_stylesheet_uri(),
			'enqueue' => true,
		),
	),

	/**
	 * Theme-specific customizations to the Theme Customizer settings
	 * @see inc/customizer.php
	 *
	 * @todo As with the Paul Clark's Styles plugin which allows themes to include a
	 * customize.json for mapping settings to elements via selectors, we should be
	 * able to define the same for non-style controls like blogname and blogdescription,
	 * and then to automatically generate inline JS:
	 *
	 * wp.customize( '{option}', function( value ) {
	 *     value.bind( function( to ) {
	 *         $( '{selector}' ).text( to );
	 *     } );
	 * } );
	 *
	 */
	'customizer' => array(
		'settings' => array(
			'blogname' => array(
				'transport' => 'postMessage',
			),
			'blogdescription' => array(
				'transport' => 'postMessage',
			),
			'header_textcolor' => array(
				'transport' => 'postMessage',
			),
		),
	),
);
