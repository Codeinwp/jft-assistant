<?php

/**
* The initializer of the plugin
*/
class JftAssistant_Plugin {

    private static $_instance;

    /**
    * Load and return the singleton instance
    */
    public static function get_instance() {
        if ( ! self::$_instance ) {
            self::$_instance  = new JftAssistant_Plugin();
        }
        return self::$_instance;
    }

    /**
    * Load the classes relevant to the plugin
    */
    public function load() {
        @mkdir( JFT_ASSISTANT_DIR__ . 'tmp' );
        new JftAssistant_Admin();
    }

    /**
    * Called when the plugin is activated
    */
    public function activate() {
        JftAssistant_Log_Debug::init();
    }

    /**
    * Called whtn the plugin is deactivate
    */
    public function deactivate() {
    }
}