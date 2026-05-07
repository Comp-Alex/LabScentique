<?php
/**
 * User Profile Page
 * Displays user information, favorites, and purchase history
 */

declare(strict_types=1);
$sessionSavePath = $_ENV['SESSION_SAVE_PATH'] ?? $_SERVER['SESSION_SAVE_PATH'] ?? null;
if (is_string($sessionSavePath) && $sessionSavePath !== '') {
    if (!is_dir($sessionSavePath)) {
        @mkdir($sessionSavePath, 0755, true);
    }
    session_save_path($sessionSavePath);
}

session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Get user information
$stmt = $pdo->prepare('
    SELECT id, username, email, full_name, bio, profile_picture_url, created_at 
    FROM users 
    WHERE id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get favorites count
$favStmt = $pdo->prepare('SELECT COUNT(*) as count FROM user_favorites WHERE user_id = ?');
$favStmt->execute([$_SESSION['user_id']]);
$favCount = $favStmt->fetch()['count'];

// Get purchases count
$purStmt = $pdo->prepare('SELECT COUNT(*) as count FROM customer_purchases WHERE customer_id = ?');
$purStmt->execute([$_SESSION['user_id']]);
$purCount = $purStmt->fetch()['count'];

// Get total spent on purchases
$totalStmt = $pdo->prepare('
    SELECT COUNT(DISTINCT perfume_id) as total_unique_perfumes, 
           SUM(quantity) as total_units
    FROM customer_purchases 
    WHERE customer_id = ?
');
$totalStmt->execute([$_SESSION['user_id']]);
$stats = $totalStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LabScentique</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 40px;
            color: white;
            margin-bottom: 40px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .profile-picture-container {
            flex-shrink: 0;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.2);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .profile-username {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .profile-bio {
            font-size: 14px;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 20px;
            max-width: 500px;
        }

        .profile-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.9;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .profile-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }

        .section-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section-empty p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .section-empty a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .favorites-grid, .purchases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .perfume-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .perfume-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .perfume-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f0f0f0;
        }

        .perfume-details {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .perfume-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .perfume-rating {
            color: #ffc107;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .perfume-description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
            flex: 1;
        }

        .perfume-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .purchase-info {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-button:hover {
            color: #764ba2;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
                flex-direction: column;
            }

            .profile-name {
                font-size: 24px;
            }

            .favorites-grid, .purchases-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body style="background: #f5f5f5;">
    <!-- Navigation -->
    <header class="header-nav">
        <a href="index.php" class="logo">LabScentique</a>
        <nav>
            <a href="index.php#home">Home</a>
            <a href="index.php#products">Perfumes</a>
            <a href="index.php#about">About</a>
            <div class="user-menu-toggle">
                <span><?php echo escape($_SESSION['username'] ?? 'User'); ?></span>
                <div class="user-menu-dropdown" style="display: none;">
                    <a href="profile.php" style="font-weight: bold; color: #667eea;">My Profile</a>
                    <a href="api/user-profile.php?action=logout">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="profile-page">
        <a href="index.php" class="back-button">← Back to Home</a>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-picture-container">
                <?php if (!empty($user['profile_picture_url'])): ?>
                    <img src="<?php echo escape($user['profile_picture_url']); ?>" alt="Profile" class="profile-picture">
                <?php else: ?>
                    <div class="profile-picture" style="display: flex; align-items: center; justify-content: center; font-size: 60px; background: rgba(255,255,255,0.3);">
                        👤
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-info">
                <div class="profile-name"><?php echo escape($user['full_name'] ?? $user['username']); ?></div>
                <div class="profile-username">@<?php echo escape($user['username']); ?></div>
                <?php if (!empty($user['bio'])): ?>
                    <div class="profile-bio"><?php echo escape($user['bio']); ?></div>
                <?php endif; ?>
                <div class="profile-meta">
                    <div>📧 <?php echo escape($user['email']); ?></div>
                    <div>📅 Member since <?php echo formatDate($user['created_at'], 'M Y'); ?></div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $favCount; ?></div>
                <div class="stat-label">Favorite Perfumes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_unique_perfumes'] ?? 0; ?></div>
                <div class="stat-label">Unique Perfumes Purchased</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_units'] ?? 0; ?></div>
                <div class="stat-label">Total Units Purchased</div>
            </div>
        </div>

        <!-- Favorites Section -->
        <div class="profile-section">
            <h2 class="section-title">❤️ My Favorite Perfumes</h2>
            <div id="favorites-container" style="margin-top: 20px;">
                <div class="section-empty">
                    <p>Loading favorites...</p>
                </div>
            </div>
        </div>

        <!-- Purchases Section -->
        <div class="profile-section">
            <h2 class="section-title">🛍️ My Purchase History</h2>
            <div id="purchases-container" style="margin-top: 20px;">
                <div class="section-empty">
                    <p>Loading purchase history...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load favorites
        fetch('api/user-favorites.php?action=get_favorites')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('favorites-container');
                if (data.data && data.data.length > 0) {
                    container.innerHTML = '<div class="favorites-grid">' + 
                        data.data.map(fav => `
                            <div class="perfume-card">
                                <img src="${fav.image_url || 'assets/placeholder.jpg'}" alt="${fav.name}" class="perfume-image">
                                <div class="perfume-details">
                                    <div class="perfume-name">${fav.name}</div>
                                    <div class="perfume-rating">${'⭐'.repeat(Math.round(fav.rating))}</div>
                                    <div class="perfume-description">${fav.description || ''}</div>
                                    <div class="perfume-actions">
                                        <button class="btn-action btn-remove" onclick="removeFavorite(${fav.perfume_id})">
                                            Remove from Favorites
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('') + '</div>';
                } else {
                    container.innerHTML = '<div class="section-empty"><p>No favorites yet</p><a href="index.php#products">Browse Perfumes</a></div>';
                }
            });

        // Load purchases
        fetch('api/user-favorites.php?action=get_purchases')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('purchases-container');
                if (data.data && data.data.length > 0) {
                    container.innerHTML = '<div class="purchases-grid">' + 
                        data.data.map(purchase => `
                            <div class="perfume-card">
                                <img src="${purchase.image_url || 'assets/placeholder.jpg'}" alt="${purchase.name}" class="perfume-image">
                                <div class="perfume-details">
                                    <div class="perfume-name">${purchase.name}</div>
                                    <div class="perfume-rating">${'⭐'.repeat(Math.round(purchase.rating))}</div>
                                    <div class="perfume-description">${purchase.description || ''}</div>
                                    <div class="purchase-info">Quantity: <strong>${purchase.quantity}</strong><br>Purchased: ${new Date(purchase.purchase_date).toLocaleDateString()}</div>
                                </div>
                            </div>
                        `).join('') + '</div>';
                } else {
                    container.innerHTML = '<div class="section-empty"><p>No purchases yet</p><a href="index.php#products">Shop Now</a></div>';
                }
            });

        function removeFavorite(perfumeId) {
            fetch('api/user-favorites.php?action=remove_favorite', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({perfume_id: perfumeId})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
?>
