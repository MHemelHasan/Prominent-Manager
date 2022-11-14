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
        // new Admin\WPMenu();
        new Admin\WPPLdownload();
    }
}
