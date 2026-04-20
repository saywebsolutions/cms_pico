#!/usr/bin/env php
<?php
/**
 * Import blog posts from Fresh Blog to CMS Pico
 *
 * Transforms frontmatter (lowercase for Pico):
 *   title → title
 *   meta → description
 *   published_at → date
 *
 * Fixes image paths: /images/ → %assets_url%/images/
 */

$sourceDir = '/home/user/repos/fresh-blog-main/data/posts';
$destDir = '/home/user/Nextcloud/CMS/web/content/blog';

// Ensure destination exists
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

$files = glob("$sourceDir/*.md");
$count = 0;
$errors = [];

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);

    // Parse frontmatter
    if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
        $errors[] = "$filename: No frontmatter found";
        continue;
    }

    $frontmatter = $matches[1];
    $body = $matches[2];

    // Parse YAML frontmatter manually (simple key: value format)
    $meta = [];
    foreach (explode("\n", $frontmatter) as $line) {
        if (preg_match('/^(\w+):\s*(.*)$/', $line, $m)) {
            $meta[strtolower($m[1])] = trim($m[2]);
        }
    }

    // Build new frontmatter (lowercase for Pico)
    $newFrontmatter = [];

    if (isset($meta['title'])) {
        $newFrontmatter[] = 'title: ' . $meta['title'];
    }

    if (isset($meta['meta'])) {
        $newFrontmatter[] = 'description: ' . $meta['meta'];
    }

    if (isset($meta['published_at'])) {
        $newFrontmatter[] = 'date: ' . $meta['published_at'];
    }

    // Fix image paths in body
    $body = preg_replace('#\(/images/#', '(%assets_url%/images/', $body);
    $body = preg_replace('#"/images/#', '"%assets_url%/images/', $body);
    $body = preg_replace('#src="/images/#', 'src="%assets_url%/images/', $body);

    // Assemble new content
    $newContent = "---\n" . implode("\n", $newFrontmatter) . "\n---\n" . $body;

    // Write to destination
    $destPath = "$destDir/$filename";
    if (file_put_contents($destPath, $newContent) === false) {
        $errors[] = "$filename: Failed to write";
        continue;
    }

    $count++;
}

echo "Imported $count posts to $destDir\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
