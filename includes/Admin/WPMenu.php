<?php

namespace WPD\Downloads\Admin;

/**
 * Menu handler class
 */

 class WPMenu {


    /**
     * Initialize the class
     */
    function __construct() {
        add_action( 'admin_menu', [$this, 'WPMenu'] );
    }


    public function WPMenu(){
        add_options_page( 'WordPress Plugin Manager page', 'WpManager', 'manage_options', 'wpmanager',  [$this, 'wpmenu_options']);
    }


    public function wpmenu_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        echo '<div class="wrap">';
        echo '<p>Here is where the form would go if I actually had options.</p>';
        echo '</div>';
    }

 }