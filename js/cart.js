document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.querySelector('.cart-items');
    if (!cartItems) return;

    // Handle quantity buttons
    cartItems.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-quantity')) {
            const input = e.target.closest('.quantity-controls').querySelector('.quantity-input');
            let quantity = parseInt(input.value);

            if (e.target.dataset.action === 'increase') {
                quantity = Math.min(quantity + 1, 10);
            } else if (e.target.dataset.action === 'decrease') {
                quantity = Math.max(quantity - 1, 1);
            }

            input.value = quantity;
            const cartItemId = e.target.closest('.cart-item').dataset.id;
            updateCartItem(cartItemId, quantity);
        }
    });
});

function updateCartItem(cartItemId, quantity) {
    fetch('/fooddelivery/php/controllers/cart_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            cart_item_id: cartItemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update cart');
    });
}

function removeCartItem(cartItemId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    fetch('/fooddelivery/php/controllers/cart_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            cart_item_id: cartItemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to remove item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove item');
    });
}

function addToCart(dishId) {
    // Check if user is logged in
    if (!document.body.classList.contains('logged-in')) {
        window.location.href = '/fooddelivery/php/views/login.php';
        return;
    }

    fetch('/fooddelivery/php/controllers/cart_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            dish_id: dishId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item added to cart successfully!');
            // Update cart count if you have one
            if (data.cartCount) {
                updateCartCount(data.cartCount);
            }
        } else {
            alert(data.error || 'Failed to add item to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add item to cart');
    });
}

function updateCartCount(count) {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Add this function to handle order placement
function placeOrder(event) {
    event.preventDefault();  // Stop form submission
    event.stopPropagation(); // Stop event bubbling
    
    const selectedAddress = document.querySelector('input[name="address_id"]:checked');
    
    if (!selectedAddress) {
        alert('Please select a delivery address');
        return;
    }

    const formData = {
        action: 'place_order',
        address_id: selectedAddress.value
    };

    fetch('/fooddelivery/php/controllers/order_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order placed successfully!');
            window.location.href = '/fooddelivery/php/views/orders.php';
        } else {
            alert(data.error || 'Failed to place order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to place order');
    });

    return false; // Extra precaution to prevent form submission
} 