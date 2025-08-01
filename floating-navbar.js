/**
 * Tirtonic Floating Navbar JavaScript
 * Advanced functionality untuk floating navbar WordPress
 */

class TirtonicFloatingNav {
    constructor() {
        this.nav = null;
        this.lastScrollTop = 0;
        this.navTimeout = null;
        this.isOpen = false;
        this.currentView = 'menu'; // menu, search, cart
        this.scrollThreshold = 100;
        this.hideDelay = 2000;
        
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        this.nav = document.getElementById('tirtonicFloatingNav');
        if (!this.nav) {
            return;
        }
        
        this.bindEvents();
        this.setupIntersectionObserver();
    }
    
    bindEvents() {
        // Exit if nav doesn't exist
        if (!this.nav) {
            return;
        }
        
        // Scroll event dengan throttling
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Quick Access Menu - toggle menu dropdown
        const toggle = this.nav.querySelector('.tirtonic-nav-toggle');
        if (toggle) {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'menu' && this.isOpen) {
                    this.close();
                } else {
                    this.openNavbar('menu');
                }
            });
        }
        
        // Search Button - toggle search dropdown
        const searchBtn = this.nav.querySelector('.tirtonic-nav-search');
        if (searchBtn) {
            searchBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'search' && this.isOpen) {
                    this.close();
                } else {
                    this.openNavbar('search');
                }
            });
        }
        
        // Cart Button - toggle cart dropdown
        const cartBtn = this.nav.querySelector('.tirtonic-nav-cart');
        if (cartBtn) {
            cartBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (this.currentView === 'cart' && this.isOpen) {
                    this.close();
                } else {
                    this.openNavbar('cart');
                }
            });
        }
        
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.nav.contains(e.target)) {
                this.close();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Prevent nav content clicks from closing
        const content = this.nav.querySelector('.tirtonic-nav-content');
        if (content) {
            content.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        // Custom icons functionality
        const customIcons = this.nav.querySelectorAll('.tirtonic-nav-custom-icon');
        customIcons.forEach(icon => {
            icon.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleCustomIcon(icon);
            });
        });
        
        // Close button functionality
        const closeBtn = this.nav.querySelector('.tirtonic-nav-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });
        }
        
        // Mobile close button - ensure it works on mobile
        if (window.innerWidth <= 768) {
            const mobileCloseBtn = this.nav.querySelector('.tirtonic-nav-close');
            if (mobileCloseBtn) {
                mobileCloseBtn.style.display = 'flex';
                mobileCloseBtn.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.close();
                }, { passive: false });
            }
        }
    }
    
    handleScroll() {
        // Exit early if nav element doesn't exist
        if (!this.nav || !this.nav.classList) {
            return;
        }
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        clearTimeout(this.navTimeout);
        
        // Don't hide if nav is open
        if (this.isOpen) return;
        
        if (scrollTop > this.lastScrollTop && scrollTop > this.scrollThreshold) {
            // Scrolling down - hide nav
            this.hide();
        } else {
            // Scrolling up - show nav
            this.show();
        }
        
        this.lastScrollTop = scrollTop;
        
        // Auto show after scroll stops
        this.navTimeout = setTimeout(() => {
            if (!this.isOpen && this.nav && this.nav.classList) {
                this.show();
            }
        }, this.hideDelay);
    }
    
    setupIntersectionObserver() {
        // Hide nav when reaching footer
        const footer = document.querySelector('footer, .footer, #footer');
        if (footer) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.hide();
                    }
                });
            }, {
                threshold: 0.1
            });
            
            observer.observe(footer);
        }
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    openNavbar(view = 'menu') {
        this.nav.classList.add('tirtonic-nav-opened');
        this.nav.classList.add('opened--clicked');
        this.isOpen = true;
        
        // Show specific view
        this.showView(view);
        
        // Prevent body scroll on mobile
        if (window.innerWidth <= 768) {
            document.body.style.overflow = 'hidden';
        }
        
        // Focus management
        if (view === 'menu') {
            const firstLink = this.nav.querySelector('.quick--access-content_link1 a');
            if (firstLink) {
                setTimeout(() => firstLink.focus(), 300);
            }
        } else if (view === 'search') {
            const searchInput = this.nav.querySelector('#tirtonic-search-input');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 300);
            }
        }
        
        // Analytics tracking disabled
    }
    
    open() {
        this.openNavbar('menu');
    }
    
    showView(view) {
        const menuSection = this.nav.querySelector('.wrapper-menu');
        const searchSection = this.nav.querySelector('.tirtonic-search-section');
        const cartSection = this.nav.querySelector('.wrapper-cart');
        
        // Force hide all sections immediately
        if (menuSection) {
            menuSection.style.display = 'none';
            menuSection.style.opacity = '0';
        }
        if (searchSection) {
            searchSection.style.display = 'none';
            searchSection.style.opacity = '0';
        }
        if (cartSection) {
            cartSection.style.display = 'none';
            cartSection.style.opacity = '0';
        }
        
        // Remove all state classes
        this.nav.classList.remove('opened-search', 'opened-cart', 'opened-menu');
        
        // Small delay to ensure clean transition
        setTimeout(() => {
            // Show requested view
            switch(view) {
                case 'search':
                    if (searchSection) {
                        searchSection.style.display = 'block';
                        searchSection.style.opacity = '1';
                    }
                    this.nav.classList.add('opened-search');
                    this.setupSearchInput();
                    this.loadNewestProducts();
                    break;
                case 'cart':
                    if (cartSection) {
                        cartSection.style.display = 'block';
                        cartSection.style.opacity = '1';
                    }
                    this.nav.classList.add('opened-cart');
                    this.loadWishlistContents();
                    break;
                default: // menu
                    if (menuSection) {
                        menuSection.style.display = 'block';
                        menuSection.style.opacity = '1';
                    }
                    this.nav.classList.add('opened-menu');
                    break;
            }
        }, 50);
        
        this.currentView = view;
    }
    
    close() {
        this.nav.classList.remove('tirtonic-nav-opened');
        this.nav.classList.remove('opened--clicked');
        this.nav.classList.remove('opened-search');
        this.nav.classList.remove('opened-cart');
        this.nav.classList.remove('opened-menu');
        this.isOpen = false;
        this.currentView = 'menu';
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Analytics tracking disabled
    }
    
    show() {
        this.nav.classList.remove('nav-hidden');
    }
    
    hide() {
        this.nav.classList.add('nav-hidden');
    }
    

    

    

    
    setupSearchInput() {
        const searchInput = this.nav.querySelector('#tirtonic-search-input');
        if (searchInput && !searchInput.hasAttribute('data-listener')) {
            searchInput.setAttribute('data-listener', 'true');
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.trim();
                if (searchTerm.length >= 2) {
                    this.performProductSearch(searchTerm);
                } else {
                    this.loadNewestProducts();
                }
            });
        }
    }
    

    

    
    loadWishlistContents() {
        if (typeof tirtonicAjax === 'undefined') return;
        
        const cartContainer = this.nav.querySelector('.wrapper-cart .Drawer__Main');
        if (!cartContainer) return;
        
        cartContainer.innerHTML = '<div class="cart-loading">Loading cart...</div>';
        
        fetch(tirtonicAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tirtonic_get_cart_contents',
                nonce: tirtonicAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                cartContainer.innerHTML = data.data.map(item => `
                    <div class="cart-item" data-key="${item.key}">
                        <div class="cart-item-content">
                            ${item.image ? `<img src="${item.image}" alt="${item.title}" class="cart-item-image">` : ''}
                            <div class="cart-item-info">
                                <h4>${item.title}</h4>
                                <div class="quantity-controls">
                                    <button class="qty-minus">-</button>
                                    <span class="quantity">${item.quantity}</span>
                                    <button class="qty-plus">+</button>
                                </div>
                                <span class="price">${item.price}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Add checkout button at the end
                const settings = JSON.parse(this.nav.getAttribute('data-settings') || '{}');
                const buttonText = settings.checkout_button_text || 'Checkout';
                const buttonAction = settings.checkout_button_action || 'checkout';
                
                const checkoutSection = document.createElement('div');
                checkoutSection.className = 'wishlist-checkout-section';
                checkoutSection.innerHTML = `<button class="wishlist-checkout-btn">${buttonText}</button>`;
                cartContainer.appendChild(checkoutSection);
                
                // Add click handler for button
                const checkoutBtn = checkoutSection.querySelector('.wishlist-checkout-btn');
                checkoutBtn.addEventListener('click', () => {
                    this.handleCheckoutAction(buttonAction, data.data, settings);
                });
                
                // Add quantity change handlers
                this.setupWishlistQuantityControls();
            } else {
                cartContainer.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
            }
        })
        .catch(error => {
            cartContainer.innerHTML = '<div class="cart-error">Error loading cart</div>';
        });
    }
    
    setupWishlistQuantityControls() {
        // Remove existing listeners first
        this.nav.querySelectorAll('.qty-plus, .qty-minus').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        
        // Add new listeners
        this.nav.querySelectorAll('.qty-plus, .qty-minus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const cartItem = e.target.closest('.cart-item');
                const key = cartItem.getAttribute('data-key');
                const isPlus = e.target.classList.contains('qty-plus');
                const quantitySpan = cartItem.querySelector('.quantity');
                const currentQty = parseInt(quantitySpan.textContent);
                const newQty = isPlus ? currentQty + 1 : Math.max(1, currentQty - 1);
                
                // Update display immediately
                quantitySpan.textContent = newQty;
                
                // Update cart via AJAX
                this.updateWishlistQuantity(key, newQty);
            });
        });
    }
    
    updateWishlistQuantity(key, quantity) {
        if (typeof tirtonicAjax === 'undefined') return;
        
        fetch(tirtonicAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tirtonic_update_cart_quantity',
                cart_key: key,
                quantity: quantity,
                nonce: tirtonicAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadWishlistContents();
                // Update cart count in navbar
                const cartCount = this.nav.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.data.cart_count || 0;
                }
            }
        })
        .catch(error => {
            // Handle error silently
        });
    }
    
    handleCheckoutAction(action, cartItems, settings) {
        switch(action) {
            case 'checkout':
                window.location.href = tirtonicAjax.checkout_url || '/checkout';
                break;
            case 'cart':
                window.location.href = tirtonicAjax.cart_url || '/cart';
                break;
            case 'whatsapp':
                this.generateWishlistWhatsApp(cartItems);
                break;
            case 'custom_url':
                const customUrl = settings.checkout_custom_url;
                if (customUrl && customUrl.trim() !== '') {
                    window.open(customUrl, '_blank');
                }
                break;
        }
    }
    
    generateWishlistWhatsApp(cartItems) {
        let message = 'Saya tertarik membeli:\n';
        
        cartItems.forEach(item => {
            message += `${item.quantity}x ${item.title}\n`;
        });
        
        const whatsappNumber = '6281234567890'; // Replace with actual number
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
        
        window.open(whatsappUrl, '_blank');
    }
    
    loadNewestProducts() {
        if (typeof tirtonicAjax === 'undefined') return;
        
        const newestWrapper = this.nav.querySelector('.newest--product-wrapper');
        const searchedWrapper = this.nav.querySelector('.newest--product-searched');
        
        if (newestWrapper) newestWrapper.style.display = 'block';
        if (searchedWrapper) searchedWrapper.style.display = 'none';
        
        // Update title text
        const settings = JSON.parse(this.nav.getAttribute('data-settings') || '{}');
        const titleText = settings.search_title_text || 'Newest product';
        const titleElement = newestWrapper ? newestWrapper.querySelector('h3') : null;
        if (titleElement) {
            titleElement.textContent = titleText;
        }
        
        fetch(tirtonicAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tirtonic_get_newest_products',
                nonce: tirtonicAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderNewestProducts(data.data);
            }
        })
        .catch(error => {
            // Handle error silently
        });
    }
    
    renderNewestProducts(products) {
        const productList = this.nav.querySelector('.newest--product-list');
        if (productList) {
            if (products.length > 0) {
                productList.innerHTML = `
                    <div class="product-grid">
                        ${products.map(product => `
                            <div class="product-card">
                                <a href="${product.url}">
                                    ${product.image ? `<img src="${product.image}" alt="${product.title}" class="product-image">` : '<div class="product-image-placeholder"></div>'}
                                    <div class="product-info">
                                        <h4 class="product-title">${product.title}</h4>
                                        <span class="product-price">${product.price}</span>
                                    </div>
                                </a>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                productList.innerHTML = '<p style="text-align:center;color:#666;">No products available</p>';
            }
        }
    }
    
    performProductSearch(searchTerm) {
        if (typeof tirtonicAjax === 'undefined') return;
        
        const searchResults = this.nav.querySelector('.searched--product-list');
        const newestWrapper = this.nav.querySelector('.newest--product-wrapper');
        const searchedWrapper = this.nav.querySelector('.newest--product-searched');
        
        if (searchResults) {
            searchResults.innerHTML = '<p>Searching...</p>';
        }
        
        if (newestWrapper) newestWrapper.style.display = 'none';
        if (searchedWrapper) searchedWrapper.style.display = 'block';
        
        fetch(tirtonicAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tirtonic_search_products',
                search_term: searchTerm,
                nonce: tirtonicAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                this.renderSearchResults(data.data);
            } else {
                if (searchResults) {
                    searchResults.innerHTML = '<p>No products found</p>';
                }
            }
        })
        .catch(error => {
            if (searchResults) {
                searchResults.innerHTML = '<p>Search error occurred</p>';
            }
        });
    }
    
    renderSearchResults(products) {
        const searchResults = this.nav.querySelector('.searched--product-list');
        if (searchResults) {
            searchResults.innerHTML = `
                <div class="product-grid">
                    ${products.map(product => `
                        <div class="product-card">
                            <a href="${product.url}">
                                ${product.image ? `<img src="${product.image}" alt="${product.title}" class="product-image">` : '<div class="product-image-placeholder"></div>'}
                                <div class="product-info">
                                    <h4 class="product-title">${product.title}</h4>
                                    <span class="product-price">${product.price}</span>
                                </div>
                            </a>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    }
    
    getSearchUrl() {
        // Get WordPress search URL
        const baseUrl = window.location.origin;
        return `${baseUrl}/?s=`;
    }
    
    trackEvent(eventName, eventData = {}) {
        // Analytics tracking disabled
        return;
    }
    
    handleCustomIcon(iconElement) {
        const action = iconElement.getAttribute('data-action');
        const url = iconElement.getAttribute('data-url');
        
        switch(action) {
            case 'search':
                this.handleSearchAction();
                break;
            case 'cart':
                this.handleCartAction();
                break;
            case 'url':
            default:
                if (url && url.trim() !== '') {
                    window.open(url, '_blank');
                }
                break;
        }
        
        // Analytics tracking disabled
    }
    
    // Public API methods
    destroy() {
        if (this.nav) {
            this.nav.remove();
        }
        
        // Remove event listeners
        window.removeEventListener('scroll', this.handleScroll);
        document.removeEventListener('click', this.handleOutsideClick);
        document.removeEventListener('keydown', this.handleKeydown);
    }
    
    updateMenu(menuItems) {
        const menuContainer = this.nav.querySelector('.tirtonic-nav-menu');
        if (menuContainer && Array.isArray(menuItems)) {
            menuContainer.innerHTML = menuItems.map(item => 
                `<a href="${item.url}">${item.title}</a>`
            ).join('');
        }
    }
    
    setTheme(theme) {
        this.nav.classList.remove('theme-light', 'theme-dark');
        this.nav.classList.add(`theme-${theme}`);
    }
}

// Auto-initialize when DOM is ready
let tirtonicNav;

function initTirtonicNav() {
    // Add small delay to ensure all elements are rendered
    setTimeout(() => {
        tirtonicNav = new TirtonicFloatingNav();
        // Make globally accessible
        if (typeof window !== 'undefined') {
            window.tirtonicNav = tirtonicNav;
            window.TirtonicFloatingNav = TirtonicFloatingNav;
        }
    }, 100);
}

// Initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTirtonicNav);
} else if (document.readyState === 'interactive' || document.readyState === 'complete') {
    initTirtonicNav();
} else {
    // Fallback
    window.addEventListener('load', initTirtonicNav);
}