<?php
/**
 * Readable Table Component
 *
 * This component provides a table with improved readability for data display.
 *
 * Usage:
 * include 'templates/includes/components/readable_table.php';
 *
 * Parameters:
 * - $table_headers (array): Array of table headers
 * - $table_data (array): Array of data rows
 * - $table_id (string, optional): ID attribute for the table
 * - $table_class (string, optional): Additional CSS classes for the table
 * - $empty_message (string, optional): Message to display when table is empty
 * - $empty_icon (string, optional): Icon to display when table is empty
 * - $responsive (bool, optional): Whether to make the table responsive
 * - $striped (bool, optional): Whether to use striped rows
 * - $hover (bool, optional): Whether to highlight rows on hover
 */

// Default values
$table_headers = $table_headers ?? [];
$table_data = $table_data ?? [];
$table_id = $table_id ?? 'table-' . uniqid();
$table_class = $table_class ?? '';
$empty_message = $empty_message ?? 'No data available';
$empty_icon = $empty_icon ?? 'bi-table';
$responsive = $responsive ?? true;
$striped = $striped ?? true;
$hover = $hover ?? true;

// Build table classes
$table_classes = ['readable-table'];
if ($striped) $table_classes[] = 'readable-table-striped';
if ($hover) $table_classes[] = 'readable-table-hover';
if (!empty($table_class)) $table_classes[] = $table_class;
$table_class_attr = implode(' ', $table_classes);

// Check if table is empty
$is_empty = empty($table_data);

// Start responsive wrapper if needed
if ($responsive) {
    echo '<div class="readable-table-responsive">';
}

// Display empty state if no data
if ($is_empty) {
    ?>
    <div class="readable-table-empty">
        <div class="readable-table-empty-icon">
            <i class="bi <?= htmlspecialchars($empty_icon) ?>"></i>
        </div>
        <h5 class="readable-table-empty-title">No Data Found</h5>
        <p class="readable-table-empty-message"><?= htmlspecialchars($empty_message) ?></p>
    </div>
    <?php
} else {
    // Display table with data
    ?>
    <table id="<?= htmlspecialchars($table_id) ?>" class="<?= htmlspecialchars($table_class_attr) ?>">
        <thead>
            <tr>
                <?php foreach ($table_headers as $header): ?>
                    <?php
                    // Check if header is an array with additional properties
                    $header_text = is_array($header) ? ($header['text'] ?? '') : $header;
                    $header_class = is_array($header) ? ($header['class'] ?? '') : '';
                    $header_width = is_array($header) ? ($header['width'] ?? '') : '';
                    $header_style = is_array($header) ? ($header['style'] ?? '') : '';

                    // Build style attribute
                    $style_attr = '';
                    if (!empty($header_width)) {
                        $style_attr .= "width: $header_width;";
                    }
                    if (!empty($header_style)) {
                        $style_attr .= $header_style;
                    }
                    ?>
                    <th class="<?= htmlspecialchars($header_class) ?>" <?= !empty($style_attr) ? 'style="' . htmlspecialchars($style_attr) . '"' : '' ?>>
                        <?= htmlspecialchars($header_text) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($table_data as $row): ?>
                <tr>
                    <?php foreach ($row as $key => $cell): ?>
                        <?php
                        // Check if cell is an array with additional properties
                        $cell_content = is_array($cell) ? ($cell['content'] ?? '') : $cell;
                        $cell_class = is_array($cell) ? ($cell['class'] ?? '') : '';
                        $cell_type = is_array($cell) ? ($cell['type'] ?? '') : '';

                        // Add appropriate class based on cell type
                        if ($cell_type === 'numeric') {
                            $cell_class .= ' numeric';
                        } elseif ($cell_type === 'date') {
                            $cell_class .= ' date';
                        } elseif ($cell_type === 'status') {
                            $status_value = is_array($cell) ? ($cell['status'] ?? 'neutral') : 'neutral';
                            $cell_content = '<span class="status-indicator status-' . htmlspecialchars($status_value) . '">' . htmlspecialchars($cell_content) . '</span>';
                        } elseif ($cell_type === 'image') {
                            $image_src = is_array($cell) ? ($cell['src'] ?? '') : '';
                            $image_alt = is_array($cell) ? ($cell['alt'] ?? '') : '';
                            $image_title = is_array($cell) ? ($cell['title'] ?? '') : '';
                            $image_subtitle = is_array($cell) ? ($cell['subtitle'] ?? '') : '';

                            // Ensure we have default values for missing data
                            $image_alt = !empty($image_alt) ? $image_alt : 'Image';
                            $image_title = !empty($image_title) ? $image_title : 'Item';

                            $cell_class .= ' cell-with-image';
                            $cell_content = '';

                            if (!empty($image_src)) {
                                $cell_content .= '<img src="' . htmlspecialchars($image_src) . '" alt="' . htmlspecialchars($image_alt) . '" class="cell-image">';
                            } else {
                                $cell_content .= '<div class="cell-icon"><i class="bi bi-image"></i></div>';
                            }

                            $cell_content .= '<div>';
                            if (!empty($image_title)) {
                                $cell_content .= '<h6 class="cell-title">' . htmlspecialchars($image_title) . '</h6>';
                            }
                            // Only add subtitle if it exists
                            if (!empty($image_subtitle)) {
                                $cell_content .= '<p class="cell-subtitle">' . htmlspecialchars($image_subtitle) . '</p>';
                            }
                            $cell_content .= '</div>';
                        } elseif ($cell_type === 'actions') {
                            $actions = is_array($cell) ? ($cell['actions'] ?? []) : [];
                            $cell_class .= ' actions-cell';
                            $cell_content = '';

                            foreach ($actions as $action) {
                                $action_url = $action['url'] ?? '#';
                                $action_icon = $action['icon'] ?? 'bi-three-dots';
                                $action_text = $action['text'] ?? '';
                                $action_class = $action['class'] ?? '';
                                $action_type = $action['type'] ?? '';

                                if ($action_type === 'edit') {
                                    $action_class .= ' action-btn-edit';
                                } elseif ($action_type === 'delete') {
                                    $action_class .= ' action-btn-delete';
                                }

                                $cell_content .= '<a href="' . htmlspecialchars($action_url) . '" class="action-btn ' . htmlspecialchars($action_class) . '" title="' . htmlspecialchars($action_text) . '">';
                                $cell_content .= '<i class="bi ' . htmlspecialchars($action_icon) . '"></i>';
                                $cell_content .= '</a>';
                            }
                        }
                        ?>
                        <td class="<?= htmlspecialchars($cell_class) ?>">
                            <?= $cell_content ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// End responsive wrapper if needed
if ($responsive) {
    echo '</div>';
}
?>
