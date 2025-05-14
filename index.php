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
    <title>Brew & Bake - Premium Coffee House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <!-- Add animation library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Add Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        <a class="nav-link" href="#">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#menu">MENU</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">ABOUT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">CONTACT</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center ms-4">
                    <a href="views/login.php" class="text-light position-relative me-3 cart-icon">
                        <i class="bi bi-cart3"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-badge">0</span>
                    </a>
                    <a href="views/login.php" class="btn btn-primary-custom btn-sm">LOGIN</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with image slider -->
    <section class="hero">
        <!-- Hero Slides -->
        <div class="hero-slide hero-slide-1 active"></div>
        <div class="hero-slide hero-slide-2"></div>
        <div class="hero-slide hero-slide-3"></div>

        <div class="container" style="position: relative; z-index: 2;">
            <div class="row min-vh-100 align-items-center">
                <div class="col-lg-6 animate__animated animate__fadeInLeft">
                    <div class="hero-content p-4" style="background-color: rgba(0,0,0,0.5); border-radius: 10px;">
                        <h1>PREMIUM COFFEE<br>& FRESH BAKERY</h1>
                        <p class="mb-4">Experience the perfect blend of premium coffee and freshly baked goods, crafted with passion and the finest ingredients.</p>
                        <div class="d-flex gap-3">
                            <a href="#menu" class="btn btn-primary-custom">EXPLORE MENU</a>
                            <a href="#about" class="btn btn-outline-light">LEARN MORE</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Dot Navigation -->
        <div class="dot-nav">
            <div class="dot active" data-slide="0"></div>
            <div class="dot" data-slide="1"></div>
            <div class="dot" data-slide="2"></div>
        </div>
    </section>

    <!-- Contact Us Button -->
    <div class="position-fixed end-0 top-50 translate-middle-y" style="z-index: 1000;">
        <a href="#contact" class="btn btn-primary-custom py-2 px-3 rounded-start">
            <i class="bi bi-envelope-fill me-2"></i>CONTACT
        </a>
    </div>

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
                                    <a href="views/login.php" class="btn btn-primary-custom">Order Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Parallax Quote Section -->
    <section class="parallax-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="display-4 mb-4">"Life begins after coffee"</h2>
                    <p class="lead mb-4">Every cup tells a story. What's yours?</p>
                    <a href="views/products.php" class="btn btn-secondary-custom btn-lg">Discover Our Menu</a>
                </div>
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
                        <button type="submit" class="btn btn-accent-custom">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">What Our Customers Say</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card p-4 shadow-sm rounded">
                        <div class="stars mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="mb-3">"The best coffee I've ever had! Their pastries are amazing too. This is my go-to spot every morning."</p>
                        <div class="d-flex align-items-center">
                            <div class="testimonial-avatar me-3">
                                <i class="bi bi-person-circle fs-1"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Maria Santos</h5>
                                <small class="text-muted">Regular Customer</small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add 2 more testimonials with similar structure -->
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
                    <p>Experience the perfect blend of premium coffee and freshly baked goods in a modern, welcoming atmosphere.</p>
                </div>
                <div class="col-lg-4">
                    <h4>QUICK LINKS</h4>
                    <ul class="list-unstyled">
                        <li><a href="#">HOME</a></li>
                        <li><a href="#menu">MENU</a></li>
                        <li><a href="#about">ABOUT</a></li>
                        <li><a href="#contact">CONTACT</a></li>
                        <li><a href="views/login.php">LOGIN</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4>FOLLOW US</h4>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4" style="border-color: rgba(255,255,255,0.1);">
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
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Hero Slider functionality
        const heroSlides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.dot-nav .dot');
        let currentSlide = 0;
        let slideInterval;

        // Function to change slide
        function changeSlide(index) {
            // Remove active class from all slides and dots
            heroSlides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            // Add active class to current slide and dot
            heroSlides[index].classList.add('active');
            dots[index].classList.add('active');

            // Update current slide index
            currentSlide = index;
        }

        // Auto slide change
        function startSlideShow() {
            slideInterval = setInterval(() => {
                let nextSlide = (currentSlide + 1) % heroSlides.length;
                changeSlide(nextSlide);
            }, 5000); // Change slide every 5 seconds
        }

        // Initialize slideshow
        startSlideShow();

        // Dot navigation click event
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                // Clear the interval to prevent conflicts
                clearInterval(slideInterval);

                // Change to the clicked slide
                changeSlide(index);

                // Restart the slideshow
                startSlideShow();
            });
        });

        // Add scroll reveal effect
        window.addEventListener('DOMContentLoaded', (event) => {
            const animateElements = document.querySelectorAll('.feature-card, .product-card, .testimonial-card');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate__animated', 'animate__fadeIn');
                        observer.unobserve(entry.target);
                    }
                });
            }, {threshold: 0.2});

            animateElements.forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
