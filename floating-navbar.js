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
    this.currentView = "menu"; // menu, search, cart
    this.scrollThreshold = 80;
    this.hideDelay = 2000;
    this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    this.isMobile = window.innerWidth <= 768;

    this.init();
  }

  init() {
    // Wait for DOM to be ready
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    this.nav = document.getElementById("tirtonicFloatingNav");
    if (!this.nav) {
      setTimeout(() => this.setup(), 500);
      return;
    }

    this.applySafariCompatibility();
    this.bindEvents();
    this.setupScrollHandler();
    this.setupIntersectionObserver();
    this.setupTouchHandlers();
  }

  bindEvents() {
    // Exit if nav doesn't exist
    if (!this.nav) {
      return;
    }

    // Scroll event dengan throttling - Safari optimized
    let ticking = false;
    const scrollHandler = () => {
      if (!ticking) {
        if (this.isSafari) {
          setTimeout(() => {
            this.handleScroll();
            ticking = false;
          }, 16);
        } else {
          requestAnimationFrame(() => {
            this.handleScroll();
            ticking = false;
          });
        }
        ticking = true;
      }
    };

    window.addEventListener("scroll", scrollHandler, { passive: true });

    // Quick Access Menu - toggle menu dropdown
    const toggle = this.nav.querySelector(".tirtonic-nav-toggle");
    if (toggle) {
      toggle.addEventListener("click", (e) => {
        e.stopPropagation();
        if (this.currentView === "menu" && this.isOpen) {
          this.close();
        } else {
          this.openNavbar("menu");
        }
      });
    }

    // Search Button - toggle search dropdown
    const searchBtn = this.nav.querySelector(".tirtonic-nav-search");
    if (searchBtn) {
      searchBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        if (this.currentView === "search" && this.isOpen) {
          this.close();
        } else {
          this.openNavbar("search");
        }
      });
    }

    // Cart Button - toggle cart dropdown
    const cartBtn = this.nav.querySelector(".tirtonic-nav-cart");
    if (cartBtn) {
      cartBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        if (this.currentView === "cart" && this.isOpen) {
          this.close();
        } else {
          this.openNavbar("cart");
        }
      });
    }

    // Close when clicking outside (but not on mobile fullscreen)
    document.addEventListener("click", (e) => {
      const mobileContent = document.getElementById(
        "mobile-fullscreen-content"
      );
      if (
        !this.nav.contains(e.target) &&
        (!mobileContent || !mobileContent.contains(e.target))
      ) {
        this.close();
      }
    });

    // Keyboard navigation
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.isOpen) {
        this.close();
      }
    });

    // Prevent nav content clicks from closing
    const content = this.nav.querySelector(".tirtonic-nav-content");
    if (content) {
      content.addEventListener("click", (e) => {
        e.stopPropagation();
      });
    }

    // Custom icons functionality
    const customIcons = this.nav.querySelectorAll(".tirtonic-nav-custom-icon");
    customIcons.forEach((icon) => {
      icon.addEventListener("click", (e) => {
        e.stopPropagation();
        this.handleCustomIcon(icon);
      });
    });

    // Close button functionality
    const closeBtn = this.nav.querySelector(".tirtonic-nav-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.close();
      });
    }

    // Mobile close button - ensure it works on mobile
    if (window.innerWidth <= 768) {
      const mobileCloseBtn = this.nav.querySelector(".tirtonic-nav-close");
      if (mobileCloseBtn) {
        mobileCloseBtn.style.display = "flex";
        mobileCloseBtn.addEventListener(
          "touchstart",
          (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.close();
          },
          { passive: false }
        );
      }
    }

    // Update cart count on page load
    this.updateCartCount();

    // iOS specific fixes
    if (this.isIOS) {
      this.applyIOSFixes();
    }
  }

  setupScrollHandler() {
    let lastScrollY = 0;
    let ticking = false;

    const handleScroll = () => {
      const navbar = document.getElementById("tirtonicFloatingNav");
      if (!navbar) return;

      const currentScrollY = window.scrollY;

      if (currentScrollY > lastScrollY && currentScrollY > 100) {
        // Scrolling down - hide
        navbar.style.transform = "translateY(-200%)";
      } else if (currentScrollY < lastScrollY) {
        // Scrolling up - show
        navbar.style.transform = "translateY(0)";
      }

      lastScrollY = currentScrollY;
      ticking = false;
    };

    window.addEventListener(
      "scroll",
      () => {
        if (!ticking) {
          requestAnimationFrame(handleScroll);
          ticking = true;
        }
      },
      { passive: true }
    );
  }

  applySafariCompatibility() {
    if (!this.nav) return;

    if (this.isSafari) {
      this.nav.classList.add("safari-browser");
    }

    if (this.isIOS) {
      this.nav.classList.add("ios-device");
      const inputs = this.nav.querySelectorAll("input");
      inputs.forEach((input) => {
        input.style.fontSize = "16px";
      });
    }

    this.nav.style.transform = "translateZ(0)";
    this.nav.style.webkitTransform = "translateZ(0)";
    this.nav.style.webkitBackfaceVisibility = "hidden";
  }

  setupTouchHandlers() {
    if (!this.isMobile) return;

    const toggle = this.nav.querySelector(".tirtonic-nav-toggle");
    const searchBtn = this.nav.querySelector(".tirtonic-nav-search");
    const cartBtn = this.nav.querySelector(".tirtonic-nav-cart");

    [toggle, searchBtn, cartBtn].forEach((btn) => {
      if (btn) {
        btn.addEventListener(
          "touchstart",
          (e) => {
            btn.style.transform = "scale(0.95)";
          },
          { passive: true }
        );

        btn.addEventListener(
          "touchend",
          (e) => {
            btn.style.transform = "scale(1)";
          },
          { passive: true }
        );
      }
    });
  }

  applyIOSFixes() {
    const viewport = document.querySelector("meta[name=viewport]");
    if (viewport) {
      viewport.setAttribute(
        "content",
        "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"
      );
    }
  }

  handleScroll() {
    // Exit immediately if navbar doesn't exist
    if (!this.nav) {
      return;
    }

    // Double check navbar still exists in DOM
    const navElement = document.getElementById("tirtonicFloatingNav");
    if (!navElement) {
      this.nav = null;
      return;
    }

    // Update reference if needed
    if (this.nav !== navElement) {
      this.nav = navElement;
    }

    // Final safety check
    if (!this.nav || !this.nav.classList) {
      return;
    }

    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollDelta = Math.abs(scrollTop - this.lastScrollTop);

    clearTimeout(this.navTimeout);

    // Don't hide if nav is open or scroll delta is too small
    if (this.isOpen || scrollDelta < 5) {
      this.lastScrollTop = scrollTop;
      return;
    }

    if (scrollTop > this.lastScrollTop && scrollTop > this.scrollThreshold) {
      // Scrolling down - hide nav with smooth transition
      this.hideWithTransition();
    } else if (scrollTop < this.lastScrollTop) {
      // Scrolling up - show nav with smooth transition
      this.showWithTransition();
    }

    this.lastScrollTop = scrollTop;

    // Auto show after scroll stops
    this.navTimeout = setTimeout(() => {
      if (!this.isOpen && this.nav && this.nav.classList) {
        this.showWithTransition();
      }
    }, 1500);
  }

  setupIntersectionObserver() {
    // Hide nav when reaching footer
    const footer = document.querySelector("footer, .footer, #footer");
    if (footer) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              this.hide();
            }
          });
        },
        {
          threshold: 0.1,
        }
      );

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

  openNavbar(view = "menu") {
    this.nav.classList.add("tirtonic-nav-opened");
    this.nav.classList.add("opened--clicked");
    this.isOpen = true;

    // Show specific view
    this.showView(view);

    // Mobile fullscreen handling
    if (window.innerWidth <= 768) {
      const content = this.nav.querySelector(".tirtonic-nav-content");

      // Create new fullscreen element
      const fullscreenContent = document.createElement("div");
      fullscreenContent.id = "mobile-fullscreen-content";

      // Clone the entire content to preserve all elements
      fullscreenContent.innerHTML = content.cloneNode(true).innerHTML;

      // Add logo to mobile view if not exists
      const wrapperMenu = fullscreenContent.querySelector(".wrapper-menu");
      if (
        wrapperMenu &&
        !fullscreenContent.querySelector(".tirtonic-nav-logo")
      ) {
        const logoDiv = document.createElement("div");
        logoDiv.className = "tirtonic-nav-logo";
        logoDiv.innerHTML =
          '<img src="https://tirtonic.com/wp-content/uploads/2025/07/cropped-default-logo.png" alt="Logo">';
        logoDiv.style.cssText =
          "text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e9ecef;";
        wrapperMenu.insertBefore(logoDiv, wrapperMenu.firstChild);
      }

      // Ensure existing logo is visible in mobile view
      const logoElement = fullscreenContent.querySelector(".tirtonic-nav-logo");
      if (logoElement) {
        logoElement.style.display = "block";
        logoElement.style.textAlign = "center";
        logoElement.style.marginBottom = "20px";
      }

      const safeAreaTop = this.isIOS ? "env(safe-area-inset-top, 0px)" : "0px";
      const safeAreaBottom = this.isIOS
        ? "env(safe-area-inset-bottom, 0px)"
        : "0px";
      const safeAreaLeft = this.isIOS
        ? "env(safe-area-inset-left, 0px)"
        : "0px";
      const safeAreaRight = this.isIOS
        ? "env(safe-area-inset-right, 0px)"
        : "0px";

      fullscreenContent.style.cssText = `
                position: fixed !important;
                top: ${safeAreaTop} !important;
                left: ${safeAreaLeft} !important;
                right: ${safeAreaRight} !important;
                bottom: ${safeAreaBottom} !important;
                width: calc(100vw - ${safeAreaLeft} - ${safeAreaRight}) !important;
                height: calc(100vh - ${safeAreaTop} - ${safeAreaBottom}) !important;
                z-index: 9999 !important;
                background: white !important;
                padding: 60px 20px 20px 20px !important;
                overflow: auto !important;
                -webkit-overflow-scrolling: touch !important;
                transform: translateY(0) !important;
                -webkit-transform: translateY(0) !important;
                opacity: 1 !important;
                visibility: visible !important;
                -webkit-backface-visibility: hidden !important;
            `;

      document.body.appendChild(fullscreenContent);
      document.body.style.overflow = "hidden";

      // Ensure close button is visible
      const closeBtn = fullscreenContent.querySelector(".tirtonic-nav-close");
      if (closeBtn) {
        closeBtn.style.display = "flex !important";
        closeBtn.addEventListener("click", () => this.close());
      }

      // Show correct section based on view
      this.showMobileView(fullscreenContent, view);

      // Add event listeners for mobile
      this.bindMobileEvents(fullscreenContent);

      // Initialize search functionality for mobile if search view
      if (view === "search") {
        const searchInput = fullscreenContent.querySelector(
          "#search--revamp input, #tirtonic-search-input"
        );
        if (searchInput) {
          this.setupMobileSearchInput(searchInput, fullscreenContent);
        }
      }

      // Prevent closing when clicking inside mobile content
      fullscreenContent.addEventListener("click", (e) => {
        e.stopPropagation();
      });
    } else {
      document.body.style.overflow = "hidden";
    }

    // Focus management
    if (view === "menu") {
      const firstLink = this.nav.querySelector(
        ".quick--access-content_link1 a"
      );
      if (firstLink) {
        setTimeout(() => firstLink.focus(), 300);
      }
    } else if (view === "search") {
      const searchInput = this.nav.querySelector("#tirtonic-search-input");
      if (searchInput) {
        setTimeout(() => searchInput.focus(), 300);
      }
    }

    // Analytics tracking disabled
  }

  open() {
    this.openNavbar("menu");
  }

  showView(view) {
    const menuSection = this.nav.querySelector(".wrapper-menu");
    const searchSection = this.nav.querySelector(".tirtonic-search-section");
    const cartSection = this.nav.querySelector(".wrapper-cart");

    // Force hide all sections immediately
    if (menuSection) {
      menuSection.style.display = "none";
      menuSection.style.opacity = "0";
    }
    if (searchSection) {
      searchSection.style.display = "none";
      searchSection.style.opacity = "0";
    }
    if (cartSection) {
      cartSection.style.display = "none";
      cartSection.style.opacity = "0";
    }

    // Remove all state classes
    this.nav.classList.remove("opened-search", "opened-cart", "opened-menu");

    // Small delay to ensure clean transition
    setTimeout(() => {
      // Show requested view for desktop
      if (window.innerWidth > 768) {
        switch (view) {
          case "search":
            if (searchSection) {
              searchSection.style.display = "block";
              searchSection.style.opacity = "1";
            }
            this.nav.classList.add("opened-search");
            this.setupSearchInput();
            this.loadNewestProducts();
            break;
          case "cart":
            if (cartSection) {
              cartSection.style.display = "block";
              cartSection.style.opacity = "1";
            }
            this.nav.classList.add("opened-cart");
            this.loadWishlistContents();
            break;
          default: // menu
            if (menuSection) {
              menuSection.style.display = "block";
              menuSection.style.opacity = "1";
            }
            this.nav.classList.add("opened-menu");
            break;
        }

        // Focus search input if opening search
        if (view === "search") {
          setTimeout(() => {
            const searchInput = this.nav.querySelector("#search--revamp input");
            if (searchInput) searchInput.focus();
          }, 300);
        }
      }
    }, 50);

    this.currentView = view;
  }

  close() {
    this.nav.classList.remove("tirtonic-nav-opened");
    this.nav.classList.remove("opened--clicked");
    this.nav.classList.remove("opened-search");
    this.nav.classList.remove("opened-cart");
    this.nav.classList.remove("opened-menu");
    this.isOpen = false;
    this.currentView = "menu";

    // Remove mobile fullscreen element
    const mobileContent = document.getElementById("mobile-fullscreen-content");
    if (mobileContent) {
      mobileContent.style.pointerEvents = "none";
      mobileContent.remove();
    }

    // Restore body scroll
    document.body.style.overflow = "";
    document.body.style.pointerEvents = "auto";

    // Analytics tracking disabled
  }

  show() {
    if (this.nav && this.nav.classList) {
      this.nav.classList.remove("nav-hidden");
    }
  }

  hide() {
    if (this.nav && this.nav.classList) {
      this.nav.classList.add("nav-hidden");
    }
  }

  showWithTransition() {
    if (
      this.nav &&
      this.nav.classList &&
      this.nav.classList.contains("nav-hidden")
    ) {
      this.nav.style.transition =
        "transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease";
      this.nav.classList.remove("nav-hidden");
    }
  }

  hideWithTransition() {
    if (
      this.nav &&
      this.nav.classList &&
      !this.nav.classList.contains("nav-hidden")
    ) {
      this.nav.style.transition =
        "transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease";
      this.nav.classList.add("nav-hidden");
    }
  }

  setupSearchInput() {
    const searchInput = this.nav.querySelector(
      "#tirtonic-search-input, #search--revamp input"
    );
    const closeIcon = this.nav.querySelector(".close--icon");

    if (searchInput && !searchInput.hasAttribute("data-listener")) {
      searchInput.setAttribute("data-listener", "true");

      let searchTimeout;
      searchInput.addEventListener("input", (e) => {
        const searchTerm = e.target.value.trim();

        // Show/hide close icon
        if (closeIcon) {
          closeIcon.style.display = e.target.value.length > 0 ? "flex" : "none";
        }

        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          if (searchTerm.length >= 2) {
            this.performProductSearch(searchTerm);
          } else {
            this.loadNewestProducts();
          }
        }, 300);
      });

      // Close icon functionality
      if (closeIcon && !closeIcon.hasAttribute("data-listener")) {
        closeIcon.setAttribute("data-listener", "true");
        closeIcon.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          searchInput.value = "";
          closeIcon.style.display = "none";
          this.loadNewestProducts();
        });
      }
    }
  }

  loadWishlistContents() {
    if (typeof tirtonicAjax === "undefined") return;

    const cartContainer = this.nav.querySelector(".wrapper-cart .Drawer__Main");
    if (!cartContainer) return;

    cartContainer.innerHTML = '<div class="cart-loading">Loading cart...</div>';

    fetch(tirtonicAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "tirtonic_get_cart_contents",
        nonce: tirtonicAjax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.data.length > 0) {
          cartContainer.innerHTML = data.data
            .map(
              (item) => `
                    <div class="cart-item" data-key="${item.key}">
                        <div class="cart-item-content">
                            ${
                              item.image
                                ? `<img src="${item.image}" alt="${item.title}" class="cart-item-image">`
                                : ""
                            }
                            <div class="cart-item-info">
                                <h4>${item.title}</h4>
                                <div class="quantity-controls">
                                    <button class="qty-minus">-</button>
                                    <span class="quantity">${
                                      item.quantity
                                    }</span>
                                    <button class="qty-plus">+</button>
                                </div>
                                <span class="price">${item.price}</span>
                            </div>
                        </div>
                    </div>
                `
            )
            .join("");

          // Add checkout button at the end
          const settings = JSON.parse(
            this.nav.getAttribute("data-settings") || "{}"
          );
          const buttonText = settings.checkout_button_text || "Checkout";
          const buttonAction = settings.checkout_button_action || "checkout";

          const checkoutSection = document.createElement("div");
          checkoutSection.className = "wishlist-checkout-section";
          checkoutSection.innerHTML = `<button class="wishlist-checkout-btn">${buttonText}</button>`;
          cartContainer.appendChild(checkoutSection);

          // Add click handler for button
          const checkoutBtn = checkoutSection.querySelector(
            ".wishlist-checkout-btn"
          );
          checkoutBtn.addEventListener("click", () => {
            this.handleCheckoutAction(buttonAction, data.data, settings);
          });

          // Add quantity change handlers
          this.setupWishlistQuantityControls();
        } else {
          cartContainer.innerHTML =
            '<div class="empty-cart">Your cart is empty</div>';
        }
      })
      .catch((error) => {
        cartContainer.innerHTML =
          '<div class="cart-error">Error loading cart</div>';
      });
  }

  setupWishlistQuantityControls() {
    // Remove existing listeners first
    this.nav.querySelectorAll(".qty-plus, .qty-minus").forEach((btn) => {
      btn.replaceWith(btn.cloneNode(true));
    });

    // Add new listeners
    this.nav.querySelectorAll(".qty-plus, .qty-minus").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const cartItem = e.target.closest(".cart-item");
        const key = cartItem.getAttribute("data-key");
        const isPlus = e.target.classList.contains("qty-plus");
        const quantitySpan = cartItem.querySelector(".quantity");
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
    if (typeof tirtonicAjax === "undefined") return;

    fetch(tirtonicAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "tirtonic_update_cart_quantity",
        cart_key: key,
        quantity: quantity,
        nonce: tirtonicAjax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.loadWishlistContents();
          // Update cart count in navbar
          const cartCount = this.nav.querySelector(".cart-count");
          if (cartCount) {
            cartCount.textContent = data.data.cart_count || 0;
          }
        }
      })
      .catch((error) => {
        // Handle error silently
      });
  }

  handleCheckoutAction(action, cartItems, settings) {
    switch (action) {
      case "checkout":
        window.location.href = tirtonicAjax.checkout_url || "/checkout";
        break;
      case "cart":
        window.location.href = tirtonicAjax.cart_url || "/cart";
        break;
      case "whatsapp":
        this.generateWishlistWhatsApp(cartItems);
        break;
      case "custom_url":
        const customUrl = settings.checkout_custom_url;
        if (customUrl && customUrl.trim() !== "") {
          window.open(customUrl, "_blank");
        }
        break;
    }
  }

  generateWishlistWhatsApp(cartItems) {
    let message = "Saya tertarik membeli:\n";

    cartItems.forEach((item) => {
      message += `${item.quantity}x ${item.title}\n`;
    });

    const whatsappNumber = "6281234567890"; // Replace with actual number
    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, "_blank");
  }

  loadNewestProducts() {
    if (typeof tirtonicAjax === "undefined") return;

    const newestWrapper = this.nav.querySelector(".newest--product-wrapper");
    const searchedWrapper = this.nav.querySelector(".newest--product-searched");

    if (newestWrapper) newestWrapper.style.display = "block";
    if (searchedWrapper) searchedWrapper.style.display = "none";

    // Update title text
    const settings = JSON.parse(this.nav.getAttribute("data-settings") || "{}");
    const titleText = settings.search_title_text || "Newest product";
    const titleElement = newestWrapper
      ? newestWrapper.querySelector("h3")
      : null;
    if (titleElement) {
      titleElement.textContent = titleText;
    }

    fetch(tirtonicAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "tirtonic_get_newest_products",
        nonce: tirtonicAjax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.renderNewestProducts(data.data);
        }
      })
      .catch((error) => {
        // Handle error silently
      });
  }

  renderNewestProducts(products) {
    const productList = this.nav.querySelector(".newest--product-list");
    if (productList) {
      if (products.length > 0) {
        productList.innerHTML = `
                    <div class="product-grid">
                        ${products
                          .map(
                            (product) => `
                            <div class="product-card">
                                <a href="${product.url}">
                                    ${
                                      product.image
                                        ? `<img src="${product.image}" alt="${product.title}" class="product-image">`
                                        : '<div class="product-image-placeholder"></div>'
                                    }
                                    <div class="product-info">
                                        <h4 class="product-title">${
                                          product.title
                                        }</h4>
                                        <span class="product-price">${
                                          product.price
                                        }</span>
                                    </div>
                                </a>
                            </div>
                        `
                          )
                          .join("")}
                    </div>
                `;
      } else {
        productList.innerHTML =
          '<p style="text-align:center;color:#666;">No products available</p>';
      }
    }
  }

  performProductSearch(searchTerm) {
    if (typeof tirtonicAjax === "undefined") {
      console.error(
        "tirtonicAjax not defined - AJAX functionality unavailable"
      );
      return;
    }

    const searchResults = this.nav.querySelector(".searched--product-list");
    const newestWrapper = this.nav.querySelector(".newest--product-wrapper");
    const searchedWrapper = this.nav.querySelector(".newest--product-searched");

    if (searchResults) {
      searchResults.innerHTML =
        '<p style="text-align:center;color:#666;padding:15px;">Searching...</p>';
    }

    if (newestWrapper) newestWrapper.style.display = "none";
    if (searchedWrapper) searchedWrapper.style.display = "block";

    fetch(tirtonicAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "tirtonic_search_products",
        search_term: searchTerm,
        nonce: tirtonicAjax.nonce,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.data && data.data.length > 0) {
          this.renderSearchResults(data.data);
        } else {
          if (searchResults) {
            searchResults.innerHTML =
              '<p style="text-align:center;color:#666;padding:15px;">No products found for "' +
              searchTerm +
              '"</p>';
          }
          // Load newest products as fallback
          this.loadNewestProducts();
        }
      })
      .catch((error) => {
        console.error("Desktop search error:", error);
        if (searchResults) {
          searchResults.innerHTML =
            '<p style="text-align:center;color:#e74c3c;padding:15px;">Search temporarily unavailable. Please try again.</p>';
        }
        // Load newest products as fallback
        setTimeout(() => {
          this.loadNewestProducts();
        }, 2000);
      });
  }

  renderSearchResults(products) {
    const searchResults = this.nav.querySelector(".searched--product-list");
    if (searchResults) {
      searchResults.innerHTML = `
                <div class="product-grid">
                    ${products
                      .map(
                        (product) => `
                        <div class="product-card">
                            <a href="${product.url}">
                                ${
                                  product.image
                                    ? `<img src="${product.image}" alt="${product.title}" class="product-image">`
                                    : '<div class="product-image-placeholder"></div>'
                                }
                                <div class="product-info">
                                    <h4 class="product-title">${
                                      product.title
                                    }</h4>
                                    <span class="product-price">${
                                      product.price
                                    }</span>
                                </div>
                            </a>
                        </div>
                    `
                      )
                      .join("")}
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

  showMobileView(fullscreenContent, view) {
    const menu = fullscreenContent.querySelector(".wrapper-menu");
    const search = fullscreenContent.querySelector(".tirtonic-search-section");
    const cart = fullscreenContent.querySelector(
      ".wrapper-cart, .quick--access-content_wrapper"
    );

    // Hide all sections first
    if (menu) {
      menu.style.display = "none";
      menu.style.opacity = "0";
    }
    if (search) {
      search.style.display = "none";
      search.style.opacity = "0";
    }
    if (cart) {
      cart.style.display = "none";
      cart.style.opacity = "0";
    }

    // Show requested section with proper visibility
    setTimeout(() => {
      switch (view) {
        case "search":
          if (search) {
            search.style.display = "block";
            search.style.opacity = "1";
            // Load newest products for mobile search
            this.loadMobileNewestProducts(fullscreenContent);
            // Focus search input
            const searchInput = fullscreenContent.querySelector(
              "#search--revamp input, #tirtonic-search-input"
            );
            if (searchInput) {
              setTimeout(() => searchInput.focus(), 100);
            }
          }
          break;
        case "cart":
          if (cart) {
            cart.style.display = "block";
            cart.style.opacity = "1";
            this.loadWishlistContents();
          }
          break;
        default:
          if (menu) {
            menu.style.display = "block";
            menu.style.opacity = "1";
          }
          break;
      }
    }, 50);
  }

  bindMobileEvents(fullscreenContent) {
    // Search functionality
    const searchInput = fullscreenContent.querySelector(
      "#search--revamp input, #tirtonic-search-input"
    );
    const closeIcon = fullscreenContent.querySelector(".close--icon");

    if (searchInput && !searchInput.hasAttribute("data-mobile-bound")) {
      searchInput.setAttribute("data-mobile-bound", "true");
      this.setupMobileSearchInput(searchInput, fullscreenContent);

      if (closeIcon) {
        closeIcon.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          searchInput.value = "";
          closeIcon.style.display = "none";
          this.loadMobileNewestProducts(fullscreenContent);
        });
      }
    }

    // Quantity controls for cart items
    fullscreenContent
      .querySelectorAll(".qty-plus, .qty-minus")
      .forEach((btn) => {
        if (!btn.hasAttribute("data-mobile-bound")) {
          btn.setAttribute("data-mobile-bound", "true");
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isPlus = e.target.classList.contains("qty-plus");
            const quantitySpan =
              e.target.parentElement.querySelector(".quantity");
            const currentQty = parseInt(quantitySpan.textContent);
            const newQty = isPlus
              ? currentQty + 1
              : Math.max(1, currentQty - 1);
            quantitySpan.textContent = newQty;
            this.updateCartCount();
          });
        }
      });

    // Checkout button
    const checkoutBtn = fullscreenContent.querySelector(
      ".wishlist-checkout-btn"
    );
    if (checkoutBtn && !checkoutBtn.hasAttribute("data-mobile-bound")) {
      checkoutBtn.setAttribute("data-mobile-bound", "true");
      checkoutBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        const settings = JSON.parse(
          this.nav.getAttribute("data-settings") || "{}"
        );
        const action = settings.checkout_button_action || "checkout";
        this.handleCheckoutAction(action, [], settings);
      });
    }
  }

  setupMobileSearchInput(searchInput, container) {
    if (!searchInput || searchInput.hasAttribute("data-mobile-listener"))
      return;

    searchInput.setAttribute("data-mobile-listener", "true");
    let searchTimeout;

    searchInput.addEventListener("input", (e) => {
      e.stopPropagation();
      const searchTerm = e.target.value.trim();
      const closeIcon = container.querySelector(".close--icon");

      if (closeIcon) {
        closeIcon.style.display = e.target.value.length > 0 ? "flex" : "none";
      }

      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        if (searchTerm.length >= 2) {
          this.performMobileProductSearch(searchTerm, container);
        } else {
          this.loadMobileNewestProducts(container);
        }
      }, 300);
    });

    // Load initial newest products for mobile
    this.loadMobileNewestProducts(container);
  }

  performMobileProductSearch(searchTerm, container) {
    if (typeof tirtonicAjax === "undefined") {
      console.error(
        "tirtonicAjax not defined - AJAX functionality unavailable"
      );
      return;
    }

    const searchResults = container.querySelector(".searched--product-list");
    const newestWrapper = container.querySelector(".newest--product-wrapper");
    const searchedWrapper = container.querySelector(
      ".newest--product-searched"
    );

    if (searchResults) searchResults.innerHTML = "<p>Searching...</p>";
    if (newestWrapper) newestWrapper.style.display = "none";
    if (searchedWrapper) searchedWrapper.style.display = "block";

    fetch(tirtonicAjax.ajaxurl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "tirtonic_search_products",
        search_term: searchTerm,
        nonce: tirtonicAjax.nonce,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success && data.data && data.data.length > 0) {
          this.renderMobileSearchResults(data.data, container);
        } else {
          if (searchResults) {
            searchResults.innerHTML =
              '<p style="text-align:center;color:#666;padding:20px;">No products found for "' +
              searchTerm +
              '"</p>';
          }
          // Load newest products as fallback
          this.loadMobileNewestProducts(container);
        }
      })
      .catch((error) => {
        console.error("Mobile search error:", error);
        if (searchResults) {
          searchResults.innerHTML =
            '<p style="text-align:center;color:#e74c3c;padding:20px;">Search temporarily unavailable. Please try again.</p>';
        }
        // Load newest products as fallback
        setTimeout(() => {
          this.loadMobileNewestProducts(container);
        }, 2000);
      });
  }

  loadMobileNewestProducts(container) {
    const newestWrapper = container.querySelector(".newest--product-wrapper");
    const searchedWrapper = container.querySelector(
      ".newest--product-searched"
    );

    if (newestWrapper) newestWrapper.style.display = "block";
    if (searchedWrapper) searchedWrapper.style.display = "none";

    // Load newest products via AJAX for mobile
    if (typeof tirtonicAjax !== "undefined") {
      const productList = container.querySelector(".newest--product-list");
      if (productList) {
        productList.innerHTML =
          '<p style="text-align:center;color:#666;">Loading products...</p>';

        fetch(tirtonicAjax.ajaxurl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "tirtonic_get_newest_products",
            nonce: tirtonicAjax.nonce,
          }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success && data.data) {
              this.renderMobileNewestProducts(data.data, container);
            } else {
              productList.innerHTML =
                '<p style="text-align:center;color:#666;">No products available</p>';
            }
          })
          .catch((error) => {
            console.error("Error loading newest products:", error);
            productList.innerHTML =
              '<p style="text-align:center;color:#666;">Unable to load products</p>';
          });
      }
    }
  }

  renderMobileSearchResults(products, container) {
    const searchResults = container.querySelector(".searched--product-list");
    if (searchResults) {
      searchResults.innerHTML = `
                <div class="product-grid">
                    ${products
                      .map(
                        (product) => `
                        <div class="product-card">
                            <a href="${product.url}">
                                ${
                                  product.image
                                    ? `<img src="${product.image}" alt="${product.title}" class="product-image">`
                                    : '<div class="product-image-placeholder"></div>'
                                }
                                <div class="product-info">
                                    <h4 class="product-title">${
                                      product.title
                                    }</h4>
                                    <span class="product-price">${
                                      product.price
                                    }</span>
                                </div>
                            </a>
                        </div>
                    `
                      )
                      .join("")}
                </div>
            `;
    }
  }

  renderMobileNewestProducts(products, container) {
    const productList = container.querySelector(".newest--product-list");
    if (productList) {
      if (products && products.length > 0) {
        productList.innerHTML = `
                    <div class="product-grid">
                        ${products
                          .map(
                            (product) => `
                            <div class="product-card">
                                <a href="${product.url}">
                                    ${
                                      product.image
                                        ? `<img src="${product.image}" alt="${product.title}" class="product-image">`
                                        : '<div class="product-image-placeholder"></div>'
                                    }
                                    <div class="product-info">
                                        <h4 class="product-title">${
                                          product.title
                                        }</h4>
                                        <span class="product-price">${
                                          product.price
                                        }</span>
                                    </div>
                                </a>
                            </div>
                        `
                          )
                          .join("")}
                    </div>
                `;
      } else {
        productList.innerHTML =
          '<p style="text-align:center;color:#666;">No products available</p>';
      }
    }
  }

  updateCartCount() {
    const cartCountEl = this.nav.querySelector(".cart-count");
    if (cartCountEl) {
      const quantities = Array.from(this.nav.querySelectorAll(".quantity")).map(
        (el) => parseInt(el.textContent) || 0
      );
      const total = quantities.reduce((sum, qty) => sum + qty, 0);
      cartCountEl.textContent = total;
    }
  }

  handleCustomIcon(iconElement) {
    const action = iconElement.getAttribute("data-action");
    const url = iconElement.getAttribute("data-url");

    switch (action) {
      case "search":
        this.handleSearchAction();
        break;
      case "cart":
        this.handleCartAction();
        break;
      case "url":
      default:
        if (url && url.trim() !== "") {
          window.open(url, "_blank");
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
    window.removeEventListener("scroll", this.handleScroll);
    document.removeEventListener("click", this.handleOutsideClick);
    document.removeEventListener("keydown", this.handleKeydown);
  }

  updateMenu(menuItems) {
    const menuContainer = this.nav.querySelector(".tirtonic-nav-menu");
    if (menuContainer && Array.isArray(menuItems)) {
      menuContainer.innerHTML = menuItems
        .map((item) => `<a href="${item.url}">${item.title}</a>`)
        .join("");
    }
  }

  setTheme(theme) {
    this.nav.classList.remove("theme-light", "theme-dark");
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
    if (typeof window !== "undefined") {
      window.tirtonicNav = tirtonicNav;
      window.TirtonicFloatingNav = TirtonicFloatingNav;
    }
  }, 100);
}

// Update cart count helper
function updateCartCount() {
  if (window.tirtonicNav) {
    window.tirtonicNav.updateCartCount();
  }
}

// Make updateCartCount globally available
if (typeof window !== "undefined") {
  window.updateCartCount = updateCartCount;
}

// Safari-compatible initialization
function safariCompatibleInit() {
  const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

  if (isSafari || isIOS) {
    setTimeout(initTirtonicNav, 200);
  } else {
    initTirtonicNav();
  }
}

// Initialize with Safari compatibility
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", safariCompatibleInit);
} else if (
  document.readyState === "interactive" ||
  document.readyState === "complete"
) {
  safariCompatibleInit();
} else {
  window.addEventListener("load", safariCompatibleInit);
}
