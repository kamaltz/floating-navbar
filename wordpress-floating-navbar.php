<?php
/**
 * Enhanced WordPress Floating Navbar Core Functions
 * Berdasarkan design orpcatalog.id dengan advanced customization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TirtonicAdvancedFloatingNavbar {
    
    private $options;
    private $icon_library;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Register wp_footer hook for navbar rendering
        add_action('wp_footer', array($this, 'render_navbar'), 999);
        add_action('wp_ajax_tirtonic_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_nopriv_tirtonic_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_tirtonic_get_cart_count', array($this, 'ajax_get_cart_count'));
        add_action('wp_ajax_nopriv_tirtonic_get_cart_count', array($this, 'ajax_get_cart_count'));
        add_action('wp_ajax_tirtonic_get_newest_products', array($this, 'ajax_get_newest_products'));
        add_action('wp_ajax_nopriv_tirtonic_get_newest_products', array($this, 'ajax_get_newest_products'));
        add_action('wp_ajax_tirtonic_get_cart_contents', array($this, 'ajax_get_cart_contents'));
        add_action('wp_ajax_nopriv_tirtonic_get_cart_contents', array($this, 'ajax_get_cart_contents'));
        add_action('wp_ajax_tirtonic_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        add_action('wp_ajax_nopriv_tirtonic_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        add_action('wp_ajax_tirtonic_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_tirtonic_get_preview', array($this, 'ajax_get_preview'));
        add_action('wp_ajax_tirtonic_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_tirtonic_check_update', array($this, 'ajax_check_update'));
        add_action('wp_ajax_tirtonic_update_plugin', array($this, 'ajax_update_plugin'));
        add_action('wp_ajax_tirtonic_clear_update_cache', array($this, 'ajax_clear_update_cache'));
        add_action('wp_ajax_tirtonic_get_settings', array($this, 'ajax_get_settings'));
        add_action('wp_ajax_tirtonic_test_feature', array($this, 'ajax_test_feature'));
        
        $this->icon_library = $this->get_icon_library();
        
        // Load options after WordPress is fully initialized
        add_action('wp_loaded', array($this, 'load_options'));
        
        // Ensure navbar is rendered on frontend - register hook properly
        add_action('wp_footer', array($this, 'render_navbar'), 999);
    }
    
    public function load_options() {
        $this->options = get_option('tirtonic_floating_nav_settings', $this->get_default_options());
    }
    
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_enhanced_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function enqueue_enhanced_scripts() {
        // Enqueue CSS
        wp_enqueue_style(
            'tirtonic-floating-navbar',
            plugin_dir_url(__FILE__) . 'floating-navbar.css',
            array(),
            TIRTONIC_NAV_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'tirtonic-floating-navbar',
            plugin_dir_url(__FILE__) . 'floating-navbar.js',
            array('jquery'),
            TIRTONIC_NAV_VERSION,
            true
        );
        
        wp_localize_script('tirtonic-floating-navbar', 'tirtonicAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tirtonic_nonce'),
            'woocommerce_active' => class_exists('WooCommerce'),
            'cart_url' => class_exists('WooCommerce') ? wc_get_cart_url() : '',
            'checkout_url' => class_exists('WooCommerce') ? wc_get_checkout_url() : '',
            'shop_url' => class_exists('WooCommerce') ? wc_get_page_permalink('shop') : '',
            'settings' => $this->options
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_tirtonic-floating-navbar' !== $hook) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_script(
            'tirtonic-admin',
            plugin_dir_url(__FILE__) . 'admin/admin.js',
            array('jquery', 'wp-color-picker'),
            TIRTONIC_NAV_VERSION,
            true
        );
        
        wp_enqueue_style(
            'tirtonic-admin',
            plugin_dir_url(__FILE__) . 'admin/admin.css',
            array('wp-color-picker'),
            TIRTONIC_NAV_VERSION
        );
        
        wp_localize_script('tirtonic-admin', 'tirtonicAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tirtonic_admin_nonce'),
            'icon_library' => $this->icon_library,
            'upload_title' => 'Choose Icon',
            'upload_button' => 'Use this icon'
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Floating Navbar',
            'Floating Navbar',
            'manage_options',
            'tirtonic-floating-navbar',
            array($this, 'admin_page'),
            'dashicons-menu',
            30
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap tirtonic-admin-wrap">
            <h1>Floating Navbar</h1>
            
            <div class="tirtonic-admin-container">
                <!-- Sidebar Navigation -->
                <div class="tirtonic-admin-sidebar">
                    <nav class="tirtonic-admin-nav">
                        <ul>
                            <li><a href="#general" class="nav-tab active" data-tab="general">
                                <span class="dashicons dashicons-admin-generic"></span>
                                General Settings
                            </a></li>
                            <li><a href="#style" class="nav-tab" data-tab="style">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                Style Customization
                            </a></li>
                            <li><a href="#icons" class="nav-tab" data-tab="icons">
                                <span class="dashicons dashicons-format-image"></span>
                                Icon Management
                            </a></li>
                            <li><a href="#menu" class="nav-tab" data-tab="menu">
                                <span class="dashicons dashicons-menu"></span>
                                Menu Builder
                            </a></li>
                            <li><a href="#responsive" class="nav-tab" data-tab="responsive">
                                <span class="dashicons dashicons-smartphone"></span>
                                Responsive
                            </a></li>
                            <li><a href="#advanced" class="nav-tab" data-tab="advanced">
                                <span class="dashicons dashicons-admin-tools"></span>
                                Advanced
                            </a></li>
                            <li><a href="#debug" class="nav-tab" data-tab="debug">
                                <span class="dashicons dashicons-admin-generic"></span>
                                Debug Panel
                            </a></li>

                            <li><a href="#preview" class="nav-tab" data-tab="preview">
                                <span class="dashicons dashicons-visibility"></span>
                                Live Preview
                            </a></li>
                        </ul>
                    </nav>
                </div>
                
                <!-- Main Content Area -->
                <div class="tirtonic-admin-content">
                    <form id="tirtonic-settings-form" method="post">
                        <?php wp_nonce_field('tirtonic_settings_nonce', 'tirtonic_nonce'); ?>
                        
                        <!-- General Settings Tab -->
                        <div id="general" class="tab-content active">
                            <div class="tirtonic-section">
                                <h2>General Settings</h2>
                                <p class="description">Configure basic settings for your floating navbar.</p>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Enable Navbar</th>
                                        <td>
                                            <label class="tirtonic-toggle">
                                                <input type="checkbox" name="enable_navbar" value="1" <?php checked($this->options['enable_navbar']); ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <p class="description">Turn the floating navbar on or off</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Navbar Title</th>
                                        <td>
                                            <input type="text" name="navbar_title" value="<?php echo esc_attr($this->options['navbar_title']); ?>" class="regular-text" placeholder="Quick Access">
                                            <p class="description">Title displayed on the navbar header</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Position</th>
                                        <td>
                                            <select name="navbar_position" class="regular-text">
                                                <option value="top-right" <?php selected($this->options['navbar_position'], 'top-right'); ?>>Top Right</option>
                                                <option value="top-left" <?php selected($this->options['navbar_position'], 'top-left'); ?>>Top Left</option>
                                                <option value="bottom-right" <?php selected($this->options['navbar_position'], 'bottom-right'); ?>>Bottom Right</option>
                                                <option value="bottom-left" <?php selected($this->options['navbar_position'], 'bottom-left'); ?>>Bottom Left</option>
                                            </select>
                                            <p class="description">Choose where to position the floating navbar</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Auto Hide on Scroll</th>
                                        <td>
                                            <label class="tirtonic-toggle">
                                                <input type="checkbox" name="auto_hide" value="1" <?php checked($this->options['auto_hide'] ?? true); ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <p class="description">Hide navbar when scrolling down, show when scrolling up</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Reset Settings</th>
                                        <td>
                                            <button type="button" class="button button-secondary" id="reset-to-default">Reset to Default</button>
                                            <p class="description">Reset all settings to default values</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Style Customization Tab -->
                        <div id="style" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Style Customization</h2>
                                <p class="description">Customize the appearance of your floating navbar.</p>
                                
                                <div class="tirtonic-style-grid">
                                    <div class="style-group">
                                        <h3>Colors</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Primary Color</th>
                                                <td>
                                                    <div class="rgba-color-picker">
                                                        <input type="color" name="primary_color" value="<?php echo esc_attr($this->options['primary_color'] ?? '#ffc83a'); ?>" class="color-input">
                                                        <label>Opacity: <input type="range" name="primary_opacity" min="0" max="1" step="0.1" value="<?php echo esc_attr($this->options['primary_opacity'] ?? '1'); ?>" class="opacity-slider"> <span class="opacity-value"><?php echo esc_attr(($this->options['primary_opacity'] ?? 1) * 100); ?>%</span></label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Secondary Color</th>
                                                <td>
                                                    <div class="rgba-color-picker">
                                                        <input type="color" name="secondary_color" value="<?php echo esc_attr($this->options['secondary_color'] ?? '#ffb800'); ?>" class="color-input">
                                                        <label>Opacity: <input type="range" name="secondary_opacity" min="0" max="1" step="0.1" value="<?php echo esc_attr($this->options['secondary_opacity'] ?? '1'); ?>" class="opacity-slider"> <span class="opacity-value"><?php echo esc_attr(($this->options['secondary_opacity'] ?? 1) * 100); ?>%</span></label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Text Color</th>
                                                <td>
                                                    <input type="text" name="text_color" value="<?php echo esc_attr($this->options['text_color']); ?>" class="color-picker" data-default-color="#000000">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Background Color</th>
                                                <td>
                                                    <input type="text" name="background_color" value="<?php echo esc_attr($this->options['background_color'] ?? '#ffffff'); ?>" class="color-picker" data-default-color="#ffffff">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="style-group">
                                        <h3>Dimensions</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Border Radius</th>
                                                <td>
                                                    <input type="range" name="border_radius" value="<?php echo esc_attr($this->options['border_radius']); ?>" min="0" max="50" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['border_radius']); ?>px</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Shadow Intensity</th>
                                                <td>
                                                    <input type="range" name="shadow_intensity" value="<?php echo esc_attr($this->options['shadow_intensity'] ?? 20); ?>" min="0" max="50" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['shadow_intensity'] ?? 20); ?>%</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Icon Size</th>
                                                <td>
                                                    <input type="range" name="icon_size" value="<?php echo esc_attr($this->options['icon_size'] ?? 24); ?>" min="16" max="48" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['icon_size'] ?? 24); ?>px</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Navbar Scale</th>
                                                <td>
                                                    <input type="range" name="navbar_scale" value="<?php echo esc_attr($this->options['navbar_scale'] ?? 100); ?>" min="50" max="150" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['navbar_scale'] ?? 100); ?>%</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="style-group">
                                        <h3>Typography</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Title Font Size</th>
                                                <td>
                                                    <input type="range" name="title_font_size" value="<?php echo esc_attr($this->options['title_font_size'] ?? 16); ?>" min="12" max="24" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['title_font_size'] ?? 16); ?>px</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Menu Font Size</th>
                                                <td>
                                                    <input type="range" name="menu_font_size" value="<?php echo esc_attr($this->options['menu_font_size'] ?? 14); ?>" min="10" max="20" class="range-slider">
                                                    <span class="range-value"><?php echo esc_html($this->options['menu_font_size'] ?? 14); ?>px</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Font Weight</th>
                                                <td>
                                                    <select name="font_weight" class="regular-text">
                                                        <option value="400" <?php selected($this->options['font_weight'] ?? '600', '400'); ?>>Normal</option>
                                                        <option value="500" <?php selected($this->options['font_weight'] ?? '600', '500'); ?>>Medium</option>
                                                        <option value="600" <?php selected($this->options['font_weight'] ?? '600', '600'); ?>>Semi Bold</option>
                                                        <option value="700" <?php selected($this->options['font_weight'] ?? '600', '700'); ?>>Bold</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="tirtonic-section">
                                    <h3>Custom CSS</h3>
                                    <p class="description">Add custom CSS to further customize your navbar appearance.</p>
                                    <textarea name="custom_css" rows="8" class="large-text code" placeholder="/* Custom CSS */&#10;.tirtonic-floating-nav {&#10;    /* Your custom styles */&#10;}"><?php echo esc_textarea($this->options['custom_css'] ?? ''); ?></textarea>
                                    
                                    <h4>Mobile Custom CSS</h4>
                                    <p class="description">Add custom CSS specifically for mobile devices (767px and below).</p>
                                    <textarea name="custom_mobile_css" rows="8" class="large-text code" placeholder="/* Mobile Custom CSS */&#10;@media (max-width: 767px) {&#10;    .tirtonic-floating-nav {&#10;        /* Your mobile styles */&#10;    }&#10;}"><?php echo esc_textarea($this->options['custom_mobile_css'] ?? ''); ?></textarea>
                                    
                                    <div class="css-guide">
                                        <h4>CSS Class Reference:</h4>
                                        <ul>
                                            <li><code>.tirtonic-floating-nav</code> - Main navbar container</li>
                                            <li><code>.tirtonic-nav-header</code> - Navbar header section</li>
                                            <li><code>.tirtonic-nav-title</code> - Navbar title text</li>
                                            <li><code>.tirtonic-nav-search</code> - Search icon</li>
                                            <li><code>.tirtonic-nav-cart</code> - Cart/wishlist icon</li>
                                            <li><code>.tirtonic-nav-content</code> - Dropdown content area</li>
                                            <li><code>.tirtonic-nav-menu</code> - Main menu links</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Icon Management Tab -->
                        <div id="icons" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Icon Management</h2>
                                <p class="description">Customize icons and their functionality.</p>
                                
                                <div class="icon-management-grid">
                                    <div class="icon-group">
                                        <h3>Search Icon</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Enable Search</th>
                                                <td>
                                                    <label class="tirtonic-toggle">
                                                        <input type="checkbox" name="enable_search" value="1" <?php checked($this->options['enable_search']); ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Search Icon</th>
                                                <td>
                                                    <div class="icon-selector">
                                                        <div class="current-icon">
                                                            <?php echo $this->render_icon($this->options['search_icon'] ?? 'search'); ?>
                                                        </div>
                                                        <button type="button" class="button icon-library-btn" data-target="search_icon">Choose Icon</button>
                                                        <button type="button" class="button upload-icon-btn" data-target="search_icon">Upload Custom</button>
                                                        <input type="hidden" name="search_icon" value="<?php echo esc_attr($this->options['search_icon'] ?? 'search'); ?>">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Search Action</th>
                                                <td>
                                                    <select name="search_action" class="regular-text">
                                                        <option value="product_search" <?php selected($this->options['search_action'] ?? 'product_search', 'product_search'); ?>>Product Search with Recommendations</option>
                                                        <option value="site_search" <?php selected($this->options['search_action'] ?? 'product_search', 'site_search'); ?>>Site-wide Search</option>
                                                        <option value="custom_url" <?php selected($this->options['search_action'] ?? 'product_search', 'custom_url'); ?>>Custom URL</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr class="search-custom-url" style="display: none;">
                                                <th scope="row">Custom URL</th>
                                                <td>
                                                    <input type="url" name="search_custom_url" value="<?php echo esc_attr($this->options['search_custom_url'] ?? ''); ?>" class="regular-text">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Search Placeholder</th>
                                                <td>
                                                    <input type="text" name="search_placeholder" value="<?php echo esc_attr($this->options['search_placeholder'] ?? 'Search products...'); ?>" class="regular-text">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="icon-group">
                                        <h3>Arrow Icon</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Arrow Icon</th>
                                                <td>
                                                    <div class="icon-selector">
                                                        <div class="current-icon">
                                                            <?php echo $this->render_icon($this->options['arrow_icon'] ?? 'arrow_down'); ?>
                                                        </div>
                                                        <button type="button" class="button icon-library-btn" data-target="arrow_icon">Choose Icon</button>
                                                        <input type="hidden" name="arrow_icon" value="<?php echo esc_attr($this->options['arrow_icon'] ?? 'arrow_down'); ?>">
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="icon-group">
                                        <h3>Cart/Wishlist Icon</h3>
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row">Enable Cart</th>
                                                <td>
                                                    <label class="tirtonic-toggle">
                                                        <input type="checkbox" name="enable_cart" value="1" <?php checked($this->options['enable_cart']); ?>>
                                                        <span class="slider"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Cart Icon</th>
                                                <td>
                                                    <div class="icon-selector">
                                                        <div class="current-icon">
                                                            <?php echo $this->render_icon($this->options['cart_icon'] ?? 'cart'); ?>
                                                        </div>
                                                        <button type="button" class="button icon-library-btn" data-target="cart_icon">Choose Icon</button>
                                                        <button type="button" class="button upload-icon-btn" data-target="cart_icon">Upload Custom</button>
                                                        <input type="hidden" name="cart_icon" value="<?php echo esc_attr($this->options['cart_icon'] ?? 'cart'); ?>">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Cart Action</th>
                                                <td>
                                                    <select name="cart_action" class="regular-text">
                                                        <option value="cart_page" <?php selected($this->options['cart_action'] ?? 'cart_page', 'cart_page'); ?>>Go to Cart Page</option>
                                                        <option value="cart_drawer" <?php selected($this->options['cart_action'] ?? 'cart_page', 'cart_drawer'); ?>>Open Cart Drawer</option>
                                                        <option value="wishlist" <?php selected($this->options['cart_action'] ?? 'cart_page', 'wishlist'); ?>>Show Wishlist</option>
                                                        <option value="custom_url" <?php selected($this->options['cart_action'] ?? 'cart_page', 'custom_url'); ?>>Custom URL</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr class="cart-custom-url" style="display: none;">
                                                <th scope="row">Custom URL</th>
                                                <td>
                                                    <input type="url" name="cart_custom_url" value="<?php echo esc_attr($this->options['cart_custom_url'] ?? ''); ?>" class="regular-text">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Checkout Button Text</th>
                                                <td>
                                                    <input type="text" name="checkout_button_text" value="<?php echo esc_attr($this->options['checkout_button_text'] ?? 'Checkout'); ?>" class="regular-text">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Checkout Button Action</th>
                                                <td>
                                                    <select name="checkout_button_action" class="regular-text">
                                                        <option value="checkout" <?php selected($this->options['checkout_button_action'] ?? 'checkout', 'checkout'); ?>>Go to Checkout</option>
                                                        <option value="cart" <?php selected($this->options['checkout_button_action'] ?? 'checkout', 'cart'); ?>>Go to Cart</option>
                                                        <option value="whatsapp" <?php selected($this->options['checkout_button_action'] ?? 'checkout', 'whatsapp'); ?>>WhatsApp Chat</option>
                                                        <option value="custom_url" <?php selected($this->options['checkout_button_action'] ?? 'checkout', 'custom_url'); ?>>Custom URL</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Checkout Custom URL</th>
                                                <td>
                                                    <input type="url" name="checkout_custom_url" value="<?php echo esc_attr($this->options['checkout_custom_url'] ?? ''); ?>" class="regular-text">
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Additional Icons Section -->
                                <div class="tirtonic-section">
                                    <h3>Additional Icons</h3>
                                    <div id="additional-icons">
                                        <?php $this->render_additional_icons(); ?>
                                    </div>
                                    <button type="button" class="button button-secondary" id="add-icon">Add Custom Icon</button>
                                    <p class="description">Add custom icons with specific actions</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Menu Builder Tab -->
                        <div id="menu" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Menu Builder</h2>
                                <p class="description">Build your navigation menu with drag & drop functionality.</p>
                                
                                <div class="menu-builder">
                                    <div class="menu-items" id="menu-items-container">
                                        <?php $this->render_menu_builder(); ?>
                                    </div>
                                    
                                    <div class="menu-actions">
                                        <button type="button" class="button button-primary" id="add-menu-item">Add Menu Item</button>
                                        <button type="button" class="button button-secondary" id="add-menu-category">Add Category</button>
                                    </div>
                                </div>
                                
                                <div class="menu-settings">
                                    <h3>Menu Settings</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Show Logo</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="show_logo" value="1" <?php checked($this->options['show_logo'] ?? false); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                                <p class="description">Display logo at the top of menu section</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Logo Image</th>
                                            <td>
                                                <div class="logo-upload-section">
                                                    <div class="logo-preview">
                                                        <?php if (!empty($this->options['logo_url'])): ?>
                                                            <img src="<?php echo esc_url($this->options['logo_url']); ?>" alt="Logo" style="max-width: 120px; max-height: 40px; object-fit: contain;">
                                                        <?php else: ?>
                                                            <div class="no-logo-placeholder" style="width: 120px; height: 40px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">No Logo</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="logo-controls" style="margin-top: 10px;">
                                                        <button type="button" class="button upload-logo-btn">Upload Logo</button>
                                                        <button type="button" class="button remove-logo-btn" style="margin-left: 10px;">Remove Logo</button>
                                                        <input type="hidden" name="logo_url" value="<?php echo esc_attr($this->options['logo_url'] ?? ''); ?>">
                                                    </div>
                                                    <p class="description">Upload a logo image (recommended size: 120x40px)</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Menu Style</th>
                                            <td>
                                                <select name="menu_style" class="regular-text">
                                                    <option value="simple" <?php selected($this->options['menu_style'] ?? 'simple', 'simple'); ?>>Simple List</option>
                                                    <option value="mega" <?php selected($this->options['menu_style'] ?? 'simple', 'mega'); ?>>Mega Menu</option>
                                                    <option value="accordion" <?php selected($this->options['menu_style'] ?? 'simple', 'accordion'); ?>>Accordion</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Show Menu Icons</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="show_menu_icons" value="1" <?php checked($this->options['show_menu_icons'] ?? true); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="menu-settings">
                                    <h3>Footer Menu Settings</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Footer Menu Item 1</th>
                                            <td>
                                                <input type="text" name="footer_menu_1_title" value="<?php echo esc_attr($this->options['footer_menu_1_title'] ?? ''); ?>" placeholder="Menu Title (e.g. Ask personal shopper)" class="regular-text">
                                                <input type="url" name="footer_menu_1_url" value="<?php echo esc_attr($this->options['footer_menu_1_url'] ?? ''); ?>" placeholder="Menu URL" class="regular-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Footer Menu Item 2</th>
                                            <td>
                                                <input type="text" name="footer_menu_2_title" value="<?php echo esc_attr($this->options['footer_menu_2_title'] ?? ''); ?>" placeholder="Menu Title (e.g. Delivery tracking)" class="regular-text">
                                                <input type="url" name="footer_menu_2_url" value="<?php echo esc_attr($this->options['footer_menu_2_url'] ?? ''); ?>" placeholder="Menu URL" class="regular-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Footer Menu Item 3</th>
                                            <td>
                                                <input type="text" name="footer_menu_3_title" value="<?php echo esc_attr($this->options['footer_menu_3_title'] ?? ''); ?>" placeholder="Menu Title (e.g. Follow our Instagram)" class="regular-text">
                                                <input type="url" name="footer_menu_3_url" value="<?php echo esc_attr($this->options['footer_menu_3_url'] ?? ''); ?>" placeholder="Menu URL" class="regular-text">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Responsive Tab -->
                        <div id="responsive" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Responsive Settings</h2>
                                <p class="description">Configure navbar behavior for different devices.</p>
                                
                                <div class="responsive-device-tabs">
                                    <button type="button" class="device-tab active" data-device="desktop">Desktop</button>
                                    <button type="button" class="device-tab" data-device="tablet">Tablet</button>
                                    <button type="button" class="device-tab" data-device="mobile">Mobile</button>
                                </div>
                                
                                <!-- Desktop Settings -->
                                <div class="device-settings active" data-device="desktop">
                                    <h3>Desktop (1024px+)</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Position</th>
                                            <td>
                                                <select name="desktop_position" class="regular-text">
                                                    <option value="top-right" <?php selected($this->options['desktop_position'] ?? 'top-right', 'top-right'); ?>>Top Right</option>
                                                    <option value="top-left" <?php selected($this->options['desktop_position'] ?? 'top-right', 'top-left'); ?>>Top Left</option>
                                                    <option value="bottom-right" <?php selected($this->options['desktop_position'] ?? 'top-right', 'bottom-right'); ?>>Bottom Right</option>
                                                    <option value="bottom-left" <?php selected($this->options['desktop_position'] ?? 'top-right', 'bottom-left'); ?>>Bottom Left</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Scale</th>
                                            <td>
                                                <input type="range" name="desktop_scale" value="<?php echo esc_attr($this->options['desktop_scale'] ?? 100); ?>" min="50" max="150" class="range-slider">
                                                <span class="range-value"><?php echo esc_html($this->options['desktop_scale'] ?? 100); ?>%</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Hide on Desktop</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="hide_desktop" value="1" <?php checked($this->options['hide_desktop'] ?? false); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Tablet Settings -->
                                <div class="device-settings" data-device="tablet">
                                    <h3>Tablet (768px - 1023px)</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Position</th>
                                            <td>
                                                <select name="tablet_position" class="regular-text">
                                                    <option value="top-right" <?php selected($this->options['tablet_position'] ?? 'top-right', 'top-right'); ?>>Top Right</option>
                                                    <option value="top-left" <?php selected($this->options['tablet_position'] ?? 'top-right', 'top-left'); ?>>Top Left</option>
                                                    <option value="bottom-right" <?php selected($this->options['tablet_position'] ?? 'top-right', 'bottom-right'); ?>>Bottom Right</option>
                                                    <option value="bottom-left" <?php selected($this->options['tablet_position'] ?? 'top-right', 'bottom-left'); ?>>Bottom Left</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Scale</th>
                                            <td>
                                                <input type="range" name="tablet_scale" value="<?php echo esc_attr($this->options['tablet_scale'] ?? 90); ?>" min="50" max="150" class="range-slider">
                                                <span class="range-value"><?php echo esc_html($this->options['tablet_scale'] ?? 90); ?>%</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Hide on Tablet</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="hide_tablet" value="1" <?php checked($this->options['hide_tablet'] ?? false); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Mobile Settings -->
                                <div class="device-settings" data-device="mobile">
                                    <h3>Mobile (767px and below)</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Position</th>
                                            <td>
                                                <select name="mobile_position" class="regular-text">
                                                    <option value="bottom-center" <?php selected($this->options['mobile_position'] ?? 'bottom-center', 'bottom-center'); ?>>Bottom Center</option>
                                                    <option value="bottom-right" <?php selected($this->options['mobile_position'] ?? 'bottom-center', 'bottom-right'); ?>>Bottom Right</option>
                                                    <option value="bottom-left" <?php selected($this->options['mobile_position'] ?? 'bottom-center', 'bottom-left'); ?>>Bottom Left</option>
                                                    <option value="top-right" <?php selected($this->options['mobile_position'] ?? 'bottom-center', 'top-right'); ?>>Top Right</option>
                                                    <option value="top-left" <?php selected($this->options['mobile_position'] ?? 'bottom-center', 'top-left'); ?>>Top Left</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Scale</th>
                                            <td>
                                                <input type="range" name="mobile_scale" value="<?php echo esc_attr($this->options['mobile_scale'] ?? 80); ?>" min="50" max="150" class="range-slider">
                                                <span class="range-value"><?php echo esc_html($this->options['mobile_scale'] ?? 80); ?>%</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Header Position</th>
                                            <td>
                                                <select name="mobile_position" class="regular-text">
                                                    <option value="bottom" <?php selected($this->options['mobile_position'] ?? 'bottom', 'bottom'); ?>>Bottom</option>
                                                    <option value="top" <?php selected($this->options['mobile_position'] ?? 'bottom', 'top'); ?>>Top</option>
                                                </select>
                                                <p class="description">Position of navbar header on mobile devices</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Full Width on Mobile</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="mobile_full_width" value="1" <?php checked($this->options['mobile_full_width'] ?? true); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Hide on Mobile</th>
                                            <td>
                                                <label class="tirtonic-toggle">
                                                    <input type="checkbox" name="hide_mobile" value="1" <?php checked($this->options['hide_mobile'] ?? false); ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Tab -->
                        <div id="advanced" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Advanced Settings</h2>
                                <p class="description">Advanced configuration options for power users.</p>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Animation Duration</th>
                                        <td>
                                            <input type="number" name="animation_duration" value="<?php echo esc_attr($this->options['animation_duration'] ?? 500); ?>" min="100" max="2000" step="100"> ms
                                            <p class="description">Duration of open/close animations</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Z-Index</th>
                                        <td>
                                            <input type="number" name="z_index" value="<?php echo esc_attr($this->options['z_index'] ?? 999); ?>" min="1" max="9999">
                                            <p class="description">CSS z-index value for the navbar</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Mobile Breakpoint</th>
                                        <td>
                                            <input type="number" name="mobile_breakpoint" value="<?php echo esc_attr($this->options['mobile_breakpoint'] ?? 768); ?>" min="320" max="1024"> px
                                            <p class="description">Screen width below which mobile layout is used</p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">Exclude Pages</th>
                                        <td>
                                            <textarea name="exclude_pages" rows="3" class="large-text" placeholder="/checkout&#10;/cart&#10;/my-account"><?php echo esc_textarea($this->options['exclude_pages'] ?? ''); ?></textarea>
                                            <p class="description">Enter page URLs to exclude navbar (one per line). Use relative URLs like /checkout or /cart</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Auto Update</th>
                                        <td>
                                            <label class="tirtonic-toggle">
                                                <input type="checkbox" name="enable_auto_update" value="1" <?php checked($this->options['enable_auto_update'] ?? true); ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <p class="description">Automatically update plugin from GitHub releases</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Update Channel</th>
                                        <td>
                                            <select name="update_channel" class="regular-text">
                                                <option value="stable" <?php selected($this->options['update_channel'] ?? 'stable', 'stable'); ?>>Stable Releases</option>
                                                <option value="beta" <?php selected($this->options['update_channel'] ?? 'stable', 'beta'); ?>>Beta Releases</option>
                                            </select>
                                            <p class="description">Choose update channel</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Plugin Updates</th>
                                        <td>
                                            <div class="update-controls">
                                                <button type="button" class="button" id="check-updates">Check for Updates</button>
                                                <button type="button" class="button button-primary" id="update-now" style="display:none;">Update Now</button>
                                                <button type="button" class="button button-secondary" id="clear-cache">Clear Update Cache</button>
                                                <div id="update-status"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Live Preview Tab -->
                        <div id="preview" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Live Preview</h2>
                                <p class="description">See how your navbar will look in real-time.</p>
                                
                                <div class="preview-container">
                                    <div class="preview-toolbar">
                                        <button type="button" class="button preview-device active" data-device="desktop">
                                            <span class="dashicons dashicons-desktop"></span> Desktop
                                        </button>
                                        <button type="button" class="button preview-device" data-device="tablet">
                                            <span class="dashicons dashicons-tablet"></span> Tablet
                                        </button>
                                        <button type="button" class="button preview-device" data-device="mobile">
                                            <span class="dashicons dashicons-smartphone"></span> Mobile
                                        </button>
                                        <button type="button" class="button button-secondary" id="refresh-preview">
                                            <span class="dashicons dashicons-update"></span> Refresh
                                        </button>
                                    </div>
                                    
                                    <div class="preview-frame">
                                        <iframe id="navbar-preview" src="about:blank" frameborder="0"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Debug Panel Tab -->
                        <div id="debug" class="tab-content">
                            <div class="tirtonic-section">
                                <h2>Debug Panel & Troubleshooting</h2>
                                <p class="description">Diagnostic tools untuk mendeteksi masalah floating navbar.</p>
                                
                                <div class="debug-panel">
                                    <div class="debug-info">
                                        <h3>System Information</h3>
                                        <ul>
                                            <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                                            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                                            <li><strong>Plugin Version:</strong> <?php echo TIRTONIC_NAV_VERSION; ?></li>
                                            <li><strong>WooCommerce:</strong> <?php echo class_exists('WooCommerce') ? 'Active' : 'Not Active'; ?></li>
                                            <li><strong>jQuery Version:</strong> <span id="jquery-version">Checking...</span></li>
                                        </ul>
                                    </div>
                                    
                                    <div class="debug-checks">
                                        <h3>Navbar Status Checker</h3>
                                        <button type="button" class="button button-primary" id="check-navbar-status">🔍 Run Status Check</button>
                                        <button type="button" class="button button-secondary" id="force-show-navbar">👁️ Force Show Navbar</button>
                                        <div id="navbar-status-results"></div>
                                    </div>
                                    
                                    <div class="debug-logs">
                                        <h3>Console Logs</h3>
                                        <textarea id="debug-console" rows="15" readonly placeholder="Console logs akan muncul di sini..."></textarea>
                                        <div class="log-controls">
                                            <button type="button" class="button" id="clear-logs">🗑️ Clear Logs</button>
                                            <button type="button" class="button" id="export-logs">📄 Export Logs</button>
                                        </div>
                                    </div>
                                    
                                    <div class="debug-actions">
                                        <h3>Quick Actions</h3>
                                        <div class="action-buttons">
                                            <button type="button" class="button" id="test-navbar-init">🔄 Test Navbar Init</button>
                                            <button type="button" class="button" id="check-conflicts">⚠️ Check Conflicts</button>
                                            <button type="button" class="button" id="reset-navbar">🔧 Reset Navbar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                        <div class="tirtonic-admin-footer">
                            <button type="submit" class="button button-primary button-large" id="save-settings-btn">Save Settings</button>
                            <button type="button" class="button button-secondary" id="test-all-features">Test All Features</button>
                            <button type="button" class="button button-secondary" id="reset-settings">Reset to Defaults</button>
                            <a href="<?php echo admin_url('plugins.php?force-check=1'); ?>" class="button button-secondary">Check for Updates</a>
                            <div id="save-status" class="save-status" style="display: none;"></div>
                            <div id="test-results" class="test-results" style="display: none;"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Icon Library Modal -->
        <div id="icon-library-modal" class="tirtonic-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Choose Icon</h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="icon-search">
                        <input type="text" placeholder="Search icons..." id="icon-search-input">
                    </div>
                    <div class="icon-grid" id="icon-grid">
                        <?php $this->render_icon_library(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .save-status {
            margin-left: 15px;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
        }
        
        .save-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .save-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .save-status.loading {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .test-results {
            margin-left: 15px;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-width: 600px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .test-results.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .test-results.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .test-category {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .test-category h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .test-item {
            margin: 5px 0;
            padding: 3px 0;
        }
        
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .test-warning { color: #ffc107; }
        
        .notice.notice-success {
            border-left-color: #46b450;
            background: #fff;
            border-left: 4px solid #46b450;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 5px 15px 2px;
            padding: 1px 12px;
        }
        
        .button-small {
            font-size: 11px !important;
            padding: 2px 8px !important;
            height: auto !important;
            line-height: 1.4 !important;
        }
        
        .debug-panel {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .debug-info, .debug-checks, .debug-logs, .debug-actions {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .debug-info ul {
            list-style: none;
            padding: 0;
            background: #fff;
            border-radius: 4px;
            padding: 15px;
        }
        
        .debug-info li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-family: monospace;
        }
        
        #debug-console {
            width: 100%;
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border: none;
            border-radius: 4px;
            resize: vertical;
        }
        
        .log-controls {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-check {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .status-check p {
            margin: 8px 0;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
        
        .status-ok {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        
        .status-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        

        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Form submission handler
            $('#tirtonic-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $saveBtn = $('#save-settings-btn');
                const $status = $('#save-status');
                
                // Show loading state
                $saveBtn.prop('disabled', true).text('Menyimpan...');
                $status.removeClass('success error').addClass('loading').text('Menyimpan pengaturan...').show();
                
                // Serialize form data
                const formData = $form.serialize() + '&action=tirtonic_save_settings';
                
                $.post(ajaxurl, formData)
                    .done(function(response) {
                        if (response.success) {
                            $status.removeClass('loading error').addClass('success').text(response.data.message);
                            
                            // Show detailed save confirmation
                            const timestamp = new Date().toLocaleString('id-ID');
                            const detailMsg = response.data.message + ' pada ' + timestamp;
                            $status.text(detailMsg);
                            
                            // Auto hide after 5 seconds
                            setTimeout(function() {
                                $status.fadeOut();
                            }, 5000);
                            
                            // Show brief success notification
                            if (typeof window.wp !== 'undefined' && window.wp.data) {
                                // WordPress 5.0+ notification
                                const notice = $('<div class="notice notice-success is-dismissible"><p><strong>Konfigurasi berhasil disimpan!</strong> Semua pengaturan navbar telah diperbarui.</p></div>');
                                $('.wrap h1').after(notice);
                                setTimeout(() => notice.fadeOut(), 4000);
                            }
                        } else {
                            $status.removeClass('loading success').addClass('error').text(response.data.message || 'Terjadi kesalahan');
                        }
                    })
                    .fail(function() {
                        $status.removeClass('loading success').addClass('error').text('Gagal menyimpan pengaturan');
                    })
                    .always(function() {
                        $saveBtn.prop('disabled', false).text('Save Settings');
                    });
            });
            
            // Menu item management
            $('#add-menu-item').on('click', function() {
                const $container = $('#menu-items-container');
                const index = $container.children().length;
                
                const menuItemHtml = `
                    <div class="menu-item" data-index="${index}">
                        <div class="menu-item-header">
                            <span class="drag-handle">⋮⋮</span>
                            <input type="text" name="menu_items[${index}][title]" placeholder="Menu Title" class="regular-text">
                            <button type="button" class="toggle-menu-item">▼</button>
                            <button type="button" class="remove-menu-item">×</button>
                        </div>
                        <div class="menu-item-content">
                            <input type="url" name="menu_items[${index}][url]" placeholder="Menu URL" class="regular-text">
                            <div class="menu-item-icon">
                                <div class="current-icon">${tirtonicAdmin.icon_library.link || ''}</div>
                                <button type="button" class="button icon-library-btn" data-target="menu_items[${index}][icon]">Choose Icon</button>
                                <input type="hidden" name="menu_items[${index}][icon]" value="link">
                            </div>
                        </div>
                    </div>
                `;
                
                $container.append(menuItemHtml);
            });
            
            // Remove menu item
            $(document).on('click', '.remove-menu-item', function() {
                $(this).closest('.menu-item').remove();
                // Re-index remaining items
                $('#menu-items-container .menu-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('input, select').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                        }
                    });
                });
            });
            
            // Footer menu validation
            $('input[name^="footer_menu_"][name$="_title"]').on('blur', function() {
                const $this = $(this);
                const index = $this.attr('name').match(/\d+/)[0];
                const $urlField = $(`input[name="footer_menu_${index}_url"]`);
                
                if ($this.val() && !$urlField.val()) {
                    $urlField.focus().attr('placeholder', 'URL diperlukan untuk menu ini');
                }
            });
            
            // Additional icons management
            $('#add-icon').on('click', function() {
                const $container = $('#additional-icons');
                const index = $container.children().length;
                
                const iconHtml = `
                    <div class="additional-icon-item" data-index="${index}">
                        <div class="icon-preview">${tirtonicAdmin.icon_library.link || ''}</div>
                        <div class="icon-fields">
                            <input type="text" name="additional_icons[${index}][title]" placeholder="Icon Title" class="regular-text">
                            <select name="additional_icons[${index}][action]" class="regular-text">
                                <option value="url">Custom URL</option>
                                <option value="search">Open Search</option>
                                <option value="cart">Open Cart</option>
                            </select>
                            <input type="url" name="additional_icons[${index}][url]" placeholder="URL (if action is Custom URL)" class="regular-text">
                        </div>
                        <div class="icon-controls">
                            <button type="button" class="button icon-library-btn" data-target="additional_icons[${index}][icon]">Choose Icon</button>
                            <button type="button" class="button remove-icon">Remove</button>
                        </div>
                        <input type="hidden" name="additional_icons[${index}][icon]" value="link">
                    </div>
                `;
                
                $container.append(iconHtml);
            });
            
            // Remove additional icon
            $(document).on('click', '.remove-icon', function() {
                $(this).closest('.additional-icon-item').remove();
            });
            
            // Logo upload functionality
            $('.upload-logo-btn').on('click', function() {
                const frame = wp.media({
                    title: 'Select Logo',
                    button: {
                        text: 'Use this logo'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('input[name="logo_url"]').val(attachment.url);
                    $('.logo-preview').html('<img src="' + attachment.url + '" alt="Logo" style="max-width: 120px; max-height: 40px; object-fit: contain;">');
                });
                
                frame.open();
            });
            
            // Remove logo
            $('.remove-logo-btn').on('click', function() {
                $('input[name="logo_url"]').val('');
                $('.logo-preview').html('<div class="no-logo-placeholder" style="width: 120px; height: 40px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">No Logo</div>');
            });
            
            // Exclude pages default values
            const $excludePages = $('textarea[name="exclude_pages"]');
            if (!$excludePages.val()) {
                $excludePages.val('cart\ncheckout\ncontact');
            }
            
            // Add quick test buttons to each section
            $('.tirtonic-section h2').each(function() {
                const sectionTitle = $(this).text().toLowerCase();
                let feature = '';
                
                if (sectionTitle.includes('general')) feature = 'navbar_display';
                else if (sectionTitle.includes('menu')) feature = 'menu_items';
                else if (sectionTitle.includes('icon')) feature = 'search_functionality';
                else if (sectionTitle.includes('responsive')) feature = 'responsive_design';
                
                if (feature) {
                    $(this).append(' <button type="button" class="button button-small test-feature-btn" data-feature="' + feature + '" style="margin-left: 10px; font-size: 11px;">Test</button>');
                }
            });
            
            // Individual feature test buttons
            $(document).on('click', '.test-feature-btn', function() {
                const feature = $(this).data('feature');
                const $btn = $(this);
                const $results = $('#test-results');
                
                $btn.prop('disabled', true).text('Testing...');
                $results.removeClass('success warning').addClass('loading').text('Testing ' + feature + '...').show();
                
                $.post(ajaxurl, {
                    action: 'tirtonic_test_feature',
                    feature: feature,
                    nonce: tirtonicAdmin.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        displayTestResults([response.data]);
                    } else {
                        $results.removeClass('loading success').addClass('warning').text('Test failed: ' + (response.data.message || 'Unknown error'));
                    }
                })
                .fail(function() {
                    $results.removeClass('loading success').addClass('warning').text('Test request failed');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Test');
                });
            });
            
            // Test all features button
            $('#test-all-features').on('click', function() {
                const $btn = $(this);
                const $results = $('#test-results');
                
                $btn.prop('disabled', true).text('Testing...');
                $results.removeClass('success warning').addClass('loading').text('Running tests...').show();
                
                $.post(ajaxurl, {
                    action: 'tirtonic_test_feature',
                    feature: 'all',
                    nonce: tirtonicAdmin.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        displayTestResults(response.data);
                    } else {
                        $results.removeClass('loading success').addClass('warning').text('Test failed: ' + (response.data.message || 'Unknown error'));
                    }
                })
                .fail(function() {
                    $results.removeClass('loading success').addClass('warning').text('Test request failed');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Test All Features');
                });
            });
            
            function displayTestResults(results) {
                const $results = $('#test-results');
                let html = '';
                let hasFailures = false;
                let hasWarnings = false;
                
                results.forEach(function(category) {
                    html += '<div class="test-category">';
                    html += '<h4>' + category.category + '</h4>';
                    
                    category.tests.forEach(function(test) {
                        let statusClass = 'test-' + test.status;
                        let statusIcon = test.status === 'pass' ? '✅' : (test.status === 'fail' ? '❌' : '⚠️');
                        
                        if (test.status === 'fail') hasFailures = true;
                        if (test.status === 'warning') hasWarnings = true;
                        
                        html += '<div class="test-item ' + statusClass + '">';
                        html += statusIcon + ' ' + test.name + ': ' + test.message;
                        html += '</div>';
                    });
                    
                    html += '</div>';
                });
                
                // Add summary at the top
                const totalTests = results.reduce((sum, cat) => sum + cat.tests.length, 0);
                const passedTests = results.reduce((sum, cat) => sum + cat.tests.filter(t => t.status === 'pass').length, 0);
                const failedTests = results.reduce((sum, cat) => sum + cat.tests.filter(t => t.status === 'fail').length, 0);
                const warningTests = results.reduce((sum, cat) => sum + cat.tests.filter(t => t.status === 'warning').length, 0);
                
                const summary = '<div style="margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px;">' +
                    '<strong>Test Summary:</strong> ' + passedTests + ' passed, ' + failedTests + ' failed, ' + warningTests + ' warnings (' + totalTests + ' total)' +
                    '</div>';
                
                $results.removeClass('loading');
                if (hasFailures) {
                    $results.addClass('warning');
                } else if (hasWarnings) {
                    $results.addClass('warning');
                } else {
                    $results.addClass('success');
                }
                
                $results.html(summary + html);
                
                // Auto hide after 15 seconds
                setTimeout(function() {
                    $results.fadeOut();
                }, 15000);
            }
            
            // Auto-test on significant changes
            let autoTestTimeout;
            $('#tirtonic-settings-form input[name="enable_navbar"], #tirtonic-settings-form input[name="enable_search"], #tirtonic-settings-form input[name="enable_cart"]').on('change', function() {
                clearTimeout(autoTestTimeout);
                autoTestTimeout = setTimeout(function() {
                    const $status = $('#save-status');
                    $status.removeClass('success error').addClass('loading').text('Validating changes...').show();
                    
                    setTimeout(function() {
                        $status.removeClass('loading').addClass('success').text('Configuration updated - remember to save!');
                        setTimeout(() => $status.fadeOut(), 3000);
                    }, 1000);
                }, 500);
            });
            
            // Debug Panel Initialization
            $('#jquery-version').text($.fn.jquery || 'Not loaded');
            
            const debugConsole = $('#debug-console');
            const originalLog = console.log;
            const originalError = console.error;
            const originalWarn = console.warn;
            const originalInfo = console.info;
            let debugLogs = [];
            
            function addToDebugConsole(message, type = 'log', stack = null) {
                const timestamp = new Date().toISOString();
                const logEntry = {
                    timestamp: timestamp,
                    type: type,
                    message: message,
                    stack: stack,
                    url: window.location.href,
                    userAgent: navigator.userAgent
                };
                
                debugLogs.push(logEntry);
                
                const prefix = {
                    'error': '[ERROR]',
                    'warn': '[WARN]',
                    'info': '[INFO]',
                    'log': '[LOG]'
                }[type] || '[LOG]';
                
                let displayMessage = timestamp + ' ' + prefix + ' ' + message;
                if (stack && type === 'error') {
                    displayMessage += '\n    Stack: ' + stack;
                }
                
                debugConsole.val(debugConsole.val() + displayMessage + '\n');
                debugConsole.scrollTop(debugConsole[0].scrollHeight);
            }
            
            // Override console methods
            console.log = function() {
                const message = Array.prototype.slice.call(arguments).join(' ');
                originalLog.apply(console, arguments);
                addToDebugConsole(message, 'log');
            };
            
            console.error = function() {
                const message = Array.prototype.slice.call(arguments).join(' ');
                const stack = (new Error()).stack;
                originalError.apply(console, arguments);
                addToDebugConsole(message, 'error', stack);
            };
            
            console.warn = function() {
                const message = Array.prototype.slice.call(arguments).join(' ');
                originalWarn.apply(console, arguments);
                addToDebugConsole(message, 'warn');
            };
            
            console.info = function() {
                const message = Array.prototype.slice.call(arguments).join(' ');
                originalInfo.apply(console, arguments);
                addToDebugConsole(message, 'info');
            };
            
            // Capture JavaScript errors
            window.addEventListener('error', function(e) {
                const errorMsg = 'JS Error: ' + e.message + ' at ' + e.filename + ':' + e.lineno + ':' + e.colno;
                addToDebugConsole(errorMsg, 'error', e.error ? e.error.stack : null);
            });
            
            // Capture unhandled promise rejections
            window.addEventListener('unhandledrejection', function(e) {
                const errorMsg = 'Unhandled Promise Rejection: ' + e.reason;
                addToDebugConsole(errorMsg, 'error', e.reason ? e.reason.stack : null);
            });
            
            // Initial debug log
            addToDebugConsole('Tirtonic Debug Panel initialized - Enhanced logging active', 'info');
            addToDebugConsole('Current page: ' + window.location.href, 'info');
            addToDebugConsole('User agent: ' + navigator.userAgent, 'info');
            
            // Event handlers
            $('#clear-logs').on('click', function() {
                debugConsole.val('');
                debugLogs = [];
                addToDebugConsole('Debug logs cleared', 'info');
            });
            
            $('#export-logs').on('click', function() {
                if (debugLogs.length === 0) {
                    alert('No logs to export');
                    return;
                }
                
                let exportContent = '=== TIRTONIC FLOATING NAVBAR DEBUG LOG ===\n';
                exportContent += 'Export Date: ' + new Date().toISOString() + '\n';
                exportContent += 'WordPress URL: ' + window.location.origin + '\n';
                exportContent += 'User Agent: ' + navigator.userAgent + '\n';
                exportContent += 'Plugin Version: <?php echo defined('TIRTONIC_NAV_VERSION') ? TIRTONIC_NAV_VERSION : 'Unknown'; ?>\n';
                exportContent += 'jQuery Version: ' + ($.fn.jquery || 'Not loaded') + '\n';
                exportContent += '\n=== SYSTEM INFO ===\n';
                exportContent += 'Navbar Element Exists: ' + (!!document.getElementById('tirtonicFloatingNav')) + '\n';
                exportContent += 'CSS Loaded: ' + ($('link[href*="floating-navbar"]').length > 0) + '\n';
                exportContent += 'JS Loaded: ' + (typeof window.tirtonicNav !== 'undefined') + '\n';
                exportContent += '\n=== DEBUG LOGS ===\n';
                
                debugLogs.forEach(function(log) {
                    exportContent += log.timestamp + ' [' + log.type.toUpperCase() + '] ' + log.message + '\n';
                    if (log.stack && log.type === 'error') {
                        exportContent += '  Stack Trace: ' + log.stack + '\n';
                    }
                });
                
                exportContent += '\n=== END OF LOG ===\n';
                
                const blob = new Blob([exportContent], { type: 'text/plain;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'tirtonic-navbar-debug-' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                addToDebugConsole('Debug logs exported to file', 'info');
            });
            
            $('#check-navbar-status').on('click', function() {
                const results = $('#navbar-status-results');
                let html = '<div class="status-check">';
                
                const navbarExists = !!document.getElementById('tirtonicFloatingNav');
                html += '<p class="' + (navbarExists ? 'status-ok' : 'status-error') + '">' + (navbarExists ? '✅' : '❌') + ' Navbar Element: ' + (navbarExists ? 'Found' : 'Not Found') + '</p>';
                
                const cssLoaded = $('link[href*="floating-navbar"]').length > 0;
                html += '<p class="' + (cssLoaded ? 'status-ok' : 'status-error') + '">' + (cssLoaded ? '✅' : '❌') + ' CSS Loaded: ' + (cssLoaded ? 'Yes' : 'No') + '</p>';
                
                const jsLoaded = typeof window.tirtonicNav !== 'undefined';
                html += '<p class="' + (jsLoaded ? 'status-ok' : 'status-error') + '">' + (jsLoaded ? '✅' : '❌') + ' JavaScript Loaded: ' + (jsLoaded ? 'Yes' : 'No') + '</p>';
                
                if (navbarExists) {
                    const navbar = document.getElementById('tirtonicFloatingNav');
                    const isVisible = navbar.offsetParent !== null;
                    const computedStyle = window.getComputedStyle(navbar);
                    html += '<p class="' + (isVisible ? 'status-ok' : 'status-warning') + '">' + (isVisible ? '✅' : '⚠️') + ' Navbar Visible: ' + (isVisible ? 'Yes' : 'No') + '</p>';
                    html += '<p class="status-info">📊 Display: ' + computedStyle.display + '</p>';
                    html += '<p class="status-info">👁️ Visibility: ' + computedStyle.visibility + '</p>';
                    html += '<p class="status-info">🔍 Opacity: ' + computedStyle.opacity + '</p>';
                    html += '<p class="status-info">📐 Z-Index: ' + computedStyle.zIndex + '</p>';
                }
                
                html += '</div>';
                results.html(html);
            });
            
            $('#force-show-navbar').on('click', function() {
                // Check if navbar is enabled in settings first
                $.post(ajaxurl, {
                    action: 'tirtonic_get_settings',
                    nonce: '<?php echo wp_create_nonce('tirtonic_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        const settings = response.data;
                        
                        if (!settings.enable_navbar) {
                            alert('Navbar is DISABLED in settings! Please enable it first.');
                            return;
                        }
                    }
                });
                
                const navbar = document.getElementById('tirtonicFloatingNav');
                if (navbar) {
                    navbar.style.display = 'block';
                    navbar.style.visibility = 'visible';
                    navbar.style.opacity = '1';
                    navbar.style.position = 'fixed';
                    navbar.style.top = '50px';
                    navbar.style.right = '50px';
                    navbar.style.zIndex = '9999';
                    navbar.classList.remove('nav-hidden');
                    alert('Navbar dipaksa untuk tampil!');
                } else {
                    alert('Navbar element tidak ditemukan! Check console for details.');
                }
            });
            
            $('#test-navbar-init').on('click', function() {
                if (typeof window.initTirtonicNav === 'function') {
                    window.initTirtonicNav();
                    alert('Navbar berhasil di-reinitialize!');
                } else {
                    alert('Function initTirtonicNav tidak ditemukan!');
                }
            });
            
            $('#reset-navbar').on('click', function() {
                const navbar = document.getElementById('tirtonicFloatingNav');
                if (navbar) {
                    navbar.removeAttribute('style');
                    navbar.className = 'tirtonic-floating-nav';
                    alert('Navbar berhasil direset!');
                } else {
                    alert('Navbar element tidak ditemukan!');
                }
            });
            

        });
        </script>
        
        <?php
    }
    
    private function render_icon($icon_name) {
        if (isset($this->icon_library[$icon_name])) {
            return $this->icon_library[$icon_name];
        }
        
        // Check if it's a custom uploaded icon
        if (filter_var($icon_name, FILTER_VALIDATE_URL)) {
            return '<img src="' . esc_url($icon_name) . '" alt="Custom Icon" style="width: 24px; height: 24px;">';
        }
        
        // Default fallback
        return $this->icon_library['search'];
    }
    
    private function render_icon_library() {
        foreach ($this->icon_library as $name => $svg) {
            echo '<div class="icon-item" data-icon="' . esc_attr($name) . '">';
            echo $svg;
            echo '<span>' . esc_html(ucfirst(str_replace('_', ' ', $name))) . '</span>';
            echo '</div>';
        }
    }
    
    private function render_additional_icons() {
        $additional_icons = $this->options['additional_icons'] ?? array();
        
        foreach ($additional_icons as $index => $icon) {
            echo '<div class="additional-icon-item" data-index="' . esc_attr($index) . '">';
            echo '<div class="icon-preview">' . $this->render_icon($icon['icon'] ?? 'link') . '</div>';
            echo '<div class="icon-fields">';
            echo '<input type="text" name="additional_icons[' . $index . '][title]" value="' . esc_attr($icon['title'] ?? '') . '" placeholder="Icon Title">';
            echo '<select name="additional_icons[' . $index . '][action]">';
            echo '<option value="url" ' . selected($icon['action'] ?? 'url', 'url', false) . '>Custom URL</option>';
            echo '<option value="search" ' . selected($icon['action'] ?? 'url', 'search', false) . '>Open Search</option>';
            echo '<option value="cart" ' . selected($icon['action'] ?? 'url', 'cart', false) . '>Open Cart</option>';
            echo '</select>';
            echo '<input type="url" name="additional_icons[' . $index . '][url]" value="' . esc_attr($icon['url'] ?? '') . '" placeholder="URL (if action is Custom URL)">';
            echo '</div>';
            echo '<div class="icon-controls">';
            echo '<button type="button" class="button icon-library-btn" data-target="additional_icons[' . $index . '][icon]">Choose Icon</button>';
            echo '<button type="button" class="button remove-icon">Remove</button>';
            echo '</div>';
            echo '<input type="hidden" name="additional_icons[' . $index . '][icon]" value="' . esc_attr($icon['icon'] ?? 'link') . '">';
            echo '</div>';
        }
    }
    
    private function render_menu_builder() {
        $menu_items = $this->options['menu_items'] ?? $this->get_default_menu_items();
        
        foreach ($menu_items as $index => $item) {
            echo '<div class="menu-item" data-index="' . esc_attr($index) . '">';
            echo '<div class="menu-item-header">';
            echo '<span class="drag-handle">⋮⋮</span>';
            echo '<input type="text" name="menu_items[' . $index . '][title]" value="' . esc_attr($item['title']) . '" placeholder="Menu Title">';
            echo '<button type="button" class="toggle-menu-item">▼</button>';
            echo '<button type="button" class="remove-menu-item">×</button>';
            echo '</div>';
            echo '<div class="menu-item-content">';
            echo '<input type="url" name="menu_items[' . $index . '][url]" value="' . esc_attr($item['url']) . '" placeholder="Menu URL">';
            echo '<div class="menu-item-icon">';
            echo '<div class="current-icon">' . $this->render_icon($item['icon'] ?? 'link') . '</div>';
            echo '<button type="button" class="button icon-library-btn" data-target="menu_items[' . $index . '][icon]">Choose Icon</button>';
            echo '<input type="hidden" name="menu_items[' . $index . '][icon]" value="' . esc_attr($item['icon'] ?? 'link') . '">';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('tirtonic_settings_nonce', 'tirtonic_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
        }
        
        // Get all form data
        $form_data = $_POST;
        unset($form_data['action'], $form_data['tirtonic_nonce']);
        
        $sanitized_settings = $this->sanitize_settings($form_data);
        
        $result = update_option('tirtonic_floating_nav_settings', $sanitized_settings);
        
        if ($result) {
            // Count configured features for detailed feedback
            $menu_count = count($sanitized_settings['menu_items'] ?? array());
            $footer_count = 0;
            for($i = 1; $i <= 3; $i++) {
                if (!empty($sanitized_settings["footer_menu_{$i}_title"])) $footer_count++;
            }
            $additional_icons = count($sanitized_settings['additional_icons'] ?? array());
            
            $details = array();
            if ($menu_count > 0) $details[] = "{$menu_count} menu items";
            if ($footer_count > 0) $details[] = "{$footer_count} footer items";
            if ($additional_icons > 0) $details[] = "{$additional_icons} custom icons";
            if ($sanitized_settings['enable_search']) $details[] = "search enabled";
            if ($sanitized_settings['enable_cart']) $details[] = "cart enabled";
            
            $detail_text = !empty($details) ? ' (' . implode(', ', $details) . ')' : '';
            
            wp_send_json_success(array(
                'message' => 'Konfigurasi berhasil disimpan!' . $detail_text,
                'details' => $details,
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error(array('message' => 'Gagal menyimpan konfigurasi'));
        }
    }
    
    public function ajax_get_preview() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        $settings = $_POST['settings'] ?? array();
        $device = $_POST['device'] ?? 'desktop';
        
        // Generate preview HTML
        ob_start();
        $this->render_preview_navbar($settings, $device);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_reset_settings() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        delete_option('tirtonic_floating_nav_settings');
        wp_send_json_success(array('message' => 'Settings reset successfully!'));
    }
    
    public function ajax_check_update() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }
        
        delete_transient('floating_navbar_remote_version');
        
        $current_version = TIRTONIC_NAV_VERSION;
        $remote_version = $this->get_latest_github_version();
        
        $needs_update = version_compare($current_version, $remote_version, '<');
        
        wp_send_json_success(array(
            'current_version' => $current_version,
            'latest_version' => $remote_version,
            'needs_update' => $needs_update,
            'download_url' => $needs_update ? "https://github.com/kamaltz/floating-navbar-wplugin/releases/download/v{$remote_version}/floating-navbar-plugin.zip" : ''
        ));
    }
    
    public function ajax_update_plugin() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }
        
        $download_url = sanitize_url($_POST['download_url']);
        $result = $this->perform_plugin_update($download_url);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    private function get_latest_github_version() {
        $response = wp_remote_get('https://api.github.com/repos/kamaltz/floating-navbar-wplugin/releases/latest');
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            return isset($data['tag_name']) ? ltrim($data['tag_name'], 'v') : TIRTONIC_NAV_VERSION;
        }
        
        return TIRTONIC_NAV_VERSION;
    }
    
    private function perform_plugin_update($download_url) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        
        $plugin_slug = plugin_basename(TIRTONIC_NAV_PATH . 'floating-navbar.php');
        
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->upgrade($plugin_slug, array(
            'package' => $download_url,
            'destination' => WP_PLUGIN_DIR,
            'clear_destination' => true,
            'clear_working' => true,
            'hook_extra' => array(
                'plugin' => $plugin_slug,
                'type' => 'plugin',
                'action' => 'update',
            )
        ));
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Plugin updated successfully!'
        );
    }
    
    public function ajax_clear_update_cache() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die('Unauthorized');
        }
        
        delete_transient('floating_navbar_latest_version');
        delete_transient('floating_navbar_remote_version');
        update_option('floating_navbar_version', TIRTONIC_NAV_VERSION);
        
        wp_send_json_success(array('message' => 'Update cache cleared'));
    }
    
    public function ajax_get_settings() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        wp_send_json_success($this->options);
    }
    
    public function ajax_test_feature() {
        check_ajax_referer('tirtonic_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access'));
        }
        
        $feature = sanitize_text_field($_POST['feature'] ?? 'all');
        $results = array();
        
        switch($feature) {
            case 'navbar_display':
                $results = $this->test_navbar_display();
                break;
            case 'menu_items':
                $results = $this->test_menu_items();
                break;
            case 'search_functionality':
                $results = $this->test_search_functionality();
                break;
            case 'cart_integration':
                $results = $this->test_cart_integration();
                break;
            case 'exclude_pages':
                $results = $this->test_exclude_pages();
                break;
            case 'responsive_design':
                $results = $this->test_responsive_design();
                break;
            case 'all':
            default:
                $results = $this->test_all_features();
                break;
        }
        
        wp_send_json_success($results);
    }
    
    private function test_navbar_display() {
        $tests = array();
        
        // Test navbar enabled
        $tests[] = array(
            'name' => 'Navbar Enabled',
            'status' => $this->options['enable_navbar'] ? 'pass' : 'fail',
            'message' => $this->options['enable_navbar'] ? 'Navbar is enabled' : 'Navbar is disabled'
        );
        
        // Test navbar title
        $tests[] = array(
            'name' => 'Navbar Title',
            'status' => !empty($this->options['navbar_title']) ? 'pass' : 'fail',
            'message' => !empty($this->options['navbar_title']) ? 'Title: ' . $this->options['navbar_title'] : 'No title set'
        );
        
        // Test colors
        $tests[] = array(
            'name' => 'Color Configuration',
            'status' => (!empty($this->options['primary_color']) && !empty($this->options['secondary_color'])) ? 'pass' : 'fail',
            'message' => 'Primary: ' . ($this->options['primary_color'] ?? 'Not set') . ', Secondary: ' . ($this->options['secondary_color'] ?? 'Not set')
        );
        
        return array('category' => 'Navbar Display', 'tests' => $tests);
    }
    
    private function test_menu_items() {
        $tests = array();
        $menu_items = $this->options['menu_items'] ?? array();
        
        // Test menu items exist
        $tests[] = array(
            'name' => 'Menu Items Count',
            'status' => count($menu_items) > 0 ? 'pass' : 'fail',
            'message' => count($menu_items) . ' menu items configured'
        );
        
        // Test menu items validity
        $valid_items = 0;
        foreach($menu_items as $item) {
            if (!empty($item['title']) && !empty($item['url'])) {
                $valid_items++;
            }
        }
        
        $tests[] = array(
            'name' => 'Valid Menu Items',
            'status' => $valid_items > 0 ? 'pass' : 'fail',
            'message' => $valid_items . ' valid menu items found'
        );
        
        // Test footer menu
        $footer_items = 0;
        for($i = 1; $i <= 3; $i++) {
            if (!empty($this->options["footer_menu_{$i}_title"])) {
                $footer_items++;
            }
        }
        
        $tests[] = array(
            'name' => 'Footer Menu Items',
            'status' => $footer_items > 0 ? 'pass' : 'warning',
            'message' => $footer_items . ' footer menu items configured'
        );
        
        return array('category' => 'Menu Configuration', 'tests' => $tests);
    }
    
    private function test_search_functionality() {
        $tests = array();
        
        // Test search enabled
        $tests[] = array(
            'name' => 'Search Feature',
            'status' => $this->options['enable_search'] ? 'pass' : 'warning',
            'message' => $this->options['enable_search'] ? 'Search is enabled' : 'Search is disabled'
        );
        
        // Test WooCommerce integration
        $tests[] = array(
            'name' => 'WooCommerce Integration',
            'status' => class_exists('WooCommerce') ? 'pass' : 'warning',
            'message' => class_exists('WooCommerce') ? 'WooCommerce is active' : 'WooCommerce not found'
        );
        
        // Test search placeholder
        $tests[] = array(
            'name' => 'Search Placeholder',
            'status' => !empty($this->options['search_placeholder']) ? 'pass' : 'warning',
            'message' => 'Placeholder: ' . ($this->options['search_placeholder'] ?? 'Not set')
        );
        
        return array('category' => 'Search Functionality', 'tests' => $tests);
    }
    
    private function test_cart_integration() {
        $tests = array();
        
        // Test cart enabled
        $tests[] = array(
            'name' => 'Cart Feature',
            'status' => $this->options['enable_cart'] ? 'pass' : 'warning',
            'message' => $this->options['enable_cart'] ? 'Cart is enabled' : 'Cart is disabled'
        );
        
        // Test WooCommerce cart
        if (class_exists('WooCommerce')) {
            $tests[] = array(
                'name' => 'WooCommerce Cart',
                'status' => 'pass',
                'message' => 'WooCommerce cart integration available'
            );
        } else {
            $tests[] = array(
                'name' => 'WooCommerce Cart',
                'status' => 'warning',
                'message' => 'WooCommerce not active - cart features limited'
            );
        }
        
        // Test checkout button
        $tests[] = array(
            'name' => 'Checkout Button',
            'status' => !empty($this->options['checkout_button_text']) ? 'pass' : 'warning',
            'message' => 'Button text: ' . ($this->options['checkout_button_text'] ?? 'Default')
        );
        
        return array('category' => 'Cart Integration', 'tests' => $tests);
    }
    
    private function test_exclude_pages() {
        $tests = array();
        $exclude_pages = $this->options['exclude_pages'] ?? '';
        
        // Test exclude pages configured
        $tests[] = array(
            'name' => 'Exclude Pages Configuration',
            'status' => !empty($exclude_pages) ? 'pass' : 'warning',
            'message' => !empty($exclude_pages) ? 'Pages configured for exclusion' : 'No pages excluded'
        );
        
        if (!empty($exclude_pages)) {
            $excluded_urls = array_filter(array_map('trim', explode("\n", $exclude_pages)));
            $tests[] = array(
                'name' => 'Excluded Pages Count',
                'status' => count($excluded_urls) > 0 ? 'pass' : 'warning',
                'message' => count($excluded_urls) . ' pages will be excluded: ' . implode(', ', $excluded_urls)
            );
        }
        
        return array('category' => 'Page Exclusion', 'tests' => $tests);
    }
    
    private function test_responsive_design() {
        $tests = array();
        
        // Test desktop settings
        $tests[] = array(
            'name' => 'Desktop Configuration',
            'status' => !$this->options['hide_desktop'] ? 'pass' : 'warning',
            'message' => !$this->options['hide_desktop'] ? 'Visible on desktop' : 'Hidden on desktop'
        );
        
        // Test mobile settings
        $tests[] = array(
            'name' => 'Mobile Configuration',
            'status' => !$this->options['hide_mobile'] ? 'pass' : 'warning',
            'message' => !$this->options['hide_mobile'] ? 'Visible on mobile' : 'Hidden on mobile'
        );
        
        // Test scaling
        $desktop_scale = $this->options['desktop_scale'] ?? 100;
        $mobile_scale = $this->options['mobile_scale'] ?? 80;
        
        $tests[] = array(
            'name' => 'Responsive Scaling',
            'status' => ($desktop_scale >= 50 && $mobile_scale >= 50) ? 'pass' : 'warning',
            'message' => "Desktop: {$desktop_scale}%, Mobile: {$mobile_scale}%"
        );
        
        return array('category' => 'Responsive Design', 'tests' => $tests);
    }
    
    private function test_all_features() {
        $all_results = array();
        
        $all_results[] = $this->test_navbar_display();
        $all_results[] = $this->test_menu_items();
        $all_results[] = $this->test_search_functionality();
        $all_results[] = $this->test_cart_integration();
        $all_results[] = $this->test_exclude_pages();
        $all_results[] = $this->test_responsive_design();
        
        return $all_results;
    }
    

    
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        );
    }
    
    private function render_preview_navbar($settings, $device) {
        // Render a preview version of the navbar
        // This would be similar to render_navbar() but with preview-specific styling
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Navbar Preview</title>
            <style>
                body { margin: 0; padding: 20px; background: #f0f0f1; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .preview-info { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                <?php echo $this->generate_preview_css($settings); ?>
            </style>
        </head>
        <body>
            <div class="preview-info">
                <strong>Preview Mode:</strong> <?php echo ucfirst($device); ?>
                <br><small>This is how your navbar will appear on your website.</small>
            </div>
            
            <?php echo $this->generate_navbar_html($settings); ?>
            
            <script>
                <?php echo $this->generate_preview_js($settings); ?>
            </script>
        </body>
        </html>
        <?php
    }
    
    private function generate_preview_css($settings) {
        // Extract all settings with defaults
        $primary_color = $settings['primary_color'] ?? '#ffc83a';
        $secondary_color = $settings['secondary_color'] ?? '#ffb800';
        $primary_opacity = $settings['primary_opacity'] ?? '1';
        $secondary_opacity = $settings['secondary_opacity'] ?? '1';
        $text_color = $settings['text_color'] ?? '#000000';
        $background_color = $settings['background_color'] ?? '#ffffff';
        $border_radius = $settings['border_radius'] ?? 10;
        $shadow_intensity = $settings['shadow_intensity'] ?? 20;
        $icon_size = $settings['icon_size'] ?? 24;
        $z_index = $settings['z_index'] ?? 999;
        $navbar_scale = $settings['navbar_scale'] ?? 100;
        
        // Typography settings
        $title_font_size = $settings['title_font_size'] ?? 16;
        $title_font_weight = $settings['title_font_weight'] ?? '600';
        $title_font_family = $settings['title_font_family'] ?? 'system';
        $menu_font_size = $settings['menu_font_size'] ?? 14;
        $menu_font_weight = $settings['menu_font_weight'] ?? '500';
        $menu_text_color = $settings['menu_text_color'] ?? '#000000';
        $submenu_font_size = $settings['submenu_font_size'] ?? 12;
        $submenu_text_color = $settings['submenu_text_color'] ?? '#666666';
        $search_title_font_size = $settings['search_title_font_size'] ?? 16;
        $search_title_color = $settings['search_title_color'] ?? '#333333';
        $product_font_size = $settings['product_font_size'] ?? 14;
        $checkout_font_size = $settings['checkout_font_size'] ?? 14;
        $checkout_font_weight = $settings['checkout_font_weight'] ?? '600';
        
        // Convert hex to RGBA
        $primary_rgb = $this->hex_to_rgb($primary_color);
        $secondary_rgb = $this->hex_to_rgb($secondary_color);
        $primary_rgba = "rgba({$primary_rgb['r']}, {$primary_rgb['g']}, {$primary_rgb['b']}, {$primary_opacity})";
        $secondary_rgba = "rgba({$secondary_rgb['r']}, {$secondary_rgb['g']}, {$secondary_rgb['b']}, {$secondary_opacity})";
        
        $font_family = $title_font_family === 'system' ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' : $title_font_family;
        
        $css = "
        :root {
            --tirtonic-primary: {$primary_rgba};
            --tirtonic-secondary: {$secondary_rgba};
            --tirtonic-text: {$text_color};
            --tirtonic-background: {$background_color};
            --tirtonic-radius: {$border_radius}px;
            --tirtonic-shadow: {$shadow_intensity}%;
            --tirtonic-icon-size: {$icon_size}px;
            --tirtonic-z-index: {$z_index};
            --tirtonic-scale: " . ($navbar_scale / 100) . ";
            --tirtonic-title-size: {$title_font_size}px;
            --tirtonic-title-weight: {$title_font_weight};
            --tirtonic-title-family: {$font_family};
            --tirtonic-menu-size: {$menu_font_size}px;
            --tirtonic-menu-weight: {$menu_font_weight};
            --tirtonic-menu-color: {$menu_text_color};
            --tirtonic-submenu-size: {$submenu_font_size}px;
            --tirtonic-submenu-color: {$submenu_text_color};
            --tirtonic-search-title-size: {$search_title_font_size}px;
            --tirtonic-search-title-color: {$search_title_color};
            --tirtonic-product-size: {$product_font_size}px;
            --tirtonic-checkout-size: {$checkout_font_size}px;
            --tirtonic-checkout-weight: {$checkout_font_weight};
        }
        ";
        
        // Add the complete CSS with all styles
        $css .= $this->get_complete_navbar_css();
        
        // Add custom CSS if provided
        if (!empty($settings['custom_css'])) {
            $css .= "\n/* Custom CSS */\n" . $settings['custom_css'];
        }
        
        return $css;
    }
    
    private function get_complete_navbar_css() {
        return '
        .tirtonic-floating-nav {
            position: fixed;
            top: 50px;
            right: 50px;
            transition: .5s ease;
            z-index: var(--tirtonic-z-index);
            font-family: var(--tirtonic-title-family);
            transform: scale(var(--tirtonic-scale));
            transform-origin: top right;
        }
        
        .tirtonic-nav-header {
            position: relative;
            z-index: 2;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--tirtonic-primary) 0%, var(--tirtonic-secondary) 100%);
            min-width: 300px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: .5s ease;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100));
            border-radius: var(--tirtonic-radius);
            cursor: pointer;
        }
        
        .tirtonic-nav-header:hover {
            box-shadow: 0 6px 25px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100 + 0.05));
            transform: translateY(-2px);
        }
        
        .tirtonic-nav-opened .tirtonic-nav-header {
            border-radius: var(--tirtonic-radius) var(--tirtonic-radius) 0 0;
            box-shadow: none;
            transform: none;
        }
        
        .tirtonic-nav-toggle {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: 1s ease;
        }
        
        .tirtonic-nav-title {
            font-size: var(--tirtonic-title-size);
            color: var(--tirtonic-text);
            font-weight: var(--tirtonic-title-weight);
            font-family: var(--tirtonic-title-family);
            letter-spacing: 0.5px;
        }
        
        .tirtonic-nav-arrow {
            display: block;
            width: fit-content;
            line-height: 0;
            transition: .3s ease;
        }
        
        .tirtonic-nav-arrow svg {
            width: 16px;
            height: 16px;
            transition: .3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .tirtonic-nav-opened .tirtonic-nav-arrow {
            transform: rotate(180deg);
        }
        
        .tirtonic-nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .tirtonic-nav-search,
        .tirtonic-nav-cart,
        .tirtonic-nav-custom-icon {
            cursor: pointer;
            transition: all .3s ease;
            position: relative;
            padding: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
        }
        
        .tirtonic-nav-search:hover,
        .tirtonic-nav-cart:hover,
        .tirtonic-nav-custom-icon:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .tirtonic-nav-search svg,
        .tirtonic-nav-cart svg,
        .tirtonic-nav-custom-icon svg {
            width: var(--tirtonic-icon-size);
            height: var(--tirtonic-icon-size);
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .tirtonic-nav-content {
            position: absolute;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: 10;
            top: 100%;
            left: 0;
            width: 100%;
            padding: 20px;
            background: var(--tirtonic-background);
            border-radius: 0 0 var(--tirtonic-radius) var(--tirtonic-radius);
            transform: translateY(-20px);
            transition: all .3s ease;
            box-shadow: 0 8px 32px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100 + 0.02));
            max-height: 400px;
            overflow: auto;
        }
        
        .tirtonic-nav-opened .tirtonic-nav-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: unset;
        }
        
        .wrapper-menu {
            display: block;
        }
        
        .quick--access-content_link1 {
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .quick--access-content_link1 a {
            font-size: var(--tirtonic-menu-size);
            display: block;
            color: var(--tirtonic-menu-color);
            margin-bottom: 8px;
            text-decoration: none;
            transition: all .3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: var(--tirtonic-menu-weight);
        }
        
        .quick--access-content_link1 a:hover {
            color: var(--tirtonic-primary);
            background: rgba(255, 200, 58, 0.1);
            transform: translateX(8px);
        }
        
        .quick--access-content_link2 {
            opacity: 0.8;
        }
        
        .quick--access-content_link2 a {
            font-size: var(--tirtonic-submenu-size);
            display: block;
            color: var(--tirtonic-submenu-color);
            text-decoration: none;
            margin-bottom: 5px;
            transition: all .3s ease;
            padding: 6px 12px;
            border-radius: 4px;
        }
        
        .quick--access-content_link2 a:hover {
            color: #333;
            background: rgba(0,0,0,0.05);
            transform: translateX(4px);
        }
        ';
    }
    
    private function generate_navbar_html($settings) {
        $title = $settings['navbar_title'] ?? 'Quick Access';
        $search_title_text = $settings['search_title_text'] ?? 'Newest product';
        $checkout_button_text = $settings['checkout_button_text'] ?? 'Checkout';
        
        ob_start();
        ?>
        <div id="tirtonicFloatingNav" class="tirtonic-floating-nav">
            <div class="tirtonic-nav-header">
                <div class="tirtonic-nav-toggle">
                    <span class="tirtonic-nav-title"><?php echo esc_html($title); ?></span>
                    <span class="tirtonic-nav-arrow">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </span>
                </div>
                
                <div class="tirtonic-nav-actions">
                    <?php if ($settings['enable_search'] ?? true): ?>
                    <div class="tirtonic-nav-search" title="Search">
                        <?php echo $this->render_icon($settings['search_icon'] ?? 'search'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['enable_cart'] ?? true): ?>
                    <div class="tirtonic-nav-cart" title="Cart">
                        <?php echo $this->render_icon($settings['cart_icon'] ?? 'cart'); ?>
                        <span class="cart-count">3</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Additional icons
                    $additional_icons = $settings['additional_icons'] ?? array();
                    foreach ($additional_icons as $icon) {
                        if (!empty($icon['title']) && !empty($icon['icon'])) {
                            echo '<div class="tirtonic-nav-custom-icon" title="' . esc_attr($icon['title']) . '">';
                            echo $this->render_icon($icon['icon']);
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="tirtonic-nav-content">
                <div class="wrapper-menu">
                    <div class="quick--access-content_link1">
                        <?php 
                        $menu_items = $settings['menu_items'] ?? $this->get_default_menu_items();
                        foreach ($menu_items as $item) {
                            echo '<a href="#" onclick="return false;">' . esc_html($item['title']) . '</a>';
                        }
                        ?>
                    </div>
                    <div class="quick--access-content_link2">
                        <?php
                        // Footer menu items
                        $footer_items = array(
                            array('title' => $settings['footer_menu_1_title'] ?? '', 'url' => '#'),
                            array('title' => $settings['footer_menu_2_title'] ?? '', 'url' => '#'),
                            array('title' => $settings['footer_menu_3_title'] ?? '', 'url' => '#')
                        );
                        
                        $has_items = false;
                        foreach ($footer_items as $item) {
                            if (!empty($item['title'])) {
                                echo '<a href="#" onclick="return false;">' . esc_html($item['title']) . '</a>';
                                $has_items = true;
                            }
                        }
                        
                        if (!$has_items) {
                            echo '<style>.quick--access-content_link2 { display: none; }</style>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function generate_preview_js($settings) {
        return "
        function initPreviewNavbar() {
            const nav = document.getElementById('tirtonicFloatingNav');
            if (!nav) return;
            
            const toggle = nav.querySelector('.tirtonic-nav-toggle');
            const content = nav.querySelector('.tirtonic-nav-content');
            const header = nav.querySelector('.tirtonic-nav-header');
            
            // Remove existing listeners
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            // Add click listener
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                nav.classList.toggle('tirtonic-nav-opened');
            });
            
            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!nav.contains(e.target)) {
                    nav.classList.remove('tirtonic-nav-opened');
                }
            });
            
            // Prevent content clicks from closing
            if (content) {
                content.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', initPreviewNavbar);
        
        // Make function globally available
        window.initPreviewNavbar = initPreviewNavbar;
        ";
    }
    
    private function sanitize_settings($settings) {
        $sanitized = array();
        
        // Basic settings
        $sanitized['enable_navbar'] = !empty($settings['enable_navbar']);
        $sanitized['navbar_title'] = sanitize_text_field($settings['navbar_title'] ?? 'Quick Access');
        $sanitized['navbar_position'] = in_array($settings['navbar_position'] ?? 'top-right', ['top-right', 'top-left', 'bottom-right', 'bottom-left']) ? $settings['navbar_position'] : 'top-right';
        $sanitized['auto_hide'] = !empty($settings['auto_hide']);
        
        // Colors
        $sanitized['primary_color'] = sanitize_hex_color($settings['primary_color'] ?? '#ffc83a');
        $sanitized['secondary_color'] = sanitize_hex_color($settings['secondary_color'] ?? '#ffb800');
        $sanitized['primary_opacity'] = floatval($settings['primary_opacity'] ?? 1);
        $sanitized['secondary_opacity'] = floatval($settings['secondary_opacity'] ?? 1);
        $sanitized['text_color'] = sanitize_hex_color($settings['text_color'] ?? '#000000');
        $sanitized['background_color'] = sanitize_hex_color($settings['background_color'] ?? '#ffffff');
        
        // Dimensions
        $sanitized['border_radius'] = intval($settings['border_radius'] ?? 10);
        $sanitized['shadow_intensity'] = intval($settings['shadow_intensity'] ?? 20);
        $sanitized['icon_size'] = intval($settings['icon_size'] ?? 24);
        $sanitized['navbar_scale'] = intval($settings['navbar_scale'] ?? 100);
        
        // Typography
        $sanitized['title_font_size'] = intval($settings['title_font_size'] ?? 16);
        $sanitized['menu_font_size'] = intval($settings['menu_font_size'] ?? 14);
        $sanitized['font_weight'] = sanitize_text_field($settings['font_weight'] ?? '600');
        
        // Icons
        $sanitized['enable_search'] = !empty($settings['enable_search']);
        $sanitized['enable_cart'] = !empty($settings['enable_cart']);
        $sanitized['search_icon'] = sanitize_text_field($settings['search_icon'] ?? 'search');
        $sanitized['cart_icon'] = sanitize_text_field($settings['cart_icon'] ?? 'cart');
        $sanitized['arrow_icon'] = sanitize_text_field($settings['arrow_icon'] ?? 'arrow_down');
        $sanitized['search_action'] = sanitize_text_field($settings['search_action'] ?? 'product_search');
        $sanitized['search_custom_url'] = esc_url_raw($settings['search_custom_url'] ?? '');
        $sanitized['search_placeholder'] = sanitize_text_field($settings['search_placeholder'] ?? 'Search products...');
        $sanitized['cart_action'] = sanitize_text_field($settings['cart_action'] ?? 'cart_page');
        $sanitized['cart_custom_url'] = esc_url_raw($settings['cart_custom_url'] ?? '');
        $sanitized['checkout_button_text'] = sanitize_text_field($settings['checkout_button_text'] ?? 'Checkout');
        $sanitized['checkout_button_action'] = sanitize_text_field($settings['checkout_button_action'] ?? 'checkout');
        $sanitized['checkout_custom_url'] = esc_url_raw($settings['checkout_custom_url'] ?? '');
        
        // Additional icons
        if (isset($settings['additional_icons']) && is_array($settings['additional_icons'])) {
            $sanitized['additional_icons'] = array();
            foreach ($settings['additional_icons'] as $icon) {
                if (!empty($icon['title'])) {
                    $sanitized['additional_icons'][] = array(
                        'title' => sanitize_text_field($icon['title']),
                        'icon' => sanitize_text_field($icon['icon'] ?? 'link'),
                        'action' => sanitize_text_field($icon['action'] ?? 'url'),
                        'url' => esc_url_raw($icon['url'] ?? '')
                    );
                }
            }
        } else {
            $sanitized['additional_icons'] = array();
        }
        
        // Menu items
        if (isset($settings['menu_items']) && is_array($settings['menu_items'])) {
            $sanitized['menu_items'] = array();
            foreach ($settings['menu_items'] as $item) {
                if (!empty($item['title']) && !empty($item['url'])) {
                    $sanitized['menu_items'][] = array(
                        'title' => sanitize_text_field($item['title']),
                        'url' => esc_url_raw($item['url']),
                        'icon' => sanitize_text_field($item['icon'] ?? 'link')
                    );
                }
            }
        } else {
            $sanitized['menu_items'] = $this->get_default_menu_items();
        }
        
        // Menu settings
        $sanitized['menu_style'] = sanitize_text_field($settings['menu_style'] ?? 'simple');
        $sanitized['show_menu_icons'] = !empty($settings['show_menu_icons']);
        $sanitized['show_logo'] = !empty($settings['show_logo']);
        $sanitized['logo_url'] = esc_url_raw($settings['logo_url'] ?? '');
        
        // Footer menu
        $sanitized['footer_menu_1_title'] = sanitize_text_field($settings['footer_menu_1_title'] ?? '');
        $sanitized['footer_menu_1_url'] = esc_url_raw($settings['footer_menu_1_url'] ?? '');
        $sanitized['footer_menu_2_title'] = sanitize_text_field($settings['footer_menu_2_title'] ?? '');
        $sanitized['footer_menu_2_url'] = esc_url_raw($settings['footer_menu_2_url'] ?? '');
        $sanitized['footer_menu_3_title'] = sanitize_text_field($settings['footer_menu_3_title'] ?? '');
        $sanitized['footer_menu_3_url'] = esc_url_raw($settings['footer_menu_3_url'] ?? '');
        
        // Responsive settings
        $sanitized['desktop_position'] = in_array($settings['desktop_position'] ?? 'top-right', ['top-right', 'top-left', 'bottom-right', 'bottom-left']) ? $settings['desktop_position'] : 'top-right';
        $sanitized['tablet_position'] = in_array($settings['tablet_position'] ?? 'top-right', ['top-right', 'top-left', 'bottom-right', 'bottom-left']) ? $settings['tablet_position'] : 'top-right';
        $sanitized['mobile_position'] = in_array($settings['mobile_position'] ?? 'bottom', ['bottom', 'top']) ? $settings['mobile_position'] : 'bottom';
        $sanitized['desktop_scale'] = intval($settings['desktop_scale'] ?? 100);
        $sanitized['tablet_scale'] = intval($settings['tablet_scale'] ?? 90);
        $sanitized['mobile_scale'] = intval($settings['mobile_scale'] ?? 80);
        $sanitized['hide_desktop'] = !empty($settings['hide_desktop']);
        $sanitized['hide_tablet'] = !empty($settings['hide_tablet']);
        $sanitized['hide_mobile'] = !empty($settings['hide_mobile']);
        $sanitized['mobile_full_width'] = !empty($settings['mobile_full_width']);
        
        // Advanced settings
        $sanitized['animation_duration'] = intval($settings['animation_duration'] ?? 500);
        $sanitized['z_index'] = intval($settings['z_index'] ?? 999);
        $sanitized['mobile_breakpoint'] = intval($settings['mobile_breakpoint'] ?? 768);
        $sanitized['exclude_pages'] = sanitize_textarea_field($settings['exclude_pages'] ?? "cart\ncheckout\ncontact");
        $sanitized['enable_auto_update'] = !empty($settings['enable_auto_update']);
        $sanitized['update_channel'] = in_array($settings['update_channel'] ?? 'stable', ['stable', 'beta']) ? $settings['update_channel'] : 'stable';
        
        // Custom CSS
        $sanitized['custom_css'] = wp_strip_all_tags($settings['custom_css'] ?? '');
        $sanitized['custom_mobile_css'] = wp_strip_all_tags($settings['custom_mobile_css'] ?? '');
        
        return $sanitized;
    }
    
    private function is_page_excluded() {
        $exclude_pages = $this->options['exclude_pages'] ?? "cart\ncheckout\ncontact";
        if (empty($exclude_pages)) {
            return false;
        }
        
        $current_url = $_SERVER['REQUEST_URI'];
        $excluded_urls = array_filter(array_map('trim', explode("\n", $exclude_pages)));
        
        foreach ($excluded_urls as $excluded_url) {
            $excluded_url = trim($excluded_url, '/');
            $current_path = trim(parse_url($current_url, PHP_URL_PATH), '/');
            
            // Check exact match or if current path starts with excluded path
            if ($excluded_url === $current_path || strpos($current_path, $excluded_url) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function render_navbar() {
        // Ensure options are loaded
        if (empty($this->options)) {
            $this->options = get_option('tirtonic_floating_nav_settings', $this->get_default_options());
        }
        
        if (!isset($this->options['enable_navbar']) || !$this->options['enable_navbar']) {
            return;
        }
        
        // Check if current page is excluded
        if ($this->is_page_excluded()) {
            return;
        }
        
        $position_class = isset($this->options['navbar_position']) ? $this->options['navbar_position'] : 'top-right';
        $title = isset($this->options['navbar_title']) ? $this->options['navbar_title'] : 'Quick Access';
        $search_title_text = isset($this->options['search_title_text']) ? $this->options['search_title_text'] : 'Newest product';
        
        ?>
        <div id="tirtonicFloatingNav" class="tirtonic-floating-nav <?php echo esc_attr($position_class); ?>" data-settings='<?php echo esc_attr(json_encode($this->options)); ?>'>
            <div class="tirtonic-nav-header">
                <div class="tirtonic-nav-toggle">
                    <span class="tirtonic-nav-title"><?php echo esc_html($title); ?></span>
                    <span class="tirtonic-nav-arrow">
                        <?php echo $this->render_icon($this->options['arrow_icon'] ?? 'arrow_down'); ?>
                    </span>
                </div>
                
                <div class="tirtonic-nav-actions">
                    <?php if (isset($this->options['enable_search']) && $this->options['enable_search']): ?>
                    <div class="tirtonic-nav-search" title="Search">
                        <?php echo $this->render_icon($this->options['search_icon'] ?? 'search'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($this->options['enable_cart']) && $this->options['enable_cart']): ?>
                    <div class="tirtonic-nav-cart" title="Cart">
                        <?php echo $this->render_icon($this->options['cart_icon'] ?? 'cart'); ?>
                        <?php if (class_exists('WooCommerce')): ?>
                        <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Render additional icons
                    $additional_icons = $this->options['additional_icons'] ?? array();
                    foreach ($additional_icons as $icon) {
                        if (!empty($icon['title']) && !empty($icon['icon'])) {
                            echo '<div class="tirtonic-nav-custom-icon" title="' . esc_attr($icon['title']) . '" data-action="' . esc_attr($icon['action'] ?? 'url') . '" data-url="' . esc_attr($icon['url'] ?? '') . '">';
                            echo $this->render_icon($icon['icon']);
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="tirtonic-nav-content">
                <button class="tirtonic-nav-close" style="display: none;">&times;</button>
                <div class="tirtonic-search-section" style="display: none;">
                    <form id="search--revamp">
                        <div class="form-group">
                            <input type="text" id="tirtonic-search-input" placeholder="<?php echo esc_attr($this->options['search_placeholder'] ?? 'Search for products'); ?>" />
                            <span class="search--icon">
                                <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.7873 18.0789L24.9626 23.253L23.2528 24.9628L18.0787 19.7875C16.1535 21.3308 13.7589 22.1702 11.2915 22.1667C5.2885 22.1667 0.416504 17.2947 0.416504 11.2917C0.416504 5.28867 5.2885 0.416672 11.2915 0.416672C17.2945 0.416672 22.1665 5.28867 22.1665 11.2917C22.17 13.7591 21.3306 16.1537 19.7873 18.0789ZM17.3634 17.1823C18.8966 15.6051 19.7529 13.4913 19.7498 11.2917C19.7498 6.61905 15.9641 2.83334 11.2915 2.83334C6.61888 2.83334 2.83317 6.61905 2.83317 11.2917C2.83317 15.9643 6.61888 19.75 11.2915 19.75C13.4911 19.7531 15.6049 18.8967 17.1821 17.3635L17.3634 17.1823Z" fill="black"/>
                                </svg>
                            </span>
                            <span class="close--icon" style="display: none;">
                                <svg class="Icon Icon--close" role="presentation" viewBox="0 0 16 14">
                                    <path d="M15 0L1 14m14 0L1 0" stroke="currentColor" fill="none" fill-rule="evenodd"></path>
                                </svg>
                            </span>
                        </div>
                    </form>
                    <div class="newest--product">
                        <div class="newest--product-wrapper">
                            <h3><?php echo esc_html($search_title_text); ?></h3>
                            <div class="newest--product-list">
                                <!-- Newest products will be loaded here -->
                            </div>
                        </div>
                        <div class="newest--product-searched" style="display: none;">
                            <h3><?php echo esc_html($this->options['search_results_title'] ?? 'Product'); ?></h3>
                            <div class="searched--product-list"></div>
                            <div class="searched--product-more" style="display: none;">
                                <a href="" class="more--searched">Show More</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="wrapper-menu">
                    <?php if (!empty($this->options['show_logo']) && !empty($this->options['logo_url'])): ?>
                    <div class="tirtonic-nav-logo">
                        <img src="<?php echo esc_url($this->options['logo_url']); ?>" alt="Logo">
                    </div>
                    <?php endif; ?>
                    <div class="quick--access-content_link1">
                        <?php $this->render_menu_items(); ?>
                    </div>
                    <div class="quick--access-content_link2">
                        <?php $this->render_secondary_menu(); ?>
                    </div>
                </div>
                
                <div class="quick--access-content_wrapper wrapper-cart">
                    <div class="button--checkout-wrapper hidden-tablet-and-up" style="display: none;">
                        <a href="<?php echo class_exists('WooCommerce') ? wc_get_page_permalink('cart') : '#'; ?>" class="button--rvmp1-secondary button--checkout-drawer">View Cart</a>
                    </div>
                    <div class="quick--access-cart">
                        <div id="sidebar-cart" class="Drawer-on Drawer Drawer--fromRight">
                            <div class="Drawer__Header" style="display: none;">
                                <p class="Drawer__Description">Browse our catalog to add your favourite products.</p>
                            </div>
                            <form class="Cart Drawer__Content" action="<?php echo class_exists('WooCommerce') ? wc_get_cart_url() : '#'; ?>" method="POST">
                                <div class="Drawer__Main" data-scrollable=""></div>
                            </form>
                            <div class="button--checkout-wrapper hidden-phone" style="display: none;">
                                <a href="<?php echo class_exists('WooCommerce') ? wc_get_page_permalink('cart') : '#'; ?>" class="button--rvmp1-secondary button--checkout-drawer">View Cart</a>
                            </div>
                            <div class="newest--product" style="display: none;">
                                <h3>Newest product</h3>
                                <div class="newest--product-list">
                                    <!-- Cart items or newest products will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php $this->render_custom_styles(); ?>
        

        
        <?php
    }
    
    private function render_menu_items() {
        // Ensure options are loaded
        if (empty($this->options)) {
            $this->options = get_option('tirtonic_floating_nav_settings', $this->get_default_options());
        }
        
        $menu_items = isset($this->options['menu_items']) ? $this->options['menu_items'] : $this->get_default_menu_items();
        
        if (is_array($menu_items) && !empty($menu_items)) {
            foreach ($menu_items as $item) {
                if (isset($item['title']) && isset($item['url']) && !empty($item['title']) && !empty($item['url'])) {
                    echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['title']) . '</a>';
                }
            }
        } else {
            // Fallback menu
            echo '<a href="' . home_url() . '">Home</a>';
            echo '<a href="' . home_url('/about') . '">About</a>';
            echo '<a href="' . home_url('/contact') . '">Contact</a>';
        }
    }
    
    private function render_secondary_menu() {
        // Ensure options are loaded
        if (empty($this->options)) {
            $this->options = get_option('tirtonic_floating_nav_settings', $this->get_default_options());
        }
        
        // Customizable footer menu items
        $footer_items = array(
            array(
                'title' => $this->options['footer_menu_1_title'] ?? '',
                'url' => $this->options['footer_menu_1_url'] ?? ''
            ),
            array(
                'title' => $this->options['footer_menu_2_title'] ?? '', 
                'url' => $this->options['footer_menu_2_url'] ?? ''
            ),
            array(
                'title' => $this->options['footer_menu_3_title'] ?? '',
                'url' => $this->options['footer_menu_3_url'] ?? ''
            )
        );
        
        $has_items = false;
        foreach ($footer_items as $item) {
            if (!empty($item['title'])) {
                $url = !empty($item['url']) ? $item['url'] : '#';
                echo '<a href="' . esc_url($url) . '">' . esc_html($item['title']) . '</a>';
                $has_items = true;
            }
        }
        
        // Show footer section even if no custom items
        if (!$has_items) {
            // Default footer items if none configured
            echo '<a href="https://api.whatsapp.com/send/?phone=6285163215511">Ask personal shopper</a>';
            echo '<a href="https://instagram.com/tirtonic">Follow our Instagram</a>';
        }
    }
    
    private function render_custom_styles() {
        $primary_color = isset($this->options['primary_color']) ? $this->options['primary_color'] : '#ffc83a';
        $secondary_color = isset($this->options['secondary_color']) ? $this->options['secondary_color'] : '#ffb800';
        $primary_opacity = isset($this->options['primary_opacity']) ? $this->options['primary_opacity'] : '1';
        $secondary_opacity = isset($this->options['secondary_opacity']) ? $this->options['secondary_opacity'] : '1';
        
        // Convert hex to RGBA
        $primary_rgb = $this->hex_to_rgb($primary_color);
        $secondary_rgb = $this->hex_to_rgb($secondary_color);
        
        $primary_rgba = "rgba({$primary_rgb['r']}, {$primary_rgb['g']}, {$primary_rgb['b']}, {$primary_opacity})";
        $secondary_rgba = "rgba({$secondary_rgb['r']}, {$secondary_rgb['g']}, {$secondary_rgb['b']}, {$secondary_opacity})";
        $text_color = isset($this->options['text_color']) ? $this->options['text_color'] : '#000000';
        $background_color = isset($this->options['background_color']) ? $this->options['background_color'] : '#ffffff';
        $border_radius = isset($this->options['border_radius']) ? $this->options['border_radius'] : '10';
        $shadow_intensity = isset($this->options['shadow_intensity']) ? $this->options['shadow_intensity'] : '20';
        $icon_size = isset($this->options['icon_size']) ? $this->options['icon_size'] : '24';
        $z_index = isset($this->options['z_index']) ? $this->options['z_index'] : '999';
        $navbar_scale = isset($this->options['navbar_scale']) ? $this->options['navbar_scale'] : '100';
        $title_font_size = isset($this->options['title_font_size']) ? $this->options['title_font_size'] : '16';
        $title_font_weight = isset($this->options['title_font_weight']) ? $this->options['title_font_weight'] : '600';
        $title_font_family = isset($this->options['title_font_family']) ? $this->options['title_font_family'] : 'system';
        $menu_font_size = isset($this->options['menu_font_size']) ? $this->options['menu_font_size'] : '14';
        $menu_font_weight = isset($this->options['menu_font_weight']) ? $this->options['menu_font_weight'] : '500';
        $menu_text_color = isset($this->options['menu_text_color']) ? $this->options['menu_text_color'] : '#000000';
        $submenu_font_size = isset($this->options['submenu_font_size']) ? $this->options['submenu_font_size'] : '12';
        $submenu_text_color = isset($this->options['submenu_text_color']) ? $this->options['submenu_text_color'] : '#666666';
        $search_title_text = 'Newest product';
        $search_title_font_size = isset($this->options['search_title_font_size']) ? $this->options['search_title_font_size'] : '16';
        $search_title_color = isset($this->options['search_title_color']) ? $this->options['search_title_color'] : '#333333';
        $product_font_size = isset($this->options['product_font_size']) ? $this->options['product_font_size'] : '14';
        $checkout_font_size = isset($this->options['checkout_font_size']) ? $this->options['checkout_font_size'] : '14';
        $checkout_font_weight = isset($this->options['checkout_font_weight']) ? $this->options['checkout_font_weight'] : '600';
        
        ?>
        <style id="tirtonic-dynamic-styles">
        :root {
            --tirtonic-primary: <?php echo esc_attr($primary_color); ?>;
            --tirtonic-secondary: <?php echo esc_attr($secondary_color); ?>;
            --tirtonic-text: <?php echo esc_attr($text_color); ?>;
            --tirtonic-background: <?php echo esc_attr($background_color); ?>;
            --tirtonic-radius: <?php echo esc_attr($border_radius); ?>px;
            --tirtonic-shadow: <?php echo esc_attr($shadow_intensity); ?>%;
            --tirtonic-icon-size: <?php echo esc_attr($icon_size); ?>px;
            --tirtonic-z-index: <?php echo esc_attr($z_index); ?>;
            --tirtonic-scale: <?php echo esc_attr($navbar_scale / 100); ?>;
            --tirtonic-title-size: <?php echo esc_attr($title_font_size); ?>px;
            --tirtonic-title-weight: <?php echo esc_attr($title_font_weight); ?>;
            --tirtonic-title-family: <?php echo $title_font_family === 'system' ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' : esc_attr($title_font_family); ?>;
            --tirtonic-menu-size: <?php echo esc_attr($menu_font_size); ?>px;
            --tirtonic-menu-weight: <?php echo esc_attr($menu_font_weight); ?>;
            --tirtonic-menu-color: <?php echo esc_attr($menu_text_color); ?>;
            --tirtonic-submenu-size: <?php echo esc_attr($submenu_font_size); ?>px;
            --tirtonic-submenu-color: <?php echo esc_attr($submenu_text_color); ?>;
            --tirtonic-search-title-size: <?php echo esc_attr($search_title_font_size); ?>px;
            --tirtonic-search-title-color: <?php echo esc_attr($search_title_color); ?>;
            --tirtonic-product-size: <?php echo esc_attr($product_font_size); ?>px;
            --tirtonic-checkout-size: <?php echo esc_attr($checkout_font_size); ?>px;
            --tirtonic-checkout-weight: <?php echo esc_attr($checkout_font_weight); ?>;
        }
        
        /* Enhanced styles based on orpcatalog.id */
        .tirtonic-floating-nav {
            position: fixed;
            top: 6.13vh;
            right: 6.614vw;
            transition: .5s ease;
            z-index: var(--tirtonic-z-index);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transform: scale(var(--tirtonic-scale));
            transform-origin: top right;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .tirtonic-floating-nav.nav-hidden {
            transform: translateY(calc(-100% - 7.13vh));
        }
        
        .tirtonic-floating-nav.top-left {
            top: 6.13vh;
            left: 6.614vw;
            right: auto;
        }
        
        .tirtonic-floating-nav.bottom-right {
            bottom: 6.13vh;
            right: 6.614vw;
            top: auto;
        }
        
        .tirtonic-floating-nav.bottom-left {
            bottom: 6.13vh;
            left: 6.614vw;
            top: auto;
            right: auto;
        }
        
        .tirtonic-nav-header {
            position: relative;
            z-index: 2;
            padding: 1.904vh 2.646vw;
            background: linear-gradient(135deg, var(--tirtonic-primary) 0%, var(--tirtonic-secondary) 100%);
            min-width: 45vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: .5s ease;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100));
            border-radius: var(--tirtonic-radius);
            cursor: pointer;
        }
        
        .tirtonic-nav-header:hover {
            box-shadow: 0 6px 25px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100 + 0.05));
            transform: translateY(-2px);
        }
        
        .tirtonic-nav-opened .tirtonic-nav-header {
            border-radius: var(--tirtonic-radius) var(--tirtonic-radius) 0 0;
            box-shadow: none;
            transform: none;
        }
        
        .tirtonic-nav-toggle {
            display: flex;
            align-items: center;
            gap: 1.5vw;
            cursor: pointer;
            transition: 1s ease;
        }
        
        .tirtonic-nav-title {
            font-size: var(--tirtonic-title-size);
            color: var(--tirtonic-text);
            font-weight: var(--tirtonic-title-weight);
            font-family: var(--tirtonic-title-family);
            letter-spacing: 0.5px;
        }
        
        .tirtonic-nav-arrow {
            display: block;
            width: fit-content;
            line-height: 0;
            transition: .3s ease;
        }
        
        .tirtonic-nav-arrow svg {
            width: 1.3vw;
            transition: .3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .tirtonic-nav-opened .tirtonic-nav-arrow {
            transform: rotate(180deg);
        }
        
        .tirtonic-nav-actions {
            display: flex;
            align-items: center;
            gap: 1.984vw;
        }
        
        .tirtonic-nav-search,
        .tirtonic-nav-cart,
        .tirtonic-nav-custom-icon {
            cursor: pointer;
            transition: all .3s ease;
            position: relative;
            padding: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
        }
        
        .tirtonic-nav-search:hover,
        .tirtonic-nav-cart:hover,
        .tirtonic-nav-custom-icon:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        .tirtonic-nav-search svg,
        .tirtonic-nav-cart svg,
        .tirtonic-nav-custom-icon svg {
            width: var(--tirtonic-icon-size);
            height: var(--tirtonic-icon-size);
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .tirtonic-nav-content {
            position: absolute;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: 10;
            top: 100%;
            left: 0;
            width: 100%;
            padding: 4vh 2.646vw;
            background: var(--tirtonic-background);
            border-radius: 0 0 var(--tirtonic-radius) var(--tirtonic-radius);
            transform: translateY(-20px);
            transition: all .3s ease;
            box-shadow: 0 8px 32px rgba(0,0,0,calc(var(--tirtonic-shadow) / 100 + 0.02));
            max-height: 80vh;
            overflow: auto;
            backdrop-filter: blur(10px);
        }
        
        .tirtonic-nav-opened .tirtonic-nav-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: unset;
        }
        
        .tirtonic-nav-opened .tirtonic-nav-header {
            border-radius: var(--tirtonic-radius) var(--tirtonic-radius) 0 0;
            box-shadow: none;
        }
        
        /* View Transitions */
        .wrapper-menu,
        .tirtonic-search-section,
        .wrapper-cart {
            transition: opacity 0.2s ease;
            opacity: 0;
            display: none;
            position: relative;
        }
        
        /* Ensure only one section is visible */
        .tirtonic-nav-opened .wrapper-menu,
        .tirtonic-nav-opened .tirtonic-search-section,
        .tirtonic-nav-opened .wrapper-cart {
            display: none !important;
            opacity: 0 !important;
        }
        
        .tirtonic-nav-opened.opened-menu .wrapper-menu {
            display: block !important;
            opacity: 1 !important;
        }
        
        .tirtonic-nav-opened.opened-search .tirtonic-search-section {
            display: block !important;
            opacity: 1 !important;
        }
        
        .tirtonic-nav-opened.opened-cart .wrapper-cart {
            display: block !important;
            opacity: 1 !important;
        }
        
        /* Menu Section Styling */
        .wrapper-menu {
            display: block;
        }
        
        .quick--access-content_link1 {
            margin-bottom: 6vh;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 3vh;
        }
        
        .quick--access-content_link1 a {
            font-size: var(--tirtonic-menu-size);
            display: block;
            color: var(--tirtonic-menu-color);
            margin-bottom: .8vh;
            text-decoration: none;
            transition: all .3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: var(--tirtonic-menu-weight);
        }
        
        .quick--access-content_link1 a:hover {
            color: var(--tirtonic-primary);
            background: rgba(255, 200, 58, 0.1);
            transform: translateX(8px);
        }
        
        .quick--access-content_link2 {
            opacity: 0.8;
        }
        
        .quick--access-content_link2 a {
            font-size: var(--tirtonic-submenu-size);
            display: block;
            color: var(--tirtonic-submenu-color);
            text-decoration: none;
            margin-bottom: .5vh;
            transition: all .3s ease;
            padding: 6px 12px;
            border-radius: 4px;
        }
        
        .quick--access-content_link2 a:hover {
            color: #333;
            background: rgba(0,0,0,0.05);
            transform: translateX(4px);
        }
        
        /* Search Section Styling */
        .tirtonic-search-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .newest--product h3,
        .newest--product-wrapper h3,
        .newest--product-searched h3 {
            font-size: var(--tirtonic-search-title-size);
            color: var(--tirtonic-search-title-color);
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        
        .newest--product-item h4 {
            font-size: var(--tirtonic-product-size);
            margin: 0;
            font-weight: 600;
        }
        
        .newest--product-item .price {
            font-size: calc(var(--tirtonic-product-size) - 1px);
            font-weight: bold;
        }
        
        #search--revamp {
            position: relative;
            margin-bottom: 2.5vw;
        }
        
        #search--revamp .form-group {
            position: relative;
        }
        
        #search--revamp .form-group input {
            width: 100%;
            font-size: 14px;
            padding: 12px 50px;
            border-radius: 50px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        #search--revamp .form-group input:focus {
            outline: none;
            border-color: var(--tirtonic-primary);
            box-shadow: 0 0 0 3px rgba(255, 200, 58, 0.1);
        }
        
        #search--revamp .close--icon,
        #search--revamp .search--icon {
            position: absolute;
            top: 0;
            bottom: 0;
            height: fit-content;
            margin: auto;
            display: flex;
        }
        
        #search--revamp .search--icon {
            left: 15px;
        }
        
        #search--revamp .close--icon {
            right: 15px;
            cursor: pointer;
        }
        
        #search--revamp svg {
            width: 18px;
            height: 18px;
        }
        
        #search--revamp .close--icon svg {
            width: 12px;
            height: 12px;
            stroke-width: 1.5px;
        }
        
        .tirtonic-search-section {
            max-width: 100%;
            overflow: hidden;
        }
        
        .newest--product {
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Product Grid Layout */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .product-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: var(--tirtonic-primary);
        }
        
        .product-card a {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        .product-image {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }
        
        .product-image-placeholder {
            width: 100%;
            height: 80px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
        }
        
        .product-image-placeholder::before {
            content: 'No Image';
        }
        
        .product-info {
            padding: 10px;
        }
        
        .product-title {
            margin: 0 0 5px 0;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            color: var(--tirtonic-primary);
            font-weight: bold;
            font-size: 11px;
            display: block;
        }
        
        /* Mobile responsive for product grid */
        @media (max-width: 767px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .product-image {
                height: 60px;
            }
            
            .product-image-placeholder {
                height: 60px;
            }
            
            .product-info {
                padding: 8px;
            }
            
            .product-title {
                font-size: 11px;
            }
            
            .product-price {
                font-size: 10px;
            }
        }
        
        /* Legacy product item styles for backward compatibility */
        .newest--product-item {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }
        
        .newest--product-item:hover {
            background: #f8f9fa;
        }
        
        .newest--product-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
        }
        
        .newest--product-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .newest--product-item h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .newest--product-item .price {
            color: var(--tirtonic-primary);
            font-weight: bold;
            font-size: 13px;
        }
        
        /* Force display if enabled */
        <?php if ($this->options['force_display'] ?? false): ?>
        .tirtonic-floating-nav {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .tirtonic-floating-nav.nav-hidden {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
        }
        <?php endif; ?>
        
        /* Custom CSS from settings */
        <?php if (!empty($this->options['custom_css'])): ?>
        <?php echo wp_strip_all_tags($this->options['custom_css']); ?>
        <?php endif; ?>
        
        /* Mobile Custom CSS */
        @media only screen and (max-width: 767px) {
            <?php if (!empty($this->options['custom_mobile_css'])): ?>
            <?php echo wp_strip_all_tags($this->options['custom_mobile_css']); ?>
            <?php endif; ?>
        }
        
        /* Custom JavaScript */
        <?php if (!empty($this->options['custom_js'])): ?>
        </style>
        <script>
        <?php echo wp_strip_all_tags($this->options['custom_js']); ?>
        </script>
        <style>
        <?php endif; ?>
        
        /* Search Overlay Styles */
        .tirtonic-search-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .search-overlay-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            position: relative;
        }
        
        .search-overlay-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            z-index: 1;
        }
        
        .search-overlay-input {
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-overlay-input input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 18px;
            outline: none;
        }
        
        .search-overlay-input input:focus {
            border-color: var(--tirtonic-primary);
        }
        
        .search-overlay-results {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .search-result-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .search-result-item a {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
            width: 100%;
        }
        
        .result-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .result-info .price {
            color: var(--tirtonic-primary);
            font-weight: bold;
        }
        
        /* Cart Modal Styles */
        .tirtonic-cart-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .cart-modal-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }
        
        .cart-modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--tirtonic-primary);
        }
        
        .cart-modal-header h3 {
            margin: 0;
            color: var(--tirtonic-text);
        }
        
        .cart-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--tirtonic-text);
        }
        
        .cart-modal-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-info h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .qty-minus, .qty-plus {
            background: var(--tirtonic-primary);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
        }
        
        .quantity {
            min-width: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .cart-modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
        }
        
        .cart-modal-footer .button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
        }
        
        .chat-to-buy {
            background: #25D366;
            color: white;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Wishlist/Cart Dropdown Styles */
        .wrapper-cart {
            display: none;
        }
        
        .cart-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-info h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .qty-minus, .qty-plus {
            background: var(--tirtonic-primary);
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            color: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-minus:hover, .qty-plus:hover {
            background: var(--tirtonic-secondary);
        }
        
        .quantity {
            min-width: 30px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            color: #333333;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .cart-item .price {
            color: var(--tirtonic-primary);
            font-weight: bold;
            font-size: 13px;
        }
        
        .wishlist-checkout-section {
            padding: 20px 0;
            text-align: center;
            border-top: 1px solid #e9ecef;
            margin-top: 20px;
        }
        
        .wishlist-checkout-btn {
            background: linear-gradient(135deg, var(--tirtonic-primary) 0%, var(--tirtonic-secondary) 100%);
            color: var(--tirtonic-text);
            padding: 12px 24px;
            border-radius: var(--tirtonic-radius);
            border: none;
            cursor: pointer;
            font-size: var(--tirtonic-checkout-size);
            font-weight: var(--tirtonic-checkout-weight);
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .wishlist-checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .wishlist-checkout-btn.whatsapp-btn {
            background: #25D366;
            color: white;
        }
        
        .wishlist-checkout-btn.whatsapp-btn:hover {
            background: #128C7E;
        }
        
        .button--checkout-wrapper {
            display: none;
        }
        
        .empty-cart, .cart-loading, .cart-error {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
        }
        

        
        /* Responsive Settings */
        <?php if ($this->options['hide_desktop'] ?? false): ?>
        @media only screen and (min-width: 1024px) {
            .tirtonic-floating-nav { display: none !important; }
        }
        <?php endif; ?>
        
        <?php if ($this->options['hide_tablet'] ?? false): ?>
        @media only screen and (min-width: 768px) and (max-width: 1023px) {
            .tirtonic-floating-nav { display: none !important; }
        }
        <?php endif; ?>
        
        <?php if ($this->options['hide_mobile'] ?? false): ?>
        @media only screen and (max-width: 767px) {
            .tirtonic-floating-nav { display: none !important; }
        }
        <?php endif; ?>
        
        /* Tablet Responsive */
        @media only screen and (min-width: 768px) and (max-width: 1023px) {
            .tirtonic-floating-nav {
                <?php 
                $tablet_position = $this->options['tablet_position'] ?? 'top-right';
                $tablet_scale = ($this->options['tablet_scale'] ?? 90) / 100;
                switch($tablet_position) {
                    case 'top-left':
                        echo 'top: 6.13vh; left: 6.614vw; right: auto; bottom: auto;';
                        break;
                    case 'bottom-right':
                        echo 'bottom: 6.13vh; right: 6.614vw; top: auto; left: auto;';
                        break;
                    case 'bottom-left':
                        echo 'bottom: 6.13vh; left: 6.614vw; top: auto; right: auto;';
                        break;
                    default:
                        echo 'top: 6.13vh; right: 6.614vw; bottom: auto; left: auto;';
                }
                ?>
                transform: scale(<?php echo $tablet_scale; ?>);
            }
        }
        
        /* Mobile Responsive */
        @media only screen and (max-width: 767px) {
            .tirtonic-floating-nav {
                <?php if (($this->options['mobile_position'] ?? 'bottom') === 'top'): ?>
                top: 3.142vh;
                bottom: auto;
                <?php else: ?>
                bottom: 3.142vh;
                top: auto;
                <?php endif; ?>
                left: 0;
                right: 0;
                width: calc(100% - 17.812vw);
                margin: auto;
                z-index: 99998;
                transform: scale(<?php echo ($this->options['mobile_scale'] ?? 80) / 100; ?>);
            }
        }
        
        .tirtonic-nav-close {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            background: rgba(0,0,0,0.1);
            border: none;
            font-size: 20px;
            color: #000;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all .3s ease;
            z-index: 1000;
        }
        
        .tirtonic-nav-close:hover {
            background: rgba(0,0,0,0.2);
            transform: rotate(90deg);
        }
        
        @media only screen and (max-width: 768px) {
            .tirtonic-floating-nav {
                bottom: 3.142vh;
                left: 0;
                right: 0;
                top: auto;
                width: calc(100% - 17.812vw);
                margin: auto;
                z-index: 99998;
            }
            
            .tirtonic-floating-nav.nav-hidden {
                transform: translateY(100%);
                transition: transform 0.3s ease;
            }
            
            .tirtonic-nav-header {
                padding: 1.878vh 9.415vw;
                min-width: 80vw;
                position: relative;
                z-index: 99999;
                margin-top: 220%;
            }
            
            .tirtonic-nav-title {
                font-size: 4.5vw;
            }
            
            .tirtonic-nav-arrow svg {
                width: 4vw;
            }
            
            .tirtonic-nav-actions {
                display: flex;
                align-items: center;
                gap: 1.984vw;
                flex-shrink: 0;
            }
            
            .tirtonic-nav-search,
            .tirtonic-nav-cart,
            .tirtonic-nav-custom-icon {
                padding: 8px;
                border-radius: 50%;
                background: rgba(255,255,255,0.2);
                transition: none;
                transform: none !important;
            }
            
            .tirtonic-nav-opened .tirtonic-nav-search,
            .tirtonic-nav-opened .tirtonic-nav-cart,
            .tirtonic-nav-opened .tirtonic-nav-custom-icon {
                transform: none !important;
            }
            
            .tirtonic-nav-search svg,
            .tirtonic-nav-cart svg,
            .tirtonic-nav-custom-icon svg {
                width: 3.5vw;
                height: 3.5vw;
            }
            
            .tirtonic-nav-content {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                padding: 9.507vh 8.906vw;
                padding-bottom: 18.723vh;
                transform: translateY(100%);
                opacity: 0;
                visibility: hidden;
                overflow: auto;
                max-height: 100vh;
                border-radius: 0;
                z-index: 99997;
                background: var(--tirtonic-background);
                transition: transform 0.3s ease;
                display: none;
            }
            
            .tirtonic-nav-opened .tirtonic-nav-content {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                display: block;
            }
            
            .tirtonic-nav-close {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                font-size: 24px;
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99998;
                background: rgba(0,0,0,0.1);
                border: none;
                border-radius: 50%;
                color: #000;
                cursor: pointer;
            }
            
            .tirtonic-nav-opened .tirtonic-nav-close {
                display: flex;
            }
            
            .quick--access-content_link1 {
                margin-bottom: 12.723vh;
            }
            
            .quick--access-content_link1 a {
                font-size: 7vw;
                padding: 12px 0;
                margin-bottom: 1.2vh;
            }
            
            .quick--access-content_link2 a {
                font-size: 5vw;
                padding: 10px 0;
            }
            
            #search--revamp .form-group input {
                font-size: 3.553vw;
                padding: 3.058vw 9.96vw;
            }
            
            #search--revamp .search--icon {
                left: 4vw;
            }
            
            #search--revamp .close--icon {
                right: 4vw;
            }
            
            #search--revamp svg {
                width: 3.2vw;
                height: 3.2vw;
            }
            
            #search--revamp .close--icon svg {
                width: 2vw;
                height: 2vw;
            }
        }
        </style>
        <?php
    }
    
    // AJAX Methods
    public function ajax_search_products() {
        check_ajax_referer('tirtonic_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $results = array();
        
        if (class_exists('WooCommerce') && !empty($search_term)) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => 8,
                's' => $search_term,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_stock_status',
                        'value' => 'instock'
                    )
                )
            );
            
            $products = new WP_Query($args);
            
            if ($products->have_posts()) {
                while ($products->have_posts()) {
                    $products->the_post();
                    $product = wc_get_product(get_the_ID());
                    
                    $results[] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'price' => $product->get_price_html(),
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                        'rating' => $product->get_average_rating(),
                        'reviews' => $product->get_review_count()
                    );
                }
                wp_reset_postdata();
            }
            
            // Add recent products if search results are limited
            if (count($results) < 5) {
                $recent_args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 5 - count($results),
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post_status' => 'publish',
                    'post__not_in' => array_column($results, 'id')
                );
                
                $recent_products = new WP_Query($recent_args);
                
                if ($recent_products->have_posts()) {
                    while ($recent_products->have_posts()) {
                        $recent_products->the_post();
                        $product = wc_get_product(get_the_ID());
                        
                        $results[] = array(
                            'id' => get_the_ID(),
                            'title' => get_the_title(),
                            'url' => get_permalink(),
                            'price' => $product->get_price_html(),
                            'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                            'rating' => $product->get_average_rating(),
                            'reviews' => $product->get_review_count(),
                            'is_recent' => true
                        );
                    }
                    wp_reset_postdata();
                }
            }
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_get_cart_count() {
        check_ajax_referer('tirtonic_nonce', 'nonce');
        
        $count = 0;
        if (class_exists('WooCommerce')) {
            $count = WC()->cart->get_cart_contents_count();
        }
        
        wp_send_json_success($count);
    }
    
    public function ajax_get_newest_products() {
        check_ajax_referer('tirtonic_nonce', 'nonce');
        
        $results = array();
        
        if (class_exists('WooCommerce')) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_stock_status',
                        'value' => 'instock'
                    )
                )
            );
            
            $products = new WP_Query($args);
            
            if ($products->have_posts()) {
                while ($products->have_posts()) {
                    $products->the_post();
                    $product = wc_get_product(get_the_ID());
                    
                    $results[] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'price' => $product->get_price_html(),
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                        'rating' => $product->get_average_rating(),
                        'reviews' => $product->get_review_count()
                    );
                }
                wp_reset_postdata();
            }
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_get_cart_contents() {
        check_ajax_referer('tirtonic_nonce', 'nonce');
        
        $results = array();
        
        if (class_exists('WooCommerce') && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $product_id = $cart_item['product_id'];
                
                $results[] = array(
                    'key' => $cart_item_key,
                    'product_id' => $product_id,
                    'title' => $product->get_name(),
                    'quantity' => $cart_item['quantity'],
                    'price' => WC()->cart->get_product_price($product),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                    'url' => get_permalink($product_id)
                );
            }
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_update_cart_quantity() {
        check_ajax_referer('tirtonic_nonce', 'nonce');
        
        $cart_key = sanitize_text_field($_POST['cart_key']);
        $quantity = intval($_POST['quantity']);
        
        if (class_exists('WooCommerce')) {
            if ($quantity <= 0) {
                WC()->cart->remove_cart_item($cart_key);
            } else {
                WC()->cart->set_quantity($cart_key, $quantity);
            }
            
            wp_send_json_success(array(
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total()
            ));
        } else {
            wp_send_json_error('WooCommerce not active');
        }
    }
    
    // Helper Methods
    private function get_default_options() {
        return array(
            'enable_navbar' => true,
            'navbar_title' => 'Quick Access',
            'navbar_position' => 'top-right',
            'primary_color' => '#ffc83a',
            'secondary_color' => '#ffb800',
            'text_color' => '#000000',
            'background_color' => '#ffffff',
            'border_radius' => 10,
            'shadow_intensity' => 20,
            'icon_size' => 24,
            'z_index' => 99999,
            'auto_hide' => true,
            'enable_search' => true,
            'enable_cart' => true,
            'search_icon' => 'search',
            'cart_icon' => 'cart',
            'search_action' => 'product_search',
            'cart_action' => 'cart_page',
            'search_placeholder' => 'Search products...',
            'menu_style' => 'simple',
            'show_menu_icons' => true,
            'animation_duration' => 500,
            'mobile_breakpoint' => 768,
            'enable_analytics' => true,
            'menu_items' => $this->get_default_menu_items(),
            'additional_icons' => array(),
            'custom_css' => '',
            'custom_mobile_css' => '',
            'search_results_title' => 'Product',
            'desktop_position' => 'top-right',
            'tablet_position' => 'top-right',
            'mobile_position' => 'bottom',
            'desktop_scale' => 100,
            'tablet_scale' => 90,
            'mobile_scale' => 80,
            'hide_desktop' => false,
            'hide_tablet' => false,
            'hide_mobile' => false,
            'mobile_full_width' => true,
            'enable_auto_update' => true,
            'update_channel' => 'stable',
            'debug_mode' => false,
            'force_display' => false,
            'custom_js' => '',
            'exclude_pages' => "cart\ncheckout\ncontact",
            'footer_menu_1_title' => 'Ask personal shopper',
            'footer_menu_1_url' => 'https://api.whatsapp.com/send/?phone=6285163215511',
            'footer_menu_2_title' => 'Follow our Instagram',
            'footer_menu_2_url' => 'https://instagram.com/tirtonic',
            'footer_menu_3_title' => '',
            'footer_menu_3_url' => ''
        );
    }
    
    private function get_default_menu_items() {
        $items = array();
        
        // Only try to get menu if WordPress is fully loaded
        if (function_exists('get_nav_menu_locations') && !is_admin()) {
            $locations = get_nav_menu_locations();
            $menu_id = isset($locations['primary']) ? $locations['primary'] : 0;
            
            if ($menu_id && function_exists('wp_get_nav_menu_items')) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                if ($menu_items && !is_wp_error($menu_items)) {
                    foreach ($menu_items as $menu_item) {
                        if ($menu_item->menu_item_parent == 0 && count($items) < 6) {
                            $items[] = array(
                                'title' => $menu_item->title,
                                'url' => $menu_item->url,
                                'icon' => 'link'
                            );
                        }
                    }
                }
            }
        }
        
        // Fallback menu items
        if (empty($items)) {
            $items[] = array('title' => 'Home', 'url' => home_url(), 'icon' => 'home');
            $items[] = array('title' => 'Shop', 'url' => 'https://tirtonic.com/store', 'icon' => 'shop');
            $items[] = array('title' => 'Articles', 'url' => 'https://tirtonic.com/article', 'icon' => 'info');
            $items[] = array('title' => 'About', 'url' => 'https://tirtonic.com/stores', 'icon' => 'info');
            $items[] = array('title' => 'Contact', 'url' => 'https://tirtonic.com/contact', 'icon' => 'contact');
            $items[] = array('title' => 'Sponsorship', 'url' => 'https://tirtonic.com/sponsorship', 'icon' => 'star');
        }
        
        return $items;
    }
    
    private function get_icon_library() {
        return array(
            'search' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
            'cart' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>',
            'home' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
            'shop' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 7h-3V6a4 4 0 0 0-8 0v1H5a1 1 0 0 0-1 1v11a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8a1 1 0 0 0-1-1zM10 6a2 2 0 0 1 4 0v1h-4V6zm8 15a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9h2v1a1 1 0 0 0 2 0V9h4v1a1 1 0 0 0 2 0V9h2v12z"/></svg>',
            'info' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
            'contact' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
            'link' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>',
            'heart' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
            'user' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
            'phone' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
            'menu' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>',
            'close' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
            'arrow_down' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
        );
    }
}

// Initialize the plugin after WordPress is fully loaded
function tirtonic_init_plugin() {
    new TirtonicAdvancedFloatingNavbar();
}
add_action('plugins_loaded', 'tirtonic_init_plugin');

// Update version after plugin upgrade
add_action('upgrader_process_complete', 'tirtonic_after_plugin_update', 10, 2);
function tirtonic_after_plugin_update($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        foreach($options['plugins'] as $plugin) {
            if ($plugin == plugin_basename(__FILE__)) {
                update_option('floating_navbar_version', TIRTONIC_NAV_VERSION);
                delete_transient('floating_navbar_latest_version');
                delete_transient('floating_navbar_remote_version');
                break;
            }
        }
    }
}