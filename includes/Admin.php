<?php

namespace WPD\Downloads;

/**
 * The admin class
 */
class Admin {

    /**
     * Initialize the class
     */
    function __construct() {
        //Showing All Plugin that are active now
        // new Admin\WPMenu();

        //Showing Download button and manage download function
        new Admin\WPPLdownload();
    }
}
