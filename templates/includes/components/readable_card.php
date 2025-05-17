<?php
/**
 * Readable Card Component
 * 
 * This component provides a card with improved readability for content display.
 * 
 * Usage:
 * include 'templates/includes/components/readable_card.php';
 * 
 * Parameters:
 * - $card_title (string): The title of the card
 * - $card_content (string): The main content of the card
 * - $card_footer (string, optional): Footer content for the card
 * - $card_image (string, optional): URL of an image to display
 * - $card_icon (string, optional): Bootstrap icon class (e.g., 'bi-star')
 * - $card_class (string, optional): Additional CSS classes for the card
 * - $card_header_class (string, optional): Additional CSS classes for the header
 * - $card_body_class (string, optional): Additional CSS classes for the body
 * - $card_footer_class (string, optional): Additional CSS classes for the footer
 * - $card_id (string, optional): ID attribute for the card
 */

// Default values
$card_title = $card_title ?? '';
$card_content = $card_content ?? '';
$card_footer = $card_footer ?? '';
$card_image = $card_image ?? '';
$card_icon = $card_icon ?? '';
$card_class = $card_class ?? '';
$card_header_class = $card_header_class ?? '';
$card_body_class = $card_body_class ?? '';
$card_footer_class = $card_footer_class ?? '';
$card_id = $card_id ?? 'card-' . uniqid();
?>

<div class="card readable-card <?= htmlspecialchars($card_class) ?>" id="<?= htmlspecialchars($card_id) ?>">
    <?php if (!empty($card_image)): ?>
    <div class="card-img-container">
        <img src="<?= htmlspecialchars($card_image) ?>" class="card-img-top" alt="<?= htmlspecialchars($card_title) ?>">
    </div>
    <?php endif; ?>
    
    <?php if (!empty($card_title)): ?>
    <div class="card-header <?= htmlspecialchars($card_header_class) ?>">
        <div class="d-flex align-items-center">
            <?php if (!empty($card_icon)): ?>
            <div class="card-icon me-3">
                <i class="bi <?= htmlspecialchars($card_icon) ?>"></i>
            </div>
            <?php endif; ?>
            
            <h5 class="card-title mb-0"><?= htmlspecialchars($card_title) ?></h5>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card-body <?= htmlspecialchars($card_body_class) ?>">
        <div class="card-text">
            <?php 
            // Check if content is HTML or plain text
            if ($card_content === strip_tags($card_content)) {
                // Plain text - format with paragraphs
                $paragraphs = explode("\n\n", $card_content);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph) !== '') {
                        echo "<p>" . nl2br(htmlspecialchars(trim($paragraph))) . "</p>";
                    }
                }
            } else {
                // HTML content - output as is
                echo $card_content;
            }
            ?>
        </div>
    </div>
    
    <?php if (!empty($card_footer)): ?>
    <div class="card-footer <?= htmlspecialchars($card_footer_class) ?>">
        <?= $card_footer ?>
    </div>
    <?php endif; ?>
</div>
