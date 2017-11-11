<?php

/**
* The Admin class
*/
class JftAssistant_Admin {

	// this will collect the slugs of the JFT themes so that when theme information is requested, we can determine it is a JFT theme.
	static $jft_themes			= array();

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
        add_filter( 'themes_api', array( $this, 'themes_api' ), 10, 3 );
        add_filter( 'themes_api_result', array( $this, 'themes_api_result' ), 10, 3 );
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
			return $this->get_themes( $args );
		}

		if ( 'theme_information' === $action ) {
			if ( isset( $args->slug ) && array_key_exists( $args->slug, self::$jft_themes ) ) {
				return (object) array( 'download_link' => self::$jft_themes[ $args->slug ] );
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
			$this->get_themes( $args );
			if ( isset( $args->slug ) && array_key_exists( $args->slug, self::$jft_themes ) ) {
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
	 */
	function get_themes( $args ) {
		$page		= isset( $args->page ) ? $args->page : 1;
		$key		= sprintf( '%s_response_%d_%d', JFT_ASSISTANT_SLUG__, $page, JFT_ASSISTANT_THEMES_PERPAGE__ );
		$response	= get_transient( $key );

		if ( false === $response ) {
			$response	= wp_remote_get( 
				 str_replace( '#', $page, JFT_ASSISTANT_THEMES_ENDPOINT__ ),
				 array(
					'headers'	=> array(
						'X-JFT-Source'		=> 'JFT Assistant v' . JFT_ASSISTANT_VERSION__,
					 ),
					'timeout' => 120,
				) 
			);

			if ( is_wp_error( $response ) ) {
				return;
			}
			if ( ! JFT_ASSISTANT_THEMES_DISABLE_CACHE__ ) {
				set_transient( $key, $response, JFT_ASSISTANT_THEMES_CACHE_DAYS__ * DAY_IN_SECONDS );
			}
		}

		$json		= json_decode( wp_remote_retrieve_body( $response ), true );
		$res		= array();
		if ( $json ) {
			$themes		= array();
			foreach ( $json as $theme ) {
				$date	= DateTime::createFromFormat( 'Y-m-d\TH:i:s', $theme['modified_gmt'] );
				self::$jft_themes[ $theme['slug'] ] = $theme['download_url'];
				$themes[]	= (object) array(
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
				);
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

		return (object) $res;
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
			'theme_tab'	=> __( 'Just Free Themes', JFT_ASSISTANT_SLUG__ ),
        ));

        wp_register_style( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/css/jft-assistant.css' );
        wp_enqueue_style( 'jft-assistant' );

    }

}