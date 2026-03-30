<?php

namespace ColibriWP\SiteLeadsThemeKit;

class Flags {
    private static $instance = null;
    private $flags           = array();
    private $is_dirty_value  = false;




    protected function __construct() {
        $this->flags = get_option( $this->get_option_name(), array() );
        add_action( 'shutdown', array( $this, 'save' ) );
    }

    public function get_option_name() {
        return Theme::prefix( 'instance_flags' );
    }

    /**
     * @param string $flag
     * @param mixed $value
     */
    public static function set( $flag, $value ) {
        static::get_instance()->set_flag( $flag, $value );
    }

    /**
     * @param string $flag
     * @param mixed $value
     */
    public function set_flag( $flag, $value ) {
        $this->with_flags( 'set', $flag, $value );
    }

    /**
     * @param $action
     * @param null $flag
     * @param null $data
     *
     * @return mixed|null
     */
    private function with_flags( $action, $flag = null, $data = null ) {
        if ( $action === 'get-all' ) {
            return $this->flags;
        }

        if ( $action === 'get' ) {
            if ( isset( $this->flags[ $flag ] ) ) {
                return $this->flags[ $flag ];
            }

            return $data;
        }

        if ( $action === 'set' ) {
            $this->flags[ $flag ] = $data;
            $this->is_dirty_value = true;
            $this->save();

            return $data;
        }

        if ( $action === 'delete' ) {
            if ( isset( $this->flags[ $flag ] ) ) {
                unset( $this->flags[ $flag ] );
                $this->is_dirty_value = true;
                $this->save();
            }

            return null;
        }
    }

    /**
     * @return null
     */
    private static function get_instance() {

        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $flag
     */
    public static function delete( $flag ) {
        static::get_instance()->delete_flag( $flag );
    }

    /**
     * @param string $flag
     */
    public function delete_flag( $flag ) {
        $this->with_flags( 'delete', $flag );
    }

    /**
     * @param string $flag
     * @param mixed $fallback
     *
     * @return mixed|null
     */
    public static function get( $flag, $fallback = null ) {
        return static::get_instance()->get_flag( $flag, $fallback );
    }

    /**
     * @param string $flag
     * @param mixed $fallback
     *
     * @return mixed|null
     */
    public function get_flag( $flag, $fallback = null ) {
        return $this->with_flags( 'get', $flag, $fallback );
    }

    public function save() {
        if ( $this->is_dirty_value ) {
            update_option( $this->get_option_name(), $this->flags, false );
        }
    }
}
