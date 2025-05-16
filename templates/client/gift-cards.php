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

// Simulate gift card designs
$giftCardDesigns = [
    [
        'id' => 1,
        'name' => 'Coffee Time',
        'image' => 'gift-card-1.jpg',
        'description' => 'Perfect for coffee lovers'
    ],
    [
        'id' => 2,
        'name' => 'Sweet Treats',
        'image' => 'gift-card-2.jpg',
        'description' => 'For those with a sweet tooth'
    ],
    [
        'id' => 3,
        'name' => 'Birthday Celebration',
        'image' => 'gift-card-3.jpg',
        'description' => 'Make their birthday special'
    ],
    [
        'id' => 4,
        'name' => 'Thank You',
        'image' => 'gift-card-4.jpg',
        'description' => 'Show your appreciation'
    ],
    [
        'id' => 5,
        'name' => 'Holiday Cheer',
        'image' => 'gift-card-5.jpg',
        'description' => 'Spread holiday joy'
    ],
    [
        'id' => 6,
        'name' => 'Congratulations',
        'image' => 'gift-card-6.jpg',
        'description' => 'Celebrate their achievement'
    ]
];

// Simulate gift card amounts
$giftCardAmounts = [100, 200, 500, 1000, 2000, 5000];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Gift Cards - Brew & Bake</title>
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

        <!-- Gift Cards Header -->
        <div class="gift-cards-header text-center mb-5">
            <h1>Brew & Bake Gift Cards</h1>
            <p class="lead">The perfect gift for coffee and pastry lovers</p>
        </div>

        <!-- Gift Card Form -->
        <div class="card mb-5">
            <div class="card-body">
                <h2 class="card-title mb-4">Create a Gift Card</h2>
                <form id="giftCardForm">
                    <div class="row">
                        <!-- Step 1: Choose Design -->
                        <div class="col-md-6 mb-4">
                            <h3 class="h5 mb-3">1. Choose a Design</h3>
                            <div class="gift-card-designs">
                                <div class="row">
                                    <?php foreach ($giftCardDesigns as $index => $design): ?>
                                        <div class="col-md-4 col-6 mb-3">
                                            <div class="gift-card-design-option">
                                                <input type="radio" name="design" id="design-<?= $design['id'] ?>" value="<?= $design['id'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                                <label for="design-<?= $design['id'] ?>">
                                                    <div class="design-preview">
                                                        <img src="../../assets/images/gift-cards/<?= $design['image'] ?>" alt="<?= $design['name'] ?>">
                                                    </div>
                                                    <span class="design-name"><?= $design['name'] ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Choose Amount -->
                        <div class="col-md-6 mb-4">
                            <h3 class="h5 mb-3">2. Choose an Amount</h3>
                            <div class="gift-card-amounts">
                                <div class="row">
                                    <?php foreach ($giftCardAmounts as $index => $amount): ?>
                                        <div class="col-md-4 col-6 mb-3">
                                            <div class="gift-card-amount-option">
                                                <input type="radio" name="amount" id="amount-<?= $amount ?>" value="<?= $amount ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                                <label for="amount-<?= $amount ?>">
                                                    <span class="amount-value">₱<?= number_format($amount) ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Step 3: Recipient Information -->
                            <h3 class="h5 mb-3 mt-4">3. Recipient Information</h3>
                            <div class="mb-3">
                                <label for="recipient-name" class="form-label">Recipient's Name</label>
                                <input type="text" class="form-control" id="recipient-name" required>
                            </div>
                            <div class="mb-3">
                                <label for="recipient-email" class="form-label">Recipient's Email</label>
                                <input type="email" class="form-control" id="recipient-email" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Personal Message (Optional)</label>
                                <textarea class="form-control" id="message" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                <i class="bi bi-gift"></i> Purchase Gift Card
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gift Card Benefits -->
        <div class="gift-card-benefits mb-5">
            <h2 class="text-center mb-4">Why Choose Our Gift Cards?</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="benefit-icon">
                        <i class="bi bi-envelope-paper"></i>
                    </div>
                    <h3>Digital Delivery</h3>
                    <p>Sent instantly to recipient's email</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="benefit-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3>No Expiration</h3>
                    <p>Gift cards never expire</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="benefit-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h3>Use Anywhere</h3>
                    <p>Valid at all Brew & Bake locations</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2 class="text-center mb-4">Frequently Asked Questions</h2>
            <div class="accordion" id="giftCardFAQ">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I redeem a gift card?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#giftCardFAQ">
                        <div class="accordion-body">
                            Gift cards can be redeemed at any Brew & Bake location. Simply present the digital gift card on your phone or a printed copy at checkout.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Can I check my gift card balance?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#giftCardFAQ">
                        <div class="accordion-body">
                            Yes, you can check your gift card balance online or at any Brew & Bake location.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Can I reload my gift card?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#giftCardFAQ">
                        <div class="accordion-body">
                            Yes, gift cards can be reloaded at any Brew & Bake location or online through your account.
                        </div>
                    </div>
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

            // Gift card form submission
            document.getElementById('giftCardForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Gift card purchase functionality will be implemented soon!');
            });
        });
    </script>
</body>
</html>
