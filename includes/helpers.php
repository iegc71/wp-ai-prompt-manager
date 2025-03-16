<?php
// Verifica si un usuario tiene permisos para administrar el plugin
function prompts_plugin_user_can_manage() {
    return current_user_can('manage_options');
}

// Sanitiza valores antes de guardarlos
function prompts_plugin_sanitize_text($text) {
    return sanitize_text_field($text);
}
