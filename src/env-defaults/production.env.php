<?php
if ( ! class_exists( 'WP_Config_Array' ) ) {
	require_once __DIR__ . '/../docroot/wp-content/mu-plugins/class-wp-config-array.php';
}

return call_user_func(function () {

	$env_array = array();
	$default_config_path = __DIR__ . '/default.env.php';
	if ( file_exists( $default_config_path ) ) {
		$env_array = require( $default_config_path );
	}
	$env = new WP_Config_Array( $env_array );

	$env->extend( array(
		'DOMAIN_CURRENT_SITE' => '__SERVER_NAME__',
		'WP_CACHE' => true,
		'WP_DEBUG' => false,
		'SCRIPT_DEBUG' => false,
		'CONCATENATE_SCRIPTS' => true,
		'COMPRESS_SCRIPTS' => true,
		'COMPRESS_CSS' => true,
		'SAVEQUERIES' => false,
		'DISABLE_WP_CRON' => true, // System should be pinging cron is now pinging wp-cron.php regularly so WP Cron spawning is not needed.
		'FORCE_SSL_LOGIN' => true,
		'FORCE_SSL_ADMIN' => true,
	) );

	/**
	 * Allow system environment variables to supply sensitive configuration information
	 */
	$keys = array(
		'DB_HOST',
		'DB_NAME',
		'DB_USER',
		'DB_PASSWORD',
		'AUTH_KEY',
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		'AUTH_SALT',
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT',
	);
	foreach ( $keys as $key ) {
		if ( ! empty( $_SERVER["WP_{$key}"] ) ) {
			$env[$key] = $_SERVER["WP_{$key}"];
		}
	}

	/**
	 * Lastly, allow an uncommitted production-overrides.env.php to supply sensitive configs
	 */
	$overrides_config_path = __DIR__ . '/' . str_replace('.env.php', '-overrides.env.php', basename( __FILE__ ));
	if ( file_exists( $overrides_config_path ) ) {
		$env->extend( require( $overrides_config_path ) );
	}

	return $env;
});
