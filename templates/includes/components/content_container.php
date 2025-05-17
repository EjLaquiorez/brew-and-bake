<?php
/**
 * Content Container Component
 * 
 * This component provides a standardized container for content with improved readability.
 * It can be used to wrap text-heavy content sections for better reading experience.
 * 
 * Usage:
 * include 'templates/includes/components/content_container.php';
 * 
 * Parameters:
 * - $content_title (string): The title of the content section
 * - $content_subtitle (string, optional): A subtitle or description
 * - $content_class (string, optional): Additional CSS classes for the container
 * - $content_id (string, optional): ID attribute for the container
 * - $max_width (string, optional): Maximum width of the container (default: '70ch')
 * - $content_start (bool, optional): Set to true to start the container (default: true)
 * - $content_end (bool, optional): Set to true to end the container (default: false)
 */

// Default values
$content_title = $content_title ?? '';
$content_subtitle = $content_subtitle ?? '';
$content_class = $content_class ?? '';
$content_id = $content_id ?? '';
$max_width = $max_width ?? '70ch';
$content_start = $content_start ?? true;
$content_end = $content_end ?? false;

// Generate a random ID if not provided
if (empty($content_id) && $content_start) {
    $content_id = 'content-' . uniqid();
}

// Start the container
if ($content_start):
?>
<div class="readable-container <?= htmlspecialchars($content_class) ?>" 
     id="<?= htmlspecialchars($content_id) ?>" 
     style="max-width: <?= htmlspecialchars($max_width) ?>;">
    
    <?php if (!empty($content_title)): ?>
    <div class="content-header mb-4">
        <h2 class="content-title"><?= htmlspecialchars($content_title) ?></h2>
        
        <?php if (!empty($content_subtitle)): ?>
        <p class="content-subtitle text-secondary"><?= htmlspecialchars($content_subtitle) ?></p>
        <?php endif; ?>
        
        <hr class="content-divider">
    </div>
    <?php endif; ?>
    
    <div class="content-body">
<?php
endif;

// End the container
if ($content_end):
?>
    </div><!-- /.content-body -->
</div><!-- /.readable-container -->
<?php
endif;
?>
