<?php
// Map images from public/assets/perfume_images to perfumes in labscentique.db
// and update the perfumes.image_url field. PHP-only (no Python).
// Run: php scripts/apply_images_php.php

$root = realpath(__DIR__ . '/../');
$dbPath = $root . DIRECTORY_SEPARATOR . 'labscentique.db';
$backup = $root . DIRECTORY_SEPARATOR . 'labscentique.db.php.bak';
$assetDirs = [
    [
        'basePath' => '/assets/perfume_images/',
        'dir' => $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'perfume_images',
    ],
];
$exts = ['jpeg','jpg','png','webp'];
$fuzzyThreshold = 0.58; // similarity threshold

if (!file_exists($dbPath)) {
    fwrite(STDERR, "Database not found at $dbPath\n");
    exit(1);
}

// backup
copy($dbPath, $backup);
echo "Backup created at $backup\n";

// gather assets
$assets = [];
foreach ($assetDirs as $assetInfo) {
    if (!is_dir($assetInfo['dir'])) {
        continue;
    }
    $dir = new DirectoryIterator($assetInfo['dir']);
    foreach ($dir as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $exts, true)) {
                $assets[strtolower($file->getBasename('.' . $ext))] = $assetInfo['basePath'] . $file->getFilename();
            }
        }
    }
}
echo "Found " . count($assets) . " assets\n";

// helpers (mirror api sanitize)
function normalizeText($text) {
    if (!function_exists('mb_strtolower')) {
        $text = strtolower($text);
    } else {
        $text = mb_strtolower($text, 'UTF-8');
    }
    $text = preg_replace('/[^\p{L}\p{N}\s\/&_\-]+/u', '', $text);
    $text = preg_replace('/[\/&_]+/u', '-', $text);
    $text = preg_replace('/[\s_]+/u', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function normalizeLocalAssetPath(string $imageUrl): string {
    $url = trim($imageUrl);
    if ($url === '') {
        return '';
    }
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $url)) {
        return $url;
    }
    $url = preg_replace('#/+#', '/', $url);
    $parts = explode('/', trim($url, '/'));
    $lastAssetsIndex = null;
    foreach ($parts as $index => $segment) {
        if ($segment === 'assets') {
            $lastAssetsIndex = $index;
        }
    }
    if ($lastAssetsIndex !== null) {
        $url = '/' . implode('/', array_slice($parts, $lastAssetsIndex));
    } else {
        $url = '/' . implode('/', $parts);
    }
    return $url;
}

function isValidLocalAssetPath(string $imageUrl, string $publicRoot): bool {
    $imageUrl = normalizeLocalAssetPath($imageUrl);
    if (str_starts_with($imageUrl, '/assets/perfume_images/')) {
        return file_exists($publicRoot . $imageUrl);
    }
    return false;
}

function getCandidates($name) {
    $name = str_replace([" – ", "—"], ' - ', $name);
    $candidates = [];
    $full = normalizeText($name);
    if ($full !== '') $candidates[] = $full;
    $parts = explode(' - ', $name, 2);
    if (count($parts) === 2) {
        $productPart = $parts[0];
        $brandPart = $parts[1];
        $productOnly = normalizeText(preg_replace('/\s*\(.*?\)/', '', $productPart));
        $brandOnly = normalizeText($brandPart);
        if ($productOnly !== '') {
            $candidates[] = $productOnly;
            if ($brandOnly !== '') {
                $candidates[] = $productOnly . '-' . $brandOnly;
                $candidates[] = $brandOnly . '-' . $productOnly;
            }
        }
    }
    // add suffix variants
    $extra = [];
    foreach ($candidates as $c) {
        foreach (['-edp','-edt','-parfum'] as $suf) {
            $extra[] = $c . $suf;
        }
    }
    $candidates = array_values(array_unique(array_merge($candidates, $extra)));
    foreach ($candidates as $candidate) {
        $underscore = str_replace('-', '_', $candidate);
        if ($underscore !== $candidate) {
            $candidates[] = $underscore;
        }
    }
    return array_values(array_unique($candidates));
}

function similarity($a, $b) {
    $len = 0;
    similar_text($a, $b, $percent);
    return $percent / 100.0;
}

// open sqlite
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('SELECT id, name, image_url FROM perfumes ORDER BY id');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$mapped = [];
$publicRoot = $root . DIRECTORY_SEPARATOR . 'public';

foreach ($rows as $r) {
    $id = (int)$r['id'];
    $name = $r['name'] ?? '';
    $originalImageUrl = $r['image_url'] ?? '';
    $image_url = normalizeLocalAssetPath($originalImageUrl);

    // normalize problematic local asset paths and update if the path resolves correctly
    if ($image_url && isValidLocalAssetPath($image_url, $publicRoot)) {
        if ($image_url !== $originalImageUrl) {
            $upd = $pdo->prepare('UPDATE perfumes SET image_url = :path WHERE id = :id');
            $upd->execute([':path' => $image_url, ':id' => $id]);
            $updated++;
            $mapped[] = [$id, $name, $image_url];
        }
        continue;
    }

    $candidates = getCandidates($name);
    $found = null;
    // exact match
    foreach ($candidates as $cand) {
        foreach ($exts as $ext) {
            $key = strtolower($cand);
            if (isset($assets[$key])) {
                $found = $assets[$key];
                break 2;
            }
            // also try with extension suffix
            $trial = $key . '.' . $ext;
            // assets keys are basenames without extension; skip
        }
    }
    // fuzzy if not found
    if (!$found) {
        $bestScore = 0;
        $bestFile = null;
        foreach ($assets as $base => $fname) {
            foreach ($candidates as $cand) {
                $score = similarity($cand, $base);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestFile = $fname;
                }
            }
        }
        if ($bestScore >= $fuzzyThreshold) {
            $found = $bestFile;
        }
    }

    if ($found) {
        $newPath = $found;
        if ($image_url !== $newPath) {
            $upd = $pdo->prepare('UPDATE perfumes SET image_url = :path WHERE id = :id');
            $upd->execute([':path' => $newPath, ':id' => $id]);
            $updated++;
            $mapped[] = [$id, $name, $newPath];
        }
    }
}

echo "Updated $updated perfumes with local images\n";
foreach ($mapped as $m) {
    echo $m[0] . ' ' . $m[1] . ' -> ' . $m[2] . "\n";
}

echo "Done.\n";
