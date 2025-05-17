<?php
/**
 * Readable Form Component
 * 
 * This component provides a form with improved readability and consistent styling.
 * 
 * Usage:
 * include 'templates/includes/components/readable_form.php';
 * 
 * Parameters:
 * - $form_action (string): The form action URL
 * - $form_method (string, optional): The form method (default: 'post')
 * - $form_id (string, optional): ID attribute for the form
 * - $form_class (string, optional): Additional CSS classes for the form
 * - $form_enctype (string, optional): Enctype attribute for file uploads
 * - $form_fields (array): Array of form field configurations
 * - $form_buttons (array, optional): Array of form button configurations
 * - $form_title (string, optional): Title for the form
 * - $form_description (string, optional): Description for the form
 */

// Default values
$form_action = $form_action ?? '';
$form_method = $form_method ?? 'post';
$form_id = $form_id ?? 'form-' . uniqid();
$form_class = $form_class ?? '';
$form_enctype = $form_enctype ?? '';
$form_fields = $form_fields ?? [];
$form_buttons = $form_buttons ?? [];
$form_title = $form_title ?? '';
$form_description = $form_description ?? '';

// Enctype attribute
$enctype_attr = !empty($form_enctype) ? ' enctype="' . htmlspecialchars($form_enctype) . '"' : '';
?>

<form action="<?= htmlspecialchars($form_action) ?>" method="<?= htmlspecialchars($form_method) ?>" 
      id="<?= htmlspecialchars($form_id) ?>" class="readable-form <?= htmlspecialchars($form_class) ?>"<?= $enctype_attr ?>>
    
    <?php if (!empty($form_title) || !empty($form_description)): ?>
    <div class="form-header mb-4">
        <?php if (!empty($form_title)): ?>
            <h3 class="form-title"><?= htmlspecialchars($form_title) ?></h3>
        <?php endif; ?>
        
        <?php if (!empty($form_description)): ?>
            <p class="form-description text-secondary"><?= htmlspecialchars($form_description) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="form-body">
        <?php foreach ($form_fields as $field): ?>
            <?php
            // Extract field properties
            $field_type = $field['type'] ?? 'text';
            $field_name = $field['name'] ?? '';
            $field_id = $field['id'] ?? $field_name;
            $field_label = $field['label'] ?? '';
            $field_placeholder = $field['placeholder'] ?? '';
            $field_value = $field['value'] ?? '';
            $field_required = isset($field['required']) && $field['required'] ? true : false;
            $field_readonly = isset($field['readonly']) && $field['readonly'] ? true : false;
            $field_disabled = isset($field['disabled']) && $field['disabled'] ? true : false;
            $field_class = $field['class'] ?? '';
            $field_help = $field['help'] ?? '';
            $field_options = $field['options'] ?? [];
            $field_cols = $field['cols'] ?? 12;
            $field_rows = $field['rows'] ?? 3;
            $field_min = $field['min'] ?? '';
            $field_max = $field['max'] ?? '';
            $field_step = $field['step'] ?? '';
            $field_pattern = $field['pattern'] ?? '';
            $field_autocomplete = $field['autocomplete'] ?? '';
            $field_prepend = $field['prepend'] ?? '';
            $field_append = $field['append'] ?? '';
            $field_wrapper_class = $field['wrapper_class'] ?? '';
            
            // Required attribute
            $required_attr = $field_required ? ' required' : '';
            $required_mark = $field_required ? ' <span class="text-danger">*</span>' : '';
            
            // Readonly attribute
            $readonly_attr = $field_readonly ? ' readonly' : '';
            
            // Disabled attribute
            $disabled_attr = $field_disabled ? ' disabled' : '';
            
            // Min, max, step attributes
            $min_attr = $field_min !== '' ? ' min="' . htmlspecialchars($field_min) . '"' : '';
            $max_attr = $field_max !== '' ? ' max="' . htmlspecialchars($field_max) . '"' : '';
            $step_attr = $field_step !== '' ? ' step="' . htmlspecialchars($field_step) . '"' : '';
            
            // Pattern attribute
            $pattern_attr = $field_pattern !== '' ? ' pattern="' . htmlspecialchars($field_pattern) . '"' : '';
            
            // Autocomplete attribute
            $autocomplete_attr = $field_autocomplete !== '' ? ' autocomplete="' . htmlspecialchars($field_autocomplete) . '"' : '';
            
            // Determine if field has input group
            $has_input_group = !empty($field_prepend) || !empty($field_append);
            ?>
            
            <div class="mb-3 form-group <?= htmlspecialchars($field_wrapper_class) ?>">
                <?php if (!empty($field_label) && $field_type !== 'checkbox' && $field_type !== 'radio'): ?>
                    <label for="<?= htmlspecialchars($field_id) ?>" class="form-label">
                        <?= htmlspecialchars($field_label) ?><?= $required_mark ?>
                    </label>
                <?php endif; ?>
                
                <?php if ($field_type === 'text' || $field_type === 'email' || $field_type === 'password' || 
                          $field_type === 'number' || $field_type === 'tel' || $field_type === 'url' || 
                          $field_type === 'date' || $field_type === 'time' || $field_type === 'datetime-local' || 
                          $field_type === 'month' || $field_type === 'week' || $field_type === 'color'): ?>
                    
                    <?php if ($has_input_group): ?>
                        <div class="input-group">
                            <?php if (!empty($field_prepend)): ?>
                                <span class="input-group-text"><?= $field_prepend ?></span>
                            <?php endif; ?>
                            
                            <input type="<?= htmlspecialchars($field_type) ?>" 
                                   class="form-control <?= htmlspecialchars($field_class) ?>" 
                                   id="<?= htmlspecialchars($field_id) ?>" 
                                   name="<?= htmlspecialchars($field_name) ?>" 
                                   value="<?= htmlspecialchars($field_value) ?>" 
                                   placeholder="<?= htmlspecialchars($field_placeholder) ?>"
                                   <?= $required_attr ?><?= $readonly_attr ?><?= $disabled_attr ?>
                                   <?= $min_attr ?><?= $max_attr ?><?= $step_attr ?>
                                   <?= $pattern_attr ?><?= $autocomplete_attr ?>>
                            
                            <?php if (!empty($field_append)): ?>
                                <span class="input-group-text"><?= $field_append ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <input type="<?= htmlspecialchars($field_type) ?>" 
                               class="form-control <?= htmlspecialchars($field_class) ?>" 
                               id="<?= htmlspecialchars($field_id) ?>" 
                               name="<?= htmlspecialchars($field_name) ?>" 
                               value="<?= htmlspecialchars($field_value) ?>" 
                               placeholder="<?= htmlspecialchars($field_placeholder) ?>"
                               <?= $required_attr ?><?= $readonly_attr ?><?= $disabled_attr ?>
                               <?= $min_attr ?><?= $max_attr ?><?= $step_attr ?>
                               <?= $pattern_attr ?><?= $autocomplete_attr ?>>
                    <?php endif; ?>
                    
                <?php elseif ($field_type === 'textarea'): ?>
                    <textarea class="form-control <?= htmlspecialchars($field_class) ?>" 
                              id="<?= htmlspecialchars($field_id) ?>" 
                              name="<?= htmlspecialchars($field_name) ?>" 
                              placeholder="<?= htmlspecialchars($field_placeholder) ?>"
                              rows="<?= htmlspecialchars($field_rows) ?>"
                              <?= $required_attr ?><?= $readonly_attr ?><?= $disabled_attr ?>
                              <?= $autocomplete_attr ?>><?= htmlspecialchars($field_value) ?></textarea>
                    
                <?php elseif ($field_type === 'select'): ?>
                    <select class="form-select <?= htmlspecialchars($field_class) ?>" 
                            id="<?= htmlspecialchars($field_id) ?>" 
                            name="<?= htmlspecialchars($field_name) ?>"
                            <?= $required_attr ?><?= $disabled_attr ?>>
                        
                        <?php if (!empty($field_placeholder)): ?>
                            <option value="" <?= empty($field_value) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($field_placeholder) ?>
                            </option>
                        <?php endif; ?>
                        
                        <?php foreach ($field_options as $option_value => $option_label): ?>
                            <?php
                            // Check if option is a group
                            if (is_array($option_label) && isset($option_label['group'])): 
                            ?>
                                <optgroup label="<?= htmlspecialchars($option_value) ?>">
                                    <?php foreach ($option_label['options'] as $group_option_value => $group_option_label): ?>
                                        <option value="<?= htmlspecialchars($group_option_value) ?>" 
                                                <?= $field_value == $group_option_value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($group_option_label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php else: ?>
                                <option value="<?= htmlspecialchars($option_value) ?>" 
                                        <?= $field_value == $option_value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option_label) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    
                <?php elseif ($field_type === 'checkbox'): ?>
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input <?= htmlspecialchars($field_class) ?>" 
                               id="<?= htmlspecialchars($field_id) ?>" 
                               name="<?= htmlspecialchars($field_name) ?>" 
                               value="1" 
                               <?= $field_value ? 'checked' : '' ?>
                               <?= $required_attr ?><?= $disabled_attr ?>>
                        
                        <label class="form-check-label" for="<?= htmlspecialchars($field_id) ?>">
                            <?= htmlspecialchars($field_label) ?><?= $required_mark ?>
                        </label>
                    </div>
                    
                <?php elseif ($field_type === 'radio'): ?>
                    <div class="mb-2"><?= htmlspecialchars($field_label) ?><?= $required_mark ?></div>
                    
                    <?php foreach ($field_options as $option_value => $option_label): ?>
                        <div class="form-check">
                            <input type="radio" 
                                   class="form-check-input <?= htmlspecialchars($field_class) ?>" 
                                   id="<?= htmlspecialchars($field_id . '_' . $option_value) ?>" 
                                   name="<?= htmlspecialchars($field_name) ?>" 
                                   value="<?= htmlspecialchars($option_value) ?>" 
                                   <?= $field_value == $option_value ? 'checked' : '' ?>
                                   <?= $required_attr ?><?= $disabled_attr ?>>
                            
                            <label class="form-check-label" for="<?= htmlspecialchars($field_id . '_' . $option_value) ?>">
                                <?= htmlspecialchars($option_label) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    
                <?php elseif ($field_type === 'hidden'): ?>
                    <input type="hidden" 
                           id="<?= htmlspecialchars($field_id) ?>" 
                           name="<?= htmlspecialchars($field_name) ?>" 
                           value="<?= htmlspecialchars($field_value) ?>">
                <?php endif; ?>
                
                <?php if (!empty($field_help)): ?>
                    <div class="form-text text-muted"><?= htmlspecialchars($field_help) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($form_buttons)): ?>
    <div class="form-footer mt-4">
        <?php foreach ($form_buttons as $button): ?>
            <?php
            // Extract button properties
            $button_type = $button['type'] ?? 'submit';
            $button_name = $button['name'] ?? '';
            $button_id = $button['id'] ?? $button_name;
            $button_text = $button['text'] ?? 'Submit';
            $button_class = $button['class'] ?? 'btn-primary';
            $button_disabled = isset($button['disabled']) && $button['disabled'] ? true : false;
            $button_icon = $button['icon'] ?? '';
            
            // Disabled attribute
            $disabled_attr = $button_disabled ? ' disabled' : '';
            ?>
            
            <button type="<?= htmlspecialchars($button_type) ?>" 
                    class="btn <?= htmlspecialchars($button_class) ?>" 
                    id="<?= htmlspecialchars($button_id) ?>" 
                    name="<?= htmlspecialchars($button_name) ?>"
                    <?= $disabled_attr ?>>
                <?php if (!empty($button_icon)): ?>
                    <i class="bi <?= htmlspecialchars($button_icon) ?> me-1"></i>
                <?php endif; ?>
                <?= htmlspecialchars($button_text) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</form>
