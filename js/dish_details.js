// Modal handling
const modal = document.getElementById('reviewModal');
const closeBtn = document.querySelector('.close');
const reviewForm = document.getElementById('reviewForm');

function openReviewForm() {
    modal.style.display = 'block';
}

closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

// Review form submission
reviewForm?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'add');

    fetch('/fooddelivery/php/controllers/review_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to submit review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit review');
    });
});

// Add to cart functionality
function addToCart(dishId) {
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