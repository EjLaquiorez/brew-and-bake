<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

$messages = [];

// Display all cookies and session data
$sessionData = $_SESSION;
$cookieData = $_COOKIE;

// Test if user is logged in
$isLoggedIn = isLoggedIn();
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

// Add a test button to set a cookie
if (isset($_POST['test_cookie'])) {
    if (setSecureCookie('test_cookie', 'test_value', 3600)) {
        $messages[] = ['type' => 'success', 'message' => '✅ Test cookie has been set successfully!'];
    } else {
        $messages[] = ['type' => 'danger', 'message' => '❌ Failed to set test cookie.'];
    }
    header("Refresh:0");
    exit;
}

// Add a test button to clear cookies
if (isset($_POST['clear_cookies'])) {
    if (deleteCookie('test_cookie')) {
        $messages[] = ['type' => 'success', 'message' => '✅ Test cookie has been cleared successfully!'];
    } else {
        $messages[] = ['type' => 'danger', 'message' => '❌ Failed to clear test cookie.'];
    }
    header("Refresh:0");
    exit;
}

// Test logout
if (isset($_POST['logout'])) {
    if (logout()) {
        $messages[] = ['type' => 'success', 'message' => '✅ Logged out successfully!'];
    } else {
        $messages[] = ['type' => 'danger', 'message' => '❌ Failed to logout.'];
    }
    header("Refresh:0");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cookie Test - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .card { margin-bottom: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .status-badge { font-size: 0.9em; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4">Cookie Test Page</h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['message'] ?></div>
        <?php endforeach; ?>

        <div class="row">
            <!-- Login Status Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Login Status</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($isLoggedIn): ?>
                            <div class="alert alert-success">
                                <h4>✅ User is logged in!</h4>
                                <p>User ID: <strong><?= htmlspecialchars($userId) ?></strong></p>
                                <p>User Role: <span class="badge bg-primary"><?= htmlspecialchars($userRole) ?></span></p>
                            </div>
                            <form method="POST">
                                <button type="submit" name="logout" class="btn btn-danger">Logout</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h4>❌ User is not logged in</h4>
                            </div>
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cookie Test Card -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Test Cookie Functions</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <button type="submit" name="test_cookie" class="btn btn-primary">Set Test Cookie</button>
                        </form>
                        
                        <form method="POST">
                            <button type="submit" name="clear_cookies" class="btn btn-danger">Clear Test Cookie</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Display Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Current Data</h3>
            </div>
            <div class="card-body">
                <h4>Session Data:</h4>
                <pre><?= htmlspecialchars(print_r($sessionData, true)) ?></pre>
                
                <h4>Cookie Data:</h4>
                <pre><?= htmlspecialchars(print_r($cookieData, true)) ?></pre>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 