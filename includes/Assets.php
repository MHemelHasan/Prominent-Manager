<?php

namespace PM\ProminentManager;

/**
 * Assets handlers class
 */
class Assets
{

    /**
     * Class constructor
     */
    function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * All available scripts
     *
     * @return array
     */
    public function get_scripts()
    {
        return [
            'pm-manager-script' => [
                'src' => PM_PROMINENT_ASSETS . '/js/frontend.js',
                'version' => filemtime(PM_PROMINENT_PATH . '/assets/js/frontend.js'),
                'deps' => ['jquery']
            ],
            'pm-manager-admin-script' => [
                'src' => PM_PROMINENT_ASSETS . '/js/pm-admin-script.js',
                'version' => filemtime(PM_PROMINENT_PATH . '/assets/js/pm-admin-script.js'),
                'deps' => ['jquery']
            ],
        ];
    }

    /**
     * All available styles
     *
     * @return array
     */
    public function get_styles()
    {
        return [
            'pm-manager-style' => [
                'src' => PM_PROMINENT_ASSETS . '/css/frontend.css',
                'version' => filemtime(PM_PROMINENT_PATH . '/assets/css/frontend.css')
            ]

        ];
    }

    /**
     * Register scripts and styles
     *
     * @return void
     */
    public function register_assets()
    {
        $scripts = $this->get_scripts();
        $styles = $this->get_styles();

        foreach ($scripts as $handle => $script) {
            $deps = isset($script['deps']) ? $script['deps'] : false;

            wp_register_script($handle, $script['src'], $deps, $script['version'], true);
        }

        foreach ($styles as $handle => $style) {
            $deps = isset($style['deps']) ? $style['deps'] : false;

            wp_register_style($handle, $style['src'], $deps, $style['version']);
        }
    }
}