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
        // Unhook default cache cleaning mechanism.
        remove_action( 'switch_theme', array( 'WP_Theme_JSON_Resolver', 'clean_cached_data' ) );
        remove_action( 'start_previewing_theme', array( 'WP_Theme_JSON_Resolver', 'clean_cached_data' ) );

        // Hook override cache cleaning mechanism.
        add_action( 'switch_theme', 'wp_hotfix_611_wp_clean_theme_json_caches' );
        add_action( 'start_previewing_theme', 'wp_hotfix_611_wp_clean_theme_json_caches' );
        add_action( 'plugins_loaded', 'wp_hotfix_611_wp_add_non_persistent_theme_json_cache_group' );
    }
}

/**
 * Clean the caches under the theme_json group.
 */
function wp_hotfix_611_wp_clean_theme_json_caches() {
    wp_cache_delete( 'wp_get_global_stylesheet', 'theme_json' );
    WP_Theme_JSON_Resolver::clean_cached_data();
}

/**
 * Tell cache mechanisms not to persist theme.json data across requests for data
 * stored under the wp_get_global_stylesheet cache group.
 */
function wp_hotfix_611_wp_add_non_persistent_theme_json_cache_group() {
    wp_cache_add_non_persistent_groups( 'theme_json' );
}
