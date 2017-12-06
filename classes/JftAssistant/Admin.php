<?php

/**
* The Admin class
*/
class JftAssistant_Admin {

    /**
    * The constructor that determines the class to load
    */
    public function __construct() {
        $this->load();
    }

    /**
    * Load the hooks
    */
    private function load() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'upgrader_process_complete', array( $this, 'post_theme_install' ), 10, 2 );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_filter( 'themes_api', array( $this, 'themes_api' ), 10, 3 );
        add_filter( 'themes_api_result', array( $this, 'themes_api_result' ), 10, 3 );
        add_filter( 'install_themes_tabs', array( $this, 'install_themes_tabs' ) );
    }

    /**
    * Create the menu item for the standalone page.
    */
	function admin_menu() {
		add_submenu_page( 'themes.php', __( 'Just Free Themes', JFT_ASSISTANT_SLUG__ ), __( 'Just Free Themes', JFT_ASSISTANT_SLUG__ ), 'manage_options', add_query_arg( array( 'browse' => 'jft', 'pg' => 'jft' ), '/theme-install.php' ) );
	}

    /**
    * Remove the upload button on the standalone page.
    */
	function install_themes_tabs( $array ) {
		if ( isset( $_GET['pg'] ) && 'jft' === $_GET['pg'] ) {
			return null;
		}
		return $array;
	}

	/**
	 * Fires when the upgrader process is complete.
	 *
	 * See also {@see 'upgrader_package_options'}.
	 *
	 *
	 * @param WP_Upgrader $this WP_Upgrader instance. In other contexts, $this, might be a
	 *                          Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
	 * @param array       $hook_extra {
	 *     Array of bulk item update data.
	 *
	 *     @type string $action       Type of action. Default 'update'.
	 *     @type string $type         Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
	 *     @type bool   $bulk         Whether the update process is a bulk update. Default true.
	 *     @type array  $plugins      Array of the basename paths of the plugins' main files.
	 *     @type array  $themes       The theme slugs.
	 *     @type array  $translations {
	 *         Array of translations update data.
	 *
	 *         @type string $language The locale the translation is for.
	 *         @type string $type     Type of translation. Accepts 'plugin', 'theme', or 'core'.
	 *         @type string $slug     Text domain the translation is for. The slug of a theme/plugin or
	 *                                'default' for core translations.
	 *         @type string $version  The version of a theme, plugin, or core.
	 *     }
	 * }
	 */
	function post_theme_install( WP_Upgrader $upgrader, $hook_extra ) {
		$zip_file		= $upgrader->result['destination_name'] . '.zip';
		// reverse look up to find out the name of the theme that was installed. Cumbersome, but the 'install_theme_complete_actions' filter that should be called,
		// is not being called so we have to do this.
		$theme_id		= null;
		$themes			= $this->get_themes( (object) array( 'all' => true ), false );
		foreach ( $themes['themes'] as $slug => $theme ) {
			if ( $zip_file === $theme['zip_file'] ) {
				$theme_id	= $theme['theme_id'];
				break;
			}
		}

		if ( ! is_null( $theme_id ) ) {
			wp_remote_get(
				str_replace( '#id#', $theme_id, JFT_THEO_TRACK_ENDPOINT__ ),
				array(
					'headers'	=> array(
						'X-Theo-User'	=> md5( site_url() ),
						'X-Theo-From'	=> 'wpadmin',
					),
				)
			);
		}
	}

	/**
	 * Filters the returned WordPress.org Themes API response.
	 *
	 * @param array|object|WP_Error $res    WordPress.org Themes API response.
	 * @param string                $action Requested action. Likely values are 'theme_information',
	 *                                      'feature_list', or 'query_themes'.
	 * @param object                $args   Arguments used to query for installer pages from the WordPress.org Themes API.
	 */
	function themes_api_result( $res, $action, $args ) {
		if ( $this->is_tab_jft( $args ) && 'query_themes' === $action ) {
			return $this->get_themes( $args, true );
		}

		if ( 'theme_information' === $action ) {
			$response	= $this->get_themes( $args, false );
			if ( isset( $args->slug ) && array_key_exists( $args->slug, $response['themes'] ) ) {
				return (object) $response['themes'][ $args->slug ];
			}
		}

		return $res;
	}

	/**
	 * Filters whether to override the WordPress.org Themes API.
	 *
	 * Passing a non-false value will effectively short-circuit the WordPress.org API request.
	 *
	 * If `$action` is 'query_themes', 'theme_information', or 'feature_list', an object MUST
	 * be passed. If `$action` is 'hot_tags', an array should be passed.
	 *
	 * @param false|object|array $override Whether to override the WordPress.org Themes API. Default false.
	 * @param string             $action   Requested action. Likely values are 'theme_information',
	 *                                    'feature_list', or 'query_themes'.
	 * @param object             $args     Arguments used to query for installer pages from the Themes API.
	 */
	function themes_api( $default, $action, $args ) {
		if ( $this->is_tab_jft( $args ) && 'query_themes' === $action ) {
			return true;
		}

		if ( 'theme_information' === $action ) {
			$response	= $this->get_themes( $args, false );
			if ( isset( $args->slug ) && array_key_exists( $args->slug, $response['themes'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether the tab requesting theme information is the JFT tab or not.
	 *
	 * @param object             $args     Arguments used to query for installer pages from the Themes API.
	 */
	function is_tab_jft( $args ) {
		$type	= isset( $args->browse ) ? ( strpos( $args->browse, '&' ) !== false ? strstr( $args->browse, '&', true ) : $args->browse ) : '';
		if ( isset( $args->browse ) && 'jft' === $type ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the themes from the endpoint.
	 *
	 * @param object             $args     Arguments used to query for installer pages from the Themes API.
	 * @param bool             $return_object     Whether to return an object or an array.
	 */
	function get_themes( $args, $return_object = true ) {
		if ( isset( $args->all ) && $args->all ) {
			return $this->get_all_themes();
		}

		$page		= isset( $args->page ) ? $args->page : 1;
		$key		= sprintf( '%s_response_%d_%d_%d', JFT_ASSISTANT_SLUG__, JFT_ASSISTANT_VERSION__, $page, JFT_ASSISTANT_THEMES_PERPAGE__ );
		$response	= get_transient( $key );

		if ( false === $response ) {
			$response	= wp_remote_get( 
				 str_replace( '#', $page, JFT_ASSISTANT_THEMES_ENDPOINT__ ),
				 array(
					'headers'	=> array(
						'X-JFT-Source'		=> 'JFT Assistant v' . JFT_ASSISTANT_VERSION__,
					 ),
					'timeout' => 180,
				) 
			);

			if ( is_wp_error( $response ) ) {
				return;
			}
			if ( ! JFT_ASSISTANT_THEMES_DISABLE_CACHE__ ) {
				set_transient( $key, $response, JFT_ASSISTANT_THEMES_CACHE_DAYS__ * DAY_IN_SECONDS );
			}
		}

		return $this->parse_response( $response, $args, $return_object );
	}

	/**
	 * Get all the themes from db, irrespective of pagination.
	 *
	 */
	function get_all_themes() {
		$themes		= array();
		$args		= (object) array();
		for ( $page = 1; $page < 100; $page++ ) {
			$response	= get_transient( sprintf( '%s_response_%d_%d_%d', JFT_ASSISTANT_SLUG__, JFT_ASSISTANT_VERSION__, $page, JFT_ASSISTANT_THEMES_PERPAGE__ ) );
			if ( false === $response ) {
				// thats it, we are done. No more pages.
				break;
			}
			$response	= $this->parse_response( $response, $args, false);
			if ( is_array( $response ) && array_key_exists( 'themes', $response ) ) {
				$themes	= array_merge( $themes, $response['themes'] );
			}
		}
		return array( 'themes' => $themes );
	}

    /**
    * Parse the response from the API or the transient.
    */
	function parse_response( $response, $args, $return_object ) {
		$json		= json_decode( wp_remote_retrieve_body( $response ), true );
		$res		= array();
		if ( $json ) {
			$themes		= array();
			foreach ( $json as $theme ) {
				$date					= DateTime::createFromFormat( 'Y-m-d\TH:i:s', $theme['modified_gmt'] );
				$link					= $theme['download_url'];
				$array					= explode( '/', $link );
				$zip_file				= end( $array );
				$theme_data	= array(
					'theme_id'			=> $theme['theme_id'],
					'slug'				=> $theme['slug'],
					'name'				=> $theme['title_attribute'],
					'version'			=> $theme['version'],
					'rating'			=> $theme['score'],
					'num_ratings'		=> $theme['downloads'],
					'author'			=> $theme['author_name'],
					'preview_url'		=> $theme['demo_url'],
					'screenshot_url'	=> is_array( $theme['listing_image'] ) && count( $theme['listing_image'] ) > 0 ? $theme['listing_image'][0] : '',
					'last_update'		=> $date->format('Y-m-d'),
					'homepage'			=> $theme['link'],
					'description'		=> $theme['description'],
					'download_link'		=> $link,
					'zip_file'			=> $zip_file,
				);
				if ( $return_object ) {
					$themes[]				= (object) $theme_data;
				} else {
					$themes[ $theme['slug'] ] = $theme_data;
				}
			}

			$headers	= wp_remote_retrieve_headers( $response );

			$res	= array(
				'info'		=> array(
					'page'		=> isset( $args->page ) ? $args->page : 1,
					'results'	=> $headers['X-WP-Total'],
					'pages'		=> $headers['X-WP-TotalPages'],
				),
				'themes'	=> $themes,
			);
		}

		if ( $return_object ) {
			return (object) $res;
		}

		return $res;
	}

    /**
    * Load the scripts and styles
    */
    function admin_enqueue_scripts() {
		$current_screen = get_current_screen();

		if ( ! isset( $current_screen->id ) ) {
			return array();
		}
		if ( ! in_array( $current_screen->id, array( 'theme-install' ) ) ) {
			return array();
		}

        wp_enqueue_script( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/js/jft-assistant.js', array( 'jquery' ) );
        wp_localize_script( 'jft-assistant', 'jft', array(
			'screen'	=> $current_screen->id,
			'tab_name'	=> __( 'Just Free Themes', JFT_ASSISTANT_SLUG__ ),
			'jft_page'	=> isset( $_GET['pg'] ) && 'jft' === $_GET['pg'],
        ));

        wp_register_style( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/css/jft-assistant.css' );
        wp_enqueue_style( 'jft-assistant' );

    }

}