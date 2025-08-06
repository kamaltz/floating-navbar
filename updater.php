<?php
/**
 * Plugin Auto-Updater
 */

if (!defined('ABSPATH')) {
    exit;
}

class FloatingNavbarUpdater {
    private $plugin_slug;
    private $version;
    private $plugin_path;
    private $plugin_file;
    private $github_repo;
    
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->plugin_path = plugin_dir_path($plugin_file);
        $this->version = '2.0.1';
        $this->github_repo = 'kamaltz/floating-navbar-wplugin';
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_action('admin_init', array($this, 'force_update_check'));
        add_action('wp_update_plugins', array($this, 'clear_version_cache'));
        add_filter('upgrader_source_selection', array($this, 'fix_plugin_folder'), 10, 4);
    }
    
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Force check if plugin is in checked list
        if (!isset($transient->checked[$this->plugin_slug])) {
            return $transient;
        }
        
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => 'floating-navbar-wplugin',
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_repo}",
                'package' => $this->get_download_url($remote_version),
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
                'compatibility' => new stdClass()
            );
        }
        
        return $transient;
    }
    
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== 'floating-navbar-wplugin') {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        return (object) array(
            'name' => 'Floating Navbar',
            'slug' => 'floating-navbar-wplugin',
            'version' => $remote_version,
            'author' => 'Kamaltz',
            'homepage' => "https://github.com/{$this->github_repo}",
            'short_description' => 'Advanced floating navbar with WooCommerce integration',
            'sections' => array(
                'description' => 'Advanced floating navbar with Elementor-like admin panel, WooCommerce integration, customizable icons, real-time product search, wishlist/cart integration.',
                'changelog' => $this->get_changelog()
            ),
            'download_link' => $this->get_download_url($remote_version)
        );
    }
    
    private function get_remote_version() {
        $transient_key = 'floating_navbar_remote_version';
        $cached_version = get_transient($transient_key);
        
        if ($cached_version !== false) {
            return $cached_version;
        }
        
        $request = wp_remote_get("https://api.github.com/repos/{$this->github_repo}/releases/latest", array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json'
            )
        ));
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            $remote_version = isset($data['tag_name']) ? ltrim($data['tag_name'], 'v') : $this->version;
            
            // Cache for 5 minutes
            set_transient($transient_key, $remote_version, 5 * MINUTE_IN_SECONDS);
            return $remote_version;
        }
        
        return $this->version;
    }
    
    private function get_download_url($version) {
        return "https://github.com/{$this->github_repo}/releases/download/v{$version}/floating-navbar-plugin.zip";
    }
    
    private function get_changelog() {
        return 'Check GitHub releases for detailed changelog.';
    }
    
    public function force_update_check() {
        if (isset($_GET['force-check']) && $_GET['force-check'] === '1') {
            delete_transient('floating_navbar_remote_version');
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }
    
    public function clear_version_cache() {
        delete_transient('floating_navbar_remote_version');
    }
    
    public function fix_plugin_folder($source, $remote_source, $upgrader, $hook_extra) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_slug) {
            $correct_folder = WP_PLUGIN_DIR . '/floating-navbar-wplugin';
            if (basename($source) !== 'floating-navbar-wplugin') {
                $new_source = dirname($source) . '/floating-navbar-wplugin';
                if (rename($source, $new_source)) {
                    return $new_source;
                }
            }
        }
        return $source;
    }
}

new FloatingNavbarUpdater(TIRTONIC_NAV_PATH . 'floating-navbar.php');