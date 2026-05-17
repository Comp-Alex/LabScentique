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
  heading: 'Discovering the perfect fragrance should be an inspiring journey, not an overwhelming task.',
  intro: 'LabScentique represents a sophisticated web-based platform designed to empower discerning individuals in their exploration and discovery of perfumes meticulously tailored to their distinctive preferences. Concurrently, the platform integrates a comprehensive backend infrastructure that enables administrative personnel to meticulously monitor inventory lifecycles and provides business proprietors with a centralized, intuitive dashboard for orchestrating daily operational activities.',
  details: 'Whether one identifies as a fragrance connoisseur pursuing intricate scent profiles or a business executive seeking to optimize operational efficiency, LabScentique seamlessly amalgamates personalized olfactory recommendations, vibrant community interactions, and streamlined business processes into a cohesive, user-centric experience. Through the harmonious fusion of perfumery expertise and cutting-edge technological innovation, we facilitate the identification of signature scents for discerning customers while equipping retailers with sophisticated tools to maintain impeccable inventory organization and operational precision.',
  features: [
    'Sophisticated Personalized Recommendations – Employ advanced algorithmic analysis to assist users in identifying perfumes that harmoniously align with their preferred meteorological conditions, ceremonial occasions, and olfactory typologies, ensuring a bespoke fragrance selection process.',
    'Comprehensive Educational Resources and Community Engagement – Deliver meticulously curated information regarding perfume olfactory components while fostering an interactive platform for users to exchange personal fragrance narratives and experiential insights.',
    'Advanced Inventory and Restocking Management System – Optimize backend operational workflows by enabling staff personnel to conduct real-time stock surveillance, systematically document compromised or expired merchandise, and autonomously generate procurement requisitions.',
    'Strategic Business Intelligence and Decision Support Framework – Furnish business proprietors with an analytical dashboard encompassing performance metrics, operational insights, and decision-support functionalities to evaluate activities, authorize procurement strategies, and propel organizational growth.',
  ],
  audience: 'LabScentique is meticulously designed to serve a discerning clientele encompassing fragrance novices seeking accessible guidance, occasion-specific consumers desiring meticulously curated recommendations, dedicated collectors and olfactory enthusiasts pursuing rare and exceptional aromatic compositions, and professional management teams necessitating sophisticated analytical tools for operational oversight, inventory management, and strategic business planning initiatives.',
  benefits: 'LabScentique elevates the fragrance exploration paradigm into an enlightened and profoundly pleasurable odyssey. Through the seamless integration of olfactory passion with state-of-the-art business technology, we facilitate the revelation of cherished scents for discerning consumers while equipping vendors with comprehensive instrumentation for operational excellence and inventory precision. We transcend conventional platform functionality—we serve as your dedicated partner in fragrance discovery, business management, and olfactory sophistication.',
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

      const response = await fetch(url, { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch perfumes');

      const result = await response.json();
      return result.data || FALLBACK_PERFUMES;
    } catch (error) {
      console.error('Error fetching perfumes:', error);
      return FALLBACK_PERFUMES;
    }
  },

  async getPerfumeById(perfumeId) {
    const perfumes = await this.getPerfumes();
    return perfumes.find(perfume => String(perfume.id) === String(perfumeId)) || null;
  },

  /**
   * Fetch about info from API
   */
  async getAbout() {
    try {
      const url = this.baseUrl + '?action=about';

      const response = await fetch(url, { cache: 'no-store' });
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

  async addToCart(perfumeId, quantity = 1) {
    try {
      const formData = new FormData();
      formData.append('action', 'cart_add');
      formData.append('perfume_id', perfumeId);
      formData.append('quantity', quantity);

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to add to cart');
      }

      return await response.json();
    } catch (error) {
      console.error('Error adding to cart:', error);
      throw error;
    }
  },

  async getCart() {
    try {
      const response = await fetch(this.baseUrl + '?action=cart_items', { cache: 'no-store' });
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to fetch cart');
      }
      return await response.json();
    } catch (error) {
      console.error('Error fetching cart:', error);
      return { success: false, data: [], cart_count: 0 };
    }
  },

  async checkoutCart() {
    try {
      const formData = new FormData();
      formData.append('action', 'cart_checkout');

      const response = await fetch(this.baseUrl, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Checkout failed');
      }
      return await response.json();
    } catch (error) {
      console.error('Error checking out cart:', error);
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

  normalizeLocalImagePath(rawPath) {
    let path = rawPath.replace(/\/+/g, '/');
    const lastAssetsIndex = path.lastIndexOf('/assets/');
    if (lastAssetsIndex !== -1) {
      path = path.slice(lastAssetsIndex);
    }
    return path.startsWith('/') ? path : `/${path.replace(/^\.\/+/, '')}`;
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
    const canPurchase = isLoggedIn && window.userData.role !== 'staff';
    const rawImageUrl = perfume.image_url || '';
    const imageSrc = rawImageUrl
      ? (() => {
          if (/^[a-zA-Z][a-zA-Z0-9+.-]*:/.test(rawImageUrl) || rawImageUrl.startsWith('//')) {
            return rawImageUrl;
          }
          const normalizedPath = this.normalizeLocalImagePath(rawImageUrl);
          if (normalizedPath.startsWith('/assets/')) {
            return normalizedPath;
          }
          if (normalizedPath.startsWith('/perfume_images/')) {
            return '/assets' + normalizedPath;
          }
          return normalizedPath.startsWith('/') ? normalizedPath : `/${normalizedPath.replace(/^\.\/+/, '')}`;
        })()
      : (perfume.id ? `/assets/perfumes/${encodeURIComponent(perfume.id)}.jpg` : '/assets/placeholder-perfume.svg');

    return `
      <article class="feature-card" id="${this.escapeHtml(perfume.name).toLowerCase().replace(/\s+/g, '-')}">
        <img src="${this.escapeHtml(imageSrc)}" alt="${this.escapeHtml(perfume.name)} Perfume" loading="lazy" onerror="this.onerror=null;this.src='/assets/placeholder-perfume.svg'" />
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
          canPurchase
            ? `<div class="perfume-purchase">
                <input type="number" min="1" max="10" value="1" class="quantity-input" data-perfume-id="${perfume.id}" />
                <button type="button" class="button button-secondary add-cart-btn icon-button" data-perfume-id="${perfume.id}" aria-label="Add ${this.escapeHtml(perfume.name)} to cart" title="Add to cart">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2Zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2ZM7 6h1.74l.65 3H18a1 1 0 0 1 .95 1.32l-1.5 4.5A1 1 0 0 1 16.5 15H9.6l-.3 1.2c-.07.27-.33.46-.61.46H7a1 1 0 0 1 0-2h1.59l.75-3H6a1 1 0 0 1 0-2h1.12L7 6Zm2.5 0h7l.4 2.4H9.3L9.5 6Z"/></svg>
                </button>
                <button type="button" class="button button-primary purchase-btn" data-perfume-id="${perfume.id}">Proceed to payment</button>
                <button type="button" class="button button-secondary review-btn icon-button" data-perfume-id="${perfume.id}" data-perfume-name="${this.escapeHtml(perfume.name)}" title="Leave a review" aria-label="Leave a review for ${this.escapeHtml(perfume.name)}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                </button>
              </div>`
            : isLoggedIn
            ? '<div class="perfume-info">Only customer accounts can purchase items.</div>'
            : '<div class="perfume-info">Login to purchase</div>'
        }
        <div class="perfume-review-section">
          <button type="button" class="button button-outline review-trigger-btn" data-perfume-id="${perfume.id}" data-perfume-name="${this.escapeHtml(perfume.name)}" title="Leave a review" aria-label="Leave a review for ${this.escapeHtml(perfume.name)}">
            💬 Leave a Review
          </button>
        </div>
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

      const response = await fetch(url, { cache: 'no-store' });
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
      button.addEventListener('click', (e) => {
        const target = e.currentTarget;
        const perfumeId = target.dataset.perfumeId;
        const quantityInput = this.container.querySelector(`.quantity-input[data-perfume-id="${perfumeId}"]`);
        const quantity = parseInt(quantityInput.value) || 1;
        window.location.href = `payment.php?perfume_id=${encodeURIComponent(perfumeId)}&quantity=${encodeURIComponent(quantity)}`;
      });
    });

    const cartButtons = this.container.querySelectorAll('.add-cart-btn');
    cartButtons.forEach(button => {
      button.addEventListener('click', async (e) => {
        const target = e.currentTarget;
        const perfumeId = target.dataset.perfumeId;
        const quantityInput = this.container.querySelector(`.quantity-input[data-perfume-id="${perfumeId}"]`);
        const quantity = parseInt(quantityInput.value) || 1;

        try {
          const result = await API.addToCart(perfumeId, quantity);
          alert(result.message);
          await CartRenderer.render();
        } catch (error) {
          alert('Add to cart failed: ' + error.message);
        }
      });
    });

    // Add review button listeners
    const reviewButtons = this.container.querySelectorAll('.review-trigger-btn, .review-btn');
    reviewButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        const target = e.currentTarget;
        const perfumeId = target.dataset.perfumeId;
        const perfumeName = target.dataset.perfumeName;
        
        if (typeof ReviewSystem !== 'undefined') {
          ReviewSystem.openModal(perfumeId, perfumeName);
        } else {
          alert('Review system not loaded. Please refresh the page.');
        }
      });
    });
  },

  renderInlineReceipt(perfumeId, receipt, paymentMethod = 'card') {
    const receiptContainer = this.container.querySelector(`.perfume-receipt[data-perfume-id="${perfumeId}"]`);
    if (!receiptContainer) return;

    receiptContainer.innerHTML = `
      <div class="receipt-card perfume-receipt-card floating-receipt-card">
        <div class="receipt-header">
          <h4>Purchase receipt</h4>
          <p>${new Date(receipt.purchased_at).toLocaleString()}</p>
        </div>
        <div class="receipt-method">Payment method: ${this.escapeHtml(paymentMethod)}</div>
        ${receipt.items.map(item => `
          <div class="receipt-item">
            <div class="receipt-item-name">${this.escapeHtml(item.name)}</div>
            <div class="receipt-item-meta">Qty: ${item.quantity}${item.remaining_stock !== undefined ? ` · Remaining: ${item.remaining_stock}` : ''}</div>
          </div>
        `).join('')}
        <div class="receipt-total">Total items: ${receipt.item_count}</div>
      </div>
    `;
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

const CartRenderer = {
  container: null,
  toggleButton: null,

  init() {
    this.createCartPanel();
    this.container = document.querySelector('.cart-panel');
    this.toggleButton = document.querySelector('.cart-toggle');

    const isLoggedIn = window.userData && window.userData.isLoggedIn;
    const canUseCart = isLoggedIn && window.userData.role !== 'staff';
    if (!canUseCart) {
      if (this.toggleButton) {
        this.toggleButton.style.display = 'none';
      }
      if (this.container) {
        this.container.style.display = 'none';
      }
      return;
    }

    if (this.toggleButton) {
      this.toggleButton.addEventListener('click', () => this.open());
    }
  },

  createCartPanel() {
    if (document.querySelector('.cart-panel')) return;

    const cartPanel = document.createElement('aside');
    cartPanel.className = 'cart-panel';
    cartPanel.innerHTML = `
      <div class="cart-header">
        <h3>My Cart</h3>
        <button type="button" class="button button-secondary cart-close">×</button>
      </div>
      <div class="cart-body">Loading cart...</div>
      <div class="cart-footer">
        <button type="button" class="button button-primary cart-checkout">Checkout</button>
      </div>
    `;

    const cartButton = document.createElement('button');
    cartButton.type = 'button';
    cartButton.className = 'button button-primary cart-toggle icon-button';
    cartButton.innerHTML = `<span class="sr-only">Open cart</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2Zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2ZM7 6h1.74l.65 3H18a1 1 0 0 1 .95 1.32l-1.5 4.5A1 1 0 0 1 16.5 15H9.6l-.3 1.2c-.07.27-.33.46-.61.46H7a1 1 0 0 1 0-2h1.59l.75-3H6a1 1 0 0 1 0-2h1.12L7 6Zm2.5 0h7l.4 2.4H9.3L9.5 6Z"/></svg>`;
    cartButton.style.position = 'fixed';
    cartButton.style.bottom = '20px';
    cartButton.style.right = '20px';
    cartButton.style.zIndex = '999';
    cartButton.style.width = '56px';
    cartButton.style.height = '56px';
    cartButton.style.padding = '0';
    cartButton.style.borderRadius = '50%';
    cartButton.style.boxShadow = '0 10px 30px rgba(92, 47, 255, 0.24)';

    cartPanel.style.position = 'fixed';
    cartPanel.style.top = '20px';
    cartPanel.style.right = '20px';
    cartPanel.style.width = '320px';
    cartPanel.style.maxHeight = '80vh';
    cartPanel.style.overflowY = 'auto';
    cartPanel.style.background = 'rgba(15, 23, 42, 0.95)';
    cartPanel.style.border = '1px solid rgba(139, 92, 246, 0.28)';
    cartPanel.style.borderRadius = '1.5rem';
    cartPanel.style.boxShadow = '0 28px 80px rgba(20, 20, 60, 0.35)';
    cartPanel.style.padding = '16px';
    cartPanel.style.display = 'none';
    cartPanel.style.zIndex = '998';

    document.body.appendChild(cartPanel);
    document.body.appendChild(cartButton);

    cartPanel.querySelector('.cart-close').addEventListener('click', () => this.close());

    const paymentUrl = (() => {
      const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
      return basePath + 'payment.php?cart=1';
    })();

    cartPanel.addEventListener('click', (event) => {
      const checkoutButton = event.target.closest('.cart-checkout');
      if (!checkoutButton) return;
      window.location.href = paymentUrl;
    });
  },

  async render() {
    if (!this.container) return;
    this.container.classList.remove('receipt-visible');
    const result = await API.getCart();
    const items = result.data || [];

    if (items.length === 0) {
      this.container.querySelector('.cart-body').innerHTML = '<p>Your cart is empty.</p>';
      return;
    }

    this.container.querySelector('.cart-body').innerHTML = items.map(item => `
      <div class="cart-item">
        <div class="cart-item-content">
          <div class="cart-item-name">${this.escapeHtml(item.name)}</div>
          <div class="cart-item-meta">${item.quantity} unit(s)</div>
        </div>
      </div>
    `).join('');
    this.container.querySelector('.cart-body').insertAdjacentHTML('beforeend', `<p style="margin-top: 12px; font-weight: 600;">Total items: ${items.reduce((sum, item) => sum + item.quantity, 0)}</p>`);
  },

  async renderReceipt(receipt) {
    if (!this.container) return;

    this.container.querySelector('.cart-body').innerHTML = receipt && receipt.items?.length ? `
      <div class="receipt-card">
        <div class="receipt-header">
          <h3>Purchase Receipt</h3>
          <p>${new Date(receipt.purchased_at).toLocaleString()}</p>
        </div>
        <div class="receipt-items">
          ${receipt.items.map(item => `
            <div class="receipt-item">
              <div class="receipt-item-name">${this.escapeHtml(item.name)}</div>
              <div class="receipt-item-meta">Qty: ${item.quantity}${item.remaining_stock !== undefined ? ` · Remaining: ${item.remaining_stock}` : ''}</div>
            </div>
          `).join('')}
        </div>
        <div class="receipt-total">Total items: ${receipt.item_count}</div>
      </div>
    ` : `
      <div class="receipt-card">
        <p>No receipt details available.</p>
      </div>
    `;
    this.container.classList.add('receipt-visible');
    this.open();
  },

  open() {
    if (!this.container) return;
    this.container.style.display = 'block';
    if (!this.container.classList.contains('receipt-visible')) {
      this.render();
    }
  },

  close() {
    if (!this.container) return;
    this.container.style.display = 'none';
  },

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
    const featuresContainer = document.querySelector('.about-details .about-block:first-child .about-features');
    if (featuresContainer && aboutData.features && Array.isArray(aboutData.features)) {
      featuresContainer.innerHTML = aboutData.features.map(f => `<p>${this.escapeHtml(f)}</p>`).join('');
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
  CartRenderer.init();

  // Load and render perfumes
  await PerfumeRenderer.render();

  // Load and render about info
  await AboutRenderer.render();

  // Setup event handlers
  setupContactFormHandler();
  setupSearchHandler();
});
