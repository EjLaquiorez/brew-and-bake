/**
 * Address Modal Manager for Brew & Bake
 * A clean implementation of the address modal functionality
 * Using Leaflet for map implementation
 *
 * This file handles:
 * - Address modal initialization and events
 * - Map initialization and interaction
 * - Location selection (Region, Province, City, Barangay)
 * - Form validation and submission
 * - Address display updates
 */

// Use IIFE to avoid global namespace pollution
const AddressModalManager = (function() {
    // Private variables
    let map = null;
    let marker = null;
    let modal = null;
    let mapInitialized = false;
    let mapLoadAttempts = 0;

    // Default location (Brew & Bake - Babuyan, Palawan)
    const defaultLocation = [9.994295, 118.918419];
    const MAX_MAP_LOAD_ATTEMPTS = 3;

    // DOM elements cache
    let elements = {};

    // Philippines location data
    const locationData = {
        regions: ['Metro Manila', 'North Luzon', 'South Luzon', 'Visayas', 'Mindanao'],
        provinces: {
            'Metro Manila': ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Taguig'],
            'North Luzon': ['Batanes', 'Cagayan', 'Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan'],
            'South Luzon': ['Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal', 'Palawan'],
            'Visayas': ['Cebu', 'Bohol', 'Leyte', 'Negros Occidental', 'Negros Oriental', 'Iloilo'],
            'Mindanao': ['Davao', 'Zamboanga', 'Misamis Oriental', 'Bukidnon', 'South Cotabato', 'Maguindanao']
        },
        cities: {
            'Manila': ['Manila City'],
            'Quezon City': ['Quezon City'],
            'Makati': ['Makati City'],
            'Pasig': ['Pasig City'],
            'Taguig': ['Taguig City'],
            'Batanes': ['Basco', 'Itbayat', 'Ivana', 'Mahatao', 'Sabtang', 'Uyugan'],
            'Palawan': ['Puerto Princesa City', 'El Nido', 'Coron', 'Brooke\'s Point', 'Taytay'],
            'Cebu': ['Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay', 'Danao'],
            'Davao': ['Davao City', 'Tagum', 'Digos', 'Panabo', 'Mati']
        },
        barangays: {
            'Puerto Princesa City': ['Bancao-Bancao', 'Irawan', 'San Pedro', 'Sta. Monica', 'Tiniguiban'],
            'Cebu City': ['Lahug', 'Mabolo', 'Talamban', 'Guadalupe', 'Pardo'],
            'Davao City': ['Poblacion', 'Talomo', 'Buhangin', 'Toril', 'Bunawan']
        }
    };

    /**
     * Initialize the address modal functionality
     */
    function init() {
        console.log('Initializing Address Modal Manager...');

        // Cache DOM elements
        cacheElements();

        // If modal not found, exit
        if (!elements.modal) {
            console.error('Address modal not found!');
            return;
        }

        // Initialize Bootstrap modal
        try {
            modal = new bootstrap.Modal(elements.modal);
            console.log('Bootstrap modal created successfully');
        } catch (error) {
            console.error('Error creating Bootstrap modal:', error);
        }

        // Bind events
        bindEvents();

        // Initialize location tabs
        initLocationTabs();

        console.log('Address Modal Manager initialized successfully');
    }

    /**
     * Cache DOM elements for better performance
     */
    function cacheElements() {
        elements = {
            // Modal elements
            modal: document.getElementById('address-modal'),
            editAddressBtn: document.getElementById('edit-address-btn'),
            addAddressBtn: document.getElementById('add-address-btn'),
            closeModalBtn: document.getElementById('close-address-modal'),
            cancelBtn: document.getElementById('cancel-address-btn'),

            // Form elements
            addressForm: document.getElementById('address-form'),
            fullNameInput: document.getElementById('full_name'),
            phoneInput: document.getElementById('modal_phone'),
            streetAddressInput: document.getElementById('street_address'),
            postalCodeInput: document.getElementById('postal_code'),
            saveAddressBtn: document.getElementById('save-address-btn'),

            // Map elements
            mapContainer: document.getElementById('map-container'),
            mapCanvas: document.getElementById('map-canvas'),
            mapError: document.getElementById('map-error'),
            mapErrorMessage: document.getElementById('map-error-message'),
            mapLoading: document.getElementById('map-loading'),
            retryMapLoadBtn: document.getElementById('retry-map-load'),
            useMyLocationBtn: document.getElementById('use-my-location'),
            latitudeInput: document.getElementById('latitude'),
            longitudeInput: document.getElementById('longitude'),

            // Location elements
            locationDisplay: document.getElementById('location-display'),
            locationSelector: document.getElementById('location-selector'),
            clearLocationBtn: document.getElementById('clear-location'),
            regionInput: document.getElementById('selected-region'),
            provinceInput: document.getElementById('selected-province'),
            cityInput: document.getElementById('selected-city'),
            barangayInput: document.getElementById('selected-barangay'),

            // Tab elements
            regionTabs: document.querySelectorAll('.region-tab'),
            regionContent: document.getElementById('region-content'),
            provinceContent: document.getElementById('province-content'),
            cityContent: document.getElementById('city-content'),
            barangayContent: document.getElementById('barangay-content'),

            // Alert containers
            addressFormAlert: document.getElementById('address-form-alert'),
            locationSelectionAlert: document.getElementById('location-selection-alert'),
            mapAlert: document.getElementById('map-alert'),

            // Success toast
            successToast: document.getElementById('address-success-toast')
        };
    }

    /**
     * Bind events to DOM elements
     */
    function bindEvents() {
        // Modal open buttons
        if (elements.editAddressBtn) {
            elements.editAddressBtn.addEventListener('click', openModal);
        }

        if (elements.addAddressBtn) {
            elements.addAddressBtn.addEventListener('click', openModal);
        }

        // Modal close buttons
        if (elements.closeModalBtn) {
            elements.closeModalBtn.addEventListener('click', closeModal);
        }

        if (elements.cancelBtn) {
            elements.cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal();
            });
        }

        // Form submission
        if (elements.addressForm) {
            elements.addressForm.addEventListener('submit', handleFormSubmit);
        }

        // Map events
        if (elements.retryMapLoadBtn) {
            elements.retryMapLoadBtn.addEventListener('click', retryMapLoad);
        }

        if (elements.useMyLocationBtn) {
            elements.useMyLocationBtn.addEventListener('click', function() {
                getCurrentLocation();
            });
        }

        // Location display click
        if (elements.locationDisplay) {
            elements.locationDisplay.addEventListener('click', toggleLocationSelector);
        }

        // Clear location button
        if (elements.clearLocationBtn) {
            elements.clearLocationBtn.addEventListener('click', clearLocation);
        }

        // Form field changes
        if (elements.streetAddressInput) {
            elements.streetAddressInput.addEventListener('change', function() {
                if (mapInitialized) updateMap();
            });
        }

        if (elements.postalCodeInput) {
            elements.postalCodeInput.addEventListener('change', function() {
                if (mapInitialized) updateMap();
            });
        }
    }

    /**
     * Open the address modal
     */
    function openModal(e) {
        if (e) e.preventDefault();
        console.log('Opening address modal...');

        try {
            // Show modal
            modal.show();

            // Ensure map container has proper height
            if (elements.mapContainer) {
                elements.mapContainer.style.height = '400px';
            }

            // Initialize map after modal is fully visible
            elements.modal.addEventListener('shown.bs.modal', function onModalShown() {
                console.log('Modal fully shown, initializing map...');

                // Force map to initialize or refresh
                setTimeout(function() {
                    initMap();
                }, 300);

                // Remove this event listener to prevent multiple initializations
                elements.modal.removeEventListener('shown.bs.modal', onModalShown);
            });

        } catch (error) {
            console.error('Error opening modal:', error);

            // Fallback method if Bootstrap modal fails
            if (elements.modal) {
                elements.modal.style.display = 'block';
                elements.modal.classList.add('show');
                document.body.classList.add('modal-open');

                // Create backdrop
                let backdrop = document.querySelector('.modal-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }

                // Initialize map with delay
                setTimeout(function() {
                    initMap();
                }, 500);
            }
        }
    }

    /**
     * Close the address modal
     */
    function closeModal() {
        console.log('Closing modal...');
        try {
            modal.hide();
        } catch (error) {
            console.error('Error hiding modal:', error);

            // Fallback method if Bootstrap modal fails
            if (elements.modal) {
                elements.modal.style.display = 'none';
                elements.modal.classList.remove('show');
                document.body.classList.remove('modal-open');

                // Remove backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }
    }

    /**
     * Initialize the map
     */
    function initMap() {
        mapLoadAttempts++;

        if (!elements.mapCanvas) {
            console.error('Map canvas element not found, will retry');
            if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
                setTimeout(initMap, 500);
            } else {
                showMapError('Map container not found. Please refresh the page and try again.');
            }
            return;
        }

        // Check if the map container has zero width or height (not visible yet)
        if (elements.mapCanvas.clientWidth === 0 || elements.mapCanvas.clientHeight === 0) {
            console.log('Map container has zero dimensions, waiting for it to be visible...');
            if (mapLoadAttempts <= MAX_MAP_LOAD_ATTEMPTS) {
                setTimeout(initMap, 500);
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
                setTimeout(initMap, 1000);
                return;
            } else {
                hideMapLoading();
                showMapError('Leaflet could not be loaded. Please check your internet connection and try again.');
                return;
            }
        }

        try {
            // Always remove existing map instance if it exists
            if (map) {
                map.remove();
                map = null;
                marker = null;
                console.log('Removed existing map instance');
            }

            // Check if we have saved coordinates
            let initialLocation = defaultLocation;
            if (elements.latitudeInput && elements.longitudeInput &&
                elements.latitudeInput.value && elements.longitudeInput.value) {
                initialLocation = [
                    parseFloat(elements.latitudeInput.value),
                    parseFloat(elements.longitudeInput.value)
                ];
            }

            // Create Leaflet map with better options
            map = L.map(elements.mapCanvas, {
                center: initialLocation,
                zoom: 15,
                zoomControl: false, // We'll add zoom control in a better position
                attributionControl: true
            });

            // Add OpenStreetMap tile layer
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Add zoom control to bottom right
            L.control.zoom({
                position: 'bottomright'
            }).addTo(map);

            // Add geocoder control for searching locations
            const geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                position: 'topright',
                placeholder: 'Search for a location...',
                errorMessage: 'Nothing found.',
                showResultIcons: true
            }).addTo(map);

            // Handle geocoder results
            geocoder.on('markgeocode', function(e) {
                const result = e.geocode || {};
                const latlng = result.center;

                // Update map and marker
                map.setView(latlng, 16);
                marker.setLatLng(latlng);

                // Update latitude and longitude inputs
                if (elements.latitudeInput && elements.longitudeInput) {
                    elements.latitudeInput.value = latlng.lat;
                    elements.longitudeInput.value = latlng.lng;
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

            marker = L.marker(initialLocation, {
                draggable: true,
                icon: markerIcon,
                title: 'Drag to set your location'
            }).addTo(map);

            // Add popup with information
            marker.bindPopup(`
                <div class="info-window">
                    <h5 class="mb-1">Brew & Bake</h5>
                    <p class="mb-1">Babuyan, Palawan, Philippines</p>
                    <p class="mb-0"><a href="tel:+6312345678" class="text-decoration-none">+63 123 456 7890</a></p>
                </div>
            `);

            // Add event listener for marker drag end
            marker.on('dragend', function() {
                const position = marker.getLatLng();
                map.setView(position);

                // Update latitude and longitude inputs
                if (elements.latitudeInput && elements.longitudeInput) {
                    elements.latitudeInput.value = position.lat;
                    elements.longitudeInput.value = position.lng;
                }

                // Update address fields based on location
                updateAddressFromLocation(position);
            });

            // Add event listener for map click
            map.on('click', function(event) {
                const position = event.latlng;
                marker.setLatLng(position);
                map.setView(position);

                // Update latitude and longitude inputs
                if (elements.latitudeInput && elements.longitudeInput) {
                    elements.latitudeInput.value = position.lat;
                    elements.longitudeInput.value = position.lng;
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

                        // Get current location
                        getCurrentLocation();
                    });

                    return container;
                }
            });

            // Add the locate control to the map
            L.control.locate().addTo(map);

        } catch (error) {
            console.error('Error in initMap:', error);
            mapInitialized = false;
            hideMapLoading();
            showMapError('There was a problem initializing the map. Please try again.');
        }
    }

    /**
     * Retry map loading
     */
    function retryMapLoad() {
        hideMapError();
        showMapLoading();

        // Reset map load attempts
        mapLoadAttempts = 0;

        // Reset map initialization state
        mapInitialized = false;

        // If map exists, try to remove it first
        if (map) {
            try {
                map.remove();
                map = null;
                marker = null;
            } catch (e) {
                console.log('Could not remove existing map:', e);
            }
        }

        // Try to initialize the map again with a delay
        setTimeout(function() {
            initMap();
        }, 500);
    }

    /**
     * Get current location using browser geolocation
     */
    function getCurrentLocation() {
        // Show loading indicator on the map
        showMapLoading();

        // Hide any previous errors
        hideMapError();

        if (!mapInitialized) {
            hideMapLoading();
            showAlert('danger', 'Map is not available. Please try again later.', elements.mapAlert);
            return;
        }

        // Clear any previous alerts
        if (elements.mapAlert) {
            elements.mapAlert.innerHTML = '';
        }

        // Show loading state on button
        if (elements.useMyLocationBtn) {
            const originalBtnText = elements.useMyLocationBtn.innerHTML;
            elements.useMyLocationBtn.disabled = true;
            elements.useMyLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Getting location...';

            // Reset button state after 10 seconds (failsafe)
            setTimeout(function() {
                elements.useMyLocationBtn.disabled = false;
                elements.useMyLocationBtn.innerHTML = originalBtnText;
            }, 10000);
        }

        if (navigator.geolocation) {
            // Get current position
            navigator.geolocation.getCurrentPosition(
                // Success callback
                function(position) {
                    const pos = [position.coords.latitude, position.coords.longitude];

                    // Update map and marker
                    map.setView(pos, 16); // Zoom in a bit more for better visibility
                    marker.setLatLng(pos);

                    // Open the popup to show the user their location
                    marker.bindPopup(`
                        <div class="info-window">
                            <h5 class="mb-1">Your Location</h5>
                            <p class="mb-0">Latitude: ${pos[0].toFixed(6)}<br>Longitude: ${pos[1].toFixed(6)}</p>
                        </div>
                    `).openPopup();

                    // Update latitude and longitude inputs
                    if (elements.latitudeInput && elements.longitudeInput) {
                        elements.latitudeInput.value = pos[0];
                        elements.longitudeInput.value = pos[1];
                    }

                    // Update address fields based on location
                    updateAddressFromLocation({lat: pos[0], lng: pos[1]});

                    // Hide loading indicator
                    hideMapLoading();

                    // Show success message
                    showAlert('success', 'Location successfully updated!', elements.mapAlert);

                    // Reset button state
                    if (elements.useMyLocationBtn) {
                        elements.useMyLocationBtn.disabled = false;
                        elements.useMyLocationBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i> Use My Location';
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

                    // Show error message
                    showAlert('danger', errorMessage, elements.mapAlert);

                    // Reset button state
                    if (elements.useMyLocationBtn) {
                        elements.useMyLocationBtn.disabled = false;
                        elements.useMyLocationBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i> Use My Location';
                    }
                },
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            console.error('Geolocation is not supported by this browser.');
            hideMapLoading();

            // Show error message
            showAlert('danger', 'Geolocation is not supported by your browser. Please enter your address manually.', elements.mapAlert);

            // Reset button state
            if (elements.useMyLocationBtn) {
                elements.useMyLocationBtn.disabled = false;
                elements.useMyLocationBtn.innerHTML = '<i class="bi bi-geo-alt me-1"></i> Use My Location';
            }
        }
    }

    /**
     * Update address fields based on location
     */
    function updateAddressFromLocation(position) {
        // Show loading
        showMapLoading();

        // For simplicity, we'll just set some default values
        // In a real implementation, you would use a reverse geocoding service

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
            province = 'Manila';
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
        if (elements.postalCodeInput) {
            elements.postalCodeInput.value = postalCode;
        }

        if (elements.streetAddressInput) {
            elements.streetAddressInput.value = streetAddress;
        }

        if (elements.regionInput) {
            elements.regionInput.value = region;
        }

        if (elements.provinceInput) {
            elements.provinceInput.value = province;
        }

        if (elements.cityInput) {
            elements.cityInput.value = city;
        }

        if (elements.barangayInput) {
            elements.barangayInput.value = barangay;
        }

        // Update location display
        updateLocationDisplay();

        // Hide loading
        hideMapLoading();
    }

    /**
     * Update map based on address fields
     */
    function updateMap() {
        if (!mapInitialized || !map) {
            console.error('Map not initialized');
            return;
        }

        const postalCode = elements.postalCodeInput ? elements.postalCodeInput.value : '';
        const streetAddress = elements.streetAddressInput ? elements.streetAddressInput.value : '';
        const region = elements.regionInput ? elements.regionInput.value : '';
        const province = elements.provinceInput ? elements.provinceInput.value : '';
        const city = elements.cityInput ? elements.cityInput.value : '';
        const barangay = elements.barangayInput ? elements.barangayInput.value : '';

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
        // In a real implementation, you would use a geocoding service

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
        map.setView(location, 15);
        marker.setLatLng(location);

        // Update latitude and longitude inputs
        if (elements.latitudeInput && elements.longitudeInput) {
            elements.latitudeInput.value = location[0];
            elements.longitudeInput.value = location[1];
        }
    }

    /**
     * Initialize location tabs
     */
    function initLocationTabs() {
        if (!elements.regionTabs || !elements.regionTabs.length) return;

        // Set active tab
        function setActiveTab(tab) {
            elements.regionTabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');

            // Show corresponding content
            const tabId = tab.getAttribute('data-tab');
            const tabContents = document.querySelectorAll('.tab-pane');
            tabContents.forEach(content => {
                content.classList.remove('show', 'active');
            });

            const activeContent = document.getElementById(tabId);
            if (activeContent) {
                activeContent.classList.add('show', 'active');
            }
        }

        // Handle tab clicks
        elements.regionTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                setActiveTab(this);
            });
        });

        // Handle region selection
        if (elements.regionContent) {
            elements.regionContent.addEventListener('click', function(e) {
                if (e.target.classList.contains('region-item')) {
                    const region = e.target.textContent.trim();
                    elements.regionInput.value = region;
                    updateLocationDisplay();

                    // Load provinces for the selected region
                    loadProvinces(region);

                    // Switch to province tab
                    const provinceTab = document.querySelector('[data-tab="province-content"]');
                    if (provinceTab) setActiveTab(provinceTab);
                }
            });
        }

        // Handle province selection
        if (elements.provinceContent) {
            elements.provinceContent.addEventListener('click', function(e) {
                if (e.target.classList.contains('province-item')) {
                    const province = e.target.textContent.trim();
                    elements.provinceInput.value = province;
                    updateLocationDisplay();

                    // Load cities for the selected province
                    loadCities(elements.regionInput.value, province);

                    // Switch to city tab
                    const cityTab = document.querySelector('[data-tab="city-content"]');
                    if (cityTab) setActiveTab(cityTab);
                }
            });
        }

        // Handle city selection
        if (elements.cityContent) {
            elements.cityContent.addEventListener('click', function(e) {
                if (e.target.classList.contains('city-item')) {
                    const city = e.target.textContent.trim();
                    elements.cityInput.value = city;
                    updateLocationDisplay();

                    // Load barangays for the selected city
                    loadBarangays(elements.regionInput.value, elements.provinceInput.value, city);

                    // Switch to barangay tab
                    const barangayTab = document.querySelector('[data-tab="barangay-content"]');
                    if (barangayTab) setActiveTab(barangayTab);
                }
            });
        }

        // Handle barangay selection
        if (elements.barangayContent) {
            elements.barangayContent.addEventListener('click', function(e) {
                if (e.target.classList.contains('barangay-item')) {
                    const barangay = e.target.textContent.trim();
                    elements.barangayInput.value = barangay;
                    updateLocationDisplay();

                    // Close the location selector
                    if (elements.locationSelector) {
                        elements.locationSelector.style.display = 'none';
                    }
                }
            });
        }
    }

    /**
     * Load provinces for the selected region
     */
    function loadProvinces(region) {
        if (!elements.provinceContent) return;

        const provinceList = locationData.provinces[region] || [];

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

        elements.provinceContent.innerHTML = html;
    }

    /**
     * Load cities for the selected province
     */
    function loadCities(region, province) {
        if (!elements.cityContent) return;

        const cityList = locationData.cities[province] || [];

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

        elements.cityContent.innerHTML = html;
    }

    /**
     * Load barangays for the selected city
     */
    function loadBarangays(region, province, city) {
        if (!elements.barangayContent) return;

        const barangayList = locationData.barangays[city] || [];

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

        elements.barangayContent.innerHTML = html;
    }

    /**
     * Toggle location selector
     */
    function toggleLocationSelector() {
        if (!elements.locationSelector) return;

        elements.locationSelector.style.display = elements.locationSelector.style.display === 'none' ? 'block' : 'none';

        // Set active tab based on what's already selected
        if (elements.regionInput.value && !elements.provinceInput.value) {
            const provinceTab = document.querySelector('[data-tab="province-content"]');
            if (provinceTab) {
                const event = new Event('click');
                provinceTab.dispatchEvent(event);
            }
        } else if (elements.provinceInput.value && !elements.cityInput.value) {
            const cityTab = document.querySelector('[data-tab="city-content"]');
            if (cityTab) {
                const event = new Event('click');
                cityTab.dispatchEvent(event);
            }
        } else if (elements.cityInput.value && !elements.barangayInput.value) {
            const barangayTab = document.querySelector('[data-tab="barangay-content"]');
            if (barangayTab) {
                const event = new Event('click');
                barangayTab.dispatchEvent(event);
            }
        }
    }

    /**
     * Clear location
     */
    function clearLocation(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (elements.regionInput) elements.regionInput.value = '';
        if (elements.provinceInput) elements.provinceInput.value = '';
        if (elements.cityInput) elements.cityInput.value = '';
        if (elements.barangayInput) elements.barangayInput.value = '';
        if (elements.locationDisplay) elements.locationDisplay.value = '';

        // Hide location selector
        if (elements.locationSelector) {
            elements.locationSelector.style.display = 'none';
        }
    }

    /**
     * Update location display
     */
    function updateLocationDisplay() {
        if (!elements.locationDisplay) return;

        const region = elements.regionInput ? elements.regionInput.value : '';
        const province = elements.provinceInput ? elements.provinceInput.value : '';
        const city = elements.cityInput ? elements.cityInput.value : '';
        const barangay = elements.barangayInput ? elements.barangayInput.value : '';

        let displayText = '';

        if (region) displayText += region;
        if (province) displayText += displayText ? ', ' + province : province;
        if (city) displayText += displayText ? ', ' + city : city;
        if (barangay) displayText += displayText ? ', ' + barangay : barangay;

        elements.locationDisplay.value = displayText;

        // Update map when location changes
        if (mapInitialized) updateMap();
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        // Reset validation state
        resetFormValidation();

        // Validate form
        if (!validateAddressForm()) {
            return false;
        }

        // Get form data
        const formData = new FormData(elements.addressForm);

        // Show loading state on submit button
        const submitBtn = elements.saveAddressBtn;
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
                if (elements.successToast) {
                    const successToast = new bootstrap.Toast(elements.successToast);
                    successToast.show();
                }

                // Close the modal
                closeModal();

                // Reload the page after a short delay to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Show error message
                showAlert('danger', data.message || 'An error occurred while updating your address.', elements.addressFormAlert);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while updating your address. Please try again.', elements.addressFormAlert);

            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    }

    /**
     * Validate the address form
     */
    function validateAddressForm() {
        let isValid = true;

        // Required fields
        const requiredFields = [
            { id: 'full_name', message: 'Please enter your full name' },
            { id: 'modal_phone', message: 'Please enter a valid Philippines mobile number' },
            { id: 'postal_code', message: 'Please enter a valid 4-digit postal code' },
            { id: 'street_address', message: 'Please enter your street address' },
            { id: 'location-display', message: 'Please select your complete location' }
        ];

        // Validate each required field
        requiredFields.forEach(field => {
            const input = document.getElementById(field.id);
            if (!input) return;

            if (!input.value.trim()) {
                markInvalid(input, field.message);
                isValid = false;
            } else {
                markValid(input);
            }
        });

        // Validate phone number format (Philippines mobile)
        const phoneInput = document.getElementById('modal_phone');
        if (phoneInput && phoneInput.value.trim()) {
            // Should start with 9 and have 10 digits total
            const phoneRegex = /^9\d{9}$/;
            if (!phoneRegex.test(phoneInput.value.trim())) {
                markInvalid(phoneInput, 'Please enter a valid Philippines mobile number (e.g., 9171234567)');
                isValid = false;
            }
        }

        // Validate postal code (4 digits)
        const postalCodeInput = document.getElementById('postal_code');
        if (postalCodeInput && postalCodeInput.value.trim()) {
            const postalCodeRegex = /^\d{4}$/;
            if (!postalCodeRegex.test(postalCodeInput.value.trim())) {
                markInvalid(postalCodeInput, 'Please enter a valid 4-digit postal code');
                isValid = false;
            }
        }

        // Validate location is selected
        const regionInput = document.getElementById('selected-region');
        const provinceInput = document.getElementById('selected-province');
        const cityInput = document.getElementById('selected-city');
        const barangayInput = document.getElementById('selected-barangay');
        const locationDisplay = document.getElementById('location-display');

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
            showAlert('danger', 'Please correct the errors in the form before submitting.', elements.addressFormAlert);
        }

        return isValid;
    }

    /**
     * Mark a form field as invalid
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
     */
    function markValid(input) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }

    /**
     * Reset form validation state
     */
    function resetFormValidation() {
        const inputs = document.querySelectorAll('.form-control, .form-select');
        inputs.forEach(input => {
            input.classList.remove('is-invalid', 'is-valid');
        });

        // Clear alert containers
        if (elements.addressFormAlert) elements.addressFormAlert.innerHTML = '';
        if (elements.locationSelectionAlert) elements.locationSelectionAlert.innerHTML = '';
        if (elements.mapAlert) elements.mapAlert.innerHTML = '';
    }

    /**
     * Show map error message
     */
    function showMapError(message) {
        console.error('Map error:', message);

        if (elements.mapError && elements.mapErrorMessage) {
            elements.mapErrorMessage.textContent = message;
            elements.mapError.style.display = 'flex';
        } else {
            // Fallback if the error element doesn't exist
            showAlert('danger', 'Map error: ' + message, elements.mapAlert);
        }

        hideMapLoading();
    }

    /**
     * Hide map error message
     */
    function hideMapError() {
        if (elements.mapError) {
            elements.mapError.style.display = 'none';
        }
    }

    /**
     * Show map loading indicator
     */
    function showMapLoading() {
        if (elements.mapLoading) {
            elements.mapLoading.style.display = 'flex';
        }

        // Hide any error messages while loading
        hideMapError();
    }

    /**
     * Hide map loading indicator
     */
    function hideMapLoading() {
        if (elements.mapLoading) {
            elements.mapLoading.style.display = 'none';
        }
    }

    /**
     * Show an alert message
     */
    function showAlert(type, message, container) {
        if (!container) return;

        // Clear any existing alerts
        container.innerHTML = '';

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi ${type === 'danger' ? 'bi-exclamation-triangle-fill' : type === 'warning' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        container.appendChild(alertDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode === container) {
                try {
                    const bsAlert = new bootstrap.Alert(alertDiv);
                    bsAlert.close();
                } catch (error) {
                    // Fallback if bootstrap Alert is not available
                    alertDiv.classList.remove('show');
                    setTimeout(() => {
                        if (alertDiv.parentNode === container) {
                            container.removeChild(alertDiv);
                        }
                    }, 150);
                }
            }
        }, 5000);
    }

    // Return public methods
    return {
        init: init
    };
})();

// Initialize the address modal manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    AddressModalManager.init();
});