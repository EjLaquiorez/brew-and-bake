<?php
/**
 * Address Modal Component
 * This file contains the address modal used across the site
 */
?>

<!-- Address Modal (Compact Design) -->
<div class="modal" id="address-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2" style="background-color: #111827; color: white;">
                <h5 class="modal-title d-flex align-items-center fs-6">
                    <i class="bi bi-geo-alt-fill me-2"></i>Manage Delivery Address
                </h5>
                <button type="button" class="btn-close btn-close-white" id="close-address-modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="alert-container"></div>
                <form id="address-form">
                    <!-- Contact Information -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control form-control-sm" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">(+63)</span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="9XX XXX XXXX" pattern="[9][0-9]{9}" maxlength="10" required>
                            </div>
                        </div>
                    </div>

                    <!-- Address Details -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label for="street_address" class="form-label">Street Name, Building, House No.</label>
                            <input type="text" class="form-control form-control-sm" id="street_address" name="street_address" required>
                        </div>
                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control form-control-sm" id="postal_code" name="postal_code" 
                                   pattern="[0-9]{4}" maxlength="4" required>
                        </div>
                    </div>

                    <!-- Map Location -->
                    <div class="mb-3">
                        <label class="form-label">Map Location</label>
                        <div id="map-container" style="position: relative; height: 200px; border-radius: 4px; overflow: hidden;">
                            <!-- Map Canvas -->
                            <div id="map-canvas" style="height: 100%; width: 100%;"></div>
                            
                            <!-- Loading Indicator -->
                            <div id="map-loading" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000;">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading map...</span>
                                    </div>
                                    <div class="mt-2">Loading map...</div>
                                </div>
                            </div>
                            
                            <!-- Error Message -->
                            <div id="map-error" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 1000; flex-direction: column;">
                                <div class="text-danger mb-2"><i class="bi bi-exclamation-triangle-fill fs-1"></i></div>
                                <div class="text-center mb-3">
                                    <div id="map-error-message" class="fw-bold text-danger">Error initializing map</div>
                                    <div class="small text-muted">Please check your internet connection</div>
                                </div>
                                <button id="retry-map-load" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Retry
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Tap on the map to set your location</small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="use-current-location">
                                <i class="bi bi-geo-alt me-1"></i> Use My Location
                            </button>
                        </div>
                        
                        <!-- Hidden coordinates inputs -->
                        <input type="hidden" id="latitude" name="latitude" value="9.7635">
                        <input type="hidden" id="longitude" name="longitude" value="118.7473">
                    </div>

                    <!-- Location Details -->
                    <div class="mb-3">
                        <label class="form-label">Location Details</label>
                        <div class="card card-body p-2">
                            <ul class="nav nav-tabs nav-fill" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#region-tab" type="button" role="tab">Region</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#province-tab" type="button" role="tab">Province</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#city-tab" type="button" role="tab">City</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#barangay-tab" type="button" role="tab">Barangay</button>
                                </li>
                            </ul>
                            <div class="tab-content border border-top-0 p-2" style="max-height: 120px; overflow-y: auto;">
                                <div class="tab-pane fade show active" id="region-tab" role="tabpanel">
                                    <div id="region-content">
                                        <div class="list-group list-group-flush">
                                            <a href="#" class="list-group-item list-group-item-action region-item">Metro Manila</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">Mindanao</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">North Luzon</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">South Luzon</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">Visayas</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="province-tab" role="tabpanel">
                                    <div id="province-content">
                                        <!-- Provinces will be loaded here -->
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="city-tab" role="tabpanel">
                                    <div id="city-content">
                                        <!-- Cities will be loaded here -->
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="barangay-tab" role="tabpanel">
                                    <div id="barangay-content">
                                        <!-- Barangays will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <input type="text" class="form-control form-control-sm" id="location-display" name="location" readonly>
                                <input type="hidden" id="selected-region" name="selected_region">
                                <input type="hidden" id="selected-province" name="selected_province">
                                <input type="hidden" id="selected-city" name="selected_city">
                                <input type="hidden" id="selected-barangay" name="selected_barangay">
                            </div>
                        </div>
                    </div>

                    <!-- Address Options -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <label class="form-label mb-0">Label As:</label>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="address_type" id="home" value="Home" checked>
                                <label class="btn btn-outline-secondary" for="home">Home</label>
                                <input type="radio" class="btn-check" name="address_type" id="work" value="Work">
                                <label class="btn btn-outline-secondary" for="work">Work</label>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">Set as Default</label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-secondary btn-sm me-2" id="cancel-address-btn">
                            <i class="bi bi-x me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-sm" style="background-color: #111827; color: white;">
                            <i class="bi bi-check me-1"></i> Save Address
                        </button>
                    </div>

                    <!-- Success message toast -->
                    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                        <div id="address-success-toast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="bi bi-check-circle me-1"></i> Address saved successfully!
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<!-- Include Address Modal Scripts -->
<script src="../../assets/js/map-utils.js"></script>
<script src="../../assets/js/address-modal.js"></script>
