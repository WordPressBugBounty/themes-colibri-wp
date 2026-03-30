<?php

namespace ColibriWP\SiteLeadsThemeKit;

trait Singleton {

	protected static $instance;

	/**
	 * Get the singleton instance
	 *
	 * @return static
	 */
	public static function get_instance() {
		return isset( static::$instance )
			? static::$instance
			: static::$instance = new static();
	}

	/**
	 * Load singleton
	 *
	 * @return static
	 */
	public static function load() {
		return static::get_instance();
	}
}
