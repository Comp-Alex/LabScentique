const menuToggle = document.querySelector('.menu-toggle');
const sidebarNav = document.getElementById('sidebar-nav');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const sidebarNavClose = document.querySelector('.sidebar-nav-close');
const authModal = document.getElementById('auth-modal');
const authLinks = document.querySelectorAll('.auth-link');
const modalClose = authModal?.querySelector('.modal-close');
const modalTabs = authModal?.querySelectorAll('.modal-tab');
const modalPanels = authModal?.querySelectorAll('.modal-panel');

// User Menu Elements
const userMenuToggle = document.querySelector('.user-menu-toggle');
const userMenuDropdown = document.getElementById('user-menu-dropdown');
const userMenuItems = document.querySelectorAll('.user-menu-item, .logout-link');

// Profile Modals
const profileModal = document.getElementById('profile-modal');
const editProfileModal = document.getElementById('edit-profile-modal');
const changePasswordModal = document.getElementById('change-password-modal');
const profileModalClose = profileModal?.querySelector('.modal-close');
const editProfileModalClose = editProfileModal?.querySelector('.modal-close');
const changePasswordModalClose = changePasswordModal?.querySelector('.modal-close');

// Slide-out sidebar functionality
function openSidebar() {
  if (sidebarNav) {
    sidebarNav.classList.add('active');
  }
  if (sidebarOverlay) {
    sidebarOverlay.classList.add('active');
  }
  if (menuToggle) {
    menuToggle.classList.add('active');
  }
  document.body.classList.add('sidebar-open');
}

function closeSidebar() {
  if (sidebarNav) {
    sidebarNav.classList.remove('active');
  }
  if (sidebarOverlay) {
    sidebarOverlay.classList.remove('active');
  }
  if (menuToggle) {
    menuToggle.classList.remove('active');
  }
  document.body.classList.remove('sidebar-open');
}

if (menuToggle) {
  menuToggle.addEventListener('click', () => {
    if (sidebarNav?.classList.contains('active')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', closeSidebar);
}

if (sidebarNavClose) {
  sidebarNavClose.addEventListener('click', closeSidebar);
}

// Auth Modal Functions
function openAuthModal(target) {
  if (!authModal) return;
  authModal.classList.add('active');
  authModal.setAttribute('aria-hidden', 'false');
  modalTabs?.forEach(tab => {
    const active = tab.dataset.modalTab === target;
    tab.classList.toggle('active', active);
  });
  modalPanels?.forEach(panel => {
    const active = panel.dataset.modalPanel === target;
    panel.classList.toggle('active', active);
  });
}

function closeAuthModal() {
  if (!authModal) return;
  authModal.classList.remove('active');
  authModal.setAttribute('aria-hidden', 'true');
}

authLinks.forEach(link => {
  link.addEventListener('click', event => {
    event.preventDefault();
    openAuthModal(link.dataset.authTarget || 'login');
  });
});

modalClose?.addEventListener('click', closeAuthModal);

authModal?.addEventListener('click', event => {
  if (event.target === authModal) {
    closeAuthModal();
  }
});

modalTabs?.forEach(tab => {
  tab.addEventListener('click', () => {
    openAuthModal(tab.dataset.modalTab || 'login');
  });
});

// User Menu Dropdown
function toggleUserMenu() {
  if (!userMenuDropdown) return;
  const isOpen = userMenuDropdown.getAttribute('aria-hidden') === 'false';
  if (isOpen) {
    closeUserMenu();
  } else {
    openUserMenu();
  }
}

function openUserMenu() {
  if (!userMenuDropdown || !userMenuToggle) return;
  userMenuDropdown.setAttribute('aria-hidden', 'false');
  userMenuToggle.setAttribute('aria-expanded', 'true');
}

function closeUserMenu() {
  if (!userMenuDropdown || !userMenuToggle) return;
  userMenuDropdown.setAttribute('aria-hidden', 'true');
  userMenuToggle.setAttribute('aria-expanded', 'false');
}

if (userMenuToggle) {
  userMenuToggle.addEventListener('click', () => {
    toggleUserMenu();
  });
}

// Close menu when clicking outside
document.addEventListener('click', event => {
  if (userMenuToggle && !userMenuToggle.contains(event.target) && userMenuDropdown && !userMenuDropdown.contains(event.target)) {
    closeUserMenu();
  }
});

// User Menu Item Actions
userMenuItems.forEach(item => {
  item.addEventListener('click', async event => {
    const action = item.dataset.action;
    closeUserMenu();

    switch (action) {
      case 'view-profile':
        await loadAndShowProfile();
        break;
      case 'edit-profile':
        await loadAndShowEditProfile();
        break;
      case 'switch-account':
        switchAccount();
        break;
      case 'open-edit-profile':
        await loadAndShowEditProfile();
        if (profileModal) {
          profileModal.classList.remove('active');
          profileModal.setAttribute('aria-hidden', 'true');
        }
        break;
      case 'change-password':
        openModal(changePasswordModal);
        if (profileModal) {
          profileModal.classList.remove('active');
          profileModal.setAttribute('aria-hidden', 'true');
        }
        break;
      case 'cancel-edit':
        closeModal(editProfileModal);
        break;
      case 'cancel-password':
        closeModal(changePasswordModal);
        break;
    }
  });
});

// Profile Modal Functions
function openModal(modal) {
  if (!modal) return;
  modal.classList.add('active');
  modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
  if (!modal) return;
  modal.classList.remove('active');
  modal.setAttribute('aria-hidden', 'true');
}

// Close modals with X button
profileModalClose?.addEventListener('click', () => closeModal(profileModal));
editProfileModalClose?.addEventListener('click', () => closeModal(editProfileModal));
changePasswordModalClose?.addEventListener('click', () => closeModal(changePasswordModal));

// Close modals when clicking overlay
profileModal?.addEventListener('click', event => {
  if (event.target === profileModal) closeModal(profileModal);
});
editProfileModal?.addEventListener('click', event => {
  if (event.target === editProfileModal) closeModal(editProfileModal);
});
changePasswordModal?.addEventListener('click', event => {
  if (event.target === changePasswordModal) closeModal(changePasswordModal);
});

// Load and Display Profile
async function loadAndShowProfile() {
  try {
    const response = await fetch('api/user-profile.php?action=get');
    if (!response.ok) throw new Error('Failed to load profile');

    const result = await response.json();
    if (!result.success) throw new Error(result.error || 'Failed to load profile');

    const user = result.data;

    // Populate profile modal
    document.getElementById('profile-picture').src = user.profile_picture_url || 'assets/logo.svg';
    document.getElementById('profile-picture').onerror = () => { document.getElementById('profile-picture').src = 'assets/logo.svg'; };
    document.getElementById('profile-name').textContent = user.full_name || user.username;
    document.getElementById('profile-username').textContent = '@' + user.username;
    document.getElementById('profile-email').textContent = user.email;
    document.getElementById('profile-bio').textContent = user.bio || 'No bio added yet';
    document.getElementById('profile-role').textContent = capitalizeRole(user.role);
    document.getElementById('profile-created').textContent = new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    openModal(profileModal);
  } catch (error) {
    console.error('Error loading profile:', error);
    alert('Failed to load profile: ' + error.message);
  }
}

// Load and Display Edit Profile Form
async function loadAndShowEditProfile() {
  try {
    const response = await fetch('api/user-profile.php?action=get');
    if (!response.ok) throw new Error('Failed to load profile');

    const result = await response.json();
    if (!result.success) throw new Error(result.error || 'Failed to load profile');

    const user = result.data;
    const form = document.getElementById('edit-profile-form');

    if (form) {
      form.full_name.value = user.full_name || '';
      form.bio.value = user.bio || '';
      form.profile_picture_url.value = user.profile_picture_url || '';
    }

    openModal(editProfileModal);
  } catch (error) {
    console.error('Error loading edit profile:', error);
    alert('Failed to load profile: ' + error.message);
  }
}

// Handle Edit Profile Form Submission
const editProfileForm = document.getElementById('edit-profile-form');
if (editProfileForm) {
  editProfileForm.addEventListener('submit', async event => {
    event.preventDefault();

    const formData = new FormData(editProfileForm);
    const data = Object.fromEntries(formData);

    try {
      const response = await fetch('api/user-profile.php?action=update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      if (!response.ok) throw new Error('Failed to update profile');

      const result = await response.json();
      if (!result.success) throw new Error(result.error || 'Failed to update profile');

      alert('Profile updated successfully!');
      closeModal(editProfileModal);
      // Reload profile to show updated info
      await loadAndShowProfile();
    } catch (error) {
      console.error('Error updating profile:', error);
      alert('Failed to update profile: ' + error.message);
    }
  });
}

// Handle Change Password Form Submission
const changePasswordForm = document.getElementById('change-password-form');
if (changePasswordForm) {
  changePasswordForm.addEventListener('submit', async event => {
    event.preventDefault();

    const currentPassword = changePasswordForm.current_password.value;
    const newPassword = changePasswordForm.new_password.value;
    const confirmPassword = changePasswordForm.confirm_password.value;

    if (newPassword !== confirmPassword) {
      alert('New passwords do not match');
      return;
    }

    if (newPassword.length < 6) {
      alert('New password must be at least 6 characters');
      return;
    }

    try {
      const response = await fetch('api/user-profile.php?action=update_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          current_password: currentPassword,
          new_password: newPassword
        })
      });

      if (!response.ok) throw new Error('Failed to update password');

      const result = await response.json();
      if (!result.success) throw new Error(result.error || 'Failed to update password');

      alert('Password updated successfully!');
      changePasswordForm.reset();
      closeModal(changePasswordModal);
    } catch (error) {
      console.error('Error updating password:', error);
      alert('Failed to update password: ' + error.message);
    }
  });
}

// Switch Account (Logout and go to login)
function switchAccount() {
  if (confirm('Are you sure you want to switch accounts? You will be logged out.')) {
    window.location.href = '?logout=1';
  }
}

// Helper: Capitalize role text
function capitalizeRole(role) {
  return role.charAt(0).toUpperCase() + role.slice(1);
}

// Intro overlay
const introOverlay = document.getElementById('intro-overlay');
const heroSection = document.querySelector('.hero');

function hideIntroOverlay() {
  if (!introOverlay) {
    return;
  }
  introOverlay.classList.add('hidden');
  if (heroSection) {
    heroSection.classList.add('ready');
  }
  introOverlay.addEventListener('transitionend', () => {
    introOverlay.remove();
  }, { once: true });
}

document.addEventListener('DOMContentLoaded', () => {
  if (heroSection) {
    heroSection.classList.add('ready');
  }

  if (!introOverlay) {
    return;
  }

  const logoImage = introOverlay.querySelector('.intro-logo');
  if (logoImage) {
    logoImage.onerror = () => {
      logoImage.src = 'assets/logo.svg';
    };
  }

  window.setTimeout(hideIntroOverlay, 2200);
});

// ============================================
// FAVORITES & PURCHASES FUNCTIONALITY
// ============================================

/**
 * Add perfume to user favorites
 */
async function addToFavorites(perfumeId) {
  try {
    const response = await fetch('api/user-favorites.php?action=add_favorite', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ perfume_id: perfumeId })
    });

    const data = await response.json();
    
    if (data.success) {
      // Update UI
      const btn = document.querySelector(`[data-favorite-btn="${perfumeId}"]`);
      if (btn) {
        btn.classList.add('active');
        btn.textContent = '❤️ Favorited';
      }
      showNotification('Added to favorites!', 'success');
    } else {
      showNotification(data.error || 'Failed to add to favorites', 'error');
    }
  } catch (error) {
    console.error('Error adding to favorites:', error);
    showNotification('An error occurred', 'error');
  }
}

/**
 * Remove perfume from user favorites
 */
async function removeFromFavorites(perfumeId) {
  try {
    const response = await fetch('api/user-favorites.php?action=remove_favorite', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ perfume_id: perfumeId })
    });

    const data = await response.json();
    
    if (data.success) {
      // Update UI
      const btn = document.querySelector(`[data-favorite-btn="${perfumeId}"]`);
      if (btn) {
        btn.classList.remove('active');
        btn.textContent = '🤍 Add to Favorites';
      }
      showNotification('Removed from favorites', 'success');
    } else {
      showNotification(data.error || 'Failed to remove from favorites', 'error');
    }
  } catch (error) {
    console.error('Error removing from favorites:', error);
    showNotification('An error occurred', 'error');
  }
}

/**
 * Record a purchase
 */
async function purchasePerfume(perfumeId, quantity = 1) {
  try {
    const response = await fetch('api/user-favorites.php?action=add_purchase', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ 
        perfume_id: perfumeId,
        quantity: quantity
      })
    });

    const data = await response.json();
    
    if (data.success) {
      showNotification('Purchase recorded successfully!', 'success');
      return true;
    } else {
      showNotification(data.error || 'Failed to record purchase', 'error');
      return false;
    }
  } catch (error) {
    console.error('Error recording purchase:', error);
    showNotification('An error occurred', 'error');
    return false;
  }
}

/**
 * Toggle favorite status
 */
async function toggleFavorite(perfumeId, isFavorited) {
  if (isFavorited) {
    await removeFromFavorites(perfumeId);
  } else {
    await addToFavorites(perfumeId);
  }
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `notification notification-${type}`;
  toast.textContent = message;
  
  Object.assign(toast.style, {
    position: 'fixed',
    bottom: '20px',
    right: '20px',
    padding: '15px 20px',
    borderRadius: '8px',
    color: 'white',
    zIndex: '9999',
    animation: 'slideIn 0.3s ease-out',
    fontSize: '14px',
    boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
  });

  if (type === 'success') {
    toast.style.background = '#28a745';
  } else if (type === 'error') {
    toast.style.background = '#dc3545';
  } else {
    toast.style.background = '#667eea';
  }

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

/**
 * Check if perfume is in user's favorites
 */
async function checkIfFavorited(perfumeId) {
  try {
    const response = await fetch('api/user-favorites.php?action=get_favorites');
    const data = await response.json();
    
    if (data.data) {
      return data.data.some(fav => fav.perfume_id === perfumeId);
    }
    return false;
  } catch (error) {
    console.error('Error checking favorites:', error);
    return false;
  }
}

/**
 * Initialize favorite buttons on page load
 */
function initializeFavoriteButtons() {
  const favoriteButtons = document.querySelectorAll('[data-favorite-btn]');
  favoriteButtons.forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const perfumeId = parseInt(btn.dataset.favoritBtn);
      const isFavorited = btn.classList.contains('active');
      await toggleFavorite(perfumeId, isFavorited);
    });
  });
}

// Add CSS animations if not already present
if (!document.querySelector('style[data-favorites]')) {
  const style = document.createElement('style');
  style.setAttribute('data-favorites', 'true');
  style.textContent = `
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .notification {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    [data-favorite-btn] {
      transition: all 0.3s ease;
      border: 2px solid #ddd;
      background: white;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
    }

    [data-favorite-btn].active {
      background: #ffc107;
      border-color: #ffc107;
      color: white;
    }

    [data-favorite-btn]:hover {
      transform: scale(1.05);
    }
  `;
  document.head.appendChild(style);
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initializeFavoriteButtons);


