<?php
return array(

	// Note the resulting base_configs gets an ksort
	// @todo If a config here has a base_config, it will not currently get recursively parsed
	'base_configs' => array(
		'001' => get_template_directory() . '/config.php',
	),

	/**
	 * Frontend stylesheets which get registered and enqueued
	 * @see inc/dependencies.php
	 */
	'styles' => array(
		'radio-style' => array(
			'src' => get_stylesheet_uri(),
			'enqueue' => true,
		),
	),

);
