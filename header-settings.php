<?php
/**
 * Helper classes for the Header - require from ThemeCustomizer.php to add action to 'customize_register' before it fires.
 *
 * Graphviz.gv: "header-settings.php" -> { "class-feedmanager.php"; "class-sdes-customizer-helper.php"; "class-sdes-static.php"; };
 *
 * @package SDES\ServicesTheme\ThemeCustomizer
 * @see https://github.com/UCF/Students-Theme/blob/2bf248dba761f0929823fd790120f74e92a52c2d/functions/config.php#L449-L502
 */

namespace SDES\ServicesTheme\ThemeCustomizer;

require_once( get_stylesheet_directory() . '/functions/class-feedmanager.php' );
	use FeedManager;

require_once( get_stylesheet_directory() . '/functions/rest-api.php' );
	use SDES\ServicesTheme\API;

require_once( get_stylesheet_directory() . '/custom-posttypes.php' );
	use SDES\ServicesTheme\PostTypes\Campaign;

require_once( get_stylesheet_directory() . '/functions/class-sdes-customizer-helper.php' );
	use SDES\CustomizerControls\SDES_Customizer_Helper;

require_once( get_stylesheet_directory() . '/functions/class-sdes-static.php' );
	use SDES\SDES_Static as SDES_Static;

/**
 * Class to define header settings and Theme Customizer controls.
 */
class Header_Settings {
	const HEADER_NAV_URL = 'http://www.ucf.edu/wp-json/ucf-rest-menus/v1/menus/52';
	const CLOUD_FONT_KEY = '//cloud.typography.com/730568/675644/css/fonts.css'; /* CSS Key relative to PROD project */

	/**
	 * Retrieve and cache remote feeds as json objects, e.g., services_theme-remote_menus_header_menu_feed.
	 *
	 * @see https://github.com/UCF/Students-Theme/blob/2bf248dba761f0929823fd790120f74e92a52c2d/functions.php#L42-L75
	 * @todo Evaluate general PHP alternatives to WP transients (PSR-6 caching).
	 */
	public static function get_remote_menu( $menu_name ) {
		global $wp_customize;
		$customizing = isset( $wp_customize );
		$result_name = $menu_name.'_json';
		$result = get_transient( $result_name );
		if ( false === $result || $customizing ) {
			$opts = array(
				'http' => array(
					'timeout' => 15,
				),
			);
			$context = stream_context_create( $opts );
			$file_location = SDES_Static::get_theme_mod_defaultIfEmpty( $menu_name.'_feed', self::HEADER_NAV_URL );
			if ( empty( $file_location ) ) {
				return;
			}
			$headers = get_headers( $file_location );
			$response_code = substr( $headers[0], 9, 3 );
			if ( '200' !== $response_code ) {
				return;
			}
			$result = json_decode( file_get_contents( $file_location, false, $context ) ); // @codingStandardsIgnoreLine WordPress.VIP.RestrictedFunctions.file_get_contents
			if ( ! $customizing ) {
				set_transient( $result_name, $result, (60 * 60 * 24) );
			}
		}
		return $result;
	}

	public static function register_header_settings( $wp_customize ) {
		// $panelId = 'header_panel';
		// $wp_customize->add_panel( $panelId, array(
		  // 'title' => __( 'Header' ),
		  // 'description' => 'Header Settings', // Include html tags such as <p>.
		  // 'priority' => 1000, // Mixed with top-level-section hierarchy.
		// ) );
		// $section_args = array( 'panelId' => $panelId );
		$section_args = array();

		static::add_section_webfonts( $wp_customize, $section_args );

		static::add_section_remote_menus( $wp_customize, $section_args );
	}

	/**
	 * Add a webfonts section to the Theme Customizer.
	 *
	 * @see https://github.com/UCF/Main-Site-Theme/blob/6610071f535ddf534e3b76c0db5098cb28600321/functions/config.php#L588-L598
	 */
	public static function add_section_webfonts( $wp_customize, $args = null ) {
		/* SECTION */
		$section = 'services_theme-webfonts';
		$wp_customize->add_section(
			$section,
			array(
				'title'    => 'Webfonts',
				'description' => '',
				'priority' => 900, // Set to 30 to be just below "Site Identity".
				'panel' => array_key_exists( 'panelId', $args ) ? $args['panelId'] : '',
			)
		);
		$wp_customize->add_setting(
			'services_theme-cloud_font_key',
			array( 'default'     => self::CLOUD_FONT_KEY, )
		);
		$wp_customize->add_control(
			'services_theme-cloud_font_key',
			array(
				'type'        => 'text',
				'label'       => 'Cloud.Typography CSS Key URL',
				'description' => 'The CSS Key provided by Cloud.Typography for this project. <strong>Only include the value in the "href" portion of the link
					tag provided; e.g. "//cloud.typography.com/000000/000000/css/fonts.css".</strong><br/><br/>NOTE: Make sure the Cloud.Typography
					project has been configured to deliver fonts to this site\'s domain.<br/>
					See the <a target="_blank" href="http://www.typography.com/cloud/user-guide/managing-domains">Cloud.Typography docs on managing domains</a> for more info.',
				'section'     => $section,
			)
		);
	}

	public static function add_section_remote_menus( $wp_customize, $args = null ) {
		/* SECTION */
		$section = 'services_theme-remote_menus';
		$wp_customize->add_section(
			$section,
			array(
				'title'    => 'Remote Menus',
				'description' => '',
				'priority' => 1000, // Set to 30 to be just below "Site Identity".
				'panel' => array_key_exists( 'panelId', $args ) ? $args['panelId'] : '',
			)
		);
		$wp_customize->add_setting(
			'services_theme-remote_menus_header_menu_feed',
			array(
				'default'     => self::HEADER_NAV_URL,
			)
		);
		$wp_customize->add_control(
			'services_theme-remote_menus_header_menu_feed',
			array(
				'type'        => 'text',
				'label'       => 'Header Menu Feed',
				'description' => 'The JSON feed of the www.ucf.edu header menu.',
				'section'     => $section,
			)
		);
	}
}
add_action( 'customize_register', __NAMESPACE__.'\Header_Settings::register_header_settings' );


/**
 * Helper class for generating Header HTML.
 */
class Header {
	/**
	 * Load header tags (meta, link, script) to be included on every page.
	 */
	public static function header_tags() {
		ob_start();
		// @codingStandardsIgnoreStart WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		?>
			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">

			<link rel="apple-touch-icon" href="<?= get_stylesheet_directory_uri(); ?>/images/apple-touch-icon.png" >
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
			<link rel="stylesheet" href="<?= get_stylesheet_uri(); ?>" >

			<script type="text/javascript" id="ucfhb-script" src="//universityheader.ucf.edu/bar/js/university-header.js?use-1200-breakpoint=1"></script>
			<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha256-KXn5puMvxCw+dAYznun+drMdG1IFl3agK0p/pqT9KAo= sha512-2e8qq0ETcfWRI4HJBzQiA3UoyFk6tbNyG+qSaIBZLyW9Xf3sWZHN/lxe9fTh1U45DpPf07yj94KsUHHWe4Yk1A==" crossorigin="anonymous"></script>
			<script src="https://cdn.jsdelivr.net/jquery.validation/1.13.1/jquery.validate.min.js" integrity="sha256-8PU3OtIDEB6pG/gmxafvj3zXSIfwa60suSd6UEUDueI=" crossorigin="anonymous"></script>
			<script src="https://cdn.jsdelivr.net/jquery.validation/1.13.1/additional-methods.min.js" integrity="sha256-TZwF+mdLcrSLlptjyffYpBb8iUAuLtidBmNiMj7ll1k=" crossorigin="anonymous"></script>
			<script type="text/javascript">
				(function javascript_fallbacks() {
					// See: http://stackoverflow.com/a/5531821
					function document_write_script( src ) {
						document.write( '<script src="' + src + '">\x3C/script>' );
					}
					if ( ! window.jQuery ) { document_write_script( '/js/jquery.min.js' ); }
					var bootstrap_enabled = ( 'function' === typeof jQuery().modal ); // Will be true if bootstrap is loaded, false otherwise
					if ( ! bootstrap_enabled ) { document_write_script( '/js/bootstrap.min.js' ); }
					if ( 'undefined' === typeof jQuery().validate ) { 
						document_write_script( '/js/jquery.validate.min.js' );
						document_write_script( '/js/additional-methods.min.js' );
					}
				})();
			</script>
			<script type="text/javascript" src="<?= get_stylesheet_directory_uri(); ?>/js/sdes_main_ucf.js"></script>
		<?php
		// @codingStandardsIgnoreEnd WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		echo ob_get_clean();
	}

	/**
	 * Load scripts only needed on the front page (for the Angular app).
	 * @see wp_enqueue_scripts
	 */
	public static function front_page_scripts() {
		$ng_directory = '/ng-app/';
		$baseURL = get_stylesheet_directory_uri() . $ng_directory;
		// Polyfills - see https://angular.io/docs/ts/latest/guide/browser-support.html
		wp_enqueue_script( 'core-js-shim', 'https://cdn.jsdelivr.net/core-js/2.4.1/shim.min.js' );
		wp_enqueue_script( 'polfyill-classList', 'https://cdn.jsdelivr.net/classlist/2014.01.31/classList.min.js' );
		wp_enqueue_script( 'polfyill-intl', 'https://cdn.polyfill.io/v2/polyfill.min.js?features=Intl.~locale.en' );
		wp_enqueue_script( 'polfyill-animations', 'https://cdn.jsdelivr.net/web-animations/2.2.2/web-animations.min.js' );
		wp_enqueue_script( 'polyfill-typedarray', 'https://cdnjs.cloudflare.com/ajax/libs/js-polyfills/0.1.27/polyfill.min.js' ); // Or 'https://cdn.rawgit.com/inexorabletash/polyfill/0.1.27/polyfill.min.js');
		wp_enqueue_script( 'polyfill-blob', 'https://cdn.rawgit.com/eligrey/Blob.js/079824b6c118fbcd0b99c561d57ad192d2c6619b/Blob.js' );
		wp_enqueue_script( 'polyfill-formdata', 'https://cdn.rawgit.com/francois2metz/html5-formdata/9eee5d49070825a07a794cfa5decf0fd2c045463/formdata.js' );
		// Angular 2 dependencies
		wp_enqueue_script( 'zonejs', 'https://unpkg.com/zone.js@0.6.21/dist/zone.js' );
		wp_enqueue_script( 'reflect-metadata', 'https://unpkg.com/reflect-metadata@0.1.3/Reflect.js' );
		// SystemJS Dependency loader (for ES6 style modules).
		wp_enqueue_script( 'systemjs', 'https://unpkg.com/systemjs@0.19.31/dist/system.js' );
		// wp_enqueue_script('config', get_stylesheet_directory_uri() . $ng_directory . 'config.js');
		wp_enqueue_script( 'config-cdn', get_stylesheet_directory_uri() . $ng_directory . 'config.cdn.js' );
		wp_enqueue_script( 'config-local', get_stylesheet_directory_uri() . $ng_directory . 'config.ucf_local.js' ); // Set window.ucf_local_config.
		wp_enqueue_script( 'ng2-bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/ng2-bootstrap/1.1.4/ng2-bootstrap.min.js' );
		wp_add_inline_script('config-local',
			"System.baseURL = '" . $baseURL . "';
				// System.config(window.ucf_local_config); // Uncomment to load config.ucf_local.js instead of config.cdn.js.
				System.import('" . $baseURL . "/main')
					  .then(
					  	function( success ) { 
					    },
					  	function( cdnErr ) {
							// Local fallbacks. See: https://github.com/systemjs/systemjs/issues/986#issuecomment-168422454
							System.config(window.ucf_local_config);
							System.import('" . $baseURL . "/main')
								  .then(
								  	function ( success ) { console.info('Successfully loaded from local files after CDN failure: ', cdnErr ); }
								  , function( err ) {
								  	console.error( 'Failed loading from CDN: ', cdnErr );
								  	console.error( err );
								  } );
					  });"
		); // /inline_script
	}

	public static function front_page_settings() {
		// Load settings.
		$search_query = array_key_exists( 'q', $_REQUEST ) ? $_REQUEST['q'] : '';
		$search_default = SDES_Static::get_theme_mod_defaultIfEmpty( 'services_theme-search_default', '' );
		$services_limit = SDES_Static::get_theme_mod_defaultIfEmpty( 'services_theme-services_limit', 7 );
		$ucf_search_lead = SDES_Static::get_theme_mod_defaultIfEmpty( 'services_theme-frontsearch_lead',
		'From orientation to graduation, the UCF experience creates<br>opportunities that last a lifetime. <b>Let\'s get started</b>.' );
		$ucf_search_placeholder = SDES_Static::get_theme_mod_defaultIfEmpty( 'services_theme-frontsearch_placeholder', 'What can we help you with today?' );
		
		// Build REST request object.
		$request = new \WP_REST_Request();
		$request_search = ( '' !== $search_query ) ? $search_query : $search_default;
		$request->set_query_params( array( 'limit' => $services_limit, 'search' => $request_search ) );
		// Send request directly to API backend.
		$services_contexts = API\route_services_summary( $request );

		$ucf_searchResults_initial = $services_contexts;
		$ucf_searchSuggestions = API\route_services_titles();
		$ucf_service_categories = API\route_categories();
		global $post;
		$ucf_campaign_primary = Campaign::get_render_context( get_post( get_post_meta( $post->ID, 'page_campaign_primary', true ) ) );
		$ucf_campaign_sidebar = Campaign::get_render_context( get_post( get_post_meta( $post->ID, 'page_campaign_sidebar', true ) ) );

		$NG_APP_SETTINGS = array(
			'ucf_searchResults_initial' => $ucf_searchResults_initial,
			'ucf_searchSuggestions' => $ucf_searchSuggestions,
			'ucf_service_categories' => $ucf_service_categories,
			'ucf_search_lead' => wp_kses_post( $ucf_search_lead ),
			'ucf_search_placeholder' => esc_attr( $ucf_search_placeholder ),
			'ucf_campaign_primary' => $ucf_campaign_primary,
			'ucf_campaign_sidebar' => $ucf_campaign_sidebar,
			'search_query' => $search_query,
			'search_default' => $search_default,
			'services_contexts' => $services_contexts,
			'services_limit' => $services_limit,
		);

		// Set NG_APP_SETTINGS for consumption by Angular's javascript via front_page_settings.js
		wp_enqueue_script( 'front-page-settings', get_stylesheet_directory_uri() . '/js/front_page_settings.js');
		wp_localize_script( 'front-page-settings', 'NG_APP_SETTINGS',  $NG_APP_SETTINGS );

		// Return NG_APP_SETTINGS for consumption by PHP.
		return $NG_APP_SETTINGS;
	}

	/**
	 * Prints the Cloud.Typography font stylesheet <link> tag.
	 *
	 * @see https://github.com/UCF/Main-Site-Theme/blob/6610071f535ddf534e3b76c0db5098cb28600321/functions.php#L1236-L1257
	 */
	protected static function webfont_stylesheet() {
		$css_key = SDES_Static::get_theme_mod_defaultIfEmpty( 'services_theme-cloud_font_key', Header_Settings::CLOUD_FONT_KEY );
		if ( $css_key ) {
			echo '<link rel="stylesheet" href="'. $css_key .'" type="text/css" media="all" />';  // @codingStandardsIgnoreLine WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		}
	}

	/**
	 * Output the CSS key for Cloud.Typography web fonts if a CSS key is set in
	 * Theme Options.
	 * Is included conditionally per-page to prevent excessive hits on our Cloud.Typography
	 * page view limit per month.
	 *
	 * @see https://github.com/UCF/Main-Site-Theme/blob/6610071f535ddf534e3b76c0db5098cb28600321/functions.php#L1236-L1257
	 * @see https://developer.wordpress.org/reference/functions/get_post_meta/ WP-Ref: get_post_meta()
	 */
	public static function page_specific_webfonts( $pageid ) {
		if ( \get_post_meta( $pageid, 'page_use_webfonts', $single = true ) === 'on' ) {
			static::webfont_stylesheet();
		}
	}

	/**
	 * Display the header menu on MD and LG screens (by default, 992px or larger).
	 *
	 * @see: https://github.com/UCF/Students-Theme/blob/2489e796a9438180e67f729dcd7ef655eecdd24f/functions.php#L86-L92
	 */
	public static function display_nav_header() {
		$menu = Header_Settings::get_remote_menu( 'services_theme-remote_menus_header_menu_feed' );
		if ( empty( $menu ) ) {
			return;
		}
		ob_start();
	?>
		<nav id="nav-header-wrap" role="navigation" class="screen-only hidden-xs hidden-sm">
			<ul id="header-menu" class="menu-list-unstyled list-inline text-center horizontal">
			<?php foreach ( $menu->items as $item ) : ?>
				<li><a href="<?php echo $item->url; ?>"><?php echo $item->title; ?></a></li>
			<?php endforeach; ?>
			</ul>
		</nav>
	<?php
		echo ob_get_clean();
	}

	/**
	 * Display the header menu on XS and SM screens (by default, screens smaller than 991px).
	 *
	 * @see: https://github.com/UCF/Students-Theme/blob/2489e796a9438180e67f729dcd7ef655eecdd24f/functions.php#L94-L111
	 */
	public static function display_nav_header_xs() {
		$menu = Header_Settings::get_remote_menu( 'services_theme-remote_menus_header_menu_feed' );
		if ( empty( $menu ) ) {
			return;
		}
		ob_start();
	?>			
		<nav id="site-nav-xs" class="hidden-md hidden-lg navbar navbar-inverse">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#header-menu-xs-collapse" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<span class="navbar-title">Navigation</span>
			</div>
			<div class="collapse navbar-collapse" id="header-menu-xs-collapse">
				<ul id="header-menu-xs" class="menu nav navbar-nav">
				<?php foreach ( $menu->items as $item ) : ?>
					<li><a href="<?php echo $item->url; ?>"><?php echo $item->title; ?></a></li>
				<?php endforeach; ?>
				</ul>
			</div>
		</nav>
	<?php
		echo ob_get_clean();
	}
}

add_action( 'wp_head', __NAMESPACE__.'\Header::header_tags' );
