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
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_ajax_' . JFT_ASSISTANT_SLUG__, array( $this, 'ajax' ) );
    }

    /**
    * Load the scripts and styles
    */
    function admin_enqueue_scripts() {
        wp_enqueue_script( 'jft-assistant', JFT_ASSISTANT_RESOURCES__ . 'admin/js/jft-assistant.js', array( 'jquery' ) );
        wp_localize_script( 'jft-assistant', 'jft', array(
            'ajax'      => array(
                'action'            => JFT_ASSISTANT_SLUG__,
                'nonce'             => wp_create_nonce( JFT_ASSISTANT_SLUG__ . filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ) ),
            ),
            'meta'      => array(
                'base_url'  => JFT_ASSISTANT_THEO_BASE_URL,
            ),
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