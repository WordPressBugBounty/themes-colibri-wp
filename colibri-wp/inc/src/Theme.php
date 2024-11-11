<?php


namespace ColibriWP\Theme;


use ColibriWP\Theme\Core\ComponentInterface;
use ColibriWP\Theme\Core\Hooks;
use Exception;
use function add_theme_support;
use function register_nav_menus;
use function register_sidebar;


class Theme {

    private static $instance = null;

    private $repository;
    private $customizer;
    private $assets_manager;
    private $plugins_manager;

    private $registered_menus = array();
    private $sidebars = array();

    private $themes_cache = array();

    public function __construct() {
        static::$instance = $this;
        Hooks::colibri_do_action( 'boot' );

        $this->repository      = new ComponentsRepository();
        $this->customizer      = new Customizer( $this );
        $this->assets_manager  = new AssetsManager( $this );
        $this->plugins_manager = new PluginsManager( $this );

        add_action( 'after_setup_theme', array( $this, 'afterSetup' ), 20 );
        add_action('init', array($this, 'onInitHook'));
        add_action('widgets_init', array($this, 'doInitWidgets'));
    }

    public static function load() {
        static::getInstance();
    }

    /**
     * @return null|Theme
     */
    public static function getInstance() {
        if ( ! static::$instance ) {
            static::$instance = new static();
            Hooks::colibri_do_action( 'theme_loaded', static::$instance );
        }

        return static::$instance;
    }

    public function modifyRegisteredSidebar( $sidebar ) {


        global $wp_registered_sidebars;

        $sidebar['before_widget'] .= '<!--colibriwp_before_widget-->';
        $sidebar['after_widget']  .= '<!--colibriwp_after_widget-->';
        $sidebar['after_title']   .= '<!--colibriwp_after_title-->';


        $wp_registered_sidebars[ $sidebar['id'] ] = $sidebar;
    }

    public function wrapWidgetsContent( $params ) {
        global $wp_registered_widgets;

        $id                = $params[0]['widget_id'];
        $original_callback = $wp_registered_widgets[ $id ]['callback'];

        if ( is_admin() ) {
            return $params;
        }

        $wp_registered_widgets[ $id ]['callback'] = function ( $args, $widget_args = 1 ) use ( $original_callback ) {
            ob_start();
            call_user_func( $original_callback, $args, $widget_args );
            $content = ob_get_clean();

            $wrapper_start_added = false;

            if ( strpos( $content, '<!--colibriwp_after_title-->' ) !== false ) {
                $content             = str_replace( '<!--colibriwp_after_title-->',
                    '<div class="colibri-widget-content-container">',
                    $content );
                $wrapper_start_added = true;
            } else {
                if ( strpos( $content, '<!--colibriwp_before_widget-->' ) !== false ) {
                    $content = str_replace( '<!--colibriwp_before_widget-->',
                        '<div class="colibri-widget-content-container">',
                        $content );
                }
                $wrapper_start_added = true;
            }

            if ( $wrapper_start_added ) {
                $content = str_replace( '<!--colibriwp_after_widget-->',
                    '</div>',
                    $content );
            }

            echo $content;

        };

        return $params;
    }


    public function afterSetup() {

        Defaults::load();
        Translations::load();



        $this->repository->load();
        $this->customizer->boot();
        $this->assets_manager->boot();
        $this->plugins_manager->boot();




        add_action( 'admin_menu', array( $this, 'addThemeInfoPage' ) );
        add_action( 'admin_notices', array( $this, 'addThemeNotice' ) );


        add_action( 'wp_ajax_colibriwp_disable_big_notice', function () {
            check_ajax_referer( 'colibriwp_disable_big_notice_nonce', 'nonce' );
            $slug = get_template() . "-page-info";
            update_option( "{$slug}-theme-notice-dismissed", true );
        } );

        add_filter( 'language_attributes', array( $this, 'languageAttributes' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ), 0 );
        add_action('wp_footer', array($this, 'addWooMyAccountIcons'));
    }

    public function onInitHook() {
        $this->registerMenus();

        add_filter( 'dynamic_sidebar_params', array( $this, 'wrapWidgetsContent' ) );
        // hooks for handling the widget content wrapping
        add_action( 'register_sidebar', array( $this, 'modifyRegisteredSidebar' ) );

    }



    public function addWooMyAccountIcons() {
        if(!function_exists('\is_account_page') ||  apply_filters( 'colibri_page_builder/installed', false )) {
            return;
        }
        if ( !is_account_page()) {
            return;
        }
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Check if we are on the "My Account" page by looking for a unique element
                if (document.querySelector('.woocommerce-MyAccount-navigation')) {

                    // Define SVGs for each menu item
                    const svgDashboard = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tachometer-alt" class="svg-inline--fa fa-tachometer-alt fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path  d="M288 32C128.94 32 0 160.94 0 320c0 52.8 14.25 102.26 39.06 144.8 5.61 9.62 16.3 15.2 27.44 15.2h443c11.14 0 21.83-5.58 27.44-15.2C561.75 422.26 576 372.8 576 320c0-159.06-128.94-288-288-288zm0 64c14.71 0 26.58 10.13 30.32 23.65-1.11 2.26-2.64 4.23-3.45 6.67l-9.22 27.67c-5.13 3.49-10.97 6.01-17.64 6.01-17.67 0-32-14.33-32-32S270.33 96 288 96zM96 384c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32zm48-160c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32zm246.77-72.41l-61.33 184C343.13 347.33 352 364.54 352 384c0 11.72-3.38 22.55-8.88 32H232.88c-5.5-9.45-8.88-20.28-8.88-32 0-33.94 26.5-61.43 59.9-63.59l61.34-184.01c4.17-12.56 17.73-19.45 30.36-15.17 12.57 4.19 19.35 17.79 15.17 30.36zm14.66 57.2l15.52-46.55c3.47-1.29 7.13-2.23 11.05-2.23 17.67 0 32 14.33 32 32s-14.33 32-32 32c-11.38-.01-20.89-6.28-26.57-15.22zM480 384c-17.67 0-32-14.33-32-32s14.33-32 32-32 32 14.33 32 32-14.33 32-32 32z"></path></svg>';
                    const svgOrders = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-basket" class="svg-inline--fa fa-shopping-basket fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M576 216v16c0 13.255-10.745 24-24 24h-8l-26.113 182.788C514.509 462.435 494.257 480 470.37 480H105.63c-23.887 0-44.139-17.565-47.518-41.212L32 256h-8c-13.255 0-24-10.745-24-24v-16c0-13.255 10.745-24 24-24h67.341l106.78-146.821c10.395-14.292 30.407-17.453 44.701-7.058 14.293 10.395 17.453 30.408 7.058 44.701L170.477 192h235.046L326.12 82.821c-10.395-14.292-7.234-34.306 7.059-44.701 14.291-10.395 34.306-7.235 44.701 7.058L484.659 192H552c13.255 0 24 10.745 24 24zM312 392V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24zm112 0V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24zm-224 0V280c0-13.255-10.745-24-24-24s-24 10.745-24 24v112c0 13.255 10.745 24 24 24s24-10.745 24-24z"></path></svg>';
                    const svgDownloads = '<svg  aria-hidden="true" focusable="false" data-prefix="far" data-icon="file-archive" class="svg-inline--fa fa-file-archive fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M128.3 160v32h32v-32zm64-96h-32v32h32zm-64 32v32h32V96zm64 32h-32v32h32zm177.6-30.1L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM256 51.9l76.1 76.1H256zM336 464H48V48h79.7v16h32V48H208v104c0 13.3 10.7 24 24 24h104zM194.2 265.7c-1.1-5.6-6-9.7-11.8-9.7h-22.1v-32h-32v32l-19.7 97.1C102 385.6 126.8 416 160 416c33.1 0 57.9-30.2 51.5-62.6zm-33.9 124.4c-17.9 0-32.4-12.1-32.4-27s14.5-27 32.4-27 32.4 12.1 32.4 27-14.5 27-32.4 27zm32-198.1h-32v32h32z"></path></svg>';
                    const svgAddresses = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="home" class="svg-inline--fa fa-home fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M280.37 148.26L96 300.11V464a16 16 0 0 0 16 16l112.06-.29a16 16 0 0 0 15.92-16V368a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v95.64a16 16 0 0 0 16 16.05L464 480a16 16 0 0 0 16-16V300L295.67 148.26a12.19 12.19 0 0 0-15.3 0zM571.6 251.47L488 182.56V44.05a12 12 0 0 0-12-12h-56a12 12 0 0 0-12 12v72.61L318.47 43a48 48 0 0 0-61 0L4.34 251.47a12 12 0 0 0-1.6 16.9l25.5 31A12 12 0 0 0 45.15 301l235.22-193.74a12.19 12.19 0 0 1 15.3 0L530.9 301a12 12 0 0 0 16.9-1.6l25.5-31a12 12 0 0 0-1.7-16.93z"></path></svg>';
                    const svgAccountDetails = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user" class="svg-inline--fa fa-user fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path></svg>';
                    const svgLogout = '<svg  aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sign-out-alt" class="svg-inline--fa fa-sign-out-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path  d="M497 273L329 441c-15 15-41 4.5-41-17v-96H152c-13.3 0-24-10.7-24-24v-96c0-13.3 10.7-24 24-24h136V88c0-21.4 25.9-32 41-17l168 168c9.3 9.4 9.3 24.6 0 34zM192 436v-40c0-6.6-5.4-12-12-12H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h84c6.6 0 12-5.4 12-12V76c0-6.6-5.4-12-12-12H96c-53 0-96 43-96 96v192c0 53 43 96 96 96h84c6.6 0 12-5.4 12-12z"></path></svg>';

                    // Define the menu item IDs
                    const menuItems = {
                        'dashboard': svgDashboard,
                        'orders': svgOrders,
                        'downloads': svgDownloads,
                        'edit-address': svgAddresses,
                        'edit-account': svgAccountDetails,
                        'customer-logout': svgLogout
                    };

                    // Loop through the items and prepend the corresponding SVG to the link text
                    for (const [key, svg] of Object.entries(menuItems)) {
                        const menuItem = document.querySelector('.woocommerce-MyAccount-navigation-link--' + key + ' a');
                        if (menuItem) {
                            menuItem.insertAdjacentHTML('afterbegin', svg + ' ');
                        }
                    }
                }
            });
        </script>
        <?php
    }
    private function registerMenus() {
        register_nav_menus( $this->registered_menus );
    }

    public function languageAttributes( $output ) {
        if ( apply_filters( 'colibri_page_builder/installed', false ) ) {
            return $output;
        }

        $theme_class = get_template() . "-theme";
        $output      .= " class='{$theme_class}'";

        return $output;

    }

    public function enqueueAdminScripts() {
        $slug = get_template() . "-page-info";

        $this->getAssetsManager()->registerScript(
            $slug,
            $this->getAssetsManager()->getBaseURL() . "/admin/admin.js",
            array( 'jquery' ),
            false
        )->registerStyle(
            $slug,
            $this->getAssetsManager()->getBaseURL() . "/admin/admin.css" .
            false );
    }

    /**
     * @return AssetsManager
     */
    public function getAssetsManager() {
        return $this->assets_manager;
    }

    public function addThemeNotice() {

        global $pagenow;
        if ( $pagenow === "update.php" ) {
            return;
        }

        $slug = get_template() . "-page-info";

        $dismissed            = get_option( "{$slug}-theme-notice-dismissed", false );
        $is_builder_installed = apply_filters( 'colibri_page_builder/installed', false );

        if ( get_option( 'extend_builder_theme', false ) ) {
            $dismissed = true;
        }

        if ( ! $dismissed && ! $is_builder_installed ) {
            wp_enqueue_style( $slug );
            wp_enqueue_script( $slug );
            wp_enqueue_script( 'wp-util' );


	          $colibri_get_started = array(
              'plugin_installed_and_active' => Translations::escHtml( 'plugin_installed_and_active' ),
              'activate'                    => Translations::escHtml( 'activate' ),
              'activating'                  => __( 'Activating', 'colibri-wp' ),
              'install_recommended'         => isset( $_GET['install_recommended'] ) ? $_GET['install_recommended'] : ''
            );

	          wp_localize_script( $slug, 'colibri_get_started', $colibri_get_started );

            ?>
            <div class="notice notice-success is-dismissible colibri-admin-big-notice notice-large">
                <?php View::make( "admin/admin-notice" ); ?>
            </div>
            <?php
        }

    }

    /**
     * @return PluginsManager
     */
    public function getPluginsManager() {
        return $this->plugins_manager;
    }

    public function addThemeInfoPage() {
        $tabs = Hooks::colibri_apply_filters( 'info_page_tabs', array() );

        if ( ! count( $tabs ) ) {
            return;
        }

        $slug = get_template() . "-page-info";

        $page_name = Hooks::colibri_apply_filters( 'theme_page_name', Translations::get( 'theme_page_name' ) );
        add_theme_page(
            $page_name,
            $page_name,
            'activate_plugins',
            $slug,
            array( $this, 'printThemePage' )
        );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueThemeInfoPageScripts' ), 20 );
    }

    public function enqueueThemeInfoPageScripts() {
        global $plugin_page;
        $slug = get_template() . "-page-info";

        if ( $plugin_page === $slug ) {
            wp_enqueue_style( $slug );
            wp_enqueue_script( $slug );
        }
    }

    public function printThemePage() {


        $tabs        = Hooks::colibri_apply_filters( 'info_page_tabs', array() );
        $tabs_slugs  = array_keys( $tabs );
        $default_tab = count( $tabs_slugs ) ? $tabs_slugs[0] : null;

        $current_tab = isset( $_REQUEST['current_tab'] ) ? $_REQUEST['current_tab'] : $default_tab;
        $url         = add_query_arg
        (
            array(
                'page' => get_template() . "-page-info",
            ),
            admin_url( "themes.php" )
        );

        $welcome_message = sprintf( Translations::translate( 'welcome_message' ), $this->getThemeHeaderData( 'Name' ) );
        $welcome_info    = Translations::translate( 'welcome_info' );


        View::make( "admin/page",
            array(
                'tabs'            => $tabs,
                'current_tab'     => $current_tab,
                'page_url'        => $url,
                'welcome_message' => Hooks::colibri_apply_filters( 'info_page_welcome_message', $welcome_message ),
                'welcome_info'    => Hooks::colibri_apply_filters( 'info_page_welcome_info', $welcome_info ),
            )
        );

    }

    public function getThemeHeaderData( $key, $child = false ) {

        $slug  = $this->getThemeSlug( $child );
        $theme = $this->getTheme( $slug );

        return $theme->get( $key );
    }

    public function getThemeSlug( $maybe_get_child = false ) {
        $slug  = get_template();
        $theme = $this->getTheme();
        if ( ! $maybe_get_child ) {
            $maybe_get_child = Hooks::colibri_apply_filters( 'use_child_theme_header_data', $maybe_get_child );
        }

        if ( $maybe_get_child && $theme->get( 'Template' ) ) {
            $slug = get_stylesheet();
        }

        return $slug;

    }

    public function getTheme( $stylesheet = null ) {

        if ( ! array_key_exists( $stylesheet, $this->themes_cache ) ) {
            $this->themes_cache[ $stylesheet ] = wp_get_theme( $stylesheet );
        }

        return $this->themes_cache[ $stylesheet ];

    }

    public function doInitWidgets() {

        foreach ( $this->sidebars as $sidebar ) {
            register_sidebar( $sidebar );
        }
    }

    /**
     * @param $component_name
     *
     * @return ComponentInterface|null
     * @throws Exception
     */
    public function get( $component_name ) {


        $component = $this->repository->getByName( $component_name );

        if ( ! $component ) {
            throw new Exception( "Null component: `{$component_name}`" );
        }


        return $component;
    }

    /**
     * @return ComponentsRepository
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * @param ComponentsRepository $repository
     */
    public function setRepository( $repository ) {
        $this->repository = $repository;
    }

    public function getVersion() {
        $theme = $this->getTheme();
        if ( $theme->get( 'Template' ) ) {
            $theme = $this->getTheme( $theme->get( 'Template' ) );
        }

        return $theme->get( 'Version' );
    }

    public function getTextDomain() {
        $theme = $this->getTheme();
        if ( $theme->get( 'Template' ) ) {
            $theme = $this->getTheme( $theme->get( 'Template' ) );
        }

        return $theme->get( 'TextDomain' );
    }

    /**
     * @return Customizer
     */
    public function getCustomizer() {
        return $this->customizer;
    }

    /**
     * @param string $feature
     * @param bool $args
     *
     * @return Theme
     */
    public function add_theme_support( $feature, $args = true ) {

        if ( $args !== true ) {
            add_theme_support( $feature, $args );
        } else {
            add_theme_support( $feature );
        }

        return $this;
    }

    /**
     * @param string $feature
     * @param bool $args
     *
     * @return Theme
     */
    public function register_menus( $menus ) {
        $this->registered_menus = array_merge( $this->registered_menus, $menus );

        return $this;
    }

    public function register_sidebars( $sidebar ) {

        $this->sidebars = array_merge( $this->sidebars, $sidebar );

        return $this;
    }

    public function contentWidth( $width = 1200 ) {
        global $content_width;
        $content_width = $width;
    }
}
