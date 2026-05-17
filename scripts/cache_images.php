<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Directory to save images
$outDir = __DIR__ . '/../public/assets/perfumes';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

echo "Scanning perfumes and caching remote images (cURL)...\n";

$stmt = $pdo->query('SELECT id, image_url FROM perfumes');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$downloaded = 0;
foreach ($rows as $r) {
    $id = (int)$r['id'];
    $url = trim((string)$r['image_url']);
    if ($url === '') continue;

    // Only process remote http/https URLs
    if (!preg_match('#^https?://#i', $url)) {
        echo "#{$id}: already local (skipping) -> {$url}\n";
        continue;
    }

    // Use cURL for robust downloads
    if (!function_exists('curl_init')) {
        echo "cURL not available, falling back to file_get_contents for #{$id}\n";
        $data = @file_get_contents($url);
        if ($data === false) {
            echo "#{$id}: download failed (fallback) -> {$url}\n";
            continue;
        }
    } else {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LabScentiqueImageCache/1.0');
        // For local dev: allow downloads even when system CA bundle missing.
        // WARNING: disabling verification is insecure; do not use in production.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlErr = curl_error($ch);

        if ($data === false || $httpCode < 200 || $httpCode >= 300) {
            echo "#{$id}: download failed (cURL) -> {$url} (http={$httpCode}) err={$curlErr}\n";
            continue;
        }
    }

    // Determine extension from content
    $ext = 'jpg';
    $info = @getimagesizefromstring($data);
    if ($info && isset($info['mime'])) {
        $mime = $info['mime'];
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        if (isset($map[$mime])) $ext = $map[$mime];
    } else {
        // fallback to path extension
        $pathExt = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        if ($pathExt) $ext = preg_replace('/[^a-zA-Z0-9]/', '', $pathExt);
    }

    $savePath = "$outDir/{$id}.{$ext}";
    if (file_put_contents($savePath, $data) === false) {
        echo "#{$id}: failed to write file {$savePath}\n";
        continue;
    }

    // Update DB to local relative path (safe relative path)
    $localPath = 'assets/perfumes/' . basename($savePath);
    try {
        $update = $pdo->prepare('UPDATE perfumes SET image_url = :path WHERE id = :id');
        $update->execute([':path' => $localPath, ':id' => $id]);
    } catch (Exception $e) {
        echo "#{$id}: DB update failed: " . $e->getMessage() . "\n";
        // still count as downloaded
    }

    $downloaded++;
    echo "#{$id}: cached -> {$localPath}\n";
}

echo "Done. Images cached: {$downloaded}\n";
