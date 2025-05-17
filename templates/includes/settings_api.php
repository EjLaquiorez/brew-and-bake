<?php
/**
 * Settings API Endpoint
 * 
 * This file provides an API endpoint for accessing and updating settings via AJAX
 */

// Start session
session_start();

// Include required files
require_once "db.php";
require_once "auth.php";
require_once "settings.php";

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is logged in and has admin privileges
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $response['message'] = 'Access denied. Admin privileges required.';
    echo json_encode($response);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Process request based on method
switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
        
    case 'POST':
        handlePostRequest();
        break;
        
    case 'DELETE':
        handleDeleteRequest();
        break;
        
    default:
        $response['message'] = 'Invalid request method.';
        echo json_encode($response);
        break;
}

/**
 * Handle GET requests to retrieve settings
 */
function handleGetRequest() {
    global $response, $settings;
    
    // Check if a specific setting is requested
    if (isset($_GET['key'])) {
        $key = $_GET['key'];
        $default = $_GET['default'] ?? null;
        
        $value = $settings->get($key, $default);
        
        $response['success'] = true;
        $response['data'] = $value;
        $response['message'] = 'Setting retrieved successfully.';
    }
    // Check if a category is requested
    elseif (isset($_GET['category'])) {
        $category = $_GET['category'];
        
        $categorySettings = $settings->getCategory($category);
        
        $response['success'] = true;
        $response['data'] = $categorySettings;
        $response['message'] = 'Category settings retrieved successfully.';
    }
    // Return all settings if no specific setting or category is requested
    else {
        $allSettings = $settings->getAll();
        
        $response['success'] = true;
        $response['data'] = $allSettings;
        $response['message'] = 'All settings retrieved successfully.';
    }
    
    echo json_encode($response);
}

/**
 * Handle POST requests to update settings
 */
function handlePostRequest() {
    global $response, $settings;
    
    // Get JSON data from request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // If no JSON data, try to get from POST
    if ($data === null) {
        $data = $_POST;
    }
    
    // Check if we're updating a single setting
    if (isset($data['category']) && isset($data['key']) && isset($data['value'])) {
        $category = $data['category'];
        $key = $data['key'];
        $value = $data['value'];
        $type = $data['type'] ?? null;
        $is_public = isset($data['is_public']) ? (bool)$data['is_public'] : null;
        $description = $data['description'] ?? null;
        
        $result = $settings->set($category, $key, $value, $type, $is_public, $description);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Setting updated successfully.';
        } else {
            $response['message'] = 'Failed to update setting.';
        }
    }
    // Check if we're updating multiple settings
    elseif (isset($data['settings']) && is_array($data['settings'])) {
        $updateCount = 0;
        $errorCount = 0;
        
        foreach ($data['settings'] as $setting) {
            if (isset($setting['category']) && isset($setting['key']) && isset($setting['value'])) {
                $category = $setting['category'];
                $key = $setting['key'];
                $value = $setting['value'];
                $type = $setting['type'] ?? null;
                $is_public = isset($setting['is_public']) ? (bool)$setting['is_public'] : null;
                $description = $setting['description'] ?? null;
                
                $result = $settings->set($category, $key, $value, $type, $is_public, $description);
                
                if ($result) {
                    $updateCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
        
        if ($errorCount === 0) {
            $response['success'] = true;
            $response['message'] = "All settings updated successfully ($updateCount settings).";
        } else {
            $response['success'] = $updateCount > 0;
            $response['message'] = "Updated $updateCount settings with $errorCount errors.";
        }
    }
    // Check if we're updating a category
    elseif (isset($data['category']) && isset($data['settings']) && is_array($data['settings'])) {
        $category = $data['category'];
        $updateCount = 0;
        $errorCount = 0;
        
        foreach ($data['settings'] as $key => $value) {
            $result = $settings->set($category, $key, $value);
            
            if ($result) {
                $updateCount++;
            } else {
                $errorCount++;
            }
        }
        
        if ($errorCount === 0) {
            $response['success'] = true;
            $response['message'] = "All $category settings updated successfully ($updateCount settings).";
        } else {
            $response['success'] = $updateCount > 0;
            $response['message'] = "Updated $updateCount $category settings with $errorCount errors.";
        }
    }
    // Check if we're clearing the cache
    elseif (isset($data['action']) && $data['action'] === 'clear_cache') {
        $settings->clearCache();
        
        $response['success'] = true;
        $response['message'] = 'Settings cache cleared successfully.';
    }
    else {
        $response['message'] = 'Invalid request data.';
    }
    
    echo json_encode($response);
}

/**
 * Handle DELETE requests to delete settings
 */
function handleDeleteRequest() {
    global $response, $settings;
    
    // Get JSON data from request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // If no JSON data, try to get from query parameters
    if ($data === null) {
        if (isset($_GET['category']) && isset($_GET['key'])) {
            $data = [
                'category' => $_GET['category'],
                'key' => $_GET['key']
            ];
        }
    }
    
    // Check if we have the required data
    if (isset($data['category']) && isset($data['key'])) {
        $category = $data['category'];
        $key = $data['key'];
        
        $result = $settings->delete($category, $key);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Setting deleted successfully.';
        } else {
            $response['message'] = 'Failed to delete setting.';
        }
    } else {
        $response['message'] = 'Invalid request data.';
    }
    
    echo json_encode($response);
}
?>
