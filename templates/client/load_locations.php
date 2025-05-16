<?php
session_start();
require_once "../includes/auth.php";

// Security check
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to access location data.'
    ]);
    exit;
}

// Define the Philippines regions
$regions = [
    'Metro Manila',
    'North Luzon',
    'South Luzon',
    'Visayas',
    'Mindanao'
];

// Define provinces by region (simplified for demo)
$provinces = [
    'Metro Manila' => ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Taguig'],
    'North Luzon' => ['Batanes', 'Cagayan', 'Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan'],
    'South Luzon' => ['Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal', 'Palawan'],
    'Visayas' => ['Cebu', 'Bohol', 'Leyte', 'Negros Occidental', 'Negros Oriental', 'Iloilo'],
    'Mindanao' => ['Davao', 'Zamboanga', 'Misamis Oriental', 'Bukidnon', 'South Cotabato', 'Maguindanao']
];

// Define cities by province (simplified for demo)
$cities = [
    'Manila' => ['Manila City'],
    'Quezon City' => ['Quezon City'],
    'Makati' => ['Makati City'],
    'Pasig' => ['Pasig City'],
    'Taguig' => ['Taguig City'],
    'Batanes' => ['Basco', 'Itbayat', 'Ivana', 'Mahatao', 'Sabtang', 'Uyugan'],
    'Palawan' => ['Puerto Princesa City', 'El Nido', 'Coron', 'Brooke\'s Point', 'Taytay'],
    'Cebu' => ['Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay', 'Danao'],
    'Davao' => ['Davao City', 'Tagum', 'Digos', 'Panabo', 'Mati']
    // Add more cities as needed
];

// Define barangays by city (simplified for demo)
$barangays = [
    'Puerto Princesa City' => ['Bancao-Bancao', 'Irawan', 'San Pedro', 'Sta. Monica', 'Tiniguiban'],
    'Cebu City' => ['Lahug', 'Mabolo', 'Talamban', 'Guadalupe', 'Pardo'],
    'Davao City' => ['Poblacion', 'Talomo', 'Buhangin', 'Toril', 'Bunawan']
    // Add more barangays as needed
];

// Get request type
$type = $_GET['type'] ?? '';
$region = $_GET['region'] ?? '';
$province = $_GET['province'] ?? '';
$city = $_GET['city'] ?? '';

// Return data based on request type
switch ($type) {
    case 'regions':
        echo json_encode([
            'success' => true,
            'data' => $regions
        ]);
        break;
        
    case 'provinces':
        if (empty($region) || !isset($provinces[$region])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid region selected.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $provinces[$region]
        ]);
        break;
        
    case 'cities':
        if (empty($province) || !isset($cities[$province])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid province selected.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $cities[$province]
        ]);
        break;
        
    case 'barangays':
        if (empty($city) || !isset($barangays[$city])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid city selected or no barangays available.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $barangays[$city]
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request type.'
        ]);
}
?>
