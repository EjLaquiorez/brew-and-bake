<?php
require_once "includes/auth.php";
$isLoggedIn = isLoggedIn();
$userRole = getCurrentUserRole();

require_once "includes/db.php";

// Fetch featured products
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredProducts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew & Bake - Coffee & Bakery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-cup-hot"></i> Brew & Bake
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#menu">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="views/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-2" href="views/register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row min-vh-100 align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Experience the Perfect Blend of Coffee & Bakery</h1>
                    <p class="lead mb-4">Discover our handcrafted coffee and freshly baked goods, made with love and the finest ingredients.</p>
                    <div class="d-flex gap-3">
                        <a href="#menu" class="btn btn-primary btn-lg">View Menu</a>
                        <a href="#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-cup-hot"></i>
                        <h3>Premium Coffee</h3>
                        <p>Handcrafted coffee made from the finest beans, roasted to perfection.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-basket"></i>
                        <h3>Fresh Bakery</h3>
                        <p>Daily baked goods made with love and the finest ingredients.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <i class="bi bi-heart"></i>
                        <h3>Made with Love</h3>
                        <p>Every cup and every bite is prepared with passion and care.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="menu" class="products py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Featured Products</h2>
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-4">
                        <div class="product-card">
                            <?php if (!empty($product['image'])): ?>
                                <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     class="product-image">
                            <?php endif; ?>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                    <a href="views/login.php" class="btn btn-primary">Order Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title mb-4">Our Story</h2>
                    <p class="lead">Welcome to Brew & Bake, where passion meets perfection in every cup and every bite.</p>
                    <p>We started with a simple dream: to create a space where people can enjoy exceptional coffee and delicious baked goods in a warm, welcoming atmosphere. Our journey began with a love for the art of coffee brewing and the joy of baking.</p>
                    <p>Today, we continue to serve our community with the same passion and dedication, using only the finest ingredients and maintaining the highest standards of quality.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about.jpg" alt="Our Coffee Shop" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Contact Us</h2>
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <div class="info-item">
                            <i class="bi bi-geo-alt"></i>
                            <h3>Location</h3>
                            <p>123 Coffee Street, Manila, Philippines</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-clock"></i>
                            <h3>Hours</h3>
                            <p>Monday - Sunday: 7:00 AM - 9:00 PM</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-telephone"></i>
                            <h3>Phone</h3>
                            <p>+63 123 456 7890</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-envelope"></i>
                            <h3>Email</h3>
                            <p>info@brewandbake.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="contact-form">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
                    <p>Experience the perfect blend of coffee and bakery in a warm, welcoming atmosphere.</p>
                </div>
                <div class="col-lg-4">
                    <h4>Quick Links</h4>
                    <ul class="list-unstyled">
                        <li><a href="#menu">Menu</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="views/login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
