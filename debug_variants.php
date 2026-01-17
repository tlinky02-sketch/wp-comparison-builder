<?php
// Debug: Check if module is enabled
$module_enabled = get_option('wpc_enable_variants_module');
error_log('WPC Variants Module Enabled: ' . var_export($module_enabled, true));

// Put this code right before line 568 in admin-ui.php to debug
?>
