<?php


namespace ColibriWP\SiteLeadsThemeKit;





class Theme {
    use Singleton;

    public static $slug     = null;
    public static $root_dir = '';
    public static $root_url = '';

    protected function __construct( $data ) {
        if ( isset( $data['root_dir'] ) && ! empty( $data['root_dir'] ) ) {
            static::$root_dir = $data['root_dir'];
        }
        if ( isset( $data['root_url'] ) && ! empty( $data['root_url'] ) ) {
            static::$root_url = $data['root_url'];
        }
        static::init_slug();
    }

    public static function slug() {
        return str_replace( '_', '-', self::$slug );
    }

    public static function init_slug() {
        if ( empty( static::$slug ) ) {
            static::$slug = str_replace( '-', '_', get_template() );
        }
    }
    public static function prefix( $str ) {
        static::init_slug();
        $slug = static::$slug ? static::$slug : 'siteleads';

        return $slug . '_siteleads_' . $str;
    }

    public static function init_data( $init_data ) {
        static::$instance = new static( $init_data );
        return static::$instance;
    }

    public static function get_url_path( $path = '' ) {
        return static::$root_url . $path;
    }
    public static function get_dir_path( $path = '' ) {
        return static::$root_dir . $path;
    }
}
