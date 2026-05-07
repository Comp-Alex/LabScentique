<?php
declare(strict_types=1);
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$dbError = false;
$dbErrorMessage = '';
$pdo = null;
try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    $dbError = true;
    $dbErrorMessage = $e->getMessage();
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accreditation - LabScentique</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <div class="header-left">
        <button class="menu-toggle" type="button" aria-label="Open navigation menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <a href="index.php" class="brand" aria-label="LabScentique home">
          <img src="assets/logo.svg" alt="LabScentique logo" class="brand-logo" />
          <span>LabScentique</span>
        </a>
        <nav class="header-nav">
          <a href="index.php#home">Home</a>
          <a href="index.php#products">Perfumes</a>
          <a href="index.php#news">News</a>
          <a href="index.php#about">About</a>
          <a href="accreditation.php">Accreditation</a>
          <a href="index.php#contact">Contact</a>
          <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['staff', 'owner'], true)): ?>
            <a href="dashboard.php">Dashboard</a>
          <?php endif; ?>
        </nav>
      </div>
      <div class="auth-links">
        <?php if (isset($_SESSION['user_id'])): ?>
          <span>Welcome, <?php echo escape($_SESSION['username'] ?? 'User'); ?>!</span>
          <a href="?logout" class="icon-button">Logout</a>
        <?php else: ?>
          <button type="button" class="icon-button auth-link" data-auth-target="login" aria-label="Open login panel">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-3-3.87"></path><path d="M12 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"></path><path d="M20 8v6"></path><path d="M22 11h-4"></path></svg>
            Login
          </button>
          <button type="button" class="icon-button auth-link" data-auth-target="register" aria-label="Open register panel">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M4 21v-2a4 4 0 0 1 3-3.87"></path><path d="M12 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"></path><path d="M12 2v4"></path><path d="M10 4h4"></path></svg>
            Register
          </button>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>
  <aside class="sidebar-nav" id="sidebar-nav" aria-hidden="true">
    <button class="sidebar-nav-close" type="button" aria-label="Close navigation">×</button>
    <nav>
      <a href="index.php#home">Home</a>
      <a href="index.php#products">Perfumes</a>
      <a href="index.php#news">News</a>
      <a href="index.php#about">About</a>
      <a href="accreditation.php">Accreditation</a>
      <a href="index.php#contact">Contact</a>
      <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['staff', 'owner'], true)): ?>
        <a href="dashboard.php">Dashboard</a>
      <?php endif; ?>
    </nav>
  </aside>

  <main class="main-content">
    <div class="content-wrapper">
      <section class="hero">
        <div class="container">
          <p class="eyebrow">Project Management</p>
          <h1>ISO 25010 Compliance</h1>
          <p>LabScentique follows ISO 25010:2023 guidelines for software product quality, ensuring efficient and effective development of our fragrance products.</p>
        </div>
      </section>

      <section class="features">
        <div class="container section-heading">
          <h2>What is ISO 25010?</h2>
        </div>
        <div class="container">
          <p>ISO 25010:2023 provides guidance for software product quality and can be used by any type of organization, including public, private or community organizations, to ensure software quality characteristics including reliability, performance, and maintainability.</p>
          <p>At LabScentique, we apply these principles to ensure our platform meets high standards of quality, reliability, and user satisfaction.</p>
        </div>
      </section>

      <section class="features">
        <div class="container section-heading">
          <h2>Benefits of ISO 25010</h2>
        </div>
        <div class="container feature-grid">
          <div class="feature-card">
            <h3>Reliability</h3>
            <p>Our platform provides dependable performance and consistent quality in delivering fragrance discovery services.</p>
          </div>
          <div class="feature-card">
            <h3>Performance Efficiency</h3>
            <p>Optimized response times and resource utilization ensure fast, smooth user experiences.</p>
          </div>
          <div class="feature-card">
            <h3>Usability</h3>
            <p>Intuitive interface and clear design make perfume discovery accessible to all users.</p>
          </div>
          <div class="feature-card">
            <h3>Security & Maintainability</h3>
            <p>Robust security measures and maintainable code ensure data protection and system longevity.</p>
          </div>
        </div>
      </section>

      <section class="features">
        <div class="container section-heading">
          <h2>Our Quality Management Practices</h2>
        </div>
        <div class="container">
          <ul>
            <li>Regular testing and validation of all features</li>
            <li>Security audits and vulnerability assessments</li>
            <li>Performance monitoring and optimization</li>
            <li>User experience evaluation and improvement</li>
            <li>Code quality maintenance and refactoring</li>
            <li>Continuous deployment with quality checks</li>
          </ul>
        </div>
      </section>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; 2024 LabScentique. All rights reserved.</p>
    </div>
  </footer>

  <script src="script.js"></script>
</body>
</html>