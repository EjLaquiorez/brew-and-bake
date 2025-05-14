<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'name' => 'Test Admin',
        'role' => 'Administrator'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test User Menu - Brew and Bake Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
    <style>
        /* Additional styles for testing */
        .test-container {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .test-sidebar {
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .test-title {
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="test-title">Test User Menu Dropdown</h1>
        
        <div class="test-sidebar">
            <div class="sidebar-footer">
                <?php include 'includes/sidebar-user-menu.php'; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Instructions</h5>
            </div>
            <div class="card-body">
                <p>Click on the user menu above to test the dropdown functionality.</p>
                <p>The dropdown should appear <strong>above</strong> the user menu.</p>
                <p>The dropdown should contain the following items:</p>
                <ul>
                    <li>My Profile</li>
                    <li>Settings</li>
                    <li>Help & Support</li>
                    <li>Logout</li>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'includes/footer-scripts.php'; ?>
</body>
</html>
