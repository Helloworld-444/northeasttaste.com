function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) return;

    fetch('/fooddelivery/php/controllers/order_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancel_order',
            order_id: orderId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to cancel order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to cancel order');
    });
} 