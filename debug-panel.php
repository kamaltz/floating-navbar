<?php
/**
 * Debug Panel for Floating Navbar
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add debug panel to advanced tab
add_action('tirtonic_advanced_tab_content', 'tirtonic_render_debug_panel');

function tirtonic_render_debug_panel() {
    ?>
    <div class="tirtonic-section">
        <h3>Debug & Diagnostics</h3>
        <div class="debug-panel">
            <div class="debug-info">
                <h4>System Information</h4>
                <ul>
                    <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong>Plugin Version:</strong> <?php echo TIRTONIC_NAV_VERSION; ?></li>
                    <li><strong>jQuery Version:</strong> <span id="jquery-version">Checking...</span></li>
                    <li><strong>WooCommerce:</strong> <?php echo class_exists('WooCommerce') ? 'Active' : 'Not Active'; ?></li>
                </ul>
            </div>
            
            <div class="debug-checks">
                <h4>Navbar Status</h4>
                <button type="button" class="button" id="check-navbar-status">Check Status</button>
                <div id="navbar-status-results"></div>
            </div>
            
            <div class="debug-logs">
                <h4>Console Logs</h4>
                <textarea id="debug-console" rows="10" readonly style="width: 100%; font-family: monospace; background: #000; color: #0f0; padding: 10px;"></textarea>
                <button type="button" class="button" id="clear-logs">Clear Logs</button>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Check jQuery version
        $('#jquery-version').text($.fn.jquery || 'Not loaded');
        
        // Debug console
        const debugConsole = $('#debug-console');
        const originalLog = console.log;
        const originalError = console.error;
        
        function addToDebugConsole(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const prefix = type === 'error' ? '[ERROR]' : '[LOG]';
            debugConsole.val(debugConsole.val() + `${timestamp} ${prefix} ${message}\n`);
            debugConsole.scrollTop(debugConsole[0].scrollHeight);
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToDebugConsole(args.join(' '), 'log');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToDebugConsole(args.join(' '), 'error');
        };
        
        $('#clear-logs').on('click', function() {
            debugConsole.val('');
        });
        
        // Check navbar status
        $('#check-navbar-status').on('click', function() {
            const results = $('#navbar-status-results');
            let html = '<div class="status-check">';
            
            // Check if navbar element exists
            const navbarExists = !!document.getElementById('tirtonicFloatingNav');
            html += `<p>${navbarExists ? '✅' : '❌'} Navbar Element: ${navbarExists ? 'Found' : 'Not Found'}</p>`;
            
            // Check if CSS is loaded
            const cssLoaded = $('link[href*="floating-navbar"]').length > 0;
            html += `<p>${cssLoaded ? '✅' : '❌'} CSS Loaded: ${cssLoaded ? 'Yes' : 'No'}</p>`;
            
            // Check if JS is loaded
            const jsLoaded = typeof window.tirtonicNav !== 'undefined';
            html += `<p>${jsLoaded ? '✅' : '❌'} JavaScript Loaded: ${jsLoaded ? 'Yes' : 'No'}</p>`;
            
            // Check navbar visibility
            if (navbarExists) {
                const navbar = document.getElementById('tirtonicFloatingNav');
                const isVisible = navbar.offsetParent !== null;
                const computedStyle = window.getComputedStyle(navbar);
                html += `<p>${isVisible ? '✅' : '❌'} Navbar Visible: ${isVisible ? 'Yes' : 'No'}</p>`;
                html += `<p>Display: ${computedStyle.display}</p>`;
                html += `<p>Visibility: ${computedStyle.visibility}</p>`;
                html += `<p>Opacity: ${computedStyle.opacity}</p>`;
                html += `<p>Z-Index: ${computedStyle.zIndex}</p>`;
            }
            
            html += '</div>';
            results.html(html);
        });
        
        // Auto-run status check
        setTimeout(function() {
            $('#check-navbar-status').click();
        }, 1000);
    });
    </script>
    
    <style>
    .debug-panel {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin-top: 15px;
    }
    
    .debug-info, .debug-checks, .debug-logs {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .debug-info ul {
        list-style: none;
        padding: 0;
    }
    
    .debug-info li {
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .status-check p {
        margin: 5px 0;
        padding: 5px;
        background: #fff;
        border-left: 3px solid #ddd;
        font-family: monospace;
    }
    </style>
    <?php
}
?>