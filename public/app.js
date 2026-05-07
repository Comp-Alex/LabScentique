/**
 * API Data Management & Client-side Rendering
 * Reduces PHP load by fetching and rendering data on the frontend
 */

// Fallback data if database is unavailable
const FALLBACK_PERFUMES = [
  {
    id: 1,
    name: 'Khamrah',
    description: 'A rich oriental fragrance blending saffron, rose, and amber for a warm, sensual experience.',
    image_url: 'https://fimgs.net/mdimg/perfume/375x500.75805.jpg',
    top_notes: 'Saffron, Rose, Bergamot',
    heart_notes: 'Amber, Patchouli, Jasmine',
    base_notes: 'Vanilla, Musk, Sandalwood',
    accords: 'Oriental, Floral, Warm',
    rating: 4.2,
  },
  {
    id: 2,
    name: 'Le Male Elixir',
    description: 'An elixir version of the classic Le Male, with enhanced lavender and mint.',
    image_url: 'https://fimgs.net/mdimg/perfume/375x500.81642.jpg',
    top_notes: 'Lavender, Mint, Cardamom',
    heart_notes: 'Orange Blossom, Cinnamon',
    base_notes: 'Vanilla, Tonka Bean, Sandalwood',
    accords: 'Aromatic, Fresh, Sweet',
    rating: 4.4,
  },
  {
    id: 3,
    name: 'Millésime Impérial',
    description: 'A luxurious chypre with citrus, floral, and woody notes.',
    image_url: 'https://fimgs.net/mdimg/perfume/375x500.466.jpg',
    top_notes: 'Bergamot, Mandarin',
    heart_notes: 'Jasmine, Rose',
    base_notes: 'Patchouli, Sandalwood, Amber',
    accords: 'Chypre, Floral, Woody',
    rating: 4.6,
  },
];

const FALLBACK_ABOUT = {
  heading: 'Finding the perfect fragrance shouldn\'t be overwhelming it should be inspiring.',
  intro: 'LabScentique is a web-based platform designed to make perfume discovery simple, personal, and enjoyable, while also helping businesses manage their inventory with ease.',
  details: 'We combine fragrance passion with business precision helping users find their signature scent while empowering owners to make smarter decisions. LabScentique isn\'t just a platform; it\'s your partner in perfume discovery and management.',
  features: [
    'Personalized Recommendations – Discover scents tailored to your style, occasion, and even the weather.',
    'Community & Learning – Share reviews, explore fragrance notes, and connect with fellow enthusiasts.',
    'Smart Inventory Management – For retailers, track stocks, log expirations, and streamline restocking with a powerful dashboard.',
  ],
  audience: 'Whether you\'re a beginner exploring perfumes, a collector seeking rare notes, or a business owner managing daily operations, LabScentique brings everything together in one seamless experience.',
  benefits: 'We combine fragrance passion with business precision—helping users find their signature scent while empowering owners to make smarter decisions. LabScentique isn\'t just a platform; it\'s your partner in perfume discovery and management.',
};

const API = {
  baseUrl: '/api/data.php',

  /**
   * Fetch perfumes from API
   */
  async getPerfumes(search = '') {
    try {
      let url = this.baseUrl + '?action=perfumes';
      if (search) {
        url += '&search=' + encodeURIComponent(search);
      }

      const response = await fetch(url);
      if (!response.ok) throw new Error('Failed to fetch perfumes');

      const result = await response.json();
      return result.data || FALLBACK_PERFUMES;
    } catch (error) {
      console.error('Error fetching perfumes:', error);
      return FALLBACK_PERFUMES;
    }
  },

  /**
   * Fetch about info from API
   */
  async getAbout() {
    try {
      const url = this.baseUrl + '?action=about';

      const response = await fetch(url);
      if (!response.ok) throw new Error('Failed to fetch about info');

      const result = await response.json();
      const data = result.data || {};

      return {
        heading: data.heading || FALLBACK_ABOUT.heading,
        intro: data.intro || FALLBACK_ABOUT.intro,
        details: data.details || FALLBACK_ABOUT.details,
        features: Array.isArray(data.features) && data.features.length ? data.features : FALLBACK_ABOUT.features,
        audience: data.audience || FALLBACK_ABOUT.audience,
        benefits: data.benefits || FALLBACK_ABOUT.benefits,
        stat_1_value: data.stat_1_value || FALLBACK_ABOUT.stat_1_value,
        stat_1_label: data.stat_1_label || FALLBACK_ABOUT.stat_1_label,
        stat_2_value: data.stat_2_value || FALLBACK_ABOUT.stat_2_value,
        stat_2_label: data.stat_2_label || FALLBACK_ABOUT.stat_2_label,
        stat_3_value: data.stat_3_value || FALLBACK_ABOUT.stat_3_value,
        stat_3_label: data.stat_3_label || FALLBACK_ABOUT.stat_3_label,
      };
    } catch (error) {
      console.error('Error fetching about info:', error);
      return FALLBACK_ABOUT;
    }
  },

  /**
   * Purchase a perfume
   */
  async purchasePerfume(perfumeId, quantity = 1) {
    try {
      const formData = new FormData();
      formData.append('action', 'purchase_perfume');
      formData.append('perfume_id', perfumeId);
      formData.append('quantity', quantity);

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Purchase failed');
      }

      const result = await response.json();
      return result;
    } catch (error) {
      console.error('Error purchasing perfume:', error);
      throw error;
    }
  },

  /**
   * Submit contact form via API
   */
  async submitContact(name, email, message) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'contact',
          name,
          email,
          message,
        }),
      });

      if (!response.ok) throw new Error('Failed to submit contact');

      const result = await response.json();
      return result;
    } catch (error) {
      console.error('Error submitting contact:', error);
      return { error: error.message };
    }
  },
};

/**
 * Perfume Renderer - Display perfumes from API data
 */
const PerfumeRenderer = {
  container: null,
  perfumes: [],

  init(containerSelector = '.feature-grid') {
    this.container = document.querySelector(containerSelector);
    if (!this.container) {
      console.warn('Perfume container not found');
      return;
    }
  },

  /**
   * Create a perfume card HTML
   */
  createCard(perfume) {
    const ratingValue = parseFloat(perfume.rating || 0);
    const fullStars = Math.floor(ratingValue);
    const halfStar = ratingValue % 1 >= 0.5 ? '☆' : '';
    const ratingStars = '★'.repeat(fullStars) + halfStar;

    const isLoggedIn = window.userData && window.userData.isLoggedIn;
    const isRegistered = window.userData && window.userData.role === 'registered';

    return `
      <article class="feature-card" id="${this.escapeHtml(perfume.name).toLowerCase().replace(/\s+/g, '-')}">
        ${
          perfume.image_url
            ? `<img src="${this.escapeHtml(perfume.image_url)}" alt="${this.escapeHtml(perfume.name)} Perfume" />`
            : ''
        }
        <h3>${this.escapeHtml(perfume.name)}</h3>
        <p>${this.escapeHtml(perfume.description)}</p>
        <div class="perfume-details">
          ${
            perfume.top_notes
              ? `<strong>Top Notes:</strong> ${this.escapeHtml(perfume.top_notes)}<br>`
              : ''
          }
          ${
            perfume.heart_notes
              ? `<strong>Heart Notes:</strong> ${this.escapeHtml(perfume.heart_notes)}<br>`
              : ''
          }
          ${
            perfume.base_notes
              ? `<strong>Base Notes:</strong> ${this.escapeHtml(perfume.base_notes)}<br>`
              : ''
          }
          ${perfume.accords ? `<strong>Accords:</strong> ${this.escapeHtml(perfume.accords)}` : ''}
        </div>
        <div class="rating">${ratingStars} (${perfume.rating}/5)</div>
        <div class="perfume-stock" data-perfume-id="${perfume.id}">Loading stock...</div>
        ${
          isRegistered
            ? `<div class="perfume-purchase">
                <input type="number" min="1" max="10" value="1" class="quantity-input" data-perfume-id="${perfume.id}" />
                <button type="button" class="button button-primary purchase-btn" data-perfume-id="${perfume.id}">Purchase</button>
              </div>`
            : isLoggedIn
            ? '<div class="perfume-info">Login as customer to purchase</div>'
            : '<div class="perfume-info">Login to purchase</div>'
        }
      </article>
    `;
  },

  /**
   * Render perfumes to the DOM
   */
  async render(search = '') {
    if (!this.container) return;

    this.container.innerHTML = '<p>Loading perfumes...</p>';

    const perfumes = await API.getPerfumes(search);
    this.perfumes = perfumes;

    if (perfumes.length === 0) {
      this.container.innerHTML = '<p>No perfumes found.</p>';
      return;
    }

    this.container.innerHTML = perfumes.map(p => this.createCard(p)).join('');

    // Load stock for each perfume
    await this.loadStockForAllPerfumes();

    // Add purchase event listeners
    this.addPurchaseListeners();
  },

  /**
   * Filter perfumes by search term (client-side)
   */
  filterBySearch(term) {
    if (!term.trim()) {
      return this.perfumes;
    }

    const lowerTerm = term.toLowerCase();
    return this.perfumes.filter(
      p =>
        p.name.toLowerCase().includes(lowerTerm) ||
        p.description.toLowerCase().includes(lowerTerm) ||
        p.top_notes?.toLowerCase().includes(lowerTerm) ||
        p.heart_notes?.toLowerCase().includes(lowerTerm) ||
        p.base_notes?.toLowerCase().includes(lowerTerm)
    );
  },

  /**
   * Load stock information for all perfumes
   */
  async loadStockForAllPerfumes() {
    const stockElements = this.container.querySelectorAll('.perfume-stock');
    for (const element of stockElements) {
      const perfumeId = element.dataset.perfumeId;
      try {
        const stock = await this.getPerfumeStock(perfumeId);
        element.textContent = `Stock: ${stock}`;
        element.classList.toggle('out-of-stock', stock === 0);
      } catch (error) {
        element.textContent = 'Stock: Unknown';
      }
    }
  },

  /**
   * Get stock for a specific perfume
   */
  async getPerfumeStock(perfumeId) {
    try {
      const url = API.baseUrl + '?action=inventory&perfume_id=' + perfumeId;

      const response = await fetch(url);
      if (!response.ok) throw new Error('Failed to fetch stock');

      const result = await response.json();
      return result.stock || 0;
    } catch (error) {
      console.error('Error fetching stock:', error);
      return 0;
    }
  },

  /**
   * Add event listeners for purchase buttons
   */
  addPurchaseListeners() {
    const purchaseButtons = this.container.querySelectorAll('.purchase-btn');
    purchaseButtons.forEach(button => {
      button.addEventListener('click', async (e) => {
        const perfumeId = e.target.dataset.perfumeId;
        const quantityInput = this.container.querySelector(`.quantity-input[data-perfume-id="${perfumeId}"]`);
        const quantity = parseInt(quantityInput.value) || 1;

        try {
          const result = await API.purchasePerfume(perfumeId, quantity);
          alert(result.message);
          
          // Update stock display
          const stockElement = this.container.querySelector(`.perfume-stock[data-perfume-id="${perfumeId}"]`);
          if (stockElement) {
            stockElement.textContent = `Stock: ${result.remaining_stock}`;
            stockElement.classList.toggle('out-of-stock', result.remaining_stock === 0);
          }
        } catch (error) {
          alert('Purchase failed: ' + error.message);
        }
      });
    });
  },

  /**
   * Escape HTML to prevent XSS
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
};

/**
 * About Info Renderer
 */
const AboutRenderer = {
  async render() {
    const aboutData = await API.getAbout();

    // Update heading section
    const headingEl = document.querySelector('.about h2');
    if (headingEl && aboutData.heading) {
      headingEl.textContent = aboutData.heading;
    }

    const introEl = document.querySelector('.about > div:first-child > p:nth-of-type(1)');
    if (introEl && aboutData.intro) {
      introEl.textContent = aboutData.intro;
    }

    const detailsEl = document.querySelector('.about > div:first-child > p:nth-of-type(2)');
    if (detailsEl && aboutData.details) {
      detailsEl.textContent = aboutData.details;
    }

    // Update features
    const featuresList = document.querySelector('.about-details .about-block:first-child ul');
    if (featuresList && aboutData.features && Array.isArray(aboutData.features)) {
      featuresList.innerHTML = aboutData.features.map(f => `<li>${this.escapeHtml(f)}</li>`).join('');
    }

    // Update audience
    const audienceEl = document.querySelector('.about-details .about-block:nth-child(2) p');
    if (audienceEl && aboutData.audience) {
      audienceEl.textContent = aboutData.audience;
    }

    // Update benefits
    const benefitsEl = document.querySelector('.about-details .about-block:nth-child(3) p');
    if (benefitsEl && aboutData.benefits) {
      benefitsEl.textContent = aboutData.benefits;
    }
  },

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },
};

/**
 * Contact Form Handler - Use API instead of PHP form submission
 */
function setupContactFormHandler() {
  const contactForm = document.querySelector('.contact-form');
  if (!contactForm) return;

  contactForm.addEventListener('submit', async e => {
    e.preventDefault();

    const formData = new FormData(contactForm);
    const name = formData.get('name') || '';
    const email = formData.get('email') || '';
    const message = formData.get('message') || '';

    // Client-side validation
    const validation = Validator.validateContact(name, email, message);
    if (!validation.valid) {
      Validator.showErrors(contactForm, validation.errors);
      return;
    }

    // Remove old error/success messages
    const existingStatus = contactForm.querySelector('.form-status');
    if (existingStatus) {
      existingStatus.remove();
    }

    // Submit via API
    const result = await API.submitContact(name, email, message);

    const statusDiv = document.createElement('div');
    if (result.error) {
      statusDiv.className = 'form-status error';
      statusDiv.textContent = result.error;
    } else {
      statusDiv.className = 'form-status success';
      statusDiv.textContent = result.message || 'Thank you! Your message was received.';
      contactForm.reset();
    }

    contactForm.insertBefore(statusDiv, contactForm.firstChild);

    // Auto-remove success message after 5 seconds
    if (!result.error) {
      setTimeout(() => statusDiv.remove(), 5000);
    }
  });
}

/**
 * Search Handler - Load perfumes and filter client-side
 */
function setupSearchHandler() {
  const searchForm = document.querySelector('.search-bar form');
  const searchInput = searchForm?.querySelector('input[name="search"]');

  if (searchForm && searchInput) {
    searchForm.addEventListener('submit', e => {
      e.preventDefault();
      const searchTerm = searchInput.value;
      PerfumeRenderer.render(searchTerm);
    });
  }

  // Also handle real-time search (optional)
  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', e => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        PerfumeRenderer.render(e.target.value);
      }, 300);
    });
  }
}

/**
 * Initialize everything on DOM load
 */
document.addEventListener('DOMContentLoaded', async () => {
  // Initialize renderers
  PerfumeRenderer.init();

  // Load and render perfumes
  await PerfumeRenderer.render();

  // Load and render about info
  await AboutRenderer.render();

  // Setup event handlers
  setupContactFormHandler();
  setupSearchHandler();
});
