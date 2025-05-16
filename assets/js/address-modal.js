/**
 * Address Modal Functionality
 * Handles the address update modal for Philippines-based addresses
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
    const editAddressBtn = document.getElementById('edit-address-btn');
    const addressModal = document.getElementById('address-modal');
    const closeModalBtn = document.getElementById('close-address-modal');
    const cancelBtn = document.getElementById('cancel-address-btn');
    const addressForm = document.getElementById('address-form');

    if (!editAddressBtn || !addressModal) return;

    // Open modal when edit button is clicked
    editAddressBtn.addEventListener('click', function() {
        addressModal.classList.add('show');
        document.body.classList.add('modal-open');
    });

    // Close modal functions
    function closeModal() {
        addressModal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === addressModal) {
            closeModal();
        }
    });

    // Handle form submission
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(addressForm);

            // Send AJAX request
            fetch('update_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message);

                    // Update the address display on the page
                    updateAddressDisplay(formData);

                    // Close the modal
                    closeModal();
                } else {
                    // Show error message
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating your address.');
            });
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
let map;
let marker;
let geocoder;
let infoWindow;
let defaultLocation = { lat: 14.5995, lng: 120.9842 }; // Manila, Philippines
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
        initMap();
    } catch (error) {
        console.error('Error initializing map:', error);
        showMapError('There was a problem loading the map. Please try again.');
    }

    // Update map when postal code changes
    if (postalCodeInput) {
        postalCodeInput.addEventListener('change', function() {
            if (mapInitialized) geocodeAddress();
        });
        postalCodeInput.addEventListener('blur', function() {
            if (mapInitialized) geocodeAddress();
        });
    }

    // Update map when street address changes
    if (streetAddressInput) {
        streetAddressInput.addEventListener('change', function() {
            if (mapInitialized) geocodeAddress();
        });
        streetAddressInput.addEventListener('blur', function() {
            if (mapInitialized) geocodeAddress();
        });
    }

    // Update map when location changes
    if (locationDisplay) {
        locationDisplay.addEventListener('change', function() {
            if (mapInitialized) geocodeAddress();
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

    // Retry map load button
    if (retryMapLoadBtn) {
        retryMapLoadBtn.addEventListener('click', function() {
            hideMapError();
            showMapLoading();

            // Reset map load attempts
            mapLoadAttempts = 0;

            // Try to initialize the map again
            setTimeout(function() {
                try {
                    initMap();
                } catch (error) {
                    console.error('Error initializing map on retry:', error);
                    showMapError('There was a problem loading the map. Please try again.');
                }
            }, 500);
        });
    }
}

/**
 * Initialize Google Map
 */
function initMap() {
    mapLoadAttempts++;

    const mapCanvas = document.getElementById('map-canvas');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    if (!mapCanvas) {
        showMapError('Map container not found.');
        return;
    }

    // Show loading indicator
    showMapLoading();

    // Check if Google Maps API is available
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
            console.log(`Attempt ${mapLoadAttempts} to load Google Maps API...`);
            setTimeout(initMap, 1000);
            return;
        } else {
            hideMapLoading();
            showMapError('Google Maps could not be loaded. Please check your internet connection and try again.');
            return;
        }
    }

    try {
        // Check if we have saved coordinates
        let initialLocation = defaultLocation;
        if (latitudeInput && longitudeInput && latitudeInput.value && longitudeInput.value) {
            initialLocation = {
                lat: parseFloat(latitudeInput.value),
                lng: parseFloat(longitudeInput.value)
            };
        }

        // Create map with simplified options
        map = new google.maps.Map(mapCanvas, {
            center: initialLocation,
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM
            }
        });

        // Create geocoder
        geocoder = new google.maps.Geocoder();

        // Create marker
        marker = new google.maps.Marker({
            position: initialLocation,
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP
        });

        // Add event listener for marker drag end
        google.maps.event.addListener(marker, 'dragend', function() {
            const position = marker.getPosition();
            map.setCenter(position);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = position.lat();
                longitudeInput.value = position.lng();
            }

            // Reverse geocode the location
            reverseGeocode(position);
        });

        // Add event listener for map click
        google.maps.event.addListener(map, 'click', function(event) {
            marker.setPosition(event.latLng);
            map.setCenter(event.latLng);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = event.latLng.lat();
                longitudeInput.value = event.latLng.lng();
            }

            // Reverse geocode the location
            reverseGeocode(event.latLng);
        });

        // Add event listener for map load
        google.maps.event.addListenerOnce(map, 'idle', function() {
            // Map has loaded
            mapInitialized = true;
            hideMapLoading();

            // Initial geocode if we have an address
            setTimeout(function() {
                geocodeAddress();
            }, 500);
        });

        // Add event listener for map load error
        google.maps.event.addListener(map, 'error', function(error) {
            console.error('Map load error:', error);
            mapInitialized = false;
            hideMapLoading();
            showMapError('There was a problem loading the map. Please try again.');
        });

    } catch (error) {
        console.error('Error in initMap:', error);
        mapInitialized = false;
        hideMapLoading();
        showMapError('There was a problem initializing the map. Please try again.');
    }
}

/**
 * Show map error message
 */
function showMapError(message) {
    const mapErrorElement = document.getElementById('map-error');
    const mapErrorMessageElement = document.getElementById('map-error-message');

    if (mapErrorElement) {
        if (mapErrorMessageElement) {
            mapErrorMessageElement.textContent = message;
        }
        mapErrorElement.style.display = 'flex';
    }

    hideMapLoading();
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

    if (mapLoadingElement) {
        mapLoadingElement.style.display = 'flex';
    }
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
    // Show loading indicator
    showMapLoading();

    // Hide any previous errors
    hideMapError();

    if (!mapInitialized) {
        hideMapLoading();
        showMapError('Map is not available. Please try again later.');
        return;
    }

    if (navigator.geolocation) {
        // Create toast notification
        const toastContainer = document.createElement('div');
        toastContainer.className = 'position-fixed bottom-0 start-50 translate-middle-x p-3';
        toastContainer.style.zIndex = '9999';

        const toastElement = document.createElement('div');
        toastElement.className = 'toast align-items-center text-white bg-primary border-0';
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');

        const toastBody = document.createElement('div');
        toastBody.className = 'd-flex';

        const toastContent = document.createElement('div');
        toastContent.className = 'toast-body';
        toastContent.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i> Getting your location...';

        toastBody.appendChild(toastContent);
        toastElement.appendChild(toastBody);
        toastContainer.appendChild(toastElement);
        document.body.appendChild(toastContainer);

        // Show toast
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();

        // Get current position
        navigator.geolocation.getCurrentPosition(
            // Success callback
            function(position) {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                // Update map and marker
                map.setCenter(pos);
                marker.setPosition(pos);

                // Update latitude and longitude inputs
                const latitudeInput = document.getElementById('latitude');
                const longitudeInput = document.getElementById('longitude');
                if (latitudeInput && longitudeInput) {
                    latitudeInput.value = pos.lat;
                    longitudeInput.value = pos.lng;
                }

                // Reverse geocode the location
                reverseGeocode(pos);

                // Hide loading indicator
                hideMapLoading();

                // Update toast
                toastContent.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Location found!';
                toastElement.classList.remove('bg-primary');
                toastElement.classList.add('bg-success');

                // Remove toast after 2 seconds
                setTimeout(function() {
                    document.body.removeChild(toastContainer);
                }, 2000);
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

                // Update toast
                toastContent.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + errorMessage;
                toastElement.classList.remove('bg-primary');
                toastElement.classList.add('bg-danger');

                // Remove toast after 5 seconds
                setTimeout(function() {
                    document.body.removeChild(toastContainer);
                }, 5000);

                // Show error in map
                showMapError(errorMessage);
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
        showMapError('Geolocation is not supported by your browser. Please enter your address manually.');
    }
}

/**
 * Geocode address to get coordinates
 */
function geocodeAddress() {
    if (!mapInitialized || !geocoder) {
        console.error('Map or geocoder not initialized');
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

    // Show loading
    showMapLoading();

    // Geocode the address
    geocoder.geocode({ 'address': address }, function(results, status) {
        if (status === google.maps.GeocoderStatus.OK && results && results.length > 0) {
            // Update map and marker
            map.setCenter(results[0].geometry.location);
            marker.setPosition(results[0].geometry.location);

            // Update latitude and longitude inputs
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = results[0].geometry.location.lat();
                longitudeInput.value = results[0].geometry.location.lng();
            }

            // Hide any previous errors
            hideMapError();
        } else {
            console.error('Geocode was not successful for the following reason:', status);

            // Don't show error for empty address
            if (address.replace(/,|\s|Philippines/g, '').length > 5) {
                showMapError('Could not find this address on the map. Please try a different address or use the current location button.');
            }
        }

        // Hide loading
        hideMapLoading();
    });
}

/**
 * Reverse geocode coordinates to get address
 */
function reverseGeocode(latLng) {
    if (!mapInitialized || !geocoder) {
        console.error('Map or geocoder not initialized');
        return;
    }

    // Show loading
    showMapLoading();

    // Reverse geocode the coordinates
    geocoder.geocode({ 'location': latLng }, function(results, status) {
        if (status === google.maps.GeocoderStatus.OK && results && results.length > 0) {
            // Extract address components
            const addressComponents = results[0].address_components;
            let postalCode = '';
            let streetNumber = '';
            let route = '';
            let sublocality = '';
            let locality = '';
            let administrative_area_level_2 = '';
            let administrative_area_level_1 = '';
            let country = '';

            for (let i = 0; i < addressComponents.length; i++) {
                const component = addressComponents[i];
                const types = component.types;

                if (types.includes('postal_code')) {
                    postalCode = component.long_name;
                } else if (types.includes('street_number')) {
                    streetNumber = component.long_name;
                } else if (types.includes('route')) {
                    route = component.long_name;
                } else if (types.includes('sublocality_level_1') || types.includes('sublocality')) {
                    sublocality = component.long_name;
                } else if (types.includes('locality')) {
                    locality = component.long_name;
                } else if (types.includes('administrative_area_level_2')) {
                    administrative_area_level_2 = component.long_name;
                } else if (types.includes('administrative_area_level_1')) {
                    administrative_area_level_1 = component.long_name;
                } else if (types.includes('country')) {
                    country = component.long_name;
                }
            }

            // Check if we're in the Philippines
            const isPhilippines = country === 'Philippines' ||
                                 country === 'PH' ||
                                 administrative_area_level_1.includes('Philippines');

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
                postalCodeInput.value = postalCode || (isPhilippines ? '1000' : '');
            }

            // Update street address
            if (streetAddressInput) {
                let streetAddress = '';
                if (streetNumber) streetAddress += streetNumber;
                if (route) streetAddress += streetAddress ? ' ' + route : route;
                if (sublocality && !barangayInput) streetAddress += streetAddress ? ', ' + sublocality : sublocality;

                // If we have a formatted address but no street components, use that
                if (!streetAddress && results[0].formatted_address) {
                    // Extract just the street part (before the first comma)
                    const formattedParts = results[0].formatted_address.split(',');
                    if (formattedParts.length > 0) {
                        streetAddress = formattedParts[0].trim();
                    }
                }

                if (streetAddress) {
                    streetAddressInput.value = streetAddress;
                }
            }

            // Map Philippines regions
            let region = '';
            if (isPhilippines) {
                if (administrative_area_level_1) {
                    if (administrative_area_level_1.includes('Metro Manila') ||
                        administrative_area_level_1 === 'NCR' ||
                        administrative_area_level_1 === 'National Capital Region') {
                        region = 'Metro Manila';
                    } else if (administrative_area_level_1.includes('Luzon') ||
                              ['Batanes', 'Cagayan', 'Ilocos', 'La Union', 'Pangasinan'].some(p => administrative_area_level_1.includes(p))) {
                        region = 'North Luzon';
                    } else if (administrative_area_level_1.includes('Luzon') ||
                              ['Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal', 'Palawan'].some(p => administrative_area_level_1.includes(p))) {
                        region = 'South Luzon';
                    } else if (administrative_area_level_1.includes('Visayas') ||
                              ['Cebu', 'Bohol', 'Leyte', 'Negros', 'Iloilo'].some(p => administrative_area_level_1.includes(p))) {
                        region = 'Visayas';
                    } else if (administrative_area_level_1.includes('Mindanao') ||
                              ['Davao', 'Zamboanga', 'Cotabato', 'Maguindanao'].some(p => administrative_area_level_1.includes(p))) {
                        region = 'Mindanao';
                    } else {
                        // Default to a region based on geography if we can't determine
                        const lat = latLng.lat();
                        if (lat > 14.5) {
                            region = 'North Luzon';
                        } else if (lat > 13) {
                            region = 'Metro Manila';
                        } else if (lat > 10) {
                            region = 'South Luzon';
                        } else if (lat > 8) {
                            region = 'Visayas';
                        } else {
                            region = 'Mindanao';
                        }
                    }
                }
            } else {
                // Not in Philippines, set default region
                region = 'Metro Manila';
            }

            // Update region
            if (regionInput) {
                regionInput.value = region;
            }

            // Update province
            if (provinceInput) {
                provinceInput.value = administrative_area_level_1 || (isPhilippines ? 'Metro Manila' : '');
            }

            // Update city
            if (cityInput) {
                if (locality) {
                    cityInput.value = locality;
                } else if (administrative_area_level_2) {
                    cityInput.value = administrative_area_level_2;
                } else {
                    cityInput.value = isPhilippines ? 'Manila' : '';
                }
            }

            // Update barangay
            if (barangayInput) {
                barangayInput.value = sublocality || '';
            }

            // Update location display
            if (locationDisplay) {
                let displayText = '';
                if (region) displayText += region;
                if (administrative_area_level_1) displayText += displayText ? ', ' + administrative_area_level_1 : administrative_area_level_1;
                if (locality) displayText += displayText ? ', ' + locality : locality;
                if (sublocality) displayText += displayText ? ', ' + sublocality : sublocality;

                locationDisplay.value = displayText;
            }

            // Hide any previous errors
            hideMapError();
        } else {
            console.error('Reverse geocode was not successful for the following reason:', status);
            showMapError('Could not determine the address for this location. You can still enter it manually.');
        }

        // Hide loading
        hideMapLoading();
    });
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
