<?php
// Load WordPress
require_once('wp-load.php');

// Get the WPC post
$args = array(
    'post_type' => 'wpc_item',
    'posts_per_page' => 1,
);
$posts = get_posts($args);

if ($posts) {
    $p = $posts[0];
    echo "Checking Post ID: " . $p->ID . "\n";
    $overrides = get_post_meta($p->ID, '_wpc_design_overrides', true);
    echo "Design Overrides Meta:\n";
    var_dump($overrides);
    
    if (is_array($overrides)) {
        echo "Show Hero Logo Value: " . (isset($overrides['show_hero_logo']) ? $overrides['show_hero_logo'] : 'Not Set') . "\n";
        echo "API Logic Result (" . '$overrides[\'show_hero_logo\'] === \'1\'' . "): " . ($overrides['show_hero_logo'] === '1' ? 'TRUE' : 'FALSE') . "\n";
    }
} else {
    echo "No WPC Items found.\n";
}
