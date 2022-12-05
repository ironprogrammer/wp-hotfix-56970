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
		// Unhook default global styles enqueue.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

		// Hook override global styles enqueue.
		add_action( 'wp_enqueue_scripts', 'wp_hotfix_611_enqueue_global_styles' );
		add_action( 'wp_footer', 'wp_hotfix_611_enqueue_global_styles', 1 );

		// Hook override for block editor styles.
		add_filter( 'block_editor_settings_all', 'wp_hotfix_611_get_block_editor_settings', PHP_INT_MAX );

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
 * Override wp_enqueue_global_styles in wp-includes/script-loader.php.
 *
 * Enqueues the global styles defined via theme.json.
 */
function wp_hotfix_611_enqueue_global_styles() {
	$separate_assets  = wp_should_load_separate_core_block_assets();
	$is_block_theme   = wp_is_block_theme();
	$is_classic_theme = ! $is_block_theme;

	/*
	 * Global styles should be printed in the head when loading all styles combined.
	 * The footer should only be used to print global styles for classic themes with separate core assets enabled.
	 *
	 * See https://core.trac.wordpress.org/ticket/53494.
	 */
	if (
		( $is_block_theme && doing_action( 'wp_footer' ) ) ||
		( $is_classic_theme && doing_action( 'wp_footer' ) && ! $separate_assets ) ||
		( $is_classic_theme && doing_action( 'wp_enqueue_scripts' ) && $separate_assets )
	) {
		return;
	}

	/*
	 * If loading the CSS for each block separately, then load the theme.json CSS conditionally.
	 * This removes the CSS from the global-styles stylesheet and adds it to the inline CSS for each block.
	 * This filter must be registered before calling wp_get_global_stylesheet();
	 */
	add_filter( 'wp_theme_json_get_style_nodes', 'wp_filter_out_block_nodes' );

	$stylesheet = wp_hotfix_611_get_global_stylesheet();

	if ( empty( $stylesheet ) ) {
		return;
	}

	wp_register_style( 'global-styles', false, array(), true, true );
	wp_add_inline_style( 'global-styles', $stylesheet );
	wp_enqueue_style( 'global-styles' );

	// Add each block as an inline css.
	wp_add_global_styles_for_blocks();
}

/**
 * Override wp_get_global_stylesheet in wp-includes/global-styles-and-settings.php.
 *
 * Returns the stylesheet resulting of merging core, theme, and user data.
 *
 * @param array $types Types of styles to load. Optional.
 *                     It accepts 'variables', 'styles', 'presets' as values.
 *                     If empty, it'll load all for themes with theme.json support
 *                     and only [ 'variables', 'presets' ] for themes without theme.json support.
 * @return string Stylesheet.
 */
function wp_hotfix_611_get_global_stylesheet( $types = array() ) {
	// Ignore cache when `WP_DEBUG` is enabled, so it doesn't interfere with the theme developers workflow.
	$can_use_cached = empty( $types ) && ! WP_DEBUG;
	$cache_key      = 'wp_get_global_stylesheet';
	$cache_group    = 'theme_json';
	if ( $can_use_cached ) {
		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( $cached ) {
			return $cached;
		}
	}
	$tree                = WP_Theme_JSON_Resolver::get_merged_data();
	$supports_theme_json = WP_Theme_JSON_Resolver::theme_has_support();
	if ( empty( $types ) && ! $supports_theme_json ) {
		$types = array( 'variables', 'presets', 'base-layout-styles' );
	} elseif ( empty( $types ) ) {
		$types = array( 'variables', 'styles', 'presets' );
	}

	/*
	 * If variables are part of the stylesheet, then add them.
	 * This is so themes without a theme.json still work as before 5.9:
	 * they can override the default presets.
	 * See https://core.trac.wordpress.org/ticket/54782
	 */
	$styles_variables = '';
	if ( in_array( 'variables', $types, true ) ) {
		/*
		 * Only use the default, theme, and custom origins. Why?
		 * Because styles for `blocks` origin are added at a later phase
		 * (i.e. in the render cycle). Here, only the ones in use are rendered.
		 * @see wp_add_global_styles_for_blocks
		 */
		$origins          = array( 'default', 'theme', 'custom' );
		$styles_variables = $tree->get_stylesheet( array( 'variables' ), $origins );
		$types            = array_diff( $types, array( 'variables' ) );
	}

	/*
	 * For the remaining types (presets, styles), we do consider origins:
	 *
	 * - themes without theme.json: only the classes for the presets defined by core
	 * - themes with theme.json: the presets and styles classes, both from core and the theme
	 */
	$styles_rest = '';
	if ( ! empty( $types ) ) {
		/*
		 * Only use the default, theme, and custom origins. Why?
		 * Because styles for `blocks` origin are added at a later phase
		 * (i.e. in the render cycle). Here, only the ones in use are rendered.
		 * @see wp_add_global_styles_for_blocks
		 */
		$origins = array( 'default', 'theme', 'custom' );
		if ( ! $supports_theme_json ) {
			$origins = array( 'default' );
		}
		$styles_rest = $tree->get_stylesheet( $types, $origins );
	}
	$stylesheet = $styles_variables . $styles_rest;
	if ( $can_use_cached ) {
		wp_cache_set( $cache_key, $stylesheet, $cache_group );
	}
	return $stylesheet;
}

/**
 * Add styles to the block editor settings.
 *
 * @param array $settings Existing block editor settings.
 * @return array New block editor settings.
 */
function wp_hotfix_611_get_block_editor_settings( $settings ) {
	// Recreate global styles.
	$new_global_styles = array();
	$presets           = array(
		array(
			'css'            => 'variables',
			'__unstableType' => 'presets',
			'isGlobalStyles' => true,
		),
		array(
			'css'            => 'presets',
			'__unstableType' => 'presets',
			'isGlobalStyles' => true,
		),
	);
	foreach ( $presets as $preset_style ) {
		$actual_css = wp_hotfix_611_get_global_stylesheet( array( $preset_style['css'] ) );
		if ( '' !== $actual_css ) {
			$preset_style['css'] = $actual_css;
			$new_global_styles[] = $preset_style;
		}
	}

	if ( WP_Theme_JSON_Resolver::theme_has_support() ) {
		$block_classes = array(
			'css'            => 'styles',
			'__unstableType' => 'theme',
			'isGlobalStyles' => true,
		);
	} else {
		// If there is no `theme.json` file, ensure base layout styles are still available.
		$block_classes = array(
			'css'            => 'base-layout-styles',
			'__unstableType' => 'base-layout',
			'isGlobalStyles' => true,
		);
	}
	$styles_css = wp_hotfix_611_get_global_stylesheet( array( $block_classes['css'] ) );
	if ( '' !== $styles_css ) {
		$block_classes['css'] = $styles_css;
		$new_global_styles[]  = $block_classes;
	}

	$settings['styles'] = array_merge( $new_global_styles, get_block_editor_theme_styles() );

	return $settings;
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
