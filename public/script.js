const menuToggle = document.querySelector('.menu-toggle');
const sidebarNav = document.getElementById('sidebar-nav');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const sidebarNavClose = document.querySelector('.sidebar-nav-close');
const authModal = document.getElementById('auth-modal');
const authLinks = document.querySelectorAll('.auth-link');
const modalClose = authModal?.querySelector('.modal-close');
const modalTabs = authModal?.querySelectorAll('.modal-tab');
const modalPanels = authModal?.querySelectorAll('.modal-panel');

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
