<?php
/**
 * Plugin Name: WP Plugin Manager
 * Description: You can easily download any plugin on your website with the help of this plugin. After Wp plugin manager activation you will see download button under each plugin on your plugin page. Clicking the download button will download the plugin in zip format.
 * Plugin URI: https://mhemelhasan.com/WpD
 * Author: M Hemel Hasan
 * Author URI: https://mhemelhasan.com
 * Version: 1.1.1
 * Text Domain: wp-manager
 * License: GPL3 
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Tags: Plugin download, wp plugin download, Plugin downloader, wp plugin downloader, wp plugin manager
 * Tested up to: 6.1.1
 * Requires PHP: 7.2
 *              
 */

/** 
 * Basic Security
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/** 
 * Auto Loader from PSR4
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * The main plugin class
 */
final class wp_manager {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.1.1';

    /**
     * Class construcotr
     */
    private function __construct() {
        $this->define_constants();

        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Initializes a singleton instance
     *
     * @return \wp_manager
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'WP_MANAGER_VERSION', self::version );
        define( 'WP_MANAGER_FILE', __FILE__ );
        define( 'WP_MANAGER_PATH', __DIR__ );
        define( 'WP_MANAGER_URL', plugins_url( '', WP_MANAGER_FILE ) );
        define( 'WP_MANAGER_ASSETS', WP_MANAGER_URL . '/assets' );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {

        new WPD\Downloads\Assets();

        if ( is_admin() ) {
            new WPD\Downloads\Admin();
        } else {
            new WPD\Downloads\Frontend();
            
        }

    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate() {
        $installed = get_option( 'wp_manager_installed' );

        if ( ! $installed ) {
            update_option( 'wp_manager_installed', time() );
        }

        update_option( 'wp_manager_version', WP_MANAGER_VERSION);
    }

}

    /**
     * Initializes the main plugin
     *
     * @return \wp_manager
     */
    function wp_manager() {
        return wp_manager::init();
    }

// kick-off the plugin
wp_manager();
