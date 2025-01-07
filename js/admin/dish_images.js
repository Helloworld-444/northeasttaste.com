function uploadDishImage(dishId) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('dish_id', dishId);
        formData.append('image', file);

        fetch('/fooddelivery/php/controllers/admin/image_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to upload image');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to upload image');
        });
    };

    input.click();
}

function setPrimaryImage(imageId, dishId) {
    const formData = new FormData();
    formData.append('action', 'set_primary');
    formData.append('image_id', imageId);
    formData.append('dish_id', dishId);

    fetch('/fooddelivery/php/controllers/admin/image_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to set primary image');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to set primary image');
    });
}

function deleteImage(imageId, dishId) {
    if (!confirm('Are you sure you want to delete this image?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('image_id', imageId);
    formData.append('dish_id', dishId);

    fetch('/fooddelivery/php/controllers/admin/image_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete image');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete image');
    });
} 