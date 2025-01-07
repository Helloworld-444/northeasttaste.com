document.addEventListener('DOMContentLoaded', function() {
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', handleCategorySubmit);
    }
});

function showAddCategoryForm() {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    const title = modal.querySelector('h3');
    
    title.textContent = 'Add New Category';
    form.reset();
    form.category_id.value = '';
    
    modal.style.display = 'block';
}

function editCategory(categoryId) {
    fetch(`/fooddelivery/php/controllers/admin/category_controller.php?action=get&id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const category = data.category;
                const modal = document.getElementById('categoryModal');
                const form = document.getElementById('categoryForm');
                const title = modal.querySelector('h3');

                title.textContent = 'Edit Category';
                form.category_id.value = category.category_id;
                form.name.value = category.name;
                form.description.value = category.description || '';

                modal.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load category details');
        });
}

function handleCategorySubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('action', formData.get('category_id') ? 'update' : 'add');
    
    fetch('/fooddelivery/php/controllers/admin/category_controller.php', {
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
            alert(data.error || 'Failed to save category');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save category');
    });
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('category_id', categoryId);

        fetch('/fooddelivery/php/controllers/admin/category_controller.php', {
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
                alert(data.error || 'Failed to delete category');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete category');
        });
    }
}

function closeModal() {
    const modal = document.getElementById('categoryModal');
    modal.style.display = 'none';
} 