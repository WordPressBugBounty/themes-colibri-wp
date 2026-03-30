<?php


require_once __DIR__ . '/vendor/autoload.php';

\ColibriWP\SiteLeadsThemeKit\Bootstrap::init_data(
    array(
        'root_dir' => trailingslashit( wp_normalize_path( __DIR__ ) ),
        'root_url' => trailingslashit( get_template_directory_uri() ) . str_replace(
                trailingslashit( wp_normalize_path( get_template_directory() ) ),
                '',
                trailingslashit( wp_normalize_path( __DIR__ ) )
            ),
    )
);
