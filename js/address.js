function showAddressForm() {
    document.getElementById('addressForm').style.display = 'flex';
    // Reset form
    document.getElementById('addressId').value = '';
    document.getElementById('addressForm').reset();
}

function hideAddressForm() {
    document.getElementById('addressForm').style.display = 'none';
}

function editAddress(addressId) {
    fetch(`/php/controllers/address_controller.php?id=${addressId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const address = data.address;
                document.getElementById('addressId').value = address.address_id;
                document.getElementById('label').value = address.label;
                document.getElementById('street_address').value = address.street_address;
                document.getElementById('city').value = address.city;
                document.getElementById('state').value = address.state;
                document.getElementById('postal_code').value = address.postal_code;
                document.getElementById('country').value = address.country;
                document.getElementById('is_default').checked = address.is_default == 1;
                showAddressForm();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load address details');
        });
}

function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) return;

    fetch('/php/controllers/address_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            address_id: addressId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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

function saveAddress(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const addressId = formData.get('address_id');
    
    const data = {
        action: addressId ? 'update' : 'add',
        address_id: addressId,
        label: formData.get('label'),
        street_address: formData.get('street_address'),
        city: formData.get('city'),
        state: formData.get('state'),
        postal_code: formData.get('postal_code'),
        country: formData.get('country'),
        is_default: formData.get('is_default') ? true : false
    };

    fetch('/php/controllers/address_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to save address');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save address');
    });

    return false;
} 