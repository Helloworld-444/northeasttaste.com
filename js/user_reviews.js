// Modal handling
const modal = document.getElementById('reviewModal');
const closeBtn = document.querySelector('.close');
const reviewForm = document.getElementById('reviewForm');

function editReview(reviewId) {
    // Fetch review details
    fetch(`/fooddelivery/php/controllers/review_controller.php?id=${reviewId}`)
        .then(response => response.json())
        .then(review => {
            document.getElementById('reviewId').value = review.review_id;
            document.querySelector(`input[name="rating"][value="${review.rating}"]`).checked = true;
            document.getElementById('comment').value = review.comment;
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load review details');
        });
}

function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) return;

    fetch('/fooddelivery/php/controllers/review_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            review_id: reviewId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete review');
    });
}

// Modal close handlers
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

// Review form submission
reviewForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'update');

    fetch('/fooddelivery/php/controllers/review_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update review');
    });
});

// Star rating handling
const starLabels = document.querySelectorAll('.star-rating label');
starLabels.forEach(label => {
    label.addEventListener('mouseover', function() {
        this.parentElement.classList.add('hover');
    });
    label.addEventListener('mouseout', function() {
        this.parentElement.classList.remove('hover');
    });
});

// Add this function to handle voting
function voteReview(reviewId, isHelpful) {
    fetch('/fooddelivery/php/controllers/vote_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId,
            is_helpful: isHelpful
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            // Update vote counts in the UI
            const reviewCard = document.querySelector(`[data-review-id="${reviewId}"]`);
            const helpfulBtn = reviewCard.querySelector('.btn-vote:first-child');
            const unhelpfulBtn = reviewCard.querySelector('.btn-vote:last-child');
            
            helpfulBtn.querySelector('.vote-count').textContent = data.helpful_count;
            unhelpfulBtn.querySelector('.vote-count').textContent = data.unhelpful_count;
            
            // Toggle active states
            helpfulBtn.classList.toggle('active', data.user_vote === true);
            unhelpfulBtn.classList.toggle('active', data.user_vote === false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to process vote');
    });
}

// Add this function to handle reporting
function reportReview(reviewId) {
    const reason = prompt('Please select a reason for reporting:\n1. Inappropriate\n2. Spam\n3. Offensive\n4. Other');
    if (!reason) return;

    const reasonMap = {
        '1': 'inappropriate',
        '2': 'spam',
        '3': 'offensive',
        '4': 'other'
    };

    const selectedReason = reasonMap[reason];
    if (!selectedReason) {
        alert('Invalid reason selected');
        return;
    }

    let details = '';
    if (selectedReason === 'other') {
        details = prompt('Please provide additional details:');
        if (!details) return;
    }

    fetch('/fooddelivery/php/controllers/report_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId,
            reason: selectedReason,
            details: details
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            alert('Review has been reported. Thank you for helping maintain our community standards.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit report');
    });
} 