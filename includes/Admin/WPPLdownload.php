<?php

namespace WPD\Downloads\Admin;

/**
 * Download handler class
 */

//  require_once 'Pclzip.php';


class WPPLdownload {
    
    /**
     * Initialize the class
     */
    function __construct() {
        // Check, if there is a 'wppm' Get param, and there is a proper nonce set
        if(isset($_GET['wppm']) && wp_verify_nonce($_GET['_wpnonce'], 'wppl-download')){
            // perform downloading
            $this->wp_manager_download();
        }

        add_filter('plugin_action_links', [$this, 'wp_plugin_download_links'], 10, 4);
    }

    /**
     * Displays "Download" Button and link on the plugins page
     */
    public function wp_plugin_download_links($links, $file, $plugin_data, $context){
        if('dropins' === $context)
            return $links;
        if('mustuse' === $context)
            $what = 'muplugin';
        else
            $what = 'plugin';

        $dowload_query = build_query(array('wppm' => $what, 'object' => $file));
        $download_link = sprintf('<a href="%s">%s</a>',
            wp_nonce_url(admin_url('?' . $dowload_query), 'wppl-download'),
            __('Download')
        );
            
        array_push($links, $download_link); 
        return $links;
    }

    /**
     * Handles the download
     *
     * @return Plugin zip folder
     */
    public function wp_manager_download(){
        // Kind of object we download (theme or plugin)
        $what = $_GET['wppm'];
        // The name of object
        $object = $_GET['object'];
    
        // Prepare object name and root path for given object type
        switch($what){
            case 'plugin':
                if(strpos($object, '/')){
                    $object = dirname($object);
                }
                $root = WP_PLUGIN_DIR;
                break;
            case 'muplugin':
                if(strpos($object, '/')){
                    $object = dirname($object);
                }
                $root = WPMU_PLUGIN_DIR;
                break;
            case 'theme':
                $root = get_theme_root($object);
                break;
            default:
                // bad URL
                wp_die();
        }
        
        $object = sanitize_file_name($object);
        if(empty($object))
            // No object name
            wp_die();
    
        // Prepare full path do the desired object
        $path = $root . '/' . $object;
        // Filename for a zip package
        $fileName = $object . '.zip';
        
        // Temporary file name in upload dir
        $upload_dir = wp_upload_dir();
        $tmpFile = trailingslashit($upload_dir['path']) . $fileName;
    
        // Create new archive
        $archive = new PclZip($tmpFile);

        // Add entire folder to the archive
        $archive->add($path, PCLZIP_OPT_REMOVE_PATH, $root);
    
        // Set headers for the zip archive
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        
        // Read file content directly
        readfile($tmpFile);
        // Remove zip file
        unlink($tmpFile);
    
        // Exit.
        exit;
    }


}