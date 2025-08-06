<?php
/**
 * Version Check Endpoint
 */

if (!defined('ABSPATH')) {
    exit;
}

class FloatingNavbarVersionCheck {
    
    public function __construct() {
        add_action('wp_ajax_floating_navbar_check_version', array($this, 'check_version'));
        add_action('wp_ajax_nopriv_floating_navbar_check_version', array($this, 'check_version'));
        add_action('admin_init', array($this, 'maybe_show_update_notice'));
    }
    
    public function check_version() {
        $current_version = get_option('floating_navbar_version', '1.0.0');
        $remote_version = $this->get_latest_version();
        
        wp_send_json(array(
            'current' => $current_version,
            'latest' => $remote_version,
            'needs_update' => version_compare($current_version, $remote_version, '<')
        ));
    }
    
    public function maybe_show_update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        $current_version = get_option('floating_navbar_version', '1.0.0');
        $plugin_version = TIRTONIC_NAV_VERSION;
        
        // Update stored version if plugin was updated
        if (version_compare($current_version, $plugin_version, '<')) {
            update_option('floating_navbar_version', $plugin_version);
            delete_transient('floating_navbar_latest_version');
            delete_transient('floating_navbar_remote_version');
            return;
        }
        
        $remote_version = $this->get_latest_version();
        
        if (version_compare($current_version, $remote_version, '<')) {
            add_action('admin_notices', array($this, 'update_notice'));
        }
    }
    
    public function update_notice() {
        $remote_version = $this->get_latest_version();
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Floating Navbar:</strong> 
                Version <?php echo esc_html($remote_version); ?> is available. 
                <a href="<?php echo admin_url('plugins.php'); ?>">Update now</a>
            </p>
        </div>
        <?php
    }
    
    private function get_latest_version() {
        $transient_key = 'floating_navbar_latest_version';
        $cached = get_transient($transient_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get('https://api.github.com/repos/kamaltz/floating-navbar-wplugin/releases/latest');
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $version = isset($data['tag_name']) ? ltrim($data['tag_name'], 'v') : '1.0.0';
            
            set_transient($transient_key, $version, 1 * HOUR_IN_SECONDS);
            return $version;
        }
        
        return '1.0.0';
    }
}

new FloatingNavbarVersionCheck();