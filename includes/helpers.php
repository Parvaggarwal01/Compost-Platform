<?php
require_once __DIR__ . '/../config/constants.php';

function validateInput($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            return false;
        }
    }
    return true;
}

function sanitize($data) {
    return is_array($data) ? array_map('htmlspecialchars', $data) : htmlspecialchars($data);
}
?>