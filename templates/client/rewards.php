<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'client') {
    $_SESSION['error'] = "Access denied. Client privileges required.";
    header("Location: ../../views/login.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get user information
$userId = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching user data: " . $e->getMessage();
    $user = [];
}

// Simulate rewards data
$rewardsPoints = rand(50, 500);
$rewardsLevel = "Gold";
$nextLevelPoints = 600;
$availableRewards = [
    [
        'name' => 'Free Brewed Coffee',
        'points' => 50,
        'expires' => date('Y-m-d', strtotime('+30 days')),
        'image' => 'hot-coffee.jpg'
    ],
    [
        'name' => 'Free Pastry with Purchase',
        'points' => 100,
        'expires' => date('Y-m-d', strtotime('+30 days')),
        'image' => 'pastry.jpg'
    ],
    [
        'name' => '50% Off Any Drink',
        'points' => 150,
        'expires' => date('Y-m-d', strtotime('+30 days')),
        'image' => 'cold-coffee.jpg'
    ],
    [
        'name' => 'Free Cake Slice',
        'points' => 200,
        'expires' => date('Y-m-d', strtotime('+30 days')),
        'image' => 'cake.jpg'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Rewards - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="client.php">
                        <i class="bi bi-cup-hot"></i> Brew & Bake
                    </a>
                </div>
                <!-- Main navigation removed -->
                <div class="header-actions">
                    <a href="orders.php" class="cart-icon">
                        <i class="bi bi-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-menu">
                        <a href="profile.php" class="user-icon">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="client.php">Dashboard</a></li>
                                <li><a href="orders.php">My Orders</a></li>
                                <li><a href="profile.php">Profile</a></li>
                                <li><a href="../includes/logout.php">Sign Out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container py-5">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Rewards Header -->
        <div class="rewards-header text-center mb-5">
            <h1>Brew & Bake Rewards</h1>
            <p class="lead">Earn points with every purchase and redeem for free drinks, pastries, and more!</p>
        </div>

        <!-- Rewards Status -->
        <div class="card mb-5">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <div class="rewards-level">
                            <div class="level-badge <?= strtolower($rewardsLevel) ?>">
                                <?= $rewardsLevel ?>
                            </div>
                            <h3 class="mt-3">Welcome, <?= htmlspecialchars($user['name'] ?? 'Member') ?>!</h3>
                            <p>You have <strong><?= $rewardsPoints ?></strong> points</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4>Progress to Next Level</h4>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" style="width: <?= min(100, ($rewardsPoints / $nextLevelPoints) * 100) ?>%"></div>
                        </div>
                        <p class="text-center"><?= $rewardsPoints ?> / <?= $nextLevelPoints ?> points to Platinum Level</p>

                        <div class="rewards-benefits mt-4">
                            <h4>Your Benefits</h4>
                            <ul class="benefits-list">
                                <li><i class="bi bi-check-circle"></i> Free birthday drink</li>
                                <li><i class="bi bi-check-circle"></i> Double points on Mondays</li>
                                <li><i class="bi bi-check-circle"></i> Free refills on brewed coffee</li>
                                <li><i class="bi bi-check-circle"></i> Exclusive member-only offers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Rewards -->
        <h2 class="mb-4">Available Rewards</h2>
        <div class="row">
            <?php foreach ($availableRewards as $reward): ?>
                <div class="col-md-3 mb-4">
                    <div class="reward-card">
                        <div class="reward-image">
                            <img src="../../assets/images/categories/<?= $reward['image'] ?>" alt="<?= $reward['name'] ?>">
                        </div>
                        <div class="reward-info">
                            <h3><?= $reward['name'] ?></h3>
                            <p class="reward-points"><?= $reward['points'] ?> points</p>
                            <p class="reward-expiry">Expires: <?= date('M d, Y', strtotime($reward['expires'])) ?></p>
                            <button class="redeem-btn" <?= $rewardsPoints >= $reward['points'] ? '' : 'disabled' ?>>
                                <?= $rewardsPoints >= $reward['points'] ? 'Redeem Reward' : 'Not Enough Points' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- How It Works -->
        <div class="how-it-works mt-5">
            <h2 class="text-center mb-4">How Rewards Work</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="step-icon">
                        <i class="bi bi-cup"></i>
                    </div>
                    <h3>Order & Earn</h3>
                    <p>Earn 1 point for every ₱10 spent on food and drinks</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <h3>Collect Points</h3>
                    <p>Watch your points add up with each purchase</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="step-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h3>Redeem Rewards</h3>
                    <p>Use your points for free drinks, food, and exclusive offers</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="bi bi-cup-hot"></i> Brew & Bake
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>About Us</h4>
                        <ul>
                            <li><a href="#">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="client.php">Dashboard</a></li>
                            <li><a href="orders.php">My Orders</a></li>
                            <li><a href="menu.php">Menu</a></li>
                            <li><a href="profile.php">Profile</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.querySelector('.user-icon');
            const userDropdown = document.querySelector('.user-dropdown');

            userIcon.addEventListener('click', function(e) {
                e.preventDefault();
                userDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-menu')) {
                    userDropdown.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
