<?php
if (!defined('LABSCENTIQUE_APP')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LabScentique</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="review-styles.css" />
</head>
<body data-user-role="<?php echo escape($_SESSION['role'] ?? 'guest'); ?>" data-user-id="<?php echo escape($_SESSION['user_id'] ?? ''); ?>">
  <div class="intro-overlay" id="intro-overlay" aria-hidden="true">
    <div class="intro-content">
      <img src="<?php echo escape($introLogoPath); ?>" alt="LabScentique logo" class="intro-logo" onerror="this.onerror=null;this.src='assets/logo.svg'" />
    </div>
  </div>

  <header class="site-header">
    <div class="container header-inner">
      <div class="header-left">
        <button class="menu-toggle" type="button" aria-label="Open navigation menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <a href="#home" class="brand" aria-label="LabScentique home">
          <img src="assets/logo.svg" alt="LabScentique logo" class="brand-logo" />
          <span>LabScentique</span>
        </a>
        <nav class="header-nav">
          <a href="#home">Home</a>
          <a href="#products">Perfumes</a>
          <a href="#news">News</a>
          <a href="#about">About</a>
          <a href="accreditation.php">Accreditation</a>
          <a href="#contact">Contact</a>
          <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['staff', 'owner'], true)): ?>
            <a href="dashboard.php">Dashboard</a>
          <?php endif; ?>
        </nav>
      </div>
      <div class="search-bar">
        <form method="get" action="#products">
          <input type="text" name="search" placeholder="Search perfumes..." />
          <button type="submit">Search</button>
        </form>
      </div>
      <div class="auth-links">
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="user-menu">
            <button type="button" class="icon-button user-menu-toggle" aria-label="User menu" aria-expanded="false" aria-haspopup="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M12 14c-6 0-8 3-8 3v3h16v-3s-2-3-8-3z"/></svg>
              <span class="user-name" id="header-username"><?php echo escape($_SESSION['username']); ?></span>
            </button>
            <div class="user-menu-dropdown" id="user-menu-dropdown" aria-hidden="true">
              <button type="button" class="user-menu-item" data-action="view-profile">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                View Profile
              </button>
              <button type="button" class="user-menu-item" data-action="edit-profile">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19H4v-3L16.5 3.5z"></path></svg>
                Edit Profile
              </button>
              <button type="button" class="user-menu-item" data-action="switch-account">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                Switch Account
              </button>
              <hr class="user-menu-divider" />
              <a href="?logout=1" class="user-menu-item logout-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
              </a>
            </div>
          </div>
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
      <a href="#home">Home</a>
      <a href="#products">Perfumes</a>
      <a href="#news">News</a>
      <a href="#about">About</a>
      <a href="accreditation.php">Accreditation</a>
      <a href="#contact">Contact</a>
      <?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['staff', 'owner'], true)): ?>
        <a href="dashboard.php">Dashboard</a>
      <?php endif; ?>
    </nav>
  </aside>

  <div class="modal-overlay" id="auth-modal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
      <button class="modal-close" type="button" aria-label="Close authentication window">×</button>
      <div class="modal-tabs">
        <button type="button" class="modal-tab active" data-modal-tab="login">Login</button>
        <button type="button" class="modal-tab" data-modal-tab="register">Register</button>
      </div>
      <div class="modal-content">
        <div class="modal-panel active" data-modal-panel="login">
          <h2 id="auth-modal-title">Login</h2>
          <?php if ($loginError): ?>
            <div class="form-status error"><?php echo escape($loginError); ?></div>
          <?php endif; ?>
          <form class="auth-form" method="post" action="<?php echo escape($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="action" value="login" />
            <label>
              Username or Email
              <input type="text" name="login_username" required />
            </label>
            <label>
              Password
              <input type="password" name="login_password" required />
            </label>
            <button type="submit" class="button button-primary">Login</button>
          </form>
        </div>
        <div class="modal-panel" data-modal-panel="register">
          <h2>Register</h2>
          <?php if ($registerError): ?>
            <div class="form-status error"><?php echo escape($registerError); ?></div>
          <?php endif; ?>
          <?php if ($registerSuccess): ?>
            <div class="form-status success"><?php echo escape($registerSuccess); ?></div>
          <?php endif; ?>
          <form class="auth-form" method="post" action="<?php echo escape($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="action" value="register" />
            <label>
              Username
              <input type="text" name="reg_username" required />
            </label>
            <label>
              Email
              <input type="email" name="reg_email" required />
            </label>
            <label>
              Password
              <input type="password" name="reg_password" required />
            </label>
            <button type="submit" class="button button-primary">Register</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="profile-modal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
      <button class="modal-close" type="button" aria-label="Close profile">×</button>
      <h2 id="profile-modal-title">My Profile</h2>
      <div class="profile-view">
        <div class="profile-header">
          <img id="profile-picture" src="assets/logo.svg" alt="Profile picture" class="profile-picture" />
          <div class="profile-info">
            <h3 id="profile-name">Loading...</h3>
            <p id="profile-username" class="profile-username">@username</p>
            <p id="profile-email" class="profile-email">email@example.com</p>
          </div>
        </div>
        <div class="profile-details">
          <div class="detail-item">
            <label>Bio</label>
            <p id="profile-bio">No bio added yet</p>
          </div>
          <div class="detail-item">
            <label>Member Since</label>
            <p id="profile-created">Loading...</p>
          </div>
          <div class="detail-item">
            <label>Role</label>
            <p id="profile-role">Loading...</p>
          </div>
        </div>
        <div class="profile-actions">
          <button type="button" class="button button-primary" data-action="open-edit-profile">Edit Profile</button>
          <button type="button" class="button button-secondary" data-action="change-password">Change Password</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="edit-profile-modal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="edit-profile-title">
      <button class="modal-close" type="button" aria-label="Close edit profile">×</button>
      <h2 id="edit-profile-title">Edit Profile</h2>
      <form class="edit-profile-form" id="edit-profile-form">
        <label>
          Full Name
          <input type="text" name="full_name" placeholder="Enter your full name" maxlength="255" />
        </label>
        <label>
          Bio
          <textarea name="bio" placeholder="Tell us about yourself" maxlength="1000" rows="4"></textarea>
        </label>
        <label>
          Profile Picture URL
          <input type="url" name="profile_picture_url" placeholder="https://example.com/profile.jpg" />
        </label>
        <div class="form-actions">
          <button type="submit" class="button button-primary">Save Changes</button>
          <button type="button" class="button button-secondary" data-action="cancel-edit">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="change-password-modal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="change-password-title">
      <button class="modal-close" type="button" aria-label="Close change password">×</button>
      <h2 id="change-password-title">Change Password</h2>
      <form class="change-password-form" id="change-password-form">
        <label>
          Current Password
          <input type="password" name="current_password" required />
        </label>
        <label>
          New Password
          <input type="password" name="new_password" required />
        </label>
        <label>
          Confirm New Password
          <input type="password" name="confirm_password" required />
        </label>
        <div class="form-actions">
          <button type="submit" class="button button-primary">Update Password</button>
          <button type="button" class="button button-secondary" data-action="cancel-password">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <main class="main-content">
    <div class="content-wrapper">
      <section class="hero" id="home">
        <div class="container hero-grid">
          <div class="hero-copy">
            <p class="eyebrow">Welcome to LabScentique</p>
            <h1>Find Your Perfect Scent</h1>
            <p>Explore our curated collection of luxury perfumes. Each fragrance is crafted with care to bring you a unique and unforgettable experience.</p>
            <div class="hero-actions">
              <a href="#products" class="button button-primary">Explore Collection</a>
              <a href="#about" class="button button-secondary">Our Story</a>
            </div>
          </div>
        </div>
      </section>

      <section class="features" id="products">
        <div class="container section-heading">
          <p class="eyebrow">Our Collection</p>
          <h2>Featured Perfumes</h2>
        </div>
        <div class="container feature-grid">
          <!-- Perfumes loaded by JavaScript from API -->
        </div>
      </section>

      <section class="news" id="news">
        <div class="container section-heading">
          <p class="eyebrow">Latest Updates</p>
          <h2>News & Articles</h2>
        </div>
        <div class="container news-grid">
          <article class="news-card">
            <h3>The Art of Perfume Creation</h3>
            <p>Discover the meticulous process behind crafting signature scents at LabScentique.</p>
            <small>April 13, 2026</small>
          </article>
          <article class="news-card">
            <h3>Seasonal Scents: Spring Collection</h3>
            <p>Explore our new line of fresh, floral fragrances perfect for the warmer months.</p>
            <small>April 10, 2026</small>
          </article>
          <article class="news-card">
            <h3>Behind the Notes: Amber</h3>
            <p>A deep dive into the warm, resinous notes that form the heart of many oriental fragrances.</p>
            <small>April 5, 2026</small>
          </article>
        </div>
      </section>

      <section class="about" id="about">
        <div class="container about-inner">
          <div class="section-heading">
            <p class="eyebrow">About LabScentique</p>
            <h2>Loading...</h2>
            <p></p>
            <p></p>
          </div>
          <div class="about-details">
            <div class="about-block">
              <h3>What we do</h3>
              <div class="about-features"></div>
            </div>
            <div class="about-block">
              <h3>Who we serve</h3>
              <p></p>
            </div>
            <div class="about-block">
              <h3>Why we exist</h3>
              <p></p>
            </div>
          </div>
        </div>
      </section>

      <section class="contact" id="contact">
        <div class="container contact-grid">
          <div class="contact-copy">
            <p class="eyebrow">Contact Us</p>
            <h2>We'd Love to Hear From You</h2>
            <p>Have questions about our perfumes? Need help finding the right fragrance? Send us a message and we'll get back to you soon.</p>
          </div>
          <form class="contact-form">
            <label>
              Name
              <input type="text" name="name" placeholder="Your name" required />
            </label>
            <label>
              Email
              <input type="email" name="email" placeholder="you@example.com" required />
            </label>
            <label>
              Message
              <textarea name="message" rows="5" placeholder="Tell us about your perfume question"></textarea>
            </label>
            <button type="submit" class="button button-primary">Send Message</button>
          </form>
        </div>
      </section>
    </div>
  </main>

  <!-- Review System Modal -->
  <?php include 'review-modal.html'; ?>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-brand">
        <p class="footer-brand-name">LabScentique</p>
        <p class="footer-copy">&copy; 2026 LabScentique. Your destination for luxury fragrances.</p>
      </div>
      <div class="footer-links-group">
        <nav class="footer-nav" aria-label="Footer navigation">
          <a href="#home">Home</a>
          <a href="#products">Products</a>
          <a href="#news">News</a>
          <a href="accreditation.php">Accreditation</a>
          <a href="#contact">Contact</a>
        </nav>
        <div class="social-links" aria-label="Social media links">
          <a href="#" class="social-link" aria-label="Instagram">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5A4.25 4.25 0 0 0 20.5 16.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5Zm8.5 2.25a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5ZM12 7.25a4.75 4.75 0 1 1 0 9.5 4.75 4.75 0 0 1 0-9.5Zm0 1.5a3.25 3.25 0 1 0 0 6.5 3.25 3.25 0 0 0 0-6.5Z"/></svg>
          </a>
          <a href="#" class="social-link" aria-label="Facebook">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-8.25h2.75l.4-3.1h-3.15V8.5c0-.9.25-1.5 1.55-1.5h1.65V4.09c-.29-.04-1.28-.12-2.43-.12-2.4 0-4.05 1.47-4.05 4.18v2.33H8.25v3.1h2.2V21h3.05Z"/></svg>
          </a>
          <a href="#" class="social-link" aria-label="Twitter">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 5.92a8.59 8.59 0 0 1-2.48.68 4.32 4.32 0 0 0 1.88-2.38 8.63 8.63 0 0 1-2.73 1.05 4.3 4.3 0 0 0-7.33 3.92A12.21 12.21 0 0 1 3.1 4.97a4.28 4.28 0 0 0 1.33 5.74 4.26 4.26 0 0 1-1.95-.54v.05a4.3 4.3 0 0 0 3.45 4.22 4.3 4.3 0 0 1-1.94.07 4.3 4.3 0 0 0 4.01 2.98A8.63 8.63 0 0 1 2 19.55a12.16 12.16 0 0 0 6.59 1.93c7.9 0 12.22-6.55 12.22-12.23 0-.19 0-.38-.01-.57A8.7 8.7 0 0 0 22 5.92Z"/></svg>
          </a>
          <a href="#" class="social-link" aria-label="LinkedIn">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.94 8.5H4V20.5h2.94V8.5Zm-1.47-3.1a1.7 1.7 0 1 1 0 3.4 1.7 1.7 0 0 1 0-3.4ZM20.5 14.5c0-3.55-1.9-5.2-4.43-5.2-2.03 0-2.93 1.12-3.43 1.9v-1.63h-2.94c.04 1.08 0 12.2 0 12.2h2.94v-6.8c0-.36.03-.71.13-.97.28-.71.92-1.45 1.99-1.45 1.4 0 1.96 1.09 1.96 2.69v6.53h2.94v-6.47Z"/></svg>
          </a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
  <script>
    window.userData = {
      isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>,
      role: '<?php echo escape($_SESSION['role'] ?? ''); ?>',
      userId: <?php echo $_SESSION['user_id'] ?? 'null'; ?>
    };
  </script>
  <script src="validate.js"></script>
  <script src="app.js?v=<?php echo filemtime(__DIR__ . '/app.js'); ?>"></script>
  <script src="script.js"></script>
  <script src="review-system.js"></script>
</body>
</html>
