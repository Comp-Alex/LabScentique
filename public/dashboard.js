/**
 * Dashboard Management
 * Handles all dashboard UI, data fetching, and form submissions
 */

const DashboardAPI = {
  baseUrl: '../api/dashboard.php',

  async getUserInfo() {
    try {
      const response = await fetch(this.baseUrl + '?action=user_info', { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch user info');
      return await response.json();
    } catch (error) {
      console.error('Error fetching user info:', error);
      return null;
    }
  },

  async getPerfumes(roleFilter = 'all') {
    try {
      const response = await fetch(this.baseUrl + '?action=perfumes&role_filter=' + roleFilter, { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch perfumes');
      const result = await response.json();
      return result.data || [];
    } catch (error) {
      console.error('Error fetching perfumes:', error);
      return [];
    }
  },

  async getInventory(roleFilter = 'all') {
    try {
      const response = await fetch(this.baseUrl + '?action=inventory&role_filter=' + roleFilter, { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch inventory');
      const result = await response.json();
      return result.data || [];
    } catch (error) {
      console.error('Error fetching inventory:', error);
      return [];
    }
  },

  async updateInventory(perfumeId, availableQty, damagedQty, expirationDate) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'update_inventory',
          perfume_id: perfumeId,
          available_quantity: availableQty,
          damaged_quantity: damagedQty,
          expiration_date: expirationDate,
        }),
      });

      if (!response.ok) throw new Error('Failed to update inventory');
      return await response.json();
    } catch (error) {
      console.error('Error updating inventory:', error);
      return { error: error.message };
    }
  },

  async createPurchaseList(perfumeId, quantity) {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'create_purchase_list',
          perfume_id: perfumeId,
          quantity: quantity,
        }),
      });

      if (!response.ok) throw new Error('Failed to create purchase list');
      return await response.json();
    } catch (error) {
      console.error('Error creating purchase list:', error);
      return { error: error.message };
    }
  },

  async getPurchaseLists(roleFilter = 'all') {
    try {
      const response = await fetch(this.baseUrl + '?action=purchase_lists&role_filter=' + roleFilter, { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch purchase lists');
      const result = await response.json();
      return result.data || [];
    } catch (error) {
      console.error('Error fetching purchase lists:', error);
      return [];
    }
  },

  async approvePurchaseList(listId, note = '') {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'approve_list',
          list_id: listId,
          note: note,
        }),
      });

      if (!response.ok) throw new Error('Failed to approve purchase list');
      return await response.json();
    } catch (error) {
      console.error('Error approving purchase list:', error);
      return { error: error.message };
    }
  },

  async rejectPurchaseList(listId, note = '') {
    try {
      const response = await fetch(this.baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'reject_list',
          list_id: listId,
          note: note,
        }),
      });

      if (!response.ok) throw new Error('Failed to reject purchase list');
      return await response.json();
    } catch (error) {
      console.error('Error rejecting purchase list:', error);
      return { error: error.message };
    }
  },

  async getStaffAccess() {
    try {
      const response = await fetch(this.baseUrl + '?action=staff_access', { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch staff access');
      const result = await response.json();
      return result.data || [];
    } catch (error) {
      console.error('Error fetching staff access:', error);
      return [];
    }
  },

  async getAuditLogs() {
    try {
      const response = await fetch(this.baseUrl + '?action=audit_logs', { cache: 'no-store' });
      if (!response.ok) throw new Error('Failed to fetch audit logs');
      const result = await response.json();
      return result.data || [];
    } catch (error) {
      console.error('Error fetching audit logs:', error);
      return [];
    }
  },
};

/**
 * Dashboard UI Controller
 */
const Dashboard = {
  userInfo: null,
  // Polling interval in milliseconds for live dashboard refresh
  refreshIntervalMs: 5000,
  _pollerId: null,

  async init() {
    // Load user info first
    this.userInfo = await DashboardAPI.getUserInfo();
    if (!this.userInfo) {
      window.location.href = 'index.php';
      return;
    }

    // Display user info
    this.displayUserInfo();

    // Show appropriate sections based on role
    if (this.userInfo.role === 'staff') {
      this.initStaffDashboard();
    } else if (this.userInfo.role === 'owner') {
      this.initOwnerDashboard();
    }

    // Common inventory display
    await this.loadInventoryOverview();

    // Start periodic refresh so owner/staff dashboards stay live
    this.startPolling();
  },

  startPolling() {
    if (this._pollerId) return; // already running
    const ms = this.refreshIntervalMs || 5000;
    this._pollerId = setInterval(async () => {
      try {
        if (!this.userInfo) return;
        // Owner should see purchase lists and inventory updates
        if (this.userInfo.role === 'owner') {
          await this.loadOwnerPurchaseLists();
        }
        // Staff should see their lists and inventory
        if (this.userInfo.role === 'staff') {
          await this.loadStaffPurchaseLists();
          await this.loadStaffInventory();
        }
        // Always refresh overview for everyone with access
        await this.loadInventoryOverview();
      } catch (err) {
        console.error('Dashboard polling error:', err);
      }
    }, ms);
  },

  stopPolling() {
    if (this._pollerId) {
      clearInterval(this._pollerId);
      this._pollerId = null;
    }
  },

  displayUserInfo() {
    document.querySelector('.username').textContent = this.userInfo.username;
    document.querySelector('.role').textContent = this.userInfo.role;
    document.querySelector('.role-badge').textContent = this.userInfo.role.charAt(0).toUpperCase() + this.userInfo.role.slice(1);

    // Show dashboard links for staff/owner
    if (this.userInfo.role === 'staff' || this.userInfo.role === 'owner') {
      document.querySelectorAll('.dashboard-link').forEach(el => (el.style.display = 'inline'));
    }
  },

  async initStaffDashboard() {
    document.querySelector('.staff-section').style.display = 'block';

    // Load perfumes for dropdowns
    const perfumes = await DashboardAPI.getPerfumes('staff');
    this.populatePerfumeSelects(perfumes);

    // Hide update form for view-only staff
    if (this.userInfo.access_level === 'view') {
      document.getElementById('inventory-form').style.display = 'none';
    } else {
      // Setup form handlers
      document.getElementById('inventory-form').addEventListener('submit', e => this.handleInventoryUpdate(e));
    }
    document.getElementById('purchase-form').addEventListener('submit', e => this.handleCreatePurchaseList(e));

    // Load initial data
    await this.loadStaffPurchaseLists();
    await this.loadStaffInventory();
  },

  async initOwnerDashboard() {
    document.querySelector('.owner-section').style.display = 'block';

    // Load owner data
    await this.loadOwnerPurchaseLists();
    await this.loadStaffAccess();
    await this.loadAuditLogs();
  },

  populatePerfumeSelects(perfumes) {
    const selects = document.querySelectorAll('select[name="perfume_id"], select[name="purchase_perfume_id"]');
    selects.forEach(select => {
      perfumes.forEach(perfume => {
        const option = document.createElement('option');
        option.value = perfume.id;
        option.textContent = perfume.name;
        select.appendChild(option);
      });
    });
  },

  async handleInventoryUpdate(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    const result = await DashboardAPI.updateInventory(
      data.get('perfume_id'),
      parseInt(data.get('available_quantity')),
      parseInt(data.get('damaged_quantity')),
      data.get('expiration_date')
    );

    this.showStatus(result);
    if (!result.error) {
      form.reset();
      await this.loadStaffInventory();
      await this.loadInventoryOverview();
    }
  },

  async handleCreatePurchaseList(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    const result = await DashboardAPI.createPurchaseList(
      data.get('purchase_perfume_id'),
      parseInt(data.get('purchase_quantity'))
    );

    this.showStatus(result);
    if (!result.error) {
      form.reset();
      await this.loadStaffPurchaseLists();
    }
  },

  async loadStaffPurchaseLists() {
    const lists = await DashboardAPI.getPurchaseLists('staff');
    const container = document.getElementById('staff-purchase-lists');
    
    if (lists.length === 0) {
      container.innerHTML = '<p>No purchase lists yet.</p>';
      return;
    }

    container.innerHTML = lists.map(list => `
      <div class="purchase-list-card">
        <h3>List #${list.id} - ${list.status}</h3>
        <p>Created: ${list.created_at}</p>
        <p>Items: ${list.item_count || 0}</p>
        ${list.owner_note ? `<p><strong>Owner's note:</strong> ${this.escapeHtml(list.owner_note)}</p>` : ''}
      </div>
    `).join('');
  },

  async loadStaffInventory() {
    const inventory = await DashboardAPI.getInventory('staff');
    const container = document.getElementById('staff-inventory');
    
    if (inventory.length === 0) {
      container.innerHTML = '<p>No inventory assigned to you.</p>';
      return;
    }

    const html = `
      <table class="inventory-table">
        <thead>
          <tr>
            <th>Perfume</th>
            <th>Available</th>
            <th>Damaged</th>
            <th>Expiration</th>
            <th>Last Updated</th>
          </tr>
        </thead>
        <tbody>
          ${inventory.map(item => `
            <tr>
              <td>${this.escapeHtml(item.perfume_name)}</td>
              <td>${item.available_quantity}</td>
              <td>${item.damaged_quantity}</td>
              <td>${item.expiration_date || '—'}</td>
              <td>${item.last_updated}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    container.innerHTML = html;
  },

  async loadOwnerPurchaseLists() {
    const lists = await DashboardAPI.getPurchaseLists('owner');
    const container = document.getElementById('owner-purchase-lists');
    
    if (lists.length === 0) {
      container.innerHTML = '<p>No purchase lists.</p>';
      return;
    }

    container.innerHTML = lists.map(list => `
      <div class="purchase-list-card">
        <h3>List #${list.id} - ${list.status}</h3>
        <p>Created by: ${this.escapeHtml(list.username)} on ${list.created_at}</p>
        <p>Items: ${list.item_count || 0}</p>
        ${list.status === 'pending' ? `
          <form class="list-action-form" data-list-id="${list.id}">
            <label>Note for staff
              <textarea name="note" rows="2"></textarea>
            </label>
            <button type="button" class="button button-primary" onclick="Dashboard.approveList(${list.id}, this.parentElement)">Approve</button>
            <button type="button" class="button button-secondary" onclick="Dashboard.rejectList(${list.id}, this.parentElement)">Reject</button>
          </form>
        ` : `<p><strong>Decision:</strong> ${this.escapeHtml(list.owner_note || 'No note')}</p>`}
      </div>
    `).join('');
  },

  async approveList(listId, formEl) {
    const note = formEl.querySelector('textarea').value;
    const result = await DashboardAPI.approvePurchaseList(listId, note);
    this.showStatus(result);
    if (!result.error) await this.loadOwnerPurchaseLists();
    try { localStorage.setItem('labscentique:refresh', Date.now().toString()); } catch (e) {}
  },

  async rejectList(listId, formEl) {
    const note = formEl.querySelector('textarea').value;
    const result = await DashboardAPI.rejectPurchaseList(listId, note);
    this.showStatus(result);
    if (!result.error) await this.loadOwnerPurchaseLists();
    try { localStorage.setItem('labscentique:refresh', Date.now().toString()); } catch (e) {}
  },

  async loadStaffAccess() {
    const staff = await DashboardAPI.getStaffAccess();
    const container = document.getElementById('staff-access-table');
    
    if (staff.length === 0) {
      container.innerHTML = '<p>No staff members found.</p>';
      return;
    }

    const html = `
      <table class="access-table">
        <thead>
          <tr>
            <th>Staff Member</th>
            <th>Max Access Level</th>
            <th>Perfumes Assigned</th>
          </tr>
        </thead>
        <tbody>
          ${staff.map(s => `
            <tr>
              <td>${this.escapeHtml(s.username)}</td>
              <td>${this.escapeHtml(s.max_access_level)}</td>
              <td>${this.escapeHtml(s.perfume_count) || '0'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    container.innerHTML = html;
  },

  async loadAuditLogs() {
    const logs = await DashboardAPI.getAuditLogs();
    const container = document.getElementById('audit-logs');
    
    if (logs.length === 0) {
      container.innerHTML = '<p>No audit logs yet.</p>';
      return;
    }

    const html = `
      <table class="audit-table">
        <thead>
          <tr>
            <th>Perfume</th>
            <th>Changed By</th>
            <th>Prev Avail</th>
            <th>New Avail</th>
            <th>Prev Damaged</th>
            <th>New Damaged</th>
            <th>Reason</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          ${logs.map(log => `
            <tr>
              <td>${this.escapeHtml(log.perfume_name)}</td>
              <td>${this.escapeHtml(log.username)}</td>
              <td>${log.prev_available}</td>
              <td>${log.new_available}</td>
              <td>${log.prev_damaged}</td>
              <td>${log.new_damaged}</td>
              <td>${this.escapeHtml(log.reason || '—')}</td>
              <td>${log.changed_at}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    container.innerHTML = html;
  },

  async loadInventoryOverview() {
    const inventory = await DashboardAPI.getInventory('all');
    const container = document.getElementById('all-inventory') || document.getElementById('inventory-overview');
    
    if (inventory.length === 0) {
      container.innerHTML = '<p>No inventory records.</p>';
      return;
    }

    const html = `
      <table class="inventory-table">
        <thead>
          <tr>
            <th>Perfume</th>
            <th>Available</th>
            <th>Damaged</th>
            <th>Expiration</th>
            <th>Last Updated</th>
          </tr>
        </thead>
        <tbody>
          ${inventory.map(item => `
            <tr>
              <td>${this.escapeHtml(item.perfume_name)}</td>
              <td>${item.available_quantity}</td>
              <td>${item.damaged_quantity}</td>
              <td>${item.expiration_date || '—'}</td>
              <td>${item.last_updated}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    container.innerHTML = html;
  },

  showStatus(result) {
    const statusDiv = result.error
      ? document.querySelector('.form-status.error')
      : document.querySelector('.form-status.success');
    const otherDiv = result.error
      ? document.querySelector('.form-status.success')
      : document.querySelector('.form-status.error');

    statusDiv.textContent = result.message || result.error;
    statusDiv.style.display = 'block';
    otherDiv.style.display = 'none';

    setTimeout(() => {
      statusDiv.style.display = 'none';
    }, 5000);
  },

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  },
};

document.addEventListener('DOMContentLoaded', () => Dashboard.init());

// Listen for cross-tab refresh signals so other open dashboards update instantly
window.addEventListener('storage', (e) => {
  if (e.key !== 'labscentique:refresh') return;
  try {
    if (Dashboard.userInfo && Dashboard.userInfo.role === 'owner') {
      Dashboard.loadOwnerPurchaseLists();
    }
    if (Dashboard.userInfo && Dashboard.userInfo.role === 'staff') {
      Dashboard.loadStaffPurchaseLists();
      Dashboard.loadStaffInventory();
    }
    Dashboard.loadInventoryOverview();
  } catch (err) {
    console.error('Storage event handler error:', err);
  }
});
