document.addEventListener('DOMContentLoaded', function() {
    // Add any user management functionality here
    const viewOrderButtons = document.querySelectorAll('.view-orders-btn');
    viewOrderButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const userId = this.dataset.userId;
            window.location.href = `/fooddelivery/php/views/admin/user_orders.php?user_id=${userId}`;
        });
    });
});

function toggleUserStatus(userId) {
    if (!confirm('Are you sure you want to change this user\'s status?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('user_id', userId);

    fetch('/fooddelivery/php/controllers/admin/user_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update user status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update user status');
    });
} 