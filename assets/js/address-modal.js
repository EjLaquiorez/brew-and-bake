/**
 * Address Modal Functionality
 * Handles the address update modal for Philippines-based addresses
 * Using Leaflet for map implementation
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the address modal
    initAddressModal();

    // Initialize region selection
    initRegionSelection();

    // Initialize map update events
    initMapEvents();
});

/**
 * Initialize the address modal functionality
 */
function initAddressModal() {
    console.log('Initializing address modal...');

    const editAddressBtn = document.getElementById('edit-address-btn');
    const addAddressBtn = document.getElementById('add-address-btn');
    const addressModal = document.getElementById('address-modal');
    const closeModalBtn = document.getElementById('close-address-modal');
    const cancelBtn = document.getElementById('cancel-address-btn');
    const addressForm = document.getElementById('address-form');

    console.log('Edit address button found:', !!editAddressBtn);
    console.log('Add address button found:', !!addAddressBtn);
    console.log('Address modal found:', !!addressModal);

    if (!addressModal) {
        console.error('Address modal not found!');
        return;
    }

    // Create Bootstrap modal instance
    let modal;
    try {
        modal = new bootstrap.Modal(addressModal);
        console.log('Bootstrap modal created successfully');
    } catch (error) {
        console.error('Error creating Bootstrap modal:', error);
    }

    // Function to open modal and initialize map
    function openAddressModal(e) {
        console.log('Opening address modal...');
        if (e) e.preventDefault();

        try {
            modal.show();
            console.log('Modal shown');

            // Force map to initialize or refresh when modal is opened
            setTimeout(function() {
                console.log('Initializing map...');
                if (addressMap) {
                    console.log('Refreshing existing map');
                    addressMap.invalidateSize();
                } else {
                    console.log('Creating new map');
                    initLeafletMap();
                }
            }, 500);
        } catch (error) {
            console.error('Error opening modal:', error);

            // Fallback method if Bootstrap modal fails
            if (addressModal) {
                addressModal.style.display = 'block';
                addressModal.classList.add('show');
                document.body.classList.add('modal-open');

                // Create backdrop if it doesn't exist
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }

                // Initialize map
                setTimeout(function() {
                    if (addressMap) {
                        addressMap.invalidateSize();
                    } else {
                        initLeafletMap();
                    }
                }, 500);
            }
        }
    }

    // Open modal when edit button is clicked
    if (editAddressBtn) {
        console.log('Adding click event listener to edit address button');
        editAddressBtn.addEventListener('click', openAddressModal);

        // Add a direct onclick handler as a fallback
        editAddressBtn.onclick = function(e) {
            console.log('Edit address button clicked (onclick)');
            openAddressModal(e);
        };
    }

    // Open modal when add address button is clicked
    if (addAddressBtn) {
        console.log('Adding click event listener to add address button');
        addAddressBtn.addEventListener('click', openAddressModal);

        // Add a direct onclick handler as a fallback
        addAddressBtn.onclick = function(e) {
            console.log('Add address button clicked (onclick)');
            openAddressModal(e);
        };
    }

    // Close modal functions
    function closeModal() {
        console.log('Closing modal...');
        try {
            modal.hide();
            console.log('Modal hidden via Bootstrap');
        } catch (error) {
            console.error('Error hiding modal:', error);

            // Fallback method if Bootstrap modal fails
            if (addressModal) {
                addressModal.style.display = 'none';
                addressModal.classList.remove('show');
                document.body.classList.remove('modal-open');

                // Remove backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }
    }

    if (closeModalBtn) {
        console.log('Adding click event listener to close button');
        closeModalBtn.addEventListener('click', closeModal);

        // Add a direct onclick handler as a fallback
        closeModalBtn.onclick = function(e) {
            console.log('Close button clicked (onclick)');
            if (e) e.preventDefault();
            closeModal();
        };
    }

    if (cancelBtn) {
        console.log('Adding click event listener to cancel button');
        cancelBtn.addEventListener('click', function(e) {
            console.log('Cancel button clicked');
            e.preventDefault();
            closeModal();
        });

        // Add a direct onclick handler as a fallback
        cancelBtn.onclick = function(e) {
            console.log('Cancel button clicked (onclick)');
            if (e) e.preventDefault();
            closeModal();
        };
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === addressModal) {
            console.log('Clicked outside modal');
            closeModal();
        }
    });

    // Handle form submission with validation
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Reset validation state
            resetFormValidation(addressForm);

            // Validate form
            if (!validateAddressForm(addressForm)) {
                return false;
            }

            // Get form data
            const formData = new FormData(addressForm);

            // Show loading state on submit button
            const submitBtn = addressForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';

            // Send AJAX request
            fetch('update_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;

                if (data.success) {
                    // Show success toast
                    const successToast = new bootstrap.Toast(document.getElementById('address-success-toast'));
                    successToast.show();

                    // Update the address display on the page
                    updateAddressDisplay(formData);

                    // Close the modal
                    closeModal();

                    // Reload the page after a short delay to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    showAlert('danger', data.message || 'An error occurred while updating your address.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating your address. Please try again.');

                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    /**
     * Validate the address form
     * @param {HTMLFormElement} form - The form to validate
     * @returns {boolean} - Whether the form is valid
     */
    function validateAddressForm(form) {
        let isValid = true;

        // Required fields
        const requiredFields = [
            { id: 'full_name', message: 'Please enter your full name' },
            { id: 'phone', message: 'Please enter a valid Philippines mobile number' },
            { id: 'postal_code', message: 'Please enter a valid 4-digit postal code' },
            { id: 'street_address', message: 'Please enter your street address' },
            { id: 'location-display', message: 'Please select your complete location' }
        ];

        // Validate each required field
        requiredFields.forEach(field => {
            const input = form.querySelector(`#${field.id}`);
            if (!input) return;

            if (!input.value.trim()) {
                markInvalid(input, field.message);
                isValid = false;
            } else {
                markValid(input);
            }
        });

        // Validate phone number format (Philippines mobile)
        const phoneInput = form.querySelector('#phone');
        if (phoneInput && phoneInput.value.trim()) {
            // Should start with 9 and have 10 digits total
            const phoneRegex = /^9\d{9}$/;
            if (!phoneRegex.test(phoneInput.value.trim())) {
                markInvalid(phoneInput, 'Please enter a valid Philippines mobile number (e.g., 9171234567)');
                isValid = false;
            }
        }

        // Validate postal code (4 digits)
        const postalCodeInput = form.querySelector('#postal_code');
        if (postalCodeInput && postalCodeInput.value.trim()) {
            const postalCodeRegex = /^\d{4}$/;
            if (!postalCodeRegex.test(postalCodeInput.value.trim())) {
                markInvalid(postalCodeInput, 'Please enter a valid 4-digit postal code');
                isValid = false;
            }
        }

        // Validate location is selected
        const regionInput = form.querySelector('#selected-region');
        const provinceInput = form.querySelector('#selected-province');
        const cityInput = form.querySelector('#selected-city');
        const barangayInput = form.querySelector('#selected-barangay');
        const locationDisplay = form.querySelector('#location-display');

        if ((!regionInput || !regionInput.value) ||
            (!provinceInput || !provinceInput.value) ||
            (!cityInput || !cityInput.value) ||
            (!barangayInput || !barangayInput.value)) {
            if (locationDisplay) {
                markInvalid(locationDisplay, 'Please select your complete location (Region, Province, City, Barangay)');
                isValid = false;
            }
        }

        // If not valid, show a general error message
        if (!isValid) {
            showAlert('danger', 'Please correct the errors in the form before submitting.');
        }

        return isValid;
    }

    /**
     * Mark a form field as invalid
     * @param {HTMLElement} input - The input element
     * @param {string} message - The error message
     */
    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        // Find or create the feedback element
        let feedback = input.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.insertBefore(feedback, input.nextSibling);
        }

        feedback.textContent = message;
    }

    /**
     * Mark a form field as valid
     * @param {HTMLElement} input - The input element
     */
    function markValid(input) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }

    /**
     * Reset form validation state
     * @param {HTMLFormElement} form - The form to reset
     */
    function resetFormValidation(form) {
        const inputs = form.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });
    }
}

/**
 * Initialize the region selection functionality
 */
function initRegionSelection() {
    const regionTabs = document.querySelectorAll('.region-tab');
    const regionInput = document.getElementById('selected-region');
    const provinceInput = document.getElementById('selected-province');
    const cityInput = document.getElementById('selected-city');
    const barangayInput = document.getElementById('selected-barangay');
    const locationDisplay = document.getElementById('location-display');
    const regionContent = document.getElementById('region-content');
    const provinceContent = document.getElementById('province-content');
    const cityContent = document.getElementById('city-content');
    const barangayContent = document.getElementById('barangay-content');

    if (!regionTabs.length || !regionInput) return;

    // Set active tab
    function setActiveTab(tab) {
        regionTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Show corresponding content
        const tabId = tab.getAttribute('data-tab');
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });

        const activeContent = document.getElementById(tabId);
        if (activeContent) {
            activeContent.style.display = 'block';
        }
    }

    // Handle tab clicks
    regionTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            setActiveTab(this);
        });
    });

    // Handle region selection
    if (regionContent) {
        regionContent.addEventListener('click', function(e) {
            if (e.target.classList.contains('region-item')) {
                const region = e.target.textContent.trim();
                regionInput.value = region;
                updateLocationDisplay();

                // Load provinces for the selected region
                loadProvinces(region);

                // Switch to province tab
                const provinceTab = document.querySelector('[data-tab="province-content"]');
                if (provinceTab) setActiveTab(provinceTab);
            }
        });
    }

    /**
     * Load provinces for the selected region
     */
    function loadProvinces(region) {
        if (!provinceContent) return;

        // For demo purposes, we'll use the predefined provinces
        // In a real application, you would fetch this data from the server
        const provinces = {
            'Metro Manila': ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Taguig'],
            'North Luzon': ['Batanes', 'Cagayan', 'Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan'],
            'South Luzon': ['Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal', 'Palawan'],
            'Visayas': ['Cebu', 'Bohol', 'Leyte', 'Negros Occidental', 'Negros Oriental', 'Iloilo'],
            'Mindanao': ['Davao', 'Zamboanga', 'Misamis Oriental', 'Bukidnon', 'South Cotabato', 'Maguindanao']
        };

        const provinceList = provinces[region] || [];

        let html = '';
        if (provinceList.length > 0) {
            html = '<div class="list-group list-group-flush">';
            provinceList.forEach(province => {
                html += `<a href="#" class="list-group-item list-group-item-action province-item">${province}</a>`;
            });
            html += '</div>';
        } else {
            html = '<div class="text-center py-3"><p>No provinces found for this region</p></div>';
        }

        provinceContent.innerHTML = html;
    }

    // Handle province selection
    if (provinceContent) {
        provinceContent.addEventListener('click', function(e) {
            if (e.target.classList.contains('province-item')) {
                const province = e.target.textContent.trim();
                provinceInput.value = province;
                updateLocationDisplay();

                // Load cities for the selected province
                loadCities(regionInput.value, province);

                // Switch to city tab
                const cityTab = document.querySelector('[data-tab="city-content"]');
                if (cityTab) setActiveTab(cityTab);
            }
        });
    }

    /**
     * Load cities for the selected province
     */
    function loadCities(region, province) {
        if (!cityContent) return;

        // For demo purposes, we'll use the predefined cities
        // In a real application, you would fetch this data from the server
        const cities = {
            'Manila': ['Manila City'],
            'Quezon City': ['Quezon City'],
            'Makati': ['Makati City'],
            'Pasig': ['Pasig City'],
            'Taguig': ['Taguig City'],
            'Batanes': ['Basco', 'Itbayat', 'Ivana', 'Mahatao', 'Sabtang', 'Uyugan'],
            'Palawan': ['Puerto Princesa City', 'El Nido', 'Coron', 'Brooke\'s Point', 'Taytay'],
            'Cebu': ['Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay', 'Danao'],
            'Davao': ['Davao City', 'Tagum', 'Digos', 'Panabo', 'Mati']
        };

        const cityList = cities[province] || [];

        let html = '';
        if (cityList.length > 0) {
            html = '<div class="list-group list-group-flush">';
            cityList.forEach(city => {
                html += `<a href="#" class="list-group-item list-group-item-action city-item">${city}</a>`;
            });
            html += '</div>';
        } else {
            html = '<div class="text-center py-3"><p>No cities found for this province</p></div>';
        }

        cityContent.innerHTML = html;
    }

    // Handle city selection
    if (cityContent) {
        cityContent.addEventListener('click', function(e) {
            if (e.target.classList.contains('city-item')) {
                const city = e.target.textContent.trim();
                cityInput.value = city;
                updateLocationDisplay();

                // Load barangays for the selected city
                loadBarangays(regionInput.value, provinceInput.value, city);

                // Switch to barangay tab
                const barangayTab = document.querySelector('[data-tab="barangay-content"]');
                if (barangayTab) setActiveTab(barangayTab);
            }
        });
    }

    /**
     * Load barangays for the selected city
     */
    function loadBarangays(region, province, city) {
        if (!barangayContent) return;

        // For demo purposes, we'll use the predefined barangays
        // In a real application, you would fetch this data from the server
        const barangays = {
            'Puerto Princesa City': ['Bancao-Bancao', 'Irawan', 'San Pedro', 'Sta. Monica', 'Tiniguiban'],
            'Cebu City': ['Lahug', 'Mabolo', 'Talamban', 'Guadalupe', 'Pardo'],
            'Davao City': ['Poblacion', 'Talomo', 'Buhangin', 'Toril', 'Bunawan']
        };

        const barangayList = barangays[city] || [];

        let html = '';
        if (barangayList.length > 0) {
            html = '<div class="list-group list-group-flush">';
            barangayList.forEach(barangay => {
                html += `<a href="#" class="list-group-item list-group-item-action barangay-item">${barangay}</a>`;
            });
            html += '</div>';
        } else {
            html = '<div class="text-center py-3"><p>No barangays found for this city</p></div>';
        }

        barangayContent.innerHTML = html;
    }

    // Handle barangay selection
    if (barangayContent) {
        barangayContent.addEventListener('click', function(e) {
            if (e.target.classList.contains('barangay-item')) {
                const barangay = e.target.textContent.trim();
                barangayInput.value = barangay;
                updateLocationDisplay();

                // Close the location selector
                document.getElementById('location-selector').style.display = 'none';
            }
        });
    }

    // Update location display
    function updateLocationDisplay() {
        if (!locationDisplay) return;

        const region = regionInput.value;
        const province = provinceInput.value;
        const city = cityInput.value;
        const barangay = barangayInput.value;

        let displayText = '';

        if (region) displayText += region;
        if (province) displayText += province ? ', ' + province : '';
        if (city) displayText += city ? ', ' + city : '';
        if (barangay) displayText += barangay ? ', ' + barangay : '';

        locationDisplay.value = displayText;

        // Update map when location changes
        updateMap();
    }

    // Toggle location selector
    if (locationDisplay) {
        locationDisplay.addEventListener('click', function() {
            const selector = document.getElementById('location-selector');
            if (selector) {
                selector.style.display = selector.style.display === 'none' ? 'block' : 'none';

                // Set active tab based on what's already selected
                if (regionInput.value && !provinceInput.value) {
                    const provinceTab = document.querySelector('[data-tab="province-content"]');
                    if (provinceTab) setActiveTab(provinceTab);
                } else if (provinceInput.value && !cityInput.value) {
                    const cityTab = document.querySelector('[data-tab="city-content"]');
                    if (cityTab) setActiveTab(cityTab);
                } else if (cityInput.value && !barangayInput.value) {
                    const barangayTab = document.querySelector('[data-tab="barangay-content"]');
                    if (barangayTab) setActiveTab(barangayTab);
                } else {
                    const regionTab = document.querySelector('[data-tab="region-content"]');
                    if (regionTab) setActiveTab(regionTab);
                }
            }
        });
    }

    // Clear location button
    const clearLocationBtn = document.getElementById('clear-location');
    if (clearLocationBtn) {
        clearLocationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            regionInput.value = '';
            provinceInput.value = '';
            cityInput.value = '';
            barangayInput.value = '';
            locationDisplay.value = '';
        });
    }
}

/**
 * Show an alert message
 */
function showAlert(type, message) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    alertContainer.appendChild(alertDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => {
            alertContainer.removeChild(alertDiv);
        }, 150);
    }, 5000);
}

// Global variables for map
let addressMap = null;
let addressMarker = null;
let defaultLocation = [9.994295, 118.918419]; // Babuyan, Palawan
let mapInitialized = false;
let mapLoadAttempts = 0;
const MAX_MAP_LOAD_ATTEMPTS = 3;

/**
 * Initialize map update events
 */
function initMapEvents() {
    const postalCodeInput = document.getElementById('postal_code');
    const streetAddressInput = document.getElementById('street_address');
    const locationDisplay = document.getElementById('location-display');
    const useCurrentLocationBtn = document.getElementById('use-current-location');
    const retryMapLoadBtn = document.getElementById('retry-map-load');
    const enablePreciseLocationCheckbox = document.getElementById('enable-precise-location');

    // Initialize the map with error handling
    try {
        initLeafletMap();
    } catch (error) {
        console.error('Error initializing map:', error);
        showMapError('There was a problem loading the map. Please try again.');
    }

    // Update map when postal code changes
    if (postalCodeInput) {
        postalCodeInput.addEventListener('change', function() {
            if (mapInitialized) updateMap();
        });
        postalCodeInput.addEventListener('blur', function() {
            if (mapInitialized) updateMap();
        });
    }

    // Update map when street address changes
    if (streetAddressInput) {
        streetAddressInput.addEventListener('change', function() {
            if (mapInitialized) updateMap();
        });
        streetAddressInput.addEventListener('blur', function() {
            if (mapInitialized) updateMap();
        });
    }

    // Update map when location changes
    if (locationDisplay) {
        locationDisplay.addEventListener('change', function() {
            if (mapInitialized) updateMap();
        });
    }

    // Use current location button
    if (useCurrentLocationBtn) {
        useCurrentLocationBtn.addEventListener('click', function() {
            if (mapInitialized) {
                const isPrecise = enablePreciseLocationCheckbox && enablePreciseLocationCheckbox.checked;
                getCurrentLocation(isPrecise);
            } else {
                showMapError('Map is not available. Please try again later.');
            }
        });
    }

    // Get current location when toggling precise location checkbox
    if (enablePreciseLocationCheckbox) {
        enablePreciseLocationCheckbox.addEventListener('change', function() {
            if (mapInitialized) {
                // Get current location with the new precision setting
                getCurrentLocation(this.checked);
            }
        });
    }

    // Retry map load button
    if (retryMapLoadBtn) {
        retryMapLoadBtn.addEventListener('click', function() {
            hideMapError();
            showMapLoading();

            // Reset map load attempts
            mapLoadAttempts = 0;

            // Reset map initialization state
            mapInitialized = false;

            // If leafletMap exists, try to remove it first
            if (typeof leafletMap !== 'undefined' && leafletMap !== null) {
                try {
                    leafletMap.remove();
                    leafletMap = null;
                } catch (e) {
                    console.log('Could not remove existing map:', e);
                }
            }

            // Try to initialize the map again with a delay
            setTimeout(function() {
                try {
                    // Force reload Leaflet script
                    const leafletScript = document.createElement('script');
                    leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    leafletScript.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                    leafletScript.crossOrigin = '';
                    leafletScript.onload = function() {
                        console.log('Leaflet script reloaded');
                        initLeafletMap();
                    };
                    document.head.appendChild(leafletScript);
                } catch (error) {
                    console.error('Error initializing map on retry:', error);
                    showMapError('There was a problem loading the map. Please try again.');
                }
            }, 500);
        });
    }
}

/**
 * Initialize Leaflet Map
 */
function initLeafletMap() {
    mapLoadAttempts++;

    const mapCanvas = document.getElementById('map-canvas');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    if (!mapCanvas) {
        console.error('Map canvas element not found, will retry');
        if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
            setTimeout(initLeafletMap, 500);
        } else {
            showMapError('Map container not found. Please refresh the page and try again.');
        }
        return;
    }

    // Check if the map container has zero width or height (not visible yet)
    if (mapCanvas.clientWidth === 0 || mapCanvas.clientHeight === 0) {
        console.log('Map container has zero dimensions, waiting for it to be visible...');
        if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
            setTimeout(initLeafletMap, 500);
        } else {
            showMapError('Map container is not visible. Please try again.');
        }
        return;
    }

    // Show loading indicator
    showMapLoading();

    // Check if Leaflet is available
    if (typeof L === 'undefined') {
        if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
            console.log(`Attempt ${mapLoadAttempts} to load Leaflet...`);
            setTimeout(initLeafletMap, 1000);
            return;
        } else {
            hideMapLoading();
            showMapError('Leaflet could not be loaded. Please check your internet connection and try again.');
            return;
        }
    }

    try {
        // Always remove existing map instance if it exists
        if (addressMap) {
            addressMap.remove();
            addressMap = null;
            addressMarker = null;
            console.log('Removed existing map instance');
        }

        // Check if we have saved coordinates
        let initialLocation = defaultLocation;
        if (latitudeInput && longitudeInput && latitudeInput.value && longitudeInput.value) {
            initialLocation = [
                parseFloat(latitudeInput.value),
                parseFloat(longitudeInput.value)
            ];
        }

        // Create Leaflet map with better options
        addressMap = L.map(mapCanvas, {
            center: initialLocation,
            zoom: 15,
            zoomControl: false, // We'll add zoom control in a better position
            attributionControl: true
        });

        // Add OpenStreetMap tile layer
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(addressMap);

        // Add zoom control to bottom right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(addressMap);

        // Add geocoder control for searching locations
        const geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            position: 'topright',
            placeholder: 'Search for a location...',
            errorMessage: 'Nothing found.',
            showResultIcons: true
        }).addTo(addressMap);

        // Handle geocoder results
        geocoder.on('markgeocode', function(e) {
            const result = e.geocode || {};
            const latlng = result.center;

            // Update map and marker
            addressMap.setView(latlng, 16);
            addressMarker.setLatLng(latlng);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = latlng.lat;
                longitudeInput.value = latlng.lng;
            }

            // Update address fields based on location
            updateAddressFromLocation(latlng);
        });

        // Create marker with custom icon
        const markerIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        addressMarker = L.marker(initialLocation, {
            draggable: true,
            icon: markerIcon,
            title: 'Drag to set your location'
        }).addTo(addressMap);

        // Add popup with information
        addressMarker.bindPopup(`
            <div class="info-window">
                <h5 class="mb-1">Brew & Bake</h5>
                <p class="mb-1">Babuyan, Palawan, Philippines</p>
                <p class="mb-0"><a href="tel:+6312345678" class="text-decoration-none">+63 123 456 7890</a></p>
            </div>
        `);

        // Add event listener for marker drag end
        addressMarker.on('dragend', function() {
            const position = addressMarker.getLatLng();
            addressMap.setView(position);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = position.lat;
                longitudeInput.value = position.lng;
            }

            // Update address fields based on location
            updateAddressFromLocation(position);
        });

        // Add event listener for map click
        addressMap.on('click', function(event) {
            const position = event.latlng;
            addressMarker.setLatLng(position);
            addressMap.setView(position);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = position.lat;
                longitudeInput.value = position.lng;
            }

            // Update address fields based on location
            updateAddressFromLocation(position);
        });

        // Map has loaded
        mapInitialized = true;
        hideMapLoading();

        // Add a location button to the map
        L.control.locate = function(options) {
            return new L.Control.Locate(options);
        };

        L.Control.Locate = L.Control.extend({
            options: {
                position: 'bottomright',
                title: 'Get my location'
            },

            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                const button = L.DomUtil.create('a', 'leaflet-control-locate', container);
                button.href = '#';
                button.title = this.options.title;
                button.innerHTML = '<i class="bi bi-geo-alt-fill" style="line-height: 30px;"></i>';

                L.DomEvent.on(button, 'click', function(e) {
                    L.DomEvent.stopPropagation(e);
                    L.DomEvent.preventDefault(e);

                    // Get current location with precision setting
                    const isPrecise = document.getElementById('enable-precise-location')?.checked || true;
                    getCurrentLocation(isPrecise);
                });

                return container;
            }
        });

        // Add the locate control to the map
        L.control.locate().addTo(addressMap);

        // Try to get user's location if we don't have saved coordinates
        if (!latitudeInput.value || !longitudeInput.value) {
            // Wait a bit for the map to be fully initialized
            setTimeout(function() {
                // Check if the user has enabled precise location
                const isPrecise = document.getElementById('enable-precise-location')?.checked || true;

                // Try to get the user's location
                if (navigator.geolocation) {
                    // Show a subtle loading indicator
                    showMapLoading();

                    navigator.geolocation.getCurrentPosition(
                        // Success callback
                        function(position) {
                            const pos = [position.coords.latitude, position.coords.longitude];

                            // Update map and marker
                            addressMap.setView(pos, 15);
                            addressMarker.setLatLng(pos);

                            // Update latitude and longitude inputs
                            if (latitudeInput && longitudeInput) {
                                latitudeInput.value = pos[0];
                                longitudeInput.value = pos[1];
                            }

                            // Update address fields based on location
                            updateAddressFromLocation({lat: pos[0], lng: pos[1]});

                            // Hide loading indicator
                            hideMapLoading();
                        },
                        // Error callback - silently fall back to default location
                        function(error) {
                            console.log('Could not get initial location, using default:', error);
                            hideMapLoading();
                            updateMap();
                        },
                        // Options
                        {
                            enableHighAccuracy: isPrecise,
                            timeout: 5000,
                            maximumAge: 60000 // Use cached location for initial load
                        }
                    );
                } else {
                    // Fall back to address-based location
                    updateMap();
                }
            }, 500);
        } else {
            // Use the saved coordinates
            updateMap();
        }

    } catch (error) {
        console.error('Error in initLeafletMap:', error);
        mapInitialized = false;
        hideMapLoading();
        showMapError('There was a problem initializing the map. Please try again.');
    }
}

/**
 * Show map error message
 */
function showMapError(message) {
    console.error('Map error:', message);
    const mapErrorElement = document.getElementById('map-error');
    const mapErrorMessageElement = document.getElementById('map-error-message');
    const mapCanvas = document.getElementById('map-canvas');

    if (mapErrorElement) {
        if (mapErrorMessageElement) {
            mapErrorMessageElement.textContent = message;
        }
        mapErrorElement.style.display = 'flex';

        // Make sure the map container is visible so the error can be seen
        if (mapCanvas && mapCanvas.parentElement) {
            mapCanvas.parentElement.style.display = 'block';
        }
    } else {
        // Fallback if the error element doesn't exist
        alert('Map error: ' + message);
    }

    hideMapLoading();

    // Reset map initialization attempts
    mapLoadAttempts = 0;
}

/**
 * Hide map error message
 */
function hideMapError() {
    const mapErrorElement = document.getElementById('map-error');

    if (mapErrorElement) {
        mapErrorElement.style.display = 'none';
    }
}

/**
 * Show map loading indicator
 */
function showMapLoading() {
    const mapLoadingElement = document.getElementById('map-loading');
    const mapCanvas = document.getElementById('map-canvas');

    if (mapLoadingElement) {
        mapLoadingElement.style.display = 'flex';

        // Make sure the map container is visible so the loading indicator can be seen
        if (mapCanvas && mapCanvas.parentElement) {
            mapCanvas.parentElement.style.display = 'block';
        }
    }

    // Hide any error messages while loading
    hideMapError();
}

/**
 * Hide map loading indicator
 */
function hideMapLoading() {
    const mapLoadingElement = document.getElementById('map-loading');

    if (mapLoadingElement) {
        mapLoadingElement.style.display = 'none';
    }
}

/**
 * Get current location using browser geolocation
 * @param {boolean} precise - Whether to use high accuracy
 */
function getCurrentLocation(precise = true) {
    // Show loading indicator on the map
    showMapLoading();

    // Hide any previous errors
    hideMapError();

    if (!mapInitialized) {
        hideMapLoading();
        showMapError('Map is not available. Please try again later.');
        return;
    }

    // Update the UI to show we're getting location
    const locationButton = document.getElementById('use-current-location');
    if (locationButton) {
        const originalText = locationButton.innerHTML;
        locationButton.disabled = true;
        locationButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Getting location...';
    }

    if (navigator.geolocation) {
        // Get current position
        navigator.geolocation.getCurrentPosition(
            // Success callback
            function(position) {
                const pos = [position.coords.latitude, position.coords.longitude];

                // Update map and marker
                addressMap.setView(pos, 16); // Zoom in a bit more for better visibility
                addressMarker.setLatLng(pos);

                // Open the popup to show the user their location
                addressMarker.bindPopup(`
                    <div class="info-window">
                        <h5 class="mb-1">Your Location</h5>
                        <p class="mb-0">Latitude: ${pos[0].toFixed(6)}<br>Longitude: ${pos[1].toFixed(6)}</p>
                    </div>
                `).openPopup();

                // Update latitude and longitude inputs
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                if (latitudeInput && longitudeInput) {
                    latitudeInput.value = pos[0];
                    longitudeInput.value = pos[1];
                }

                // Update address fields based on location
                updateAddressFromLocation({lat: pos[0], lng: pos[1]});

                // Hide loading indicator
                hideMapLoading();

                // Reset the location button
                if (locationButton) {
                    locationButton.disabled = false;
                    locationButton.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i> Use My Current Location';

                    // Add a success class briefly to indicate success
                    locationButton.classList.remove('btn-outline-primary');
                    locationButton.classList.add('btn-success');

                    setTimeout(function() {
                        locationButton.classList.remove('btn-success');
                        locationButton.classList.add('btn-outline-primary');
                    }, 1500);
                }
            },
            // Error callback
            function(error) {
                console.error('Error getting current location:', error);
                hideMapLoading();

                let errorMessage = 'Error getting your location. ';

                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please allow location access in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'The request to get your location timed out.';
                        break;
                    case error.UNKNOWN_ERROR:
                    default:
                        errorMessage += 'An unknown error occurred.';
                        break;
                }

                // Reset the location button
                if (locationButton) {
                    locationButton.disabled = false;
                    locationButton.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i> Use My Current Location';
                }

                // Show error in map
                showMapError(errorMessage);

                // Show a small alert under the map
                const mapContainer = document.getElementById('map-container');
                if (mapContainer) {
                    const alertElement = document.createElement('div');
                    alertElement.className = 'alert alert-danger alert-dismissible fade show mt-2';
                    alertElement.setAttribute('role', 'alert');
                    alertElement.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;

                    // Insert after map container
                    mapContainer.parentNode.insertBefore(alertElement, mapContainer.nextSibling);

                    // Auto-dismiss after 5 seconds
                    setTimeout(function() {
                        alertElement.classList.remove('show');
                        setTimeout(function() {
                            if (alertElement.parentNode) {
                                alertElement.parentNode.removeChild(alertElement);
                            }
                        }, 150);
                    }, 5000);
                }
            },
            // Options
            {
                enableHighAccuracy: precise,
                timeout: 10000,
                maximumAge: precise ? 0 : 60000 // Use cached location if not precise
            }
        );
    } else {
        console.error('Geolocation is not supported by this browser.');
        hideMapLoading();

        // Reset the location button
        if (locationButton) {
            locationButton.disabled = false;
            locationButton.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i> Use My Current Location';
        }

        showMapError('Geolocation is not supported by your browser. Please enter your address manually.');
    }
}

/**
 * Update map based on address fields
 */
function updateMap() {
    if (!mapInitialized || !addressMap) {
        console.error('Map not initialized');
        return;
    }

    const postalCode = document.getElementById('postal_code')?.value || '';
    const streetAddress = document.getElementById('street_address')?.value || '';
    const region = document.getElementById('selected-region')?.value || '';
    const province = document.getElementById('selected-province')?.value || '';
    const city = document.getElementById('selected-city')?.value || '';
    const barangay = document.getElementById('selected-barangay')?.value || '';

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
    addressMap.setView(location, 15);
    addressMarker.setLatLng(location);

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

    // Update the hidden address field for backward compatibility
    const addressInput = document.getElementById('address');
    if (addressInput) {
        addressInput.value = `${streetAddress}, ${location}, ${postalCode}`;
    }

    let addressHtml = `
        <strong>${fullName}</strong><br>
        ${phone}<br>
        ${streetAddress}<br>
        ${location}<br>
        ${postalCode}<br>
        <span class="badge bg-secondary">${addressType}</span>
    `;

    addressDisplay.innerHTML = addressHtml;
}
