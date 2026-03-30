<?php

namespace ColibriWP\SiteLeadsThemeKit;


class Bootstrap {
    use Singleton;

    protected function __construct( $init_data ) {
        Theme::init_data( $init_data );
        SiteLeads::load();
    }
    public static function init_data( $init_data ) {
        static::$instance = new static( $init_data );
        return static::$instance;
    }
}
