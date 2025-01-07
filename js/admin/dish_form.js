// Initialize form state on page load
document.addEventListener('DOMContentLoaded', function() {
    // Handle file input change for image preview
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF)');
                    this.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size should not exceed 5MB');
                    this.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        imageInput.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="clearImagePreview()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Form validation
    const dishForm = document.getElementById('dishForm');
    if (dishForm) {
        dishForm.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const category = document.getElementById('category').value;
            const price = document.getElementById('price').value;

            if (!name) {
                e.preventDefault();
                alert('Please enter a dish name');
                return;
            }

            if (!category) {
                e.preventDefault();
                alert('Please select a category');
                return;
            }

            if (!price || price <= 0) {
                e.preventDefault();
                alert('Please enter a valid price');
                return;
            }
        });
    }
});

function clearImagePreview() {
    const imageInput = document.getElementById('image');
    const preview = document.querySelector('.image-preview');
    
    if (imageInput) {
        imageInput.value = '';
    }
    if (preview) {
        preview.remove();
    }
}

function fetchDishDetails(dishId) {
    fetch(`/fooddelivery/php/controllers/admin/dish_controller.php?action=get&id=${dishId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            populateForm(data.dish);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load dish details');
            closeModal();
        });
}

function populateForm(dish) {
    const form = document.getElementById('dishForm');
    if (!form) return;

    form.name.value = dish.name;
    form.category.value = dish.category;
    form.price.value = dish.price;
    form.description.value = dish.description || '';
    form.available.checked = dish.available == 1;

    // Clear any existing image preview
    clearImagePreview();

    // Show current image if exists
    if (dish.image_blob && dish.image_type) {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `
            <img src="data:${dish.image_type};base64,${dish.image_blob}" 
                 alt="Current Image" 
                 style="max-width: 200px; max-height: 200px;">
            <p class="text-muted small">Current image will be replaced if you select a new one</p>
        `;
        document.getElementById('image').parentNode.appendChild(preview);
    }
} 