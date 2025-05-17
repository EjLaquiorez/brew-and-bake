/**
 * Map Utilities for Brew & Bake
 * Helper functions for the Leaflet map implementation
 */

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
                leafletMap.setView(pos, 16); // Zoom in a bit more for better visibility
                marker.setLatLng(pos);

                // Open the popup to show the user their location
                marker.bindPopup(`
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
