<?php

namespace ColibriWP\SiteLeadsThemeKit\Customizer\Sections;


class SiteLeadsSection extends \WP_Customize_Section {

    public $type = 'colibriwp_siteleads_section';

    protected function render_template() {
        ?>
        <li id="accordion-section-{{ data.id }}" class="accordion-section control-section control-section-{{ data.type }}">
            <h3 class="accordion-section-title">
                <button type="button" class="accordion-trigger" aria-expanded="false" aria-controls="{{ data.id }}-content">
                    <svg fill="none" viewBox="0 0 39 39" xmlns="http://www.w3.org/2000/svg" style="width: 1.5em; margin-right: 10px;">
                        <circle class="color-element" cx="19.439" cy="19.439" r="19.439" fill="currentColor"></circle>
                        <path d="M28.465 23.99c-1.641-.15-3.133-.597-4.625-1.194-.746-.298-1.64 0-1.939.746l-1.044 1.79c-3.133-1.64-5.669-4.326-7.31-7.31l1.94-1.044c.745-.298 1.044-1.193.745-1.94-.596-1.49-1.044-3.132-1.193-4.624 0-.746-.746-1.342-1.492-1.342h-3.73c-.447 0-.745.298-.745.746 0 2.983.596 5.967 1.94 8.652 1.938 4.177 5.37 7.459 9.397 9.398 2.685 1.343 5.669 1.94 8.652 1.94.448 0 .746-.3.746-.747v-3.58c0-.895-.596-1.492-1.342-1.492z" fill="#FFF"></path>
                    </svg>
                    <span>
                          {{ data.title }}
                    </span>

                </button>
            </h3>
            <ul class="accordion-section-content" id="{{ data.id }}-content">
                <li class="customize-section-description-container section-meta <# if ( data.description_hidden ) { #>customize-info<# } #>">
                    <div class="customize-section-title">
                        <button class="customize-section-back" tabindex="-1">
							<span class="screen-reader-text">
								<?php
                                /* translators: Hidden accessibility text. */
                                _e( ' Back', 'colibri-wp' );
                                ?>
							</span>
                        </button>
                        <h3>
							<span class="customize-action">
								{{{ data.customizeAction }}}
							</span>
                            {{ data.title }}
                        </h3>
                        <# if ( data.description && data.description_hidden ) { #>
                        <button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false"><span class="screen-reader-text">
								<?php
                                /* translators: Hidden accessibility text. */
                                _e( 'Help', 'colibriwp');
                                ?>
							</span></button>
                        <div class="description customize-section-description">
                            {{{ data.description }}}
                        </div>
                        <# } #>

                        <div class="customize-control-notifications-container"></div>
                    </div>

                    <# if ( data.description && ! data.description_hidden ) { #>
                    <div class="description customize-section-description">
                        {{{ data.description }}}
                    </div>
                    <# } #>
                </li>
            </ul>
        </li>
        <?php
    }
}



