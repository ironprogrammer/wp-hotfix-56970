<?php
/**
 * Plugin Name: Hotfix for Trac 56970
 * Description: Cleans theme.json cache. See <a href="https://core.trac.wordpress.org/ticket/56970">Trac 56970</a>.
 * Version: 0.1
 */

// Initialize plugin.
WP_Hotfix_56970_Controller::init();

class WP_Hotfix_56970_Controller {
    public static function init() {
        add_action( 'init', 'wp_hotfix_56970_init' );
    }
}

function wp_hotfix_56970_init() {
    global $wp_version;

    // Issue described in Trac 56970 only affects upgrade from < 6.1 to 6.1.1.
    if ( '6.1.1' === $wp_version ) {
    }
}

