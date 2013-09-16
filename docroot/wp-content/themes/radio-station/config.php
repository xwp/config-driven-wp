<?php
return array(

	'base_configs' => array(
		get_template_directory() . '/config.php' => true,
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
