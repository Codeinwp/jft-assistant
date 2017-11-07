<?php

/**
* The Admin class
*/
class JftAssistant_Admin {

	const LISTING_ENDPOINT		= 'http://s24607.p182.sites.pressdns.com/wp-json/wp/v2/posts?per_page=100&orderby=score&order=desc&page=#';

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
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_ajax_' . JFT_ASSISTANT_SLUG__, array( $this, 'ajax' ) );
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
		$response	= wp_remote_get( 
			 str_replace( '#', isset( $args->page ) ? $args->page : 1, self::LISTING_ENDPOINT ),
			 array(
				 'headers'	=> array(
					'X-JFT-Source'		=> 'JFT Assistant v' . JFT_ASSISTANT_VERSION__,
				 )
			) 
		);

		$headers	= wp_remote_retrieve_headers( $response );
		$json		= json_decode( wp_remote_retrieve_body( $response ), true );

		$themes	= array();
		if ( $json ) {
			foreach ( $json as $theme ) {
				$date	= DateTime::createFromFormat( 'Y-m-d\TH:i:s', $theme['modified_gmt'] );
				self::$jft_themes[ $theme['slug'] ] = 'http://www.google.com'; // need download url;
				$themes[]	= (object) array(
					'slug'				=> $theme['slug'],
					'name'				=> $theme['title_attribute'],
					'version'			=> $theme['version'],
					'rating'			=> $theme['score'],
					'num_ratings'		=> $theme['downloads'],
					'author'			=> $theme['author_name'],
					'preview_url'		=> $theme['demo_url'],
					'screenshot_url'	=> $theme['listing_image'][0],
					'last_update'		=> $date->format('Y-m-d'),
					'homepage'			=> $theme['link'],
					'description'		=> $theme['content']['rendered'],
				);
			}
		}

		$res	= array(
			'info'		=> array(
				'page'		=> isset( $args->page ) ? $args->page : 1,
				'results'	=> $headers['X-WP-Total'],
				'pages'		=> $headers['X-WP-TotalPages'],
			),
			'themes'	=> $themes,
		);

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
		if ( ! in_array( $current_screen->id, array( 'theme-install', 'settings_page_' . JFT_ASSISTANT_SLUG__ ) ) ) {
			return array();
		}

        wp_enqueue_script( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/js/jft-assistant.js', array( 'jquery' ) );
        wp_localize_script( 'jft-assistant', 'jft', array(
            'ajax'      => array(
                'action'            => JFT_ASSISTANT_SLUG__,
                'nonce'             => wp_create_nonce( JFT_ASSISTANT_SLUG__ . filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ) ),
            ),
            'meta'      => array(
                'base_url'  => JFT_ASSISTANT_THEO_BASE_URL,
            ),
			'screen'	=> $current_screen->id,
			'theme_tab'	=> __( 'Just Free Themes', JFT_ASSISTANT_SLUG__ ),
        ));

        wp_register_style( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/css/jft-assistant.css' );
        wp_enqueue_style( 'jft-assistant' );

    }

    /**
    * Show the admin menu
    */
    function admin_menu() {
        add_options_page( JFT_ASSISTANT_NAME__, JFT_ASSISTANT_NAME__, "manage_options", JFT_ASSISTANT_SLUG__, array( $this, 'admin_settings' ) );
    }

    /**
    * Show the settings page where themes can be searched and installed
    */
    function admin_settings() {
        require_once JFT_ASSISTANT_DIR__ . 'resources/admin/includes/search.php';
    }

    /**
    * Ajax actions
    */
    function ajax() {
        check_ajax_referer( JFT_ASSISTANT_SLUG__ . filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ), 'nonce' );

        switch ( $_POST['_action'] ) {
            case 'search':
                $name       = $_POST['name'];
                $page       = isset( $_POST['page'] ) ? $_POST['page'] : 1;
                $url        = str_replace( array( '#name', '#page' ),  array( $name, $page ), JFT_ASSISTANT_THEO_API_URL );
                $results    = wp_remote_retrieve_body( wp_remote_get( $url, array( 'headers' => array( 'X-Theo-User' => site_url() ) ) ) );
                wp_send_json_success( array( 'data' => json_decode( $results, true ) ) );
                break;
            case 'download':
                include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
                $id         = $_POST['id'];
                $url        = JFT_ASSISTANT_THEO_BASE_URL . $id . '.zip';
                $upgrader   = new Theme_Upgrader( new Theme_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
                $upgrader->install( $url );

                if ( ! empty( $upgrader->result['destination_name'] ) ) {
                    switch_theme( $upgrader->theme_info()->get_stylesheet() );
                }
                break;
        }
        wp_die();
    }

}