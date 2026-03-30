<?php
namespace ColibriWP\SiteLeadsThemeKit;


use ColibriWP\SiteLeadsThemeKit\Customizer\Sections\SiteLeadsSection;
use ColibriWP\SiteLeadsThemeKit\Customizer\Controls\SiteLeadsIntegrationButton;


class SiteLeads {


    use Singleton;

    const PLUGIN_SLUG = 'siteleads';
    const PLUGIN_FILE = 'siteleads/siteleads.php';
    const PRO_PLUGIN_FILE = 'siteleads-pro/siteleads.php';
    const THEME_ALREADY_ACTIVATED_FLAG = 'theme_already_activated_flag';
    const ENABLE_SITE_LEADS_INTEGRATION_FLAG = 'enable_site_leads_integration';

    protected function __construct() {
        add_action('after_switch_theme', array($this, 'after_theme_activation'));

        if(!$this->get_siteleads_integration_is_active()) {
            return;
        }

        add_action( 'customize_register', array( $this, 'add_customizer_controls_and_settings' ) );
        add_action( 'customize_controls_enqueue_scripts', array( $this, 'register_customizer_assets' ) );

        Hooks::add_wp_ajax(
            'siteleads_init_setup',
            array( $this, 'ajax_siteleads_plugin_init_setup' )
        );
        Hooks::add_wp_ajax(
            'siteleads_toggle_enabled',
            array( $this, 'ajax_siteleads_plugin_toggle_enabled' )
        );

        add_action( 'wp_footer', array( $this, 'print_call_icon_no_site_leads' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

    }


    public static function show_install_siteleads_recommendation() {
        $instance = static::get_instance();

        $result =  $instance->get_siteleads_integration_is_active() && !$instance->get_site_leads_plugin_is_active();
        return $result;
    }

    public static function get_current_theme_name() {
        $theme = wp_get_theme();
        return $theme->get('Name');
    }
    public function get_siteleads_integration_is_active() {
        return   Flags::get(static::ENABLE_SITE_LEADS_INTEGRATION_FLAG);
    }

    public function run_first_time_activation_hooks() {
        Flags::set(static::ENABLE_SITE_LEADS_INTEGRATION_FLAG, true);
    }
    public function on_activation() {
        $this->run_first_time_activation_hooks();

    }
    function get_theme_has_changes() {
        $theme_options = get_option('theme_mods_' . get_option('stylesheet'));
        if(empty($theme_options)) {
            return false;
        }
        if(is_array($theme_options) && count($theme_options) > 5) {
            return true;
        }

        return false;
    }
    public function after_theme_activation() {
        if(Flags::get(static::THEME_ALREADY_ACTIVATED_FLAG)) {
           return;
        }
        Flags::set(static::THEME_ALREADY_ACTIVATED_FLAG, true);
        //only run theme activation logic once while no changes are made to the theme options.
        //The flag is there to make sure the logic only runs once. The check for theme changes
        //is there to handle the case of existing clients to not run logic for clients that already made changes
        if($this->get_theme_has_changes()) {
            return;
        }

        $this->on_activation();
    }
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            Theme::prefix( 'siteleads-style' ),
            Theme::get_url_path( '/assets/frontend/css/frontend-style.min.css' )
        );
        if ( $this->should_show_contact_widge_no_site_leads_active() ) {
            wp_enqueue_style(
                Theme::prefix( 'siteleads-style-no-plugin' ),
                Theme::get_url_path( '/assets/frontend/css/frontend-without-siteleads-plugin-style.min.css' )
            );
        }
    }

    public function register_customizer_assets() {

        wp_enqueue_script( Theme::prefix( 'siteleads-customizer-js' ), Theme::get_url_path( '/assets/customizer/js/customizer.min.js' ), array( 'jquery' ), false, true );
        wp_enqueue_style(
            Theme::prefix( 'siteleads-customizer-css' ),
            Theme::get_url_path( '/assets/customizer/css/customizer-style.min.css' )
        );
        $settings = $this->get_js_data();
        wp_add_inline_script(
            'jquery',
            sprintf(
                'window.colibriwpSiteLeadsCustomizerData = %s;',
                wp_json_encode( $settings )
            )
        );
    }

    public static function printSiteLeadsRecommendationPlugins() {
        ?>
          <div class="colibriwp-siteleads-recommendation-plugins-tooltip__container">
              <span><?php echo esc_html(__('plugins', 'colibri-wp')) ?></span>
              <div class="colibriwp-siteleads-recommendation-plugins-tooltip">
                    <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g
                                opacity="0.5" > <path
                                    d="M8.00016 14.9544C11.6821 14.9544 14.6668 11.9697 14.6668 8.28776C14.6668 4.60586 11.6821 1.62109 8.00016 1.62109C4.31826 1.62109 1.3335 4.60586 1.3335 8.28776C1.3335 11.9697 4.31826 14.9544 8.00016 14.9544Z"
                                    stroke="#747C97" stroke-width="1.33" stroke-linecap="round"
                                    stroke-linejoin="round"></path> <path d="M8 10.9538V8.28711" stroke="#747C97"
                                                                          stroke-width="1.33" stroke-linecap="round"
                                                                          stroke-linejoin="round"></path> <path
                                    d="M8 5.62109H8.00667" stroke="#747C97" stroke-width="1.33" stroke-linecap="round"
                                    stroke-linejoin="round"></path> </g> <defs>
                            <clipPath id="clip0_1771_2646">
                                <rect width="16" height="16" fill="white"
                                        transform="translate(0 0.287109)">

                                </rect>
                            </clipPath>
                        </defs>
                    </svg>
                        <div class="colibriwp-siteleads-recommendation-plugins-tooltip__content">
                            <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item">
                                <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item__title">
                                    <?php echo esc_html(__('Colibri Page Builder (free)', 'colibri-wp'));?>
                                </div>
                                <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item__description">
                                    <?php echo esc_html(sprintf(__('Adds drag and drop functionality and many other features to the %s theme.', 'colibri-wp'),  static::get_current_theme_name()));?>
                                </div>
                            </div>

                            <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item">
                                <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item__title">
                                    <?php echo esc_html(__('SiteLeads (free)', 'colibri-wp'))?>
                                </div>
                                <div class="colibriwp-siteleads-recommendation-plugins-tooltip__item__description">
                                    <?php echo esc_html(__('Get more leads from your site by adding popular contact channels: Whatsapp, Messenger, Phone, AI Assistant, etc.', 'colibri-wp'))?>
                                </div>
                            </div>

                        </div>
                </div>
          </div>
            <?php
    }

    public function print_call_icon_no_site_leads() {
        if ( ! $this->should_show_contact_widge_no_site_leads_active() ) {
            return;
        }
        $phone = get_theme_mod( Theme::prefix( 'siteleads_number' ), '' );
        $phone_link = '#';
        $extra_link_attributes = '';
        if(!is_customize_preview() && !empty($phone) ) {
            $phone_link = "tel: $phone";
            $extra_link_attributes = 'target="_blank"';
        }
        ?>
        <div class="colibriwp-siteleads-for-theme-root --colibriwp-siteleads-for-theme-root-alignment-right">
            <div class="colibriwp-siteleads-for-theme-root-inner">
                <div class="colibriwp-siteleads-for-theme-fc-widgets siteleads-fc-widgets">
                    <div class="colibriwp-siteleads-for-theme-widget --colibriwp-siteleads-for-theme-root-alignment-right --sl-simple-view --medium-size --has-attention-effect" style="--sl-full-fab-size: calc(var(--sl-base-fab-size) + 0px * 2 );--sl-fab-border-width: 0px; --sl-shadow-3:0px 3px 3px -2px rgba(0, 0, 0, 0.15), 0px 3px 4px 0px rgba(0, 0, 0, 0.105), 0px 1px 8px 0px rgba(0, 0, 0, 0.09)" data-widget-id="widget-1770032923949" data-view="simple" data-device="desktop" data-has-agent="false" data-single-channel="true">
                        <div class="colibriwp-siteleads-for-theme-widget__channels" style="grid-template-columns: 1fr;--desktop-widget-color: #029900;box-shadow: var(--sl-shadow-3);border-radius:100%">
                            <div class="colibriwp-siteleads-for-theme-channel__wrapper" style="color: red; --sl-icon-color: #029900; transition-duration: 0s; opacity: 1;">
                                <a class="colibriwp-siteleads-for-theme-channel colibriwp-siteleads-for-theme-channel__phone" rel="nofollow noopener" href="<?php echo esc_attr( $phone_link ); ?>" <?php echo esc_attr($extra_link_attributes); ?> data-channel="phone" style="color: #029900;border-radius: 100%;background-color: #029900;box-shadow: var(--sl-shadow-3);border-width: 0px;--sl-fab-border-width: 0px;border-color: #2363EB">
									<span class="colibriwp-siteleads-for-theme-channel__icon" style="color: #029900;background: var(--sl-icon-color, transparent);border-radius: 100%;box-shadow: var(--sl-shadow-3)">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path fill="#FFF" d="M8.407 3.337a1.524 1.524 0 0 0-1.803-.885l-.207.057c-2.422.66-4.492 3.007-3.888 5.865A17.06 17.06 0 0 0 15.626 21.49c2.861.608 5.205-1.466 5.865-3.889l.056-.206a1.52 1.52 0 0 0-.88-1.804l-3.65-1.518a1.52 1.52 0 0 0-1.762.442l-1.448 1.77A12.84 12.84 0 0 1 7.83 10.1l1.657-1.35c.522-.424.698-1.14.443-1.763z"></path>
                                    </svg>
									</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }





    public function ajax_siteleads_plugin_toggle_enabled() {
        check_ajax_referer( Theme::prefix( 'siteleads_nonce' ) );
        if ( ! current_user_can( 'edit_theme_options' ) ) {
            wp_send_json_error( __( 'Not allowed', 'colibri-wp' ), 400 );
        }
        if ( ! $this->get_site_leads_plugin_is_active() ) {
            wp_send_json_error( __( 'Required plugin is missing', 'colibri-wp' ), 400 );
        }
        if ( ! class_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager' ) ||
            ! method_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager', 'toggleWidgetEnabled' ) ||
            ! method_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager', 'getWidgetListOptions' )
        ) {
            wp_send_json_error(__('Required class or functions are missing', 'colibri-wp'), 400);
        }


        if ( ! isset( $_POST['enabled'] ) ) {
            wp_send_json_error( __( 'Missing required parameter: enabled', 'colibri-wp' ), 400 );
        }
        if ( ! isset( $_POST['widget_id'] ) ) {
            wp_send_json_error( __( 'Missing required parameter: widget_id', 'colibri-wp' ), 400 );
        }
        // Sanitize the input
        $raw_enabled = sanitize_text_field( $_POST['enabled'] );

        $enabled = in_array( $raw_enabled, array( 'true', '1' ), true );



        $widget_id =  sanitize_text_field( $_POST['widget_id'] );
        if ( empty( $widget_id ) || !is_string($widget_id)) {


            //if no widget_id is provided try to find the first and only widget id if more or less are present skip this logic
            $widgets_lists =  \SiteLeads\Features\Widgets\FCWidgetsManager::getWidgetListOptions();
            if(!empty($widgets_lists) && is_array($widgets_lists) && count($widgets_lists) === 1) {
                $first_widget = $widgets_lists[0];
                $first_widget_id = isset($first_widget['value']) ? $first_widget['value'] : null;
                if(!empty($first_widget_id)) {
                    $widget_id = $first_widget_id;
                }
            }

            if(empty( $widget_id ) || !is_string($widget_id)) {
                wp_send_json_error( __( 'Missing widget id', 'colibri-wp' ), 400 );
            }


        }


        \SiteLeads\Features\Widgets\FCWidgetsManager::toggleWidgetEnabled( $widget_id, $enabled );
        wp_send_json_success();
    }
    public function ajax_siteleads_plugin_init_setup() {
        check_ajax_referer( Theme::prefix( 'siteleads_nonce' ) );
        if ( ! current_user_can( 'edit_theme_options' ) ) {
            wp_send_json_error( __( 'Not allowed', 'colibri-wp' ), 400 );
        }

        if ( ! $this->get_site_leads_plugin_is_active() ) {
            wp_send_json_error( __( 'Required plugin is missing', 'colibri-wp' ), 400 );
        }

        //in case of failures only try init once
        $already_setup = Flags::get( 'siteLeadsInstalled', null );

        if ( ! empty( $already_setup ) ) {
            wp_send_json_success( $this->get_site_leads_settings_page_url() );
        } else {
            Flags::set( 'siteLeadsInstalled', true );
        }
        if ( ! class_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager' ) ||
            ! method_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager', 'createDefaultWidgetWithOnlyPhoneChannel' ) ||
            ! method_exists( '\SiteLeads\Features\Widgets\FCWidgetsManager', 'createDefaultWidgetWithPhoneWhatsappAndEmail' )
        ) {
            wp_send_json_error(__('Required class or functions are missing', 'colibri-wp'), 400);
        }
        $phone_nr = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

        try {
            if(empty($phone_nr)) {
                //try to get the phone number from the customizer for cases like wp admin screen
                $customzier_phone_nr = get_theme_mod( Theme::prefix( 'siteleads_number' ), '' );
                if(!empty($customzier_phone_nr)) {
                    $phone_nr = $customzier_phone_nr;
                }
            }
            $enabled = get_theme_mod( Theme::prefix( 'show_contact_phone' ), true );
            $start_source = isset( $_POST['start_source'] ) ? sanitize_text_field( $_POST['start_source'] ) : '';
            $options = [];
            if(!empty($start_source) && is_string($start_source)) {
                $options['start_source'] = $start_source;
            }
            $options['isEnabled'] = $enabled;


            //if phone is provided create phone only widget otherwise create the 3 widget default
            if(!empty($phone_nr)) {
                $method = new \ReflectionMethod(
                    \SiteLeads\Features\Widgets\FCWidgetsManager::class,
                    'createDefaultWidgetWithOnlyPhoneChannel'
                );

                $number_of_parameters = $method->getNumberOfParameters();
                if ($number_of_parameters === 2) {
                    \SiteLeads\Features\Widgets\FCWidgetsManager::createDefaultWidgetWithOnlyPhoneChannel($phone_nr, $options);
                } else {
                    \SiteLeads\Features\Widgets\FCWidgetsManager::createDefaultWidgetWithOnlyPhoneChannel($phone_nr);
                }
            } else {
                $method = new \ReflectionMethod(
                    \SiteLeads\Features\Widgets\FCWidgetsManager::class,
                    'createDefaultWidgetWithPhoneWhatsappAndEmail'
                );

                $number_of_parameters = $method->getNumberOfParameters();
                if ($number_of_parameters === 1) {
                    \SiteLeads\Features\Widgets\FCWidgetsManager::createDefaultWidgetWithPhoneWhatsappAndEmail($options);
                } else {
                    \SiteLeads\Features\Widgets\FCWidgetsManager::createDefaultWidgetWithPhoneWhatsappAndEmail();
                }
            }

            wp_send_json_success();

        } catch ( \Exception $e ) {

            wp_send_json_error( $e->getMessage(), 400 );
        }
    }

    public function is_siteleads_inactive() {
        return !$this->get_site_leads_plugin_is_active();
    }



    public function add_customizer_controls_and_settings( $wp_customize ) {
        $wp_customize->register_section_type(
            SiteLeadsSection::class
        );

        $wp_customize->add_section(
            new SiteLeadsSection(
                $wp_customize,
                Theme::prefix( 'contact_settings' ),
                array(
                    'title'    => __( 'Contact Settings', 'colibri-wp' ),
                    'priority' => 9999,
                )
            )
        );
        $dummy_setting_id = Theme::prefix( 'install_button_dummy' );

        $wp_customize->add_setting(
            $dummy_setting_id,
            array(
                'default'           => '',
                'type'              => 'theme_mod',
                'capability'        => 'edit_theme_options',
                'sanitize_callback' => 'sanitize_text_field', // Required even for dummy setting
                'transport'         => 'postMessage',
            )
        );

        $wp_customize->add_control(
            new SiteLeadsIntegrationButton(
                $wp_customize,
                Theme::prefix( 'install_button' ),
                array(
                    'section'  => Theme::prefix( 'contact_settings' ),
                    'priority' => 20,
                    'settings' => $dummy_setting_id,
                )
            )
        );
        /**
         * Phone number setting
         */
        $wp_customize->add_setting(
            Theme::prefix( 'siteleads_number' ),
            array(
                'default'           => '',
                'type'              => 'theme_mod',
                'sanitize_callback' => array( $this, 'sanitize_phone_number' ),
                'transport'         => 'postMessage',

            )
        );

        /**
         * Phone number control
         */
        $wp_customize->add_control(
            Theme::prefix( 'siteleads_number' ),
            array(
                'label'       => __( 'Phοne Number', 'colibri-wp' ),
                'section'     => Theme::prefix( 'contact_settings' ),
                'type'        => 'text',
                'priority'    => 10,
                'active_callback' => array( $this, 'is_siteleads_inactive' ),
            )
        );

        $wp_customize->add_setting(
            Theme::prefix( 'show_contact_phone' ),
            array(
                'default'           => true,
                'type'              => 'theme_mod',
                'sanitize_callback' => 'wp_validate_boolean',
                'transport'         => 'postMessage',
            )
        );

        $wp_customize->add_control(
            Theme::prefix( 'show_contact_phone' ),
            array(
                'label'    => __( 'Show Contact Widget', 'colibri-wp' ),
                'section'  => Theme::prefix( 'contact_settings' ),
                'type'     => 'checkbox',
                'priority' => 9
            )
        );

        if ( isset( $wp_customize->selective_refresh ) ) {
            $wp_customize->selective_refresh->add_partial(
                Theme::prefix( 'siteleads_number' ),
                array(
                    'selector'        => '.siteleads-fc-widgets',
                    'settings'        => array(
                        $dummy_setting_id,
                    ),
                    'render_callback' => function () {
                        // Re-render happens automatically via PHP output
                        return '';
                    },
                )
            );
        }
    }

    /**
     * Allow numbers, spaces and common phone symbols
     */
    public function sanitize_phone_number( $value ) {
        // First, run standard text sanitization to strip tags and trim whitespace
        $value = sanitize_text_field( $value );

        // Then apply the specific phone regex
        return preg_replace( '/[^0-9\+\-\(\)\s]/', '', $value );
    }

    public function get_site_leads_settings_page_url() {

        return add_query_arg(
            array(
                'page' => 'siteleads',
            ),
            admin_url( 'admin.php' )
        );
    }

    public function get_plugin_action_link($plugin_file) {

        return add_query_arg(
            array(
                'action'        => 'activate',
                'plugin'        => rawurlencode( $plugin_file ),
                'plugin_status' => 'all',
                'paged'         => '1',
                '_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $plugin_file ),
            ),
            esc_url( 'plugins.php' )
        );
    }
    public function get_plugin_status( $plugin_file ) {
        if ( is_plugin_active( $plugin_file ) ) {
            return 'active';
        }
        // Check if file exists to determine if installed
        $installed_plugins = get_plugins();
        if ( isset( $installed_plugins[ $plugin_file ] ) ) {
            return 'installed';
        }
        return 'not-installed';
    }

    public function get_js_data() {

        $plugin_slug = static::PLUGIN_SLUG;
        $pro_plugin_status = $this->get_plugin_status(static::PRO_PLUGIN_FILE);
        if($pro_plugin_status !== 'not-installed') {
            $plugin_file = static::PRO_PLUGIN_FILE;
            $plugin_status = $pro_plugin_status;
        } else {
            $plugin_file = static::PLUGIN_FILE;
            $plugin_status = $this->get_plugin_status(static::PLUGIN_FILE);
        }



        $site_leads_settings = array(
            'pluginSlug'                         => $plugin_slug,
            'pluginStatus'                       => $plugin_status,
            'activationLink'                     => $this->get_plugin_action_link($plugin_file),
            'siteLeadsNonce'                     => wp_create_nonce( Theme::prefix( 'siteleads_nonce' ) ),
            'themePrefix'                        => Theme::get_instance()->prefix( '' ),
            'siteLeadsInitWpAjaxHandle'          => Theme::prefix( 'siteleads_init_setup' ),
            'siteLeadsToggleEnabledWpAjaxHandle' => Theme::prefix( 'siteleads_toggle_enabled' ),
            'pluginSettingsUrl'                  => $this->get_site_leads_settings_page_url(),
            'adminPhpUrl'                        => admin_url('admin.php'),
            'contactSectionId'                   =>  Theme::prefix( 'contact_settings' ),
            'translations'                       => $this->get_js_text_translations(),
            'siteLeadsIntegrationIsEnabled'      => static::show_install_siteleads_recommendation(),
        );

        return $site_leads_settings;
    }

    public function get_js_text_translations() {
        return array(
            'notice_title_not_installed'             => __( 'For more contact channels & settings, please install the', 'colibri-wp' ),
            'notice_title_installed'             => __( 'For more contact channels & settings, please activate the', 'colibri-wp' ),
            'notice_title_active'             => __( 'Contact channels & settings can be found inside the', 'colibri-wp' ),

            'plugin_text'                      => __( 'plugin', 'colibri-wp' ),



            'notice_description_1'             => __( 'SiteLeads adds popular contact channels to your site:', 'colibri-wp' ),
            'notice_description_2'             => __( 'Whatsapp, Messenger, Phone, Email, AI Assistant, etc.', 'colibri-wp' ),
            'notice_description_3'             => __( 'Visitors can easily reach you and you never miss a lead.', 'colibri-wp' ),

            'info_notice_installing'            => __('Installing SiteLeads', 'colibri-wp'),
            'info_notice_activating'            => __('Activating SiteLeads', 'colibri-wp'),
            'info_notice_init'                  => __('Preparing SiteLeads', 'colibri-wp'),


            'install_button_text_active_plugin'     => __( 'Manage Contact Widget', 'colibri-wp' ),
            'install_button_text_installed_plugin'  => __( 'Activate SiteLeads', 'colibri-wp' ),
            'install_button_not_installed_plugin'   => __( 'Install SiteLeads', 'colibri-wp' ),

            'error_could_not_init_plugin_data'      => __( 'Could not initialise the SiteLeads plugin data', 'colibri-wp' ),
            'error_could_not_install_plugin'        => __( 'Could not install the SiteLeads plugin', 'colibri-wp' ),
            'error_could_not_activate_plugin'       => __( 'Could not activate the SiteLeads plugin', 'colibri-wp' ),
            'error_could_not_toggle_plugin_enabled' => __( 'Could not toggle Siteleads plugin status', 'colibri-wp' ),

            'installingSiteLeads' => __('Installing SiteLeads', 'colibri-wp'),
            'activatingSiteLeads' => __('Activating SiteLeads', 'colibri-wp'),
            'initSetupSiteLeads'  => __('Creating initial SiteLeads widget', 'colibri-wp')
        );
    }



    public function should_show_contact_widge_no_site_leads_active() {
        $site_leads_is_active = defined( 'SITELEADS_VERSION' );
        if ( $site_leads_is_active ) {
            return false;
        }
        if ( ! is_customize_preview() ) {
            $phone = get_theme_mod( Theme::prefix( 'siteleads_number' ), '' );
            if ( empty( $phone ) && $phone !== '0' ) {
                return false;
            }
        }

        $enabled = get_theme_mod( Theme::prefix( 'show_contact_phone' ), true );
        if ( ! $enabled ) {
            return false;
        }
        return true;
    }

    public function get_site_leads_plugin_is_active() {
        if(defined( 'SITELEADS_VERSION' )) {
            return true;
        }
        $files = [static::PLUGIN_FILE, static::PRO_PLUGIN_FILE];
        $is_active = false;
        foreach($files as $file) {
            if(is_plugin_active($file)) {
                $is_active = true;
            }
        }
        return $is_active;
    }
}
