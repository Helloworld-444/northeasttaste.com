document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    if (addToCartButtons) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const dishId = this.dataset.dishId;
                addToCart(dishId);
            });
        });
    }
});

function addToCart(dishId) {
    fetch('/fooddelivery/php/controllers/cart_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            dish_id: dishId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to cart!');
            updateCartCount(data.cart_count);
        } else {
            alert(data.error || 'Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add to cart');
    });
}

function updateCartCount(count) {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = count;
    }
} 