<?php
/**
 * Plugin Name: Floating Navbar
 * Description: Advanced floating navbar with Elementor-like admin panel, WooCommerce integration, customizable icons, real-time product search, wishlist/cart integration, dan comprehensive style customization. Perfectly matches orpcatalog.id design with enhanced functionality.
 * Version: 4.9.7
 * Author: Kamaltz
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Text Domain: floating-navbar
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('TIRTONIC_NAV_URL', plugin_dir_url(__FILE__));
define('TIRTONIC_NAV_PATH', plugin_dir_path(__FILE__));
define('TIRTONIC_NAV_VERSION', '2.0.1');

// Include main functionality
require_once TIRTONIC_NAV_PATH . 'wordpress-floating-navbar.php';
require_once TIRTONIC_NAV_PATH . 'updater.php';
require_once TIRTONIC_NAV_PATH . 'version-check.php';

// Enqueue assets
function tirtonic_plugin_enqueue_assets() {
    wp_enqueue_style(
        'tirtonic-floating-navbar',
        TIRTONIC_NAV_URL . 'floating-navbar.css',
        array(),
        TIRTONIC_NAV_VERSION
    );
    
    wp_enqueue_script(
        'tirtonic-floating-navbar',
        TIRTONIC_NAV_URL . 'floating-navbar.js',
        array('jquery'),
        TIRTONIC_NAV_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'tirtonic_plugin_enqueue_assets');

// Plugin activation hook
register_activation_hook(__FILE__, 'tirtonic_floating_navbar_activate');

function tirtonic_floating_navbar_activate() {
    // Set default options
    $default_options = array(
        'enable_navbar' => true,
        'navbar_title' => 'Quick Access',
        'navbar_position' => 'top-right',
        'primary_color' => '#ffc83a',
        'secondary_color' => '#ffb800',
        'text_color' => '#000000',
        'border_radius' => 10,
        'enable_search' => true,
        'enable_cart' => true,
        'search_placeholder' => 'Search products...',
        'show_logo' => false,
        'logo_url' => '',
        'menu_items' => array(
            array('title' => 'Home', 'url' => home_url()),
            array('title' => 'Shop', 'url' => 'https://tirtonic.com/store'),
            array('title' => 'Articles', 'url' => 'https://tirtonic.com/article'),
            array('title' => 'About', 'url' => 'https://tirtonic.com/stores'),
            array('title' => 'Contact', 'url' => 'https://tirtonic.com/contact')
        )
    );
    
    add_option('tirtonic_floating_nav_settings', $default_options);
    update_option('floating_navbar_version', TIRTONIC_NAV_VERSION);
    
    // Clean up any old remote access keys
    delete_option('tirtonic_remote_key');
    
    set_transient('tirtonic_floating_navbar_activated', true, 30);
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'tirtonic_floating_navbar_deactivate');

function tirtonic_floating_navbar_deactivate() {
    // Cleanup if needed
}

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tirtonic_floating_navbar_settings_link');

function tirtonic_floating_navbar_settings_link($links) {
    $settings_link = '<a href="admin.php?page=tirtonic-floating-navbar">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add admin notice for successful activation
add_action('admin_notices', 'tirtonic_floating_navbar_admin_notice');

function tirtonic_floating_navbar_admin_notice() {
    if (get_transient('tirtonic_floating_navbar_activated')) {
        ?>
<div class="notice notice-success is-dismissible">
    <p><strong>Floating Navbar</strong> has been activated successfully! <a
            href="<?php echo admin_url('admin.php?page=tirtonic-floating-navbar'); ?>">Configure settings</a></p>
</div>
<?php
        delete_transient('tirtonic_floating_navbar_activated');
    }
}

// Force update check on plugins page
add_action('load-plugins.php', 'tirtonic_force_update_check');
function tirtonic_force_update_check() {
    delete_site_transient('update_plugins');
    delete_transient('floating_navbar_remote_version');
    delete_transient('floating_navbar_latest_version');
}


?>