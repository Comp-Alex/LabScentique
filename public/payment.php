<?php
declare(strict_types=1);
$sessionSavePath = $_ENV['SESSION_SAVE_PATH'] ?? $_SERVER['SESSION_SAVE_PATH'] ?? null;
if (is_string($sessionSavePath) && $sessionSavePath !== '') {
    if (!is_dir($sessionSavePath)) {
        @mkdir($sessionSavePath, 0755, true);
    }
    session_save_path($sessionSavePath);
}
session_start();

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LabScentique Payment</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="brand" aria-label="LabScentique home">
        <img src="assets/logo.svg" alt="LabScentique logo" class="brand-logo" />
        <span>LabScentique</span>
      </a>
      <div class="header-nav">
        <a href="index.php#products">Back to shop</a>
        <a href="index.php#contact">Support</a>
      </div>
      <div class="auth-links">
        <?php if ($userId): ?>
          <span class="user-logged-in">Logged in as <?php echo escape($userName); ?></span>
        <?php else: ?>
          <a class="button button-secondary" href="index.php">Login to pay</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="main-content">
    <div class="container checkout-page">
      <section class="checkout-panel">
        <h1>Payment & Receipt</h1>
        <div id="checkout-summary" class="checkout-summary">
          <p>Loading your payment details…</p>
        </div>
      </section>
      <aside class="receipt-sidebar">
        <div class="receipt-card checkout-receipt-card" id="checkout-receipt">
          <div class="receipt-header">
            <h3>Shopping receipt</h3>
            <p>A clean, modern purchase summary will appear here.</p>
          </div>
          <div class="receipt-empty">
            <p>Select a payment option and confirm your purchase.</p>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <script>
    window.userData = {
      isLoggedIn: <?php echo $userId ? 'true' : 'false'; ?>,
      role: '<?php echo escape($userRole); ?>',
      userId: <?php echo $userId ? (int)$userId : 'null'; ?>
    };
  </script>
  <script src="app.js?v=<?php echo filemtime(__DIR__ . '/app.js'); ?>"></script>
  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const selectedPerfumeId = urlParams.get('perfume_id');
    const selectedQuantity = parseInt(urlParams.get('quantity'), 10) || 1;

    const summaryElement = document.getElementById('checkout-summary');
    const receiptElement = document.getElementById('checkout-receipt');

    function renderCheckoutError(message) {
      summaryElement.innerHTML = `
        <div class="checkout-error">
          <h2>Unable to load payment</h2>
          <p>${message}</p>
          <a href="index.php#products" class="button button-secondary">Return to shop</a>
        </div>
      `;
    }

    function formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    }

    function renderReceipt(receipt, paymentMethod) {
      const itemsHtml = receipt.items.map(item => `
        <div class="receipt-item">
          <div class="receipt-item-name">${item.name}</div>
          <div class="receipt-item-meta">Qty: ${item.quantity}${item.remaining_stock !== undefined ? ` · Remaining: ${item.remaining_stock}` : ''}</div>
        </div>
      `).join('');

      receiptElement.innerHTML = `
        <div class="receipt-card checkout-receipt-card">
          <div class="receipt-header">
            <h3>Shopping receipt</h3>
            <p>${new Date(receipt.purchased_at).toLocaleString()}</p>
          </div>
          <div class="receipt-method">Payment method: ${paymentMethod}</div>
          <div class="receipt-items">
            ${itemsHtml}
          </div>
          <div class="receipt-total">Total items: ${receipt.item_count}</div>
        </div>
      `;
    }

    async function renderCartCheckout(cart) {
      const itemRows = cart.data.map(item => `
        <div class="checkout-item-row">
          <div>${item.name}</div>
          <div>Qty: ${item.quantity}</div>
        </div>
      `).join('');
      const totalItems = cart.data.reduce((sum, item) => sum + item.quantity, 0);
      const price = 49.99;
      const totalPrice = price * totalItems;

      summaryElement.innerHTML = `
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h2>Cart payment</h2>
            <p>Complete payment for all items in your cart.</p>
          </div>
          <div class="checkout-details">
            ${itemRows}
            <div><strong>Total items</strong><span>${totalItems}</span></div>
            <div><strong>Estimated total</strong><span>${formatCurrency(totalPrice)}</span></div>
          </div>
          <div class="checkout-methods">
            <label><input type="radio" name="payment_method" value="Card" checked /> Card</label>
            <label><input type="radio" name="payment_method" value="GCash" /> GCash</label>
            <label><input type="radio" name="payment_method" value="Bank transfer" /> Bank transfer</label>
          </div>
          <div class="checkout-actions">
            <button id="confirm-payment" class="button button-primary">Confirm payment</button>
            <a href="index.php#products" class="button button-secondary">Cancel</a>
          </div>
        </div>
      `;

      document.getElementById('confirm-payment').addEventListener('click', async () => {
        if (!window.userData.isLoggedIn) {
          alert('Please log in before completing payment.');
          window.location.href = 'index.php';
          return;
        }

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Card';
        try {
          const result = await API.checkoutCart();
          renderReceipt(result.receipt, paymentMethod);
          summaryElement.innerHTML = `
            <div class="checkout-success">
              <h2>Payment successful</h2>
              <p>Your cart has been paid and a receipt is shown to the right.</p>
            </div>
          `;
        } catch (error) {
          renderCheckoutError(error.message || 'Payment failed.');
        }
      });
    }

    async function initPaymentPage() {
      const isCartCheckout = urlParams.get('cart') === '1';

      if (isCartCheckout) {
        const cart = await API.getCart();
        if (!cart.success || !cart.data || cart.data.length === 0) {
          renderCheckoutError('Your cart is empty. Add items before checking out.');
          return;
        }
        await renderCartCheckout(cart);
        return;
      }

      if (!selectedPerfumeId) {
        renderCheckoutError('No perfume selected for checkout.');
        return;
      }

      const perfume = await API.getPerfumeById(selectedPerfumeId);
      if (!perfume) {
        renderCheckoutError('Selected perfume not found.');
        return;
      }

      const price = 49.99;
      const totalPrice = price * selectedQuantity;

      summaryElement.innerHTML = `
        <div class="checkout-card">
          <div class="checkout-card-header">
            <h2>${perfume.name}</h2>
            <p>${perfume.description}</p>
          </div>
          <div class="checkout-details">
            <div><strong>Quantity</strong><span>${selectedQuantity}</span></div>
            <div><strong>Unit price</strong><span>${formatCurrency(price)}</span></div>
            <div><strong>Total</strong><span>${formatCurrency(totalPrice)}</span></div>
          </div>
          <div class="checkout-methods">
            <label><input type="radio" name="payment_method" value="Card" checked /> Card</label>
            <label><input type="radio" name="payment_method" value="GCash" /> GCash</label>
            <label><input type="radio" name="payment_method" value="Bank transfer" /> Bank transfer</label>
          </div>
          <div class="checkout-actions">
            <button id="confirm-payment" class="button button-primary">Confirm payment</button>
            <a href="index.php#products" class="button button-secondary">Cancel</a>
          </div>
        </div>
      `;

      document.getElementById('confirm-payment').addEventListener('click', async () => {
        if (!window.userData.isLoggedIn) {
          alert('Please log in before completing payment.');
          window.location.href = 'index.php';
          return;
        }

        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Card';

        try {
          const result = await API.purchasePerfume(selectedPerfumeId, selectedQuantity);
          renderReceipt(result.receipt, paymentMethod);
          summaryElement.innerHTML = `
            <div class="checkout-success">
              <h2>Payment successful</h2>
              <p>Your order has been confirmed and a receipt is displayed to the right.</p>
            </div>
          `;
        } catch (error) {
          renderCheckoutError(error.message || 'Payment failed.');
        }
      });
    }

    initPaymentPage();
  </script>
</body>
</html>
