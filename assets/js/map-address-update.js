/**
 * Map Address Update Functions for Brew & Bake
 * Functions for updating address fields based on map interactions
 */

/**
 * Update map based on address fields
 */
function updateMap() {
    if (!mapInitialized) {
        console.error('Map not initialized');
        return;
    }

    const postalCode = document.getElementById('postal_code').value;
    const streetAddress = document.getElementById('street_address').value;
    const region = document.getElementById('selected-region').value;
    const province = document.getElementById('selected-province').value;
    const city = document.getElementById('selected-city').value;
    const barangay = document.getElementById('selected-barangay').value;

    // Build address string
    let address = '';
    if (streetAddress) address += streetAddress;
    if (barangay) address += address ? ', ' + barangay : barangay;
    if (city) address += address ? ', ' + city : city;
    if (province) address += address ? ', ' + province : province;
    if (region) address += address ? ', ' + region : region;
    if (postalCode) address += address ? ', ' + postalCode : postalCode;
    address += ', Philippines';

    // If address is empty or just Philippines, return
    if (address === ', Philippines' || !address.replace(/,|\s|Philippines/g, '').length) {
        return;
    }

    // For simplicity, we'll just use the default location for Palawan
    // In a real implementation, you would use a geocoding service like Nominatim
    // to convert the address to coordinates

    // Set a location in Palawan based on the address
    let location = defaultLocation;

    // If the address contains Palawan, use the default location
    if (address.toLowerCase().includes('palawan')) {
        location = defaultLocation;
    } else if (address.toLowerCase().includes('manila')) {
        location = [14.5995, 120.9842]; // Manila
    } else if (address.toLowerCase().includes('cebu')) {
        location = [10.3157, 123.8854]; // Cebu
    } else if (address.toLowerCase().includes('davao')) {
        location = [7.1907, 125.4553]; // Davao
    }

    // Update map and marker
    leafletMap.setView(location, 15);
    marker.setLatLng(location);

    // Update latitude and longitude inputs
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    if (latitudeInput && longitudeInput) {
        latitudeInput.value = location[0];
        longitudeInput.value = location[1];
    }

    // Hide any previous errors
    hideMapError();

    // Hide loading
    hideMapLoading();
}

/**
 * Update address fields based on location
 */
function updateAddressFromLocation(position) {
    // Show loading
    showMapLoading();

    // For simplicity, we'll just set some default values
    // In a real implementation, you would use a reverse geocoding service
    // to convert the coordinates to an address

    // Set default values for Philippines
    let postalCode = '5300'; // Palawan postal code
    let streetAddress = 'Babuyan Beach Road';
    let region = 'South Luzon';
    let province = 'Palawan';
    let city = 'Puerto Princesa City';
    let barangay = 'Babuyan';

    // Check if we're near Palawan
    const lat = position.lat;
    const lng = position.lng;

    // Determine region and province based on coordinates
    if (lat > 14.5) {
        region = 'North Luzon';
        province = 'Batanes';
        city = 'Basco';
        barangay = 'San Antonio';
        postalCode = '3900';
        streetAddress = 'Abad Santos St.';
    } else if (lat > 13) {
        region = 'Metro Manila';
        province = 'Metro Manila';
        city = 'Manila City';
        barangay = 'Malate';
        postalCode = '1004';
        streetAddress = 'Mabini St.';
    } else if (lat > 10) {
        region = 'South Luzon';
        province = 'Palawan';
        city = 'Puerto Princesa City';
        barangay = 'Babuyan';
        postalCode = '5300';
        streetAddress = 'Babuyan Beach Road';
    } else if (lat > 8) {
        region = 'Visayas';
        province = 'Cebu';
        city = 'Cebu City';
        barangay = 'Lahug';
        postalCode = '6000';
        streetAddress = 'Gorordo Ave.';
    } else {
        region = 'Mindanao';
        province = 'Davao';
        city = 'Davao City';
        barangay = 'Poblacion';
        postalCode = '8000';
        streetAddress = 'C.M. Recto Ave.';
    }

    // Update address fields
    const postalCodeInput = document.getElementById('postal_code');
    const streetAddressInput = document.getElementById('street_address');
    const regionInput = document.getElementById('selected-region');
    const provinceInput = document.getElementById('selected-province');
    const cityInput = document.getElementById('selected-city');
    const barangayInput = document.getElementById('selected-barangay');
    const locationDisplay = document.getElementById('location-display');

    // Update postal code
    if (postalCodeInput) {
        postalCodeInput.value = postalCode;
    }

    // Update street address
    if (streetAddressInput) {
        streetAddressInput.value = streetAddress;
    }

    // Update region
    if (regionInput) {
        regionInput.value = region;
    }

    // Update province
    if (provinceInput) {
        provinceInput.value = province;
    }

    // Update city
    if (cityInput) {
        cityInput.value = city;
    }

    // Update barangay
    if (barangayInput) {
        barangayInput.value = barangay;
    }

    // Update location display
    if (locationDisplay) {
        let displayText = '';
        if (region) displayText += region;
        if (province) displayText += displayText ? ', ' + province : province;
        if (city) displayText += displayText ? ', ' + city : city;
        if (barangay) displayText += displayText ? ', ' + barangay : barangay;

        locationDisplay.value = displayText;
    }

    // Hide any previous errors
    hideMapError();

    // Hide loading
    hideMapLoading();
}

/**
 * Update the address display on the page
 */
function updateAddressDisplay(formData) {
    const addressDisplay = document.getElementById('delivery-address-display');
    if (!addressDisplay) return;

    const fullName = formData.get('full_name');
    const phone = formData.get('phone');
    const postalCode = formData.get('postal_code');
    const streetAddress = formData.get('street_address');
    const location = formData.get('location');
    const addressType = formData.get('address_type');
    const latitude = formData.get('latitude');
    const longitude = formData.get('longitude');

    // Format phone number with +63 prefix
    const formattedPhone = phone ? `+63${phone}` : '';

    // Update the hidden address field for backward compatibility
    const addressInput = document.getElementById('address');
    if (addressInput) {
        addressInput.value = `${streetAddress}, ${location}, ${postalCode}`;
    }

    // Create badge based on address type
    const badgeClass = addressType === 'Home' ? 'bg-primary' : 'bg-info';
    const badgeIcon = addressType === 'Home' ? 'bi-house-fill' : 'bi-briefcase-fill';

    let addressHtml = `
        <div class="address-card p-3 border rounded shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0">${fullName}</h5>
                <span class="badge ${badgeClass}">
                    <i class="bi ${badgeIcon} me-1"></i> ${addressType}
                </span>
            </div>
            <div class="text-muted mb-2">
                <i class="bi bi-telephone-fill me-2"></i>${formattedPhone}
            </div>
            <div class="mb-2">
                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>
                <strong>${streetAddress}</strong>
            </div>
            <div class="small text-muted">
                ${location}, ${postalCode}
            </div>
        </div>
    `;

    addressDisplay.innerHTML = addressHtml;

    // Update the profile page address display if it exists
    const profileAddressDisplay = document.getElementById('profile-address-display');
    if (profileAddressDisplay) {
        profileAddressDisplay.innerHTML = `
            <div class="mb-1"><strong>${streetAddress}</strong></div>
            <div class="text-muted small">${location}, ${postalCode}</div>
        `;
    }
}
