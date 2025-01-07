function showEditProfile() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function showChangePassword() {
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function hideModals() {
    document.getElementById('editProfileModal').style.display = 'none';
    document.getElementById('changePasswordModal').style.display = 'none';
}

function updateProfile(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('/php/controllers/user_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_profile',
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            phone: formData.get('phone')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update profile');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update profile');
    });

    return false;
}

function updatePassword(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('New passwords do not match');
        return false;
    }

    fetch('/php/controllers/user_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_password',
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password updated successfully');
            hideModals();
            form.reset();
        } else {
            alert(data.error || 'Failed to update password');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update password');
    });

    return false;
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        hideModals();
    }
}

// Profile form handling
function editProfile() {
    const profileForm = document.getElementById('profileForm');
    const profileDetails = document.getElementById('profileDetails');
    
    if (profileForm.style.display === 'none') {
        profileForm.style.display = 'grid';
        profileDetails.style.display = 'none';
    } else {
        profileForm.style.display = 'none';
        profileDetails.style.display = 'block';
    }
}

document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        first_name: document.getElementById('firstName').value,
        last_name: document.getElementById('lastName').value,
        phone: document.getElementById('phone').value
    };

    fetch('/fooddelivery/php/controllers/profile_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_profile',
            data: formData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully');
            location.reload();
        } else {
            alert(data.error || 'Failed to update profile');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update profile');
    });
});

// Address management
function toggleAddressForm() {
    const form = document.getElementById('addressForm');
    if (form) {
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
}

function editAddress(addressId) {
    // First fetch the address details
    fetch(`/fooddelivery/php/controllers/profile_controller.php?action=get_address&address_id=${addressId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(address => {
            if (address.error) {
                alert(address.error);
                return;
            }
            
            const form = document.getElementById('addressForm');
            if (!form) {
                throw new Error('Address form not found');
            }

            // Update form title and action
            form.querySelector('h3').textContent = 'Edit Address';
            form.querySelector('form').action = '/fooddelivery/php/controllers/address.php';
            form.querySelector('input[name="action"]').value = 'edit';
            
            // Add address ID for editing
            let hiddenInput = form.querySelector('input[name="address_id"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'address_id';
                form.querySelector('form').appendChild(hiddenInput);
            }
            hiddenInput.value = address.address_id;

            // Fill in the form fields
            form.querySelector('#label').value = address.label || '';
            form.querySelector('#street_address').value = address.street_address || '';
            form.querySelector('#city').value = address.city || '';
            form.querySelector('#state').value = address.state || '';
            form.querySelector('#postal_code').value = address.postal_code || '';
            form.querySelector('#country').value = address.country || '';
            form.querySelector('input[name="is_default"]').checked = address.is_default == 1;

            // Show the form
            form.style.display = 'block';

            // Scroll to the form
            form.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch address details');
        });
}

function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) return;

    fetch('/fooddelivery/php/controllers/profile_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_address',
            address_id: addressId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Address deleted successfully');
            location.reload();
        } else {
            alert(data.error || 'Failed to delete address');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete address');
    });
} 

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleAddressBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const form = document.getElementById('addressForm');
            if (form) {
                if (form.style.display === 'none' || form.style.display === '') {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            }
        });
    }
});
 