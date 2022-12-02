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
    }
}

