document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    const dishForm = document.getElementById('dishForm');
    if (dishForm) {
        dishForm.addEventListener('submit', handleDishSubmit);
    }

    // Add New Dish button
    const addDishBtn = document.getElementById('addDishBtn');
    if (addDishBtn) {
        addDishBtn.addEventListener('click', () => showDishModal());
    }

    // Close modal buttons
    const closeButtons = document.querySelectorAll('.close, .btn-secondary');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => closeModal());
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('dishModal');
        if (event.target === modal) {
            closeModal();
        }
    });
});

function showDishModal(dishId = null) {
    const modal = document.getElementById('dishModal');
    const form = document.getElementById('dishForm');
    const title = document.getElementById('modalTitle');
    
    // Reset form
    form.reset();
    
    if (dishId) {
        // Edit mode
        title.textContent = 'Edit Dish';
        form.elements['action'].value = 'edit';
        form.dish_id.value = dishId;
        
        // Fetch and populate dish data
        fetchDishDetails(dishId);
    } else {
        // Add mode
        title.textContent = 'Add New Dish';
        form.elements['action'].value = 'add';
        form.dish_id.value = '';
    }
    
    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('dishModal');
    if (modal) {
        modal.style.display = 'none';
        // Reset form
        const form = document.getElementById('dishForm');
        if (form) {
            form.reset();
        }
    }
}

function handleDishSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Show loading indicator
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    fetch('/fooddelivery/php/controllers/admin/dish_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        throw new Error('Invalid response format. Expected JSON.');
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.error || 'Failed to save dish');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save dish: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function editDish(dish) {
    const modal = document.getElementById('dishModal');
    const form = document.getElementById('dishForm');
    
    document.getElementById('modalTitle').textContent = 'Edit Dish';
    form.elements['dish_id'].value = dish.dish_id;
    form.elements['name'].value = dish.name;
    form.elements['category'].value = dish.category;
    form.elements['price'].value = dish.price;
    form.elements['description'].value = dish.description || '';
    form.elements['available'].checked = dish.available == 1;
    form.elements['action'].value = 'edit';
    
    modal.style.display = 'block';
}

function deleteDish(dishId) {
    if (!confirm('Are you sure you want to delete this dish?')) {
        return;
    }

    fetch('/fooddelivery/php/controllers/admin/dish_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&dish_id=${dishId}`
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        throw new Error('Invalid response format. Expected JSON.');
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.error || 'Failed to delete dish');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message);
    });
}

function toggleAvailability(dishId) {
    if (!confirm('Are you sure you want to change the availability of this dish?')) {
        return;
    }

    fetch('/fooddelivery/php/controllers/admin/dish_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_availability&dish_id=${dishId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            throw new Error(data.error || 'Failed to update dish availability');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message);
    });
} 