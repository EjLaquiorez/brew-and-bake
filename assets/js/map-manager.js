/**
 * Map Manager for Brew & Bake
 * Handles the Leaflet map implementation for address selection
 */

// Global variables for map
let leafletMap;
let marker;
let defaultLocation = [9.994295, 118.918419]; // Babuyan, Palawan
let mapInitialized = false;
let mapLoadAttempts = 0;
const MAX_MAP_LOAD_ATTEMPTS = 3;

/**
 * Initialize map when the document is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map events
    initMapEvents();
});

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
    const addressModal = document.getElementById('address-modal');

    // Initialize the map with error handling when modal is shown
    if (addressModal) {
        addressModal.addEventListener('shown.bs.modal', function() {
            try {
                if (typeof leafletMap !== 'undefined' && leafletMap !== null) {
                    leafletMap.invalidateSize();
                } else {
                    initLeafletMap();
                }
            } catch (error) {
                console.error('Error initializing map:', error);
                showMapError('There was a problem loading the map. Please try again.');
            }
        });
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
                    initLeafletMap();
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

    // Check if map is already initialized
    if (mapInitialized && leafletMap) {
        console.log('Map already initialized, refreshing size');
        leafletMap.invalidateSize();
        hideMapLoading();
        return;
    }

    try {
        // Check if we have saved coordinates
        let initialLocation = defaultLocation;
        if (latitudeInput && longitudeInput && latitudeInput.value && longitudeInput.value) {
            initialLocation = [
                parseFloat(latitudeInput.value),
                parseFloat(longitudeInput.value)
            ];
        }

        // Create Leaflet map with better options
        leafletMap = L.map(mapCanvas, {
            center: initialLocation,
            zoom: 15,
            zoomControl: false, // We'll add zoom control in a better position
            attributionControl: true
        });

        // Add OpenStreetMap tile layer
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(leafletMap);

        // Add zoom control to bottom right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(leafletMap);

        // Add geocoder control for searching locations
        const geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            position: 'topright',
            placeholder: 'Search for a location...',
            errorMessage: 'Nothing found.',
            showResultIcons: true
        }).addTo(leafletMap);

        // Handle geocoder results
        geocoder.on('markgeocode', function(e) {
            const result = e.geocode || {};
            const latlng = result.center;

            // Update map and marker
            leafletMap.setView(latlng, 16);
            marker.setLatLng(latlng);

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

        marker = L.marker(initialLocation, {
            draggable: true,
            icon: markerIcon,
            title: 'Drag to set your location'
        }).addTo(leafletMap);

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
            leafletMap.setView(position);

            // Update latitude and longitude inputs
            if (latitudeInput && longitudeInput) {
                latitudeInput.value = position.lat;
                longitudeInput.value = position.lng;
            }

            // Update address fields based on location
            updateAddressFromLocation(position);
        });

        // Add event listener for map click
        leafletMap.on('click', function(event) {
            const position = event.latlng;
            marker.setLatLng(position);
            leafletMap.setView(position);

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
                    const isPrecise = document.getElementById('enable-precise-location').checked;
                    getCurrentLocation(isPrecise);
                });

                return container;
            }
        });

        // Add the locate control to the map
        L.control.locate().addTo(leafletMap);

    } catch (error) {
        console.error('Error in initLeafletMap:', error);
        mapInitialized = false;
        hideMapLoading();
        showMapError('There was a problem initializing the map. Please try again.');
    }
}
