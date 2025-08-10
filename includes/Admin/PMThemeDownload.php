<?php

namespace PM\ProminentManager\Admin;

/**
 * Prominent Manager - Admin Download & Rollback Handler
 * Single-file, maintainable, feature-separated implementation.
 *
 * Features:
 *  - Plugin & MU-Plugin download (one-click)
 *  - Theme download (one-click)
 *  - Plugin rollback (select version from WP.org and restore)
 *  - Theme rollback (select version from WP.org and restore)
 *
 * Notes:
 *  - Requires WordPress filesystem and proper capabilities.
 *  - Creates backups to uploads/pm-backups before any rollback.
 *
 * How to use:
 *  - Replace your existing admin download file with this file.
 *  - Ensure PclZip.php (if required) is available in the same folder.
 *  - The class registers action links under Plugins and Themes.
 */

// If PclZip is still used in your environment keep compatibility; otherwise ZipArchive + WP functions are used.
if (file_exists(__DIR__ . '/Pclzip.php')) {
    require_once __DIR__ . '/Pclzip.php';
}

class PMThemeDownload
{

    public function __construct()
    {
        // Handle GET/POST actions early (admin only)
        add_action('admin_init', [$this, 'maybe_handle_action']);

        // Add action links for plugins
        add_filter('plugin_action_links', [$this, 'plugin_action_links'], 10, 4);

        // Add theme actions
        // $this->register_theme_action_links();

        // wp_enqueue_script('pm-manager-admin-script');

    }

    /**
     * Entry point that inspects the request and delegates to appropriate handler.
     */
    public function maybe_handle_action()
    {
        if (isset($_GET['pmpd']) || isset($_POST['pmpd'])) {
            $action = isset($_REQUEST['pmpd']) ? sanitize_text_field(wp_unslash($_REQUEST['pmpd'])) : '';

            // Capability check
            if (!current_user_can('manage_options') && !current_user_can('install_plugins')) {
                wp_die(__('Insufficient permissions to perform this action.', 'prominent-manager'));
            }

            switch ($action) {
                case 'plugin_download':
                case 'muplugin_download':
                case 'theme_download':
                    $this->handle_download($action);
                    break;

                case 'plugin_rollback':
                case 'theme_rollback':
                    // If POST with nonce -> perform rollback
                    if (isset($_POST['pmpd_action']) && $_POST['pmpd_action'] === 'do_rollback') {
                        $this->perform_rollback();
                    } else {
                        // Display version selection UI
                        $this->show_rollback_ui($action);
                    }
                    break;

                default:
                    // unknown action
                    break;
            }
        }
    }

    /**
     * Add download / rollback links under each plugin row
     */
    public function plugin_action_links($links, $file, $plugin_data, $context)
    {
        if ('dropins' === $context) {
            return $links;
        }

        // create download link
        $what = strpos($file, '/') ? dirname($file) : $file;
        $download_query = build_query(array(
            'pmpd' => 'plugin_download',
            'object' => $what,
        ));
        $download_link = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('?' . $download_query), 'pmpd-download'),
            esc_html__('Download', 'prominent-manager')
        );

        // rollback link
        // $rollback_query = build_query(array(
        //     'pmpd' => 'plugin_rollback',
        //     'object' => $what,
        // ));
        // $rollback_link = sprintf(
        //     '<a href="%s">%s</a>',
        //     wp_nonce_url(admin_url('?' . $rollback_query), 'pmpd-rollback'),
        //     esc_html__('Rollback', 'prominent-manager')
        // );

        array_push($links, $download_link);
        // array_push($links, $rollback_link);

        return $links;
    }

    public function register_theme_action_links()
    {
        // add_filter('theme_action_links_twentytwentytwo', function ($actions, $theme) {
        //     var_dump($actions);
        //     $actions['download'] = '<a href="' . esc_url(admin_url('themes.php?page=download-theme&theme=' . urlencode($theme->stylesheet))) . '">Download</a>';
        //     $actions['rollback'] = '<a href="' . esc_url(admin_url('themes.php?page=rollback-theme&theme=' . urlencode($theme->stylesheet))) . '">Rollback</a>';

        //     return $actions;
        // }, 10, 2);
        // // Get all installed themes
        $themes = wp_get_themes();

        foreach ($themes as $stylesheet => $theme_obj) {
            var_dump($stylesheet);
            add_filter(
                "theme_action_links_{$stylesheet}",
                [$this, 'theme_action_links'],
                10,
                2
            );
        }
    }

    /**
     * Add download / rollback links for themes on the Appearance > Themes screen
     */
    public function theme_action_links($actions, $theme)
    {
        // $theme is WP_Theme instance
        $stylesheet = $theme->get_stylesheet();

        var_dump($stylesheet);

        // Download link
        $download_query = build_query(array(
            'pmpd' => 'theme_download',
            'object' => $stylesheet,
        ));
        $actions['pm_download'] = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('?' . $download_query), 'pmpd-download'),
            esc_html__('Download', 'prominent-manager')
        );

        // Rollback link
        $rollback_query = build_query(array(
            'pmpd' => 'theme_rollback',
            'object' => $stylesheet,
        ));
        $actions['pm_rollback'] = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('?' . $rollback_query), 'pmpd-rollback'),
            esc_html__('Rollback', 'prominent-manager')
        );

        return $actions;
    }

    /**
     * Shared download handler for plugin / muplugin / theme
     */
    protected function handle_download($action)
    {
        // Check nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'pmpd-download')) {
            wp_die(__('Security check failed.', 'prominent-manager'));
        }

        $what = '';
        $object = isset($_GET['object']) ? sanitize_text_field(wp_unslash($_GET['object'])) : '';

        switch ($action) {
            case 'plugin_download':
                $what = 'plugin';
                $root = WP_PLUGIN_DIR;
                if (strpos($object, '/')) {
                    $object = dirname($object);
                }
                break;
            case 'muplugin_download':
                $what = 'muplugin';
                $root = WPMU_PLUGIN_DIR;
                if (strpos($object, '/')) {
                    $object = dirname($object);
                }
                break;
            case 'theme_download':
                $what = 'theme';
                $root = get_theme_root($object);
                break;
            default:
                wp_die();
        }

        $object = sanitize_file_name($object);
        if (empty($object)) {
            wp_die(__('No object specified.', 'prominent-manager'));
        }

        $path = $root . '/' . $object;
        if (!file_exists($path)) {
            wp_die(__('Requested object does not exist on disk.', 'prominent-manager'));
        }

        // filename and temp path
        $fileName = $object . '.zip';
        $upload_dir = wp_upload_dir();
        $tmpFile = trailingslashit($upload_dir['path']) . $fileName;

        // create zip (try ZipArchive first)
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $this->add_folder_to_zip($path, $zip, strlen($root) + 1);
                $zip->close();
            } else {
                wp_die(__('Could not create zip archive.', 'prominent-manager'));
            }
        } elseif (class_exists('PclZip')) {
            $archive = new \PclZip($tmpFile);
            $archive->add($path, PCLZIP_OPT_REMOVE_PATH, $root);
        } else {
            wp_die(__('No zip support available on server.', 'prominent-manager'));
        }

        // Stream file
        if (!file_exists($tmpFile)) {
            wp_die(__('Temporary archive not found.', 'prominent-manager'));
        }

        // Force download headers
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Content-Length: ' . filesize($tmpFile));

        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    /**
     * Recursive add folder to ZipArchive instance
     */
    protected function add_folder_to_zip($folder, \ZipArchive $zip, $remove_length)
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $localPath = substr($filePath, $remove_length);
                $zip->addFile($filePath, $localPath);
            }
        }
    }

    /**
     * Show rollback UI where user can choose a version to rollback to
     */
    protected function show_rollback_ui($action)
    {
        // Basic sanity
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'pmpd-rollback')) {
            wp_die(__('Security check failed.', 'prominent-manager'));
        }

        $object = isset($_GET['object']) ? sanitize_text_field(wp_unslash($_GET['object'])) : '';
        if (empty($object)) {
            wp_die(__('No object specified.', 'prominent-manager'));
        }

        $is_plugin = $action === 'plugin_rollback';

        // Fetch versions from WP.org
        $slug = $object;
        $api_url = $is_plugin
            ? 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=' . rawurlencode($slug) . '&request[fields][versions]=1'
            : 'https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=' . rawurlencode($slug) . '&request[fields][versions]=1';

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            wp_die(__('Could not contact WordPress.org API.', 'prominent-manager'));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        if (!$data || empty($data->versions)) {
            wp_die(__('No available versions found via WordPress.org.', 'prominent-manager'));
        }

        // Show a minimal form to pick a version
        $versions = (array) $data->versions;
        krsort($versions); // newest first

        $admin_url = admin_url();
        $current_url = esc_url(add_query_arg(null, null));

        echo '<div class="wrap"><h1>' . esc_html__('Prominent Manager - Rollback', 'prominent-manager') . '</h1>';
        printf('<p>%s</p>', esc_html(sprintf(__('Select a version of "%s" to rollback to:', 'prominent-manager'), esc_html($slug))));

        echo '<form method="post" action="' . esc_url($current_url) . '">';
        wp_nonce_field('pmpd-rollback-action');
        echo '<input type="hidden" name="pmpd" value="' . esc_attr($action) . '">';
        echo '<input type="hidden" name="object" value="' . esc_attr($slug) . '">';
        echo '<input type="hidden" name="pmpd_action" value="do_rollback">';

        echo '<select name="version">';
        foreach ($versions as $ver => $download_url) {
            printf('<option value="%s">%s</option>', esc_attr($ver), esc_html($ver));
        }
        echo '</select> ';
        submit_button(__('Rollback to selected version', 'prominent-manager'));
        echo '</form>';

        echo '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . esc_html__('Back to Plugins', 'prominent-manager') . '</a></p>';
        echo '</div>';
        exit;
    }

    /**
     * Perform rollback (download selected version, backup current, replace files)
     */
    protected function perform_rollback()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(wp_unslash($_POST['_wpnonce']), 'pmpd-rollback-action')) {
            wp_die(__('Security check failed.', 'prominent-manager'));
        }

        $action = isset($_POST['pmpd']) ? sanitize_text_field(wp_unslash($_POST['pmpd'])) : '';
        $object = isset($_POST['object']) ? sanitize_text_field(wp_unslash($_POST['object'])) : '';
        $version = isset($_POST['version']) ? sanitize_text_field(wp_unslash($_POST['version'])) : '';

        if (empty($action) || empty($object) || empty($version)) {
            wp_die(__('Missing parameters for rollback.', 'prominent-manager'));
        }

        $is_plugin = $action === 'plugin_rollback';

        $download_url = $is_plugin
            ? 'https://downloads.wordpress.org/plugin/' . rawurlencode($object) . '.' . rawurlencode($version) . '.zip'
            : 'https://downloads.wordpress.org/theme/' . rawurlencode($object) . '.' . rawurlencode($version) . '.zip';

        // Download the requested archive to a temp file
        $tmp = download_url($download_url);
        if (is_wp_error($tmp)) {
            wp_die(__('Failed to download requested version from WordPress.org.', 'prominent-manager'));
        }

        // prepare paths
        $root = $is_plugin ? WP_PLUGIN_DIR : get_theme_root($object);
        $path = $root . '/' . $object;
        if (!file_exists($path)) {
            // If plugin/theme folder missing, just extract to root
        }

        // Ensure WP Filesystem is available
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Create backup ZIP of current folder (if exists)
        $upload = wp_upload_dir();
        $backup_dir = trailingslashit($upload['basedir']) . 'pm-backups';
        if (!$wp_filesystem->is_dir($backup_dir)) {
            $wp_filesystem->mkdir($backup_dir);
        }

        $timestamp = date('Ymd-His');
        $backup_name = $object . '-' . $timestamp . '.zip';
        $backup_path = trailingslashit($backup_dir) . $backup_name;

        if (file_exists($path)) {
            // Create backup archive
            if (class_exists('ZipArchive')) {
                $zipBackup = new \ZipArchive();
                if ($zipBackup->open($backup_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    $this->add_folder_to_zip($path, $zipBackup, strlen(dirname($path)) + 1);
                    $zipBackup->close();
                }
            } elseif (class_exists('PclZip')) {
                $arc = new \PclZip($backup_path);
                $arc->add($path, PCLZIP_OPT_REMOVE_PATH, dirname($path));
            }
        }

        // Remove current folder (if exists)
        if (file_exists($path)) {
            $this->recursive_delete($path);
        }

        // Unzip downloaded version into root
        $unzip_result = unzip_file($tmp, $root);
        unlink($tmp);

        if (is_wp_error($unzip_result)) {
            wp_die(__('Failed to unzip the downloaded archive.', 'prominent-manager'));
        }

        // Success notice and link back
        $redirect = $is_plugin ? admin_url('plugins.php') : admin_url('themes.php');
        wp_safe_redirect(add_query_arg('pm_msg', 'rollback_success', $redirect));
        exit;
    }

    /**
     * Recursive delete helper using PHP functions (keeps dependency low)
     */
    protected function recursive_delete($dir)
    {
        if (!is_dir($dir)) {
            return true;
        }

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        return rmdir($dir);
    }

}
// End of file
