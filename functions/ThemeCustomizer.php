<?php
/**
 * Add and configure Theme Customizer options for this theme (non-admin settings).
 * Relies implementation in SDES_Customizer_Helper.
 */

namespace SDES\ServicesTheme\ThemeCustomizer;
use \WP_Customize_Control;
use \WP_Customize_Color_Control;
require_once( get_stylesheet_directory() . '/functions/class-sdes-customizer-helper.php' );
	use SDES\CustomizerControls\SDES_Customizer_Helper;
require_once( get_stylesheet_directory() . '/functions/classes-wp-customize-control.php' );
	use SDES\CustomizerControls\Textarea_CustomControl;
	use SDES\CustomizerControls\Phone_CustomControl;
require_once( get_stylesheet_directory() . '/functions/class-sdes-static.php' );
	use SDES\SDES_Static as SDES_Static;


/**
 * Add component settings.
 */
require_once( get_stylesheet_directory() . '/header-settings.php' );
require_once( get_stylesheet_directory() . '/footer-settings.php' );
require_once( get_stylesheet_directory() . '/functions/class-weatherbox.php' );

/**
 * Removes the core 'Menus' panel from the Customizer.
 *
 * @see https://developer.wordpress.org/reference/hooks/customize_loaded_components/#comment-1005
 * @param array $components Core Customizer components list.
 * @return array (Maybe) modified components list.
 */
function wpdocs_remove_nav_menus_panel( $components ) {
	$i = array_search( 'nav_menus', $components );
	if ( false !== $i ) {
		unset( $components[ $i ] );
	}
	return $components;
}
add_filter( 'customize_loaded_components', __NAMESPACE__.'\wpdocs_remove_nav_menus_panel' );
do_action( 'plugins_loaded' ); // The customize_loaded_components filter generally runs during the ‘plugins_loaded’ action.

/**
 * Defines all of the sections, settings, and controls for the various
 * options introduced into the Theme Customizer
 *
 * @see http://developer.wordpress.org/themes/advanced-topics/customizer-api/ WP-Handbook: The Customizer API
 * @see http://codex.wordpress.org/Theme_Customization_API WP-Codex: Theme Customization API
 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/customize_register WP-Codex: customize_register()
 * @see http://codex.wordpress.org/Class_Reference/WP_Customize_Control WP-Codex: class WP_Customize_Control
 * @see http://codex.wordpress.org/Data_Validation WP-Codex: Data Validation
 * @param   object $wp_customizer    A reference to the WP_Customize_Manager Theme Customizer.
 */
function register_theme_customizer( $wp_customizer ) {

	// Build-in sections.
	add_to_section_TitleAndTagline( $wp_customizer );
	$wp_customizer->remove_section( 'colors' );

	add_section_home_custom( $wp_customizer );

	add_section_service_profile( $wp_customizer );

}
add_action( 'customize_register', __NAMESPACE__.'\register_theme_customizer' );

/**
 * Register taglineURL option with the built-in `title_tagline` section, add settings and controls.
 */
function add_to_section_TitleAndTagline( $wp_customizer, $args = null ) {
	$section = 'title_tagline';

	// Sitetitle Anchor Max Width
	$sitetitle_anchor_maxwidth_args = $args['services_theme-sitetitle_anchor_maxwidth'];
	SDES_Static::set_default_keyValue_array( $sitetitle_anchor_maxwidth_args, array(
		'description' => 'Default max-width: "360px"',
		'transport' => 'refresh',
		'default' => '360px',
		'sanitize_callback' => 'esc_attr',
	));
	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-sitetitle_anchor_maxwidth',	// Id.
		'Title Max Width',				// Label.
		$section,						// Section.
		$sitetitle_anchor_maxwidth_args	// Arguments array.
	);
}

function add_section_home_custom( $wp_customizer, $args = null ) {
	/* SECTION */
	$section = 'services_theme-home_custom';
	$wp_customizer->add_section(
		$section,
		array(
			'title'    => 'Home Customization',
			'priority' => 200,
			'panel' => $args['panelId'],
		)
	);

	$wp_customizer->add_setting(
		'gtm_id'
	);
	$wp_customizer->add_control(
		'gtm_id',
		array(
			'type'        => 'text',
			'label'       => 'Google Tag Manager ID',
			'description' => 'Example: <em>MTG-ABC123</em>. Leave blank for development.',
			'section'     => $section,
			'priority'    => 5,  // Default control priority is 10.
			)
	);

	/** ARGS */
	$frontsearch_args = $args['services_theme-frontsearch_lead'];
	SDES_Static::set_default_keyValue_array( $frontsearch_args, array(
		'sanitize_callback' => 'wp_kses_post',
		'sanitize_js_callback' => 'wp_kses_post',
	));

	$placeholder_args = $args['services_theme-frontsearch_placeholder'];
	SDES_Static::set_default_keyValue_array( $placeholder_args, array(
		'sanitize_callback' => 'wp_kses_post',
		'sanitize_js_callback' => 'wp_kses_post',
	));
	$search_default_args = $args['services_theme-search_default'];
	SDES_Static::set_default_keyValue_array( $search_default_args, array(
		'sanitize_callback' => 'esc_attr',
		'sanitize_js_callback' => 'esc_attr',
		'default' => '',
		'description' => "The default search term for the home page's results.",
	));
	$services_limit_args = $args['services_theme-services_limit'];
	SDES_Static::set_default_keyValue_array( $services_limit_args, array(
		'sanitize_callback' => 'esc_attr',
		'sanitize_js_callback' => 'esc_attr',
		'default' => 7,
		'description' => 'Limit the initial number of services to display.',
	));
	$calendar_args = $args['services_theme-academic_cal_feed_url'];
	SDES_Static::set_default_keyValue_array( $calendar_args, array(
		'sanitize_callback' => 'esc_url',
		'sanitize_js_callback' => 'esc_url',
		'default' => 'http://calendar.ucf.edu/json',
	));

	/** FIELDS */
	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-frontsearch_lead',	// Id.
		'Search Lead Text',			// Label.
		$section,				// Section.
		$frontsearch_args		// Arguments array.
	);

	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-frontsearch_placeholder',	// Id.
		'Search Placeholder Text',	// Label.
		$section,				// Section.
		$placeholder_args		// Arguments array.
	);

	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-search_default',	// Id.
		'Search Default',			// Label.
		$section,				// Section.
		$search_default_args	// Arguments array.
	);

	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-services_limit',	// Id.
		'Services Limit',			// Label.
		$section,				// Section.
		$services_limit_args	// Arguments array.
	);

	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-academic_cal_feed_url',	// Id.
		'Front Page Calendar Feed',	// Label.
		$section,				// Section.
		$calendar_args		// Arguments array.
	);
}

function add_section_service_profile( $wp_customizer, $args = null ) {
	/* SECTION */
	$section = 'services_theme-service_profiles';
	$wp_customizer->add_section(
		$section,
		array(
			'title'    => 'Service Profiles',
			'priority' => 900,
			'panel' => $args['panelId'],
		)
	);

	/** ARGS */
	$profile_image_default_args = $args['services_theme-profile_image_default'];
	SDES_Static::set_default_keyValue_array( $profile_image_default_args, array(
		'description' => 'A default image for the header of a student profile.',
	));

	$closing_soon_args = $args['services_theme-closing_soon_minutes'];
	SDES_Static::set_default_keyValue_array( $closing_soon_args, array(
		'sanitize_callback' => 'wp_kses_post',
		'sanitize_js_callback' => 'wp_kses_post',
		'description' => 'Number of minutes to show "closing soon" styling for services before they close.',
		'default' => 60,
	));

	/** FIELDS */
	SDES_Customizer_Helper::add_setting_and_control(
		'Image_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-profile_image_default',	// Id.
		'Profile Header Image',			// Label.
		$section,				// Section.
		$profile_image_default_args		// Arguments array.
	);

	SDES_Customizer_Helper::add_setting_and_control('WP_Customize_Control', // Control Type.
		$wp_customizer,			// WP_Customize_Manager.
		'services_theme-closing_soon_minutes',	// Id.
		'Closing Soon Minutes',			// Label.
		$section,				// Section.
		$closing_soon_args		// Arguments array.
	);

}

// Allow AJAX updates to theme from Theme Customizer interface by
// using the Theme Customizer API in javascript.
// Enables $wp_customizer->add_setting() with 'transport'=>'postMessage'.
/**
 * Registers and enqueues the `theme-customizer.js` file responsible
 * for handling the transport messages for the Theme Customizer.
 */
function theme_customizer_live_preview() {
	wp_enqueue_script(
	    'theme-customizer-postMessage',
	    get_template_directory_uri() . '/js/theme-customizer.js',
	    array( 'jquery', 'customize-preview' ),
	    '1.0.0',
	    true
	);
}
add_action( 'customize_preview_init', __NAMESPACE__.'\theme_customizer_live_preview' );
