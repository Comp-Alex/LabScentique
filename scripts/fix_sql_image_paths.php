<?php
// Fix existing perfume image_url entries in labscentique.db to use the public asset folder path.
$root = realpath(__DIR__ . '/..');
$dbPath = $root . DIRECTORY_SEPARATOR . 'labscentique.db';
$backupPath = $root . DIRECTORY_SEPARATOR . 'labscentique.db.imagepath.bak';
$logPath = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'fix_sql_image_paths.log';

if (!file_exists($dbPath)) {
    file_put_contents($logPath, "ERROR: Database file not found at $dbPath\n");
    exit(1);
}

if (!copy($dbPath, $backupPath)) {
    file_put_contents($logPath, "ERROR: Failed to create backup at $backupPath\n");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $updates = [
        [
            'label' => "perfume_images/ -> /assets/perfume_images/",
            'find' => "perfume_images/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('perfume_images/') + 1) WHERE image_url LIKE 'perfume_images/%'",
        ],
        [
            'label' => "assets/perfume_images/ -> /assets/perfume_images/",
            'find' => "assets/perfume_images/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('assets/perfume_images/') + 1) WHERE image_url LIKE 'assets/perfume_images/%'",
        ],
        [
            'label' => "./perfume_images/ -> /assets/perfume_images/",
            'find' => "./perfume_images/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('./perfume_images/') + 1) WHERE image_url LIKE './perfume_images/%'",
        ],
        [
            'label' => "pics-labscentique/ -> /assets/perfume_images/",
            'find' => "pics-labscentique/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('pics-labscentique/') + 1) WHERE image_url LIKE 'pics-labscentique/%'",
        ],
        [
            'label' => "assets/pics-labscentique/ -> /assets/perfume_images/",
            'find' => "assets/pics-labscentique/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('assets/pics-labscentique/') + 1) WHERE image_url LIKE 'assets/pics-labscentique/%'",
        ],
        [
            'label' => "./assets/pics-labscentique/ -> /assets/perfume_images/",
            'find' => "./assets/pics-labscentique/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('./assets/pics-labscentique/') + 1) WHERE image_url LIKE './assets/pics-labscentique/%'",
        ],
        [
            'label' => "/pics-labscentique/ -> /assets/perfume_images/",
            'find' => "/pics-labscentique/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('/pics-labscentique/') + 1) WHERE image_url LIKE '/pics-labscentique/%'",
        ],
        [
            'label' => "/assets/pics-labscentique/ -> /assets/perfume_images/",
            'find' => "/assets/pics-labscentique/%",
            'sql' => "UPDATE perfumes SET image_url = '/assets/perfume_images/' || substr(image_url, LENGTH('/assets/pics-labscentique/') + 1) WHERE image_url LIKE '/assets/pics-labscentique/%'",
        ],
    ];

    $log = [];
    $updated = 0;
    $matched = 0;
    $totalOldRows = 0;
    foreach ($updates as $update) {
        $countStmt = $pdo->query("SELECT COUNT(*) AS c FROM perfumes WHERE image_url LIKE '{$update['find']}'");
        $count = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['c'];
        $totalOldRows += $count;
        if ($count > 0) {
            $updateStmt = $pdo->prepare($update['sql']);
            $updateStmt->execute();
            $rows = $updateStmt->rowCount();
            $log[] = "Fixed {$rows} rows for {$update['label']}";
            $updated += $rows;
        }
    }

    $matchedStmt = $pdo->query("SELECT COUNT(*) AS c FROM perfumes WHERE image_url LIKE '/assets/perfume_images/%'");
    $matched = (int)$matchedStmt->fetch(PDO::FETCH_ASSOC)['c'];

    $log[] = "Backup created: $backupPath";
    $log[] = "Old path rows found: $totalOldRows";
    $log[] = "Updated rows: $updated";
    $log[] = "Rows now using /assets/perfume_images/: $matched";
    $log[] = "Done.";
    file_put_contents($logPath, implode("\n", $log) . "\n");
    exit(0);
} catch (PDOException $e) {
    file_put_contents($logPath, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
