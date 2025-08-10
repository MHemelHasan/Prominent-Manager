<?php

namespace PM\ProminentManager;

/**
 * The admin class
 */
class Admin
{

    /**
     * Initialize the class
     */
    function __construct()
    {
        //Showing All Plugin that are active now
        // new Admin\PMMenu();

        //Showing Download button and manage download function
        // new Admin\ProminentManagerdownload();
        new Admin\PMThemeDownload();
    }
}
