document.addEventListener('DOMContentLoaded', function() {
    const cartDropdown = document.querySelector('.cart-dropdown');
    const cartIconBtn = document.querySelector('.cart-icon-btn');
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const cartCount = document.querySelector('.cart-count');
    
    // Toggle cart dropdown
    cartIconBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        cartDropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!cartDropdown.contains(e.target)) {
            cartDropdown.classList.remove('active');
        }
    });

    // Add to cart functionality
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemCard = this.closest('.item-card');
            const itemId = itemCard.dataset.itemId;

            // Add item to cart via AJAX
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay(data.cart_items, data.total);
                    updateCartCount(data.total_items);
                    showNotification('Item added to cart!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding item to cart', true);
            });
        });
    });

    // Remove from cart functionality
    function removeFromCart(itemId) {
        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If cart is now empty, update display accordingly
                if (data.cart_items.length === 0) {
                    updateCartDisplay([], 0);
                    updateCartCount(0);
                } else {
                    updateCartDisplay(data.cart_items, data.total);
                    updateCartCount(data.total_items);
                }
                showNotification('Item removed from cart!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error removing item from cart', true);
        });
    }

    // Update cart count
    function updateCartCount(count) {
        cartCount.textContent = count;
    }

    // Update cart display
    function updateCartDisplay(items, total) {
        const cartContent = document.querySelector('.cart-dropdown-content');
        
        if (!items || items.length === 0) {
            cartContent.innerHTML = '<p class="empty-cart-message">Your cart is empty</p>';
            return;
        }

        let html = '<div class="cart-items-container">';
        
        items.forEach(item => {
            const imagePath = item.image_path ? '../admin dashboard/' + item.image_path : '/api/placeholder/50/50';
            html += `
                <div class="cart-dropdown-item" data-item-id="${item.id}">
                    <img src="${imagePath}" alt="${item.name}" class="cart-item-thumbnail">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>$${item.price} x ${item.quantity}</p>
                    </div>
                    <button class="remove-item-btn" data-item-id="${item.id}">&times;</button>
                </div>
            `;
        });

        html += `</div>
            <div class="cart-dropdown-footer">
                <div class="cart-total">Total: $${total.toFixed(2)}</div>
                <button class="purchase-btn">Purchase</button>
            </div>`;

        cartContent.innerHTML = html;
        
        // Reattach event listeners
        attachRemoveButtonListeners();
        attachPurchaseButtonListener();
    }

    // Show notification
    function showNotification(message, isError = false) {
        const notification = document.createElement('div');
        notification.className = `notification ${isError ? 'error' : 'success'}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Attach listeners to remove buttons
    function attachRemoveButtonListeners() {
        const removeButtons = document.querySelectorAll('.remove-item-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                removeFromCart(itemId);
            });
        });
    }

    // Attach listener to purchase button
    function attachPurchaseButtonListener() {
        const purchaseBtn = document.querySelector('.purchase-btn');
        if (purchaseBtn) {
            purchaseBtn.addEventListener('click', function() {
                fetch('process_purchase.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartDisplay([], 0);
                        updateCartCount(0);
                        showNotification('Purchase successful!');
                        
                        // Show success modal
                        const modal = document.getElementById('purchaseModal');
                        modal.classList.add('show');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error processing purchase', true);
                });
            });
        }
    }

    // Close modal button
    const modalCloseBtn = document.querySelector('.modal-close-btn');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', function() {
            document.getElementById('purchaseModal').classList.remove('show');
        });
    }

    // Initial attachment of listeners
    attachRemoveButtonListeners();
    attachPurchaseButtonListener();
});