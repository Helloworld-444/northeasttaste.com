document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all status select elements
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            updateOrderStatus(this);
        });
    });

    if (document.querySelector('.category-list') || document.querySelector('select[name="category"]')) {
        loadCategories();
    }
});

function updateOrderStatus(selectElement) {
    const orderId = selectElement.dataset.orderId;
    const newStatus = selectElement.value;
    const currentStatus = selectElement.getAttribute('data-current-status') || selectElement.value;
    
    const formData = new URLSearchParams();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);

    fetch('/fooddelivery/php/controllers/admin/order_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Order status updated successfully');
            selectElement.setAttribute('data-current-status', newStatus);
        } else {
            // Show error message and revert selection
            alert('Failed to update order status: ' + (data.error || 'Unknown error'));
            selectElement.value = currentStatus;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the order status');
        selectElement.value = currentStatus;
    });
}

function updatePaymentStatus(selectElement) {
    const orderId = selectElement.dataset.orderId;
    const newStatus = selectElement.value;

    fetch('/fooddelivery/php/controllers/admin/order_controller.php?action=update_payment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            payment_status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment status updated successfully');
        } else {
            alert(data.error || 'Failed to update payment status');
            selectElement.value = selectElement.getAttribute('data-previous-value');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update payment status');
    });
}

function viewOrderDetails(orderId) {
    window.location.href = `/fooddelivery/php/views/admin/order_details.php?id=${orderId}`;
}

// Category Management Functions
function showAddCategoryModal() {
    // Remove any existing modals
    const existingModal = document.querySelector('.modal-overlay');
    if (existingModal) {
        existingModal.remove();
    }

    // Create overlay and form
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    
    const form = document.createElement('form');
    form.className = 'admin-form';
    form.innerHTML = `
        <h3>Add New Category</h3>
        <div class="form-group">
            <label for="name">Category Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Category</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal(this)">Cancel</button>
        </div>
    `;

    form.onsubmit = function(e) {
        e.preventDefault();
        submitAddCategory(this);
    };

    modalOverlay.appendChild(form);
    document.body.appendChild(modalOverlay);
    
    requestAnimationFrame(() => {
        modalOverlay.classList.add('active');
        form.classList.add('active');
    });
}

function editCategory(categoryId) {
    alert('Categories are predefined in the system and cannot be modified. Please contact the database administrator to modify the category list.');
}

function createCategoryModal() {
    alert('Categories are predefined in the system and cannot be modified. Please contact the database administrator to modify the category list.');
}

function submitAddCategory() {
    alert('Categories are predefined in the system and cannot be modified. Please contact the database administrator to modify the category list.');
    return false;
}

function submitCategoryEdit() {
    alert('Categories are predefined in the system and cannot be modified. Please contact the database administrator to modify the category list.');
    return false;
}

function deleteCategory() {
    alert('Categories are predefined in the system and cannot be modified. Please contact the database administrator to modify the category list.');
}

// Dish Management Functions
function showAddDishModal() {
    const modal = document.getElementById('dishModal');
    if (!modal) {
        console.error('Dish modal not found');
        return;
    }

    // Reset the form
    document.getElementById('dishForm').reset();
    document.getElementById('modalTitle').textContent = 'Add New Dish';
    document.getElementById('dishForm').elements['action'].value = 'add';
    
    // Show the modal
    modal.style.display = 'block';
}

function editDish(dish) {
    const modal = document.getElementById('dishModal');
    if (!modal) {
        console.error('Dish modal not found');
        return;
    }

    // Set form values
    document.getElementById('modalTitle').textContent = 'Edit Dish';
    document.getElementById('dish_id').value = dish.dish_id;
    document.getElementById('name').value = dish.name;
    document.getElementById('category').value = dish.category;
    document.getElementById('price').value = dish.price;
    document.getElementById('description').value = dish.description || '';
    document.getElementById('available').checked = dish.available == 1;
    document.getElementById('dishForm').elements['action'].value = 'edit';
    
    // Show the modal
    modal.style.display = 'block';
}

function deleteDish(dishId) {
    if (confirm('Are you sure you want to delete this dish?')) {
        window.location.href = `/fooddelivery/php/controllers/admin/dish_controller.php?action=delete&id=${dishId}`;
    }
}

function closeModal(element) {
    const modal = element.closest('.modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper Functions
function getCategoryOptions(selectedId = null) {
    const categoriesDiv = document.getElementById('categoriesData');
    if (!categoriesDiv) {
        console.error('Categories data div not found');
        return '';
    }
    
    const categories = categoriesDiv.getElementsByTagName('div');
    let options = '';
    
    // Add a default "Select Category" option
    options += '<option value="">Select Category</option>';
    
    // Add all category options
    Array.from(categories).forEach(cat => {
        const id = cat.dataset.categoryId;
        const name = cat.dataset.categoryName;
        if (id && name) {
            options += `<option value="${escapeHtml(id)}" ${id === selectedId ? 'selected' : ''}>${escapeHtml(name)}</option>`;
        }
    });
    
    return options;
}

function loadCategories() {
    const categoriesDiv = document.getElementById('categoriesData');
    if (!categoriesDiv) {
        console.error('Categories data div not found');
        return;
    }

    const categories = Array.from(categoriesDiv.getElementsByTagName('div')).map(div => ({
        id: div.dataset.categoryId,
        name: div.dataset.categoryName
    })).filter(cat => cat.id && cat.name);

    // Update all category dropdowns
    const categoryDropdowns = document.querySelectorAll('select[name="category"]');
    categoryDropdowns.forEach(dropdown => {
        const currentValue = dropdown.value;
        dropdown.innerHTML = '<option value="">Select Category</option>' + 
            categories.map(category => `
                <option value="${escapeHtml(category.id)}" ${currentValue === category.id ? 'selected' : ''}>
                    ${escapeHtml(category.name)}
                </option>
            `).join('');
    });
}

// Call loadCategories when the page loads if we're on a page with category dropdowns
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('select[name="category"]')) {
        loadCategories();
    }
}); 