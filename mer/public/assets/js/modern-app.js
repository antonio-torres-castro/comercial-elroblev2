// Modern E-commerce Mall JavaScript
// Funcionalidades avanzadas para carrito y UX

document.addEventListener('DOMContentLoaded', function() {
    initializeMall();
});

function initializeMall() {
    initCartModal();
    initQuantityControls();
    initStoreNavigation();
    initProductInteractions();
    initFormEnhancements();
    updateCartCount();
}

// ==================== CARRITO MODAL ====================
function initCartModal() {
    const cartToggle = document.querySelector('.cart-toggle');
    const cartModal = document.querySelector('.cart-modal');
    const cartClose = document.querySelector('.cart-close');
    
    if (cartToggle && cartModal) {
        cartToggle.addEventListener('click', openCartModal);
        
        // Cerrar modal
        if (cartClose) {
            cartClose.addEventListener('click', closeCartModal);
        }
        
        // Cerrar al hacer clic fuera
        cartModal.addEventListener('click', function(e) {
            if (e.target === cartModal) {
                closeCartModal();
            }
        });
        
        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCartModal();
            }
        });
    }
}

function openCartModal() {
    const cartModal = document.querySelector('.cart-modal');
    if (cartModal) {
        cartModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadCartData();
    }
}

function closeCartModal() {
    const cartModal = document.querySelector('.cart-modal');
    if (cartModal) {
        cartModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function loadCartData() {
    // Simular carga de datos del carrito
    // En implementación real, esto haría una petición AJAX
    const cartItems = document.querySelectorAll('.cart-table tbody tr');
    const cartSummary = document.querySelector('.cart-summary');
    
    if (cartItems.length === 0) {
        showEmptyCart();
    } else {
        populateCartModal(cartItems);
    }
}

function showEmptyCart() {
    const cartBody = document.querySelector('.cart-body');
    if (cartBody) {
        cartBody.innerHTML = `
            <div class="text-center" style="padding: 2rem;">
                <p>Tu carrito está vacío</p>
                <a href="index.php" class="btn btn-primary mt-md">Continuar comprando</a>
            </div>
        `;
    }
}

function populateCartModal(items) {
    const cartBody = document.querySelector('.cart-body');
    if (!cartBody) return;
    
    let modalContent = '';
    let totalItems = 0;
    let totalAmount = 0;
    
    items.forEach(item => {
        const name = item.querySelector('td:first-child').textContent;
        const price = parseFloat(item.querySelector('td:nth-child(2)').textContent.replace('$', ''));
        const quantity = parseInt(item.querySelector('input[type="number"]').value);
        const productId = item.querySelector('input[type="number"]').name.match(/\[(\d+)\]/)[1];
        
        totalItems += quantity;
        totalAmount += price * quantity;
        
        modalContent += `
            <div class="cart-item">
                <img src="assets/images/product-placeholder.jpg" alt="${name}" class="cart-item-image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';">
                <div class="cart-item-info">
                    <div class="cart-item-name">${name}</div>
                    <div class="cart-item-price">$${price.toFixed(2)}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="qty-btn" onclick="updateQuantity(${productId}, -1)">-</button>
                    <span class="qty-display">${quantity}</span>
                    <button class="qty-btn" onclick="updateQuantity(${productId}, 1)">+</button>
                    <button class="qty-btn" onclick="removeFromCart(${productId})" style="margin-left: 8px; color: var(--error);">×</button>
                </div>
            </div>
        `;
    });
    
    cartBody.innerHTML = modalContent;
    
    // Actualizar total
    const totalElement = document.querySelector('.summary-total');
    if (totalElement) {
        totalElement.textContent = `$${totalAmount.toFixed(2)}`;
    }
    
    updateCartCount();
}

// ==================== CONTROLES DE CANTIDAD ====================
function initQuantityControls() {
    // Controles en formulario de productos
    document.querySelectorAll('.product-form').forEach(form => {
        const qtyInput = form.querySelector('input[name="qty"]');
        const addBtn = form.querySelector('button[type="submit"]');
        
        if (qtyInput && addBtn) {
            addBtn.addEventListener('click', function(e) {
                if (parseInt(qtyInput.value) < 1) {
                    e.preventDefault();
                    qtyInput.value = 1;
                    showNotification('La cantidad mínima es 1', 'warning');
                }
            });
        }
    });
    
    // Controles en página del carrito
    document.querySelectorAll('.cart-table input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                // Agregar campo oculto para trigger del update
                const updateField = document.createElement('input');
                updateField.type = 'hidden';
                updateField.name = 'update';
                updateField.value = '1';
                form.appendChild(updateField);
                
                // Auto-submit después de un delay
                setTimeout(() => {
                    form.submit();
                }, 500);
            }
        });
    });
}

function updateQuantity(productId, change) {
    // En una implementación real, esto haría una petición AJAX
    console.log(`Updating product ${productId} quantity by ${change}`);
    showNotification('Cantidad actualizada', 'success');
    loadCartData();
}

function removeFromCart(productId) {
    // En una implementación real, esto haría una petición AJAX
    if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        console.log(`Removing product ${productId} from cart`);
        showNotification('Producto eliminado', 'success');
        loadCartData();
    }
}

// ==================== NAVEGACIÓN DE TIENDAS ====================
function initStoreNavigation() {
    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

// ==================== INTERACCIONES DE PRODUCTOS ====================
function initProductInteractions() {
    // Hover effects para tarjetas de productos
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Quick add to cart (double click)
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('dblclick', function() {
            const form = this.querySelector('.product-form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                }
            }
        });
    });
}

// ==================== MEJORAS DE FORMULARIOS ====================
function initFormEnhancements() {
    // Validación en tiempo real
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
    
    // Auto-focus en primer campo de formularios
    document.querySelectorAll('form').forEach(form => {
        const firstInput = form.querySelector('input[type="text"], input[type="email"]');
        if (firstInput && !firstInput.value) {
            setTimeout(() => firstInput.focus(), 100);
        }
    });
    
    // Confirmar acciones destructivas
    document.querySelectorAll('button[name="clear"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
                e.preventDefault();
            }
        });
    });
}

function validateEmail(input) {
    const email = input.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        input.style.borderColor = 'var(--error)';
        showNotification('Por favor ingresa un email válido', 'error');
        return false;
    } else {
        input.style.borderColor = 'var(--neutral-200)';
        return true;
    }
}

// ==================== UTILIDADES ====================
function updateCartCount() {
    // Simular conteo del carrito
    // En implementación real, esto obtendría el número real del servidor
    const cartItems = document.querySelectorAll('.cart-table tbody tr');
    const count = cartItems.length;
    
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

function showNotification(message, type = 'info') {
    // Crear notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Estilos inline
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 24px',
        borderRadius: '8px',
        color: 'white',
        fontWeight: '500',
        zIndex: '10000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        maxWidth: '300px',
        wordWrap: 'break-word'
    });
    
    // Colores según tipo
    const colors = {
        success: 'var(--success)',
        error: 'var(--error)',
        warning: 'var(--warning)',
        info: 'var(--primary-500)'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Mostrar
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// ==================== SHIPPING ====================
function initShippingMethods() {
    document.querySelectorAll('.ship-select').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                // Auto-submit para actualizar costos de envío
                const updateField = document.createElement('input');
                updateField.type = 'hidden';
                updateField.name = 'update';
                updateField.value = '1';
                form.appendChild(updateField);
                
                setTimeout(() => {
                    form.submit();
                }, 300);
            }
        });
    });
}

// ==================== COUPONS ====================
function initCouponSystem() {
    const couponInput = document.querySelector('input[name="coupon_code"]');
    const couponBtn = document.querySelector('button[onclick*="coupon"]');
    
    if (couponInput && couponBtn) {
        couponInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                couponBtn.click();
            }
        });
        
        // Validación de código de cupón en tiempo real
        couponInput.addEventListener('input', function() {
            const code = this.value.trim().toUpperCase();
            if (code.length >= 3) {
                this.style.borderColor = 'var(--success)';
            } else {
                this.style.borderColor = 'var(--neutral-200)';
            }
        });
    }
}

// ==================== RESPONSIVE HELPERS ====================
function initResponsiveFeatures() {
    // Detectar dispositivo móvil
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Deshabilitar hover effects en móvil
        document.querySelectorAll('.card').forEach(card => {
            card.style.transition = 'none';
        });
        
        // Optimizar scroll
        document.documentElement.style.scrollBehavior = 'smooth';
    }
    
    // Manejar resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            updateCartCount();
            initResponsiveFeatures();
        }, 250);
    });
}

// ==================== ACCESSIBILITY ====================
function initAccessibility() {
    // Navegación por teclado
    document.addEventListener('keydown', function(e) {
        // Alt + C para abrir carrito
        if (e.altKey && e.key === 'c') {
            e.preventDefault();
            const cartToggle = document.querySelector('.cart-toggle');
            if (cartToggle) {
                cartToggle.click();
            }
        }
        
        // Alt + S para buscar (si existe input de búsqueda)
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
    
    // Focus management para modales
    document.querySelectorAll('.cart-modal').forEach(modal => {
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                trapFocus(e, modal);
            }
        });
    });
}

function trapFocus(e, container) {
    const focusableElements = container.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
    } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
    }
}

// Inicializar características adicionales
document.addEventListener('DOMContentLoaded', function() {
    initShippingMethods();
    initCouponSystem();
    initResponsiveFeatures();
    initAccessibility();
});