<?php
/**
 * Common Head
 * 
 * This file contains common <head> elements that should be included in all pages.
 * It loads essential CSS and meta tags for better readability and SEO.
 */

// Get the current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get site name from settings
$site_name = get_setting('general.site_name', 'Brew & Bake');
$site_description = get_setting('general.site_description', 'Artisanal coffee and baked goods');

// Default title fallback
$page_title = $page_title ?? $site_name;

// Add site name to title if not already included
if (strpos($page_title, $site_name) === false) {
    $page_title .= ' - ' . $site_name;
}
?>
<!-- Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="description" content="<?= htmlspecialchars($page_description ?? $site_description) ?>">
<meta name="author" content="<?= htmlspecialchars($site_name) ?>">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<!-- Title -->
<title><?= htmlspecialchars($page_title) ?></title>

<!-- Favicon -->
<link rel="icon" href="<?= $root_path ?? '' ?>favicon.ico" type="image/x-icon">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Common CSS -->
<link rel="stylesheet" href="<?= $root_path ?? '' ?>assets/css/styles.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $root_path ?? '' ?>assets/css/readability.css?v=<?= time() ?>">

<!-- Page-specific CSS -->
<?php if (isset($page_css) && !empty($page_css)): ?>
    <?php foreach ($page_css as $css): ?>
        <link rel="stylesheet" href="<?= $root_path ?? '' ?><?= $css ?>?v=<?= time() ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- Custom styles -->
<?php if (isset($custom_css)): ?>
<style>
    <?= $custom_css ?>
</style>
<?php endif; ?>
