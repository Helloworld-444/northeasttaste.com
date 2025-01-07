function toggleReviewStatus(reviewId) {
    if (!confirm('Are you sure you want to change this review\'s status?')) return;

    fetch('/php/controllers/admin/review_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle_status',
            review_id: reviewId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update review status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update review status');
    });
}

function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) return;

    fetch('/php/controllers/admin/review_controller.php', {
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

function toggleBulkSelect(checkbox) {
    const checkboxes = document.querySelectorAll('.review-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActionButtons();
}

function updateBulkActionButtons() {
    const selectedCount = document.querySelectorAll('.review-checkbox:checked').length;
    const bulkActions = document.getElementById('bulkActions');
    bulkActions.style.display = selectedCount > 0 ? 'flex' : 'none';
    document.getElementById('selectedCount').textContent = selectedCount;
}

function executeBulkAction(action) {
    const selectedReviews = Array.from(document.querySelectorAll('.review-checkbox:checked'))
        .map(cb => parseInt(cb.value));

    if (!selectedReviews.length) return;

    const confirmMessage = action === 'delete' 
        ? 'Are you sure you want to delete these reviews? This action cannot be undone.'
        : 'Are you sure you want to change the status of these reviews?';

    if (!confirm(confirmMessage)) return;

    fetch('/php/controllers/admin/review_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'bulk_action',
            bulk_action: action,
            review_ids: selectedReviews
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to perform bulk action');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to perform bulk action');
    });
} 