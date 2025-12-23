<?php
/**
 * ============================================================================
 * REUSABLE UI COMPONENTS LIBRARY
 * ============================================================================
 * File: panel/components/ui_components.php
 * 
 * Purpose: Provides standardized, reusable UI components for consistency
 * across all module pages.
 * 
 * Usage: Include this file in your module pages:
 * require_once ROOT_PATH . '/panel/components/ui_components.php';
 */

// ============================================================================
// 1. BUTTON COMPONENTS
// ============================================================================

/**
 * Render a standardized button
 * 
 * @param string $label Button text
 * @param string $url Target URL
 * @param string $type Button type (primary, secondary, danger, success, warning)
 * @param string $icon Icon class (optional)
 * @param array $attributes Additional HTML attributes
 * @return string HTML button element
 */
function renderButton($label, $url = '#', $type = 'primary', $icon = null, $attributes = []) {
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        $attr_string .= " $key=\"" . htmlspecialchars($value) . "\"";
    }
    
    $icon_html = $icon ? "<i class='icon-$icon'></i> " : '';
    $class = "btn btn-$type";
    
    return "<a href=\"" . htmlspecialchars($url) . "\" class=\"$class\"$attr_string>$icon_html$label</a>";
}

/**
 * Render a button group
 * 
 * @param array $buttons Array of button configurations
 * @return string HTML button group
 */
function renderButtonGroup($buttons) {
    $html = '<div style="display: flex; gap: 10px; margin-top: 20px;">';
    foreach ($buttons as $button) {
        $html .= renderButton(
            $button['label'],
            $button['url'] ?? '#',
            $button['type'] ?? 'primary',
            $button['icon'] ?? null
        );
    }
    $html .= '</div>';
    return $html;
}

// ============================================================================
// 2. FORM COMPONENTS
// ============================================================================

/**
 * Render a form text input field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param string $value Current value
 * @param bool $required Is field required
 * @param array $attributes Additional HTML attributes
 * @return string HTML form field
 */
function renderFormField($name, $label, $value = '', $required = false, $attributes = []) {
    $required_attr = $required ? 'required' : '';
    $required_mark = $required ? '<span style="color: #f00;">*</span>' : '';
    $attr_string = '';
    
    foreach ($attributes as $key => $val) {
        $attr_string .= " $key=\"" . htmlspecialchars($val) . "\"";
    }
    
    $html = '<div style="margin-bottom: 20px;">';
    $html .= "<label for=\"$name\" style=\"display: block; margin-bottom: 8px; font-weight: 500;\">$label $required_mark</label>";
    $html .= "<input type=\"text\" id=\"$name\" name=\"$name\" value=\"" . htmlspecialchars($value) . "\" $required_attr style=\"width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;\"$attr_string>";
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a form textarea field
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param string $value Current value
 * @param bool $required Is field required
 * @param int $rows Number of rows
 * @return string HTML textarea field
 */
function renderFormTextarea($name, $label, $value = '', $required = false, $rows = 5) {
    $required_attr = $required ? 'required' : '';
    $required_mark = $required ? '<span style="color: #f00;">*</span>' : '';
    
    $html = '<div style="margin-bottom: 20px;">';
    $html .= "<label for=\"$name\" style=\"display: block; margin-bottom: 8px; font-weight: 500;\">$label $required_mark</label>";
    $html .= "<textarea id=\"$name\" name=\"$name\" rows=\"$rows\" $required_attr style=\"width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit;\">" . htmlspecialchars($value) . "</textarea>";
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a form select dropdown
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param array $options Array of options (value => label)
 * @param string $selected Currently selected value
 * @param bool $required Is field required
 * @return string HTML select field
 */
function renderFormSelect($name, $label, $options = [], $selected = '', $required = false) {
    $required_attr = $required ? 'required' : '';
    $required_mark = $required ? '<span style="color: #f00;">*</span>' : '';
    
    $html = '<div style="margin-bottom: 20px;">';
    $html .= "<label for=\"$name\" style=\"display: block; margin-bottom: 8px; font-weight: 500;\">$label $required_mark</label>";
    $html .= "<select id=\"$name\" name=\"$name\" $required_attr style=\"width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;\">";
    $html .= '<option value="">-- Select --</option>';
    
    foreach ($options as $value => $label_text) {
        $selected_attr = ($value === $selected) ? 'selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($value) . "\" $selected_attr>" . htmlspecialchars($label_text) . "</option>";
    }
    
    $html .= '</select>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a form checkbox
 * 
 * @param string $name Field name
 * @param string $label Field label
 * @param string $value Field value
 * @param bool $checked Is checked
 * @return string HTML checkbox field
 */
function renderFormCheckbox($name, $label, $value = '1', $checked = false) {
    $checked_attr = $checked ? 'checked' : '';
    
    $html = '<div style="margin-bottom: 20px;">';
    $html .= "<label style=\"display: flex; align-items: center; gap: 8px; font-weight: 500;\">";
    $html .= "<input type=\"checkbox\" name=\"$name\" value=\"" . htmlspecialchars($value) . "\" $checked_attr>";
    $html .= $label;
    $html .= '</label>';
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// 3. TABLE COMPONENTS
// ============================================================================

/**
 * Render a data table with headers and rows
 * 
 * @param array $headers Array of column headers
 * @param array $rows Array of row data (each row is an array of cells)
 * @param array $actions Array of action buttons (optional)
 * @return string HTML table
 */
function renderDataTable($headers, $rows, $actions = []) {
    $html = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
    
    // Table header
    $html .= '<thead style="background: #f5f7fa; border-bottom: 2px solid #ddd;">';
    $html .= '<tr>';
    foreach ($headers as $header) {
        $html .= '<th style="padding: 12px; text-align: left; font-weight: 600; color: #2d3748;">' . htmlspecialchars($header) . '</th>';
    }
    if (!empty($actions)) {
        $html .= '<th style="padding: 12px; text-align: center; font-weight: 600; color: #2d3748;">Actions</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    
    // Table body
    $html .= '<tbody>';
    if (empty($rows)) {
        $colspan = count($headers) + (empty($actions) ? 0 : 1);
        $html .= '<tr><td colspan="' . $colspan . '" style="padding: 30px; text-align: center; color: #718096;">No data found</td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr style="border-bottom: 1px solid #eee; transition: background 0.2s;" onmouseover="this.style.background=\'#f9fafb\'" onmouseout="this.style.background=\'transparent\'">';
            foreach ($row as $cell) {
                $html .= '<td style="padding: 12px; color: #2d3748;">' . $cell . '</td>';
            }
            if (!empty($actions)) {
                $html .= '<td style="padding: 12px; text-align: center;">';
                foreach ($actions as $action) {
                    $html .= '<a href="' . htmlspecialchars($action['url']) . '" class="btn btn-sm btn-' . $action['type'] . '" style="margin: 0 2px;">' . htmlspecialchars($action['label']) . '</a>';
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody>';
    $html .= '</table>';
    
    return $html;
}

// ============================================================================
// 4. ALERT & MESSAGE COMPONENTS
// ============================================================================

/**
 * Render an alert message
 * 
 * @param string $message Alert message
 * @param string $type Alert type (success, error, warning, info)
 * @param bool $dismissible Can be dismissed
 * @return string HTML alert
 */
function renderAlert($message, $type = 'info', $dismissible = true) {
    $dismiss_btn = $dismissible ? '<button onclick="this.parentElement.style.display=\'none\';" style="background: none; border: none; font-size: 20px; cursor: pointer; float: right;">×</button>' : '';
    
    $colors = [
        'success' => ['bg' => '#d4edda', 'border' => '#28a745', 'color' => '#155724'],
        'error' => ['bg' => '#f8d7da', 'border' => '#f5c6cb', 'color' => '#721c24'],
        'warning' => ['bg' => '#fff3cd', 'border' => '#ffc107', 'color' => '#856404'],
        'info' => ['bg' => '#d1ecf1', 'border' => '#17a2b8', 'color' => '#0c5460']
    ];
    
    $style = $colors[$type] ?? $colors['info'];
    
    $html = '<div style="background: ' . $style['bg'] . '; border-left: 4px solid ' . $style['border'] . '; color: ' . $style['color'] . '; padding: 15px 20px; border-radius: 6px; margin-bottom: 20px;">';
    $html .= $dismiss_btn;
    $html .= htmlspecialchars($message);
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// 5. CARD COMPONENTS
// ============================================================================

/**
 * Render a card with title and content
 * 
 * @param string $title Card title
 * @param string $content Card content (HTML)
 * @param string $footer Card footer (optional)
 * @return string HTML card
 */
function renderCard($title, $content, $footer = '') {
    $html = '<div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 25px; margin-bottom: 20px;">';
    
    if (!empty($title)) {
        $html .= '<h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 600; color: #2d3748;">' . htmlspecialchars($title) . '</h3>';
    }
    
    $html .= $content;
    
    if (!empty($footer)) {
        $html .= '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">' . $footer . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// 6. STAT COMPONENTS
// ============================================================================

/**
 * Render a stat card (for dashboards)
 * 
 * @param string $label Stat label
 * @param string $value Stat value
 * @param string $icon Icon class (optional)
 * @param string $color Color theme (blue, green, red, orange)
 * @return string HTML stat card
 */
function renderStatCard($label, $value, $icon = null, $color = 'blue') {
    $colors = [
        'blue' => '#667eea',
        'green' => '#48bb78',
        'red' => '#f56565',
        'orange' => '#ed8936'
    ];
    
    $bg_color = $colors[$color] ?? $colors['blue'];
    $icon_html = $icon ? "<i class='icon-$icon' style='font-size: 32px; margin-bottom: 10px;'></i>" : '';
    
    $html = '<div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 25px; text-align: center; border-top: 4px solid ' . $bg_color . ';">';
    $html .= $icon_html;
    $html .= '<h3 style="font-size: 32px; font-weight: 700; color: ' . $bg_color . '; margin: 10px 0;">' . htmlspecialchars($value) . '</h3>';
    $html .= '<p style="color: #718096; font-size: 14px;">' . htmlspecialchars($label) . '</p>';
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// 7. BREADCRUMB COMPONENT
// ============================================================================

/**
 * Render breadcrumb navigation
 * 
 * @param array $breadcrumbs Array of breadcrumbs (label => url)
 * @return string HTML breadcrumb
 */
function renderBreadcrumb($breadcrumbs = []) {
    $html = '<nav style="margin-bottom: 20px; font-size: 14px;">';
    $html .= '<a href="' . ROOT_PATH . '/panel/admin.php" style="color: #667eea; text-decoration: none;">Dashboard</a>';
    
    foreach ($breadcrumbs as $label => $url) {
        $html .= ' <span style="color: #cbd5e0;"> / </span> ';
        if ($url === '#' || $url === null) {
            $html .= '<span style="color: #2d3748;">' . htmlspecialchars($label) . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($url) . '" style="color: #667eea; text-decoration: none;">' . htmlspecialchars($label) . '</a>';
        }
    }
    
    $html .= '</nav>';
    
    return $html;
}

// ============================================================================
// 8. PAGINATION COMPONENT
// ============================================================================

/**
 * Render pagination controls
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @return string HTML pagination
 */
function renderPagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div style="display: flex; justify-content: center; gap: 5px; margin-top: 30px;">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="btn btn-secondary">← Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $base_url . '?page=1" class="btn btn-secondary">1</a>';
        if ($start > 2) {
            $html .= '<span style="padding: 8px 12px;">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $current_page) {
            $html .= '<button style="padding: 8px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">' . $i . '</button>';
        } else {
            $html .= '<a href="' . $base_url . '?page=' . $i . '" class="btn btn-secondary">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<span style="padding: 8px 12px;">...</span>';
        }
        $html .= '<a href="' . $base_url . '?page=' . $total_pages . '" class="btn btn-secondary">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="btn btn-secondary">Next →</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

?>