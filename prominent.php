<?php
/**
 * Plugin Name: Prominent Manager 
 * Description: Download the plugin from your WordPress site! Much easier because now there are prominent managers. You can easily download any plugin on your website with the help of this plugin. After Prominent Manager activation you will see download button under each plugin on your plugin page. Clicking the download button will download the plugin in zip format.
 * Plugin URI: https://mhemelhasan.com/WpD
 * Author: M Hemel Hasan
 * Author URI: https://mhemelhasan.com
 * Version: 1.1.2
 * Text Domain: wp-manager
 * License: GPL3 
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Tags: plugin download, wp plugin download, plugin downloader, wp plugin downloader, wp plugin manager
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
final class pm_prominent {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.1.2';

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
     * @return \pm_prominent
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
        define( 'PM_PROMINENT_VERSION', self::version );
        define( 'PM_PROMINENT_FILE', __FILE__ );
        define( 'PM_PROMINENT_PATH', __DIR__ );
        define( 'PM_PROMINENT_URL', plugins_url( '', PM_PROMINENT_FILE ) );
        define( 'PM_PROMINENT_ASSETS', PM_PROMINENT_URL . '/assets' );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {

        new PM\ProminentManager\Assets();

        if ( is_admin() ) {
            new PM\ProminentManager\Admin();
        } else {
            new PM\ProminentManager\Frontend();
            
        }

    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate() {
        $installed = get_option( 'pm_prominent_installed' );

        if ( ! $installed ) {
            update_option( 'pm_prominent_installed', time() );
        }

        update_option( 'pm_prominent_version', PM_PROMINENT_VERSION);
    }

}

    /**
     * Initializes the main plugin
     *
     * @return \pm_prominent
     */
    function pm_prominent() {
        return pm_prominent::init();
    }

// kick-off the plugin
pm_prominent();
