/**
 * Review System - JavaScript Functionality
 * Handles review submission, display, and staff replies
 */

const ReviewSystem = {
  currentPerfumeId: null,
  currentPerfumeName: null,
  userRole: null,
  userId: null,
  currentRating: 0,

  /**
   * Initialize the review system
   */
  init() {
    this.userRole = document.body.dataset.userRole || 'guest';
    this.userId = document.body.dataset.userId || null;
    
    this.setupEventListeners();
    this.checkGuestStatus();
  },

  /**
   * Setup event listeners for form interactions
   */
  setupEventListeners() {
    // Star rating clicks
    document.querySelectorAll('.star-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.setRating(parseInt(btn.dataset.value));
      });
    });

    // Comment input counter
    const commentInput = document.getElementById('commentInput');
    if (commentInput) {
      commentInput.addEventListener('input', (e) => {
        document.getElementById('charCount').textContent = e.target.value.length;
      });
    }

    // Reply input counter
    const replyInput = document.getElementById('replyTextInput');
    if (replyInput) {
      replyInput.addEventListener('input', (e) => {
        document.getElementById('replyCharCount').textContent = e.target.value.length;
      });
    }

    // Form submission
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
      reviewForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.submitReview();
      });
    }

    // Reply form submission
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
      replyForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.submitReply();
      });
    }
  },

  /**
   * Check if user is guest and show appropriate fields
   */
  checkGuestStatus() {
    const guestNotice = document.getElementById('guestNotice');
    const guestInfoFields = document.getElementById('guestInfoFields');

    if (this.userRole === 'guest') {
      if (guestNotice) guestNotice.style.display = 'block';
      if (guestInfoFields) guestInfoFields.style.display = 'flex';
    } else {
      if (guestNotice) guestNotice.style.display = 'none';
      if (guestInfoFields) guestInfoFields.style.display = 'none';
    }
  },

  /**
   * Set star rating
   */
  setRating(rating) {
    this.currentRating = rating;
    document.getElementById('ratingInput').value = rating;

    // Update star display
    document.querySelectorAll('.star-btn').forEach((btn, index) => {
      if (index < rating) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    // Update feedback text
    const feedbackMap = {
      1: '⭐ Poor',
      2: '⭐⭐ Fair',
      3: '⭐⭐⭐ Good',
      4: '⭐⭐⭐⭐ Very Good',
      5: '⭐⭐⭐⭐⭐ Excellent'
    };

    document.getElementById('ratingFeedback').textContent = feedbackMap[rating] || '';
  },

  /**
   * Open review modal for a specific perfume
   */
  async openModal(perfumeId, perfumeName) {
    this.currentPerfumeId = perfumeId;
    this.currentPerfumeName = perfumeName;

    document.getElementById('reviewPerfumeName').textContent = perfumeName;
    const modal = document.getElementById('reviewModal');
    modal.style.display = 'flex';

    // Clear form
    this.resetForm();

    // Load existing reviews
    await this.loadReviews();
  },

  /**
   * Close review modal
   */
  closeModal() {
    const modal = document.getElementById('reviewModal');
    modal.style.display = 'none';
    this.resetForm();
  },

  /**
   * Reset the review form
   */
  resetForm() {
    const form = document.getElementById('reviewForm');
    if (form) form.reset();

    this.currentRating = 0;
    document.getElementById('ratingInput').value = 0;
    document.querySelectorAll('.star-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('ratingFeedback').textContent = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('commentInput').value = '';
  },

  /**
   * Load and display reviews for the current perfume
   */
  async loadReviews() {
    const reviewsList = document.getElementById('reviewsList');
    reviewsList.innerHTML = '<div class="review-loading"><span class="spinner"></span>Loading reviews...</div>';

    try {
      const response = await fetch(`/api/reviews.php?action=get_reviews&perfume_id=${this.currentPerfumeId}`, { cache: 'no-store' });
      const result = await response.json();

      if (result.success && result.data.length > 0) {
        reviewsList.innerHTML = result.data.map(review => this.buildReviewCard(review)).join('');
      } else if (result.data.length === 0) {
        reviewsList.innerHTML = '<p class="empty-state">No reviews yet. Be the first to review!</p>';
      } else {
        reviewsList.innerHTML = '<p class="empty-state">Unable to load reviews</p>';
      }
    } catch (error) {
      console.error('Error loading reviews:', error);
      reviewsList.innerHTML = '<p class="review-error">❌ Failed to load reviews</p>';
    }
  },

  /**
   * Build HTML for a review card
   */
  buildReviewCard(review) {
    const reviewerName = review.reviewer_name || 'Anonymous';
    const roleClass = review.reviewer_role ? `review-role-badge ${review.reviewer_role}` : '';
    const roleBadge = review.reviewer_role ? `<span class="review-role-badge ${review.reviewer_role}">${review.reviewer_role.toUpperCase()}</span>` : '';
    const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
    const date = new Date(review.created_at).toLocaleDateString();

    let replyHtml = '';
    if (review.reply) {
      replyHtml = `
        <div class="review-reply">
          <div class="reply-header">
            <span class="reply-badge">RESPONSE</span>
            <span class="reply-author">${review.reply.responder_name}</span>
            <span class="reply-date">${new Date(review.reply.created_at).toLocaleDateString()}</span>
          </div>
          <div class="reply-text">${this.escapeHtml(review.reply.reply_text)}</div>
        </div>
      `;
    } else if (['staff', 'owner', 'admin'].includes(this.userRole)) {
      replyHtml = `
        <button type="button" class="reply-action-btn" onclick="ReviewSystem.openReplyModal(${review.id}, '${this.escapeHtml(reviewerName)}', ${review.rating})">
          💬 Reply to Review
        </button>
      `;
    }

    return `
      <div class="review-card">
        <div class="review-header">
          <div>
            <span class="review-author">${this.escapeHtml(reviewerName)}</span>
            ${roleBadge}
          </div>
          <span class="review-date">${date}</span>
        </div>
        <div class="review-rating">
          <span class="review-stars">${stars}</span>
          <span class="review-rating-value">${review.rating}/5</span>
        </div>
        <div class="review-comment">${this.escapeHtml(review.comment)}</div>
        ${replyHtml}
      </div>
    `;
  },

  /**
   * Submit a new review
   */
  async submitReview() {
    const perfumeId = this.currentPerfumeId;
    const rating = parseInt(document.getElementById('ratingInput').value);
    const comment = document.getElementById('commentInput').value.trim();
    const guestName = document.getElementById('guestNameInput')?.value.trim() || '';
    const guestEmail = document.getElementById('guestEmailInput')?.value.trim() || '';
    const isVisibleToGuests = document.getElementById('visibilityInput').checked;

    // Validation
    if (!rating || rating < 1 || rating > 5) {
      this.showError('Please select a rating');
      return;
    }

    if (!comment || comment.length < 5) {
      this.showError('Comment must be at least 5 characters');
      return;
    }

    if (this.userRole === 'guest') {
      if (!guestName || !guestEmail) {
        this.showError('Please enter your name and email');
        return;
      }
      if (!this.isValidEmail(guestEmail)) {
        this.showError('Please enter a valid email address');
        return;
      }
    }

    const submitBtn = document.getElementById('submitReviewBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
      const response = await fetch('/api/reviews.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'submit_review',
          perfume_id: perfumeId,
          rating: rating,
          comment: comment,
          guest_name: guestName,
          guest_email: guestEmail,
          is_visible_to_guests: isVisibleToGuests
        })
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess('Review submitted successfully! ✓');
        setTimeout(() => {
          this.resetForm();
          this.loadReviews();
        }, 1500);
      } else {
        this.showError(result.error || 'Failed to submit review');
      }
    } catch (error) {
      console.error('Error submitting review:', error);
      this.showError('Error submitting review. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Review';
    }
  },

  /**
   * Open reply modal for staff/owner
   */
  openReplyModal(reviewId, reviewerName, rating) {
    document.getElementById('replyReviewId').value = reviewId;

    const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
    document.getElementById('reviewContext').innerHTML = `
      <strong>Replying to ${this.escapeHtml(reviewerName)}'s Review</strong>
      Rating: ${stars} (${rating}/5)
    `;

    const modal = document.getElementById('replyModal');
    modal.style.display = 'flex';
    document.getElementById('replyTextInput').focus();
  },

  /**
   * Close reply modal
   */
  closeReplyModal() {
    const modal = document.getElementById('replyModal');
    modal.style.display = 'none';
    const form = document.getElementById('replyForm');
    if (form) form.reset();
    document.getElementById('replyCharCount').textContent = '0';
  },

  /**
   * Submit a reply to a review
   */
  async submitReply() {
    const reviewId = parseInt(document.getElementById('replyReviewId').value);
    const replyText = document.getElementById('replyTextInput').value.trim();

    if (!reviewId || !replyText || replyText.length < 5) {
      this.showError('Reply must be at least 5 characters');
      return;
    }

    const submitBtn = document.getElementById('submitReplyBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    try {
      const response = await fetch('/api/reviews.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'submit_reply',
          review_id: reviewId,
          reply_text: replyText
        })
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess('Reply submitted successfully! ✓');
        setTimeout(() => {
          this.closeReplyModal();
          this.loadReviews();
        }, 1500);
      } else {
        this.showError(result.error || 'Failed to submit reply');
      }
    } catch (error) {
      console.error('Error submitting reply:', error);
      this.showError('Error submitting reply. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Send Reply';
    }
  },

  /**
   * Show error message
   */
  showError(message) {
    const container = document.querySelector('.review-form-section') || document.querySelector('.review-modal-body');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'review-error';
    errorDiv.textContent = '❌ ' + message;

    if (container) {
      container.insertBefore(errorDiv, container.firstChild);
      setTimeout(() => errorDiv.remove(), 5000);
    }
  },

  /**
   * Show success message
   */
  showSuccess(message) {
    const container = document.querySelector('.review-form-section') || document.querySelector('.review-modal-body');
    const successDiv = document.createElement('div');
    successDiv.className = 'review-success';
    successDiv.textContent = '✓ ' + message;

    if (container) {
      container.insertBefore(successDiv, container.firstChild);
      setTimeout(() => successDiv.remove(), 3000);
    }
  },

  /**
   * Escape HTML to prevent XSS
   */
  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  },

  /**
   * Validate email format
   */
  isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  ReviewSystem.init();
});

// Close modals on backdrop click
document.addEventListener('click', (e) => {
  const reviewModal = document.getElementById('reviewModal');
  const replyModal = document.getElementById('replyModal');

  if (e.target === reviewModal) {
    ReviewSystem.closeModal();
  }
  if (e.target === replyModal) {
    ReviewSystem.closeReplyModal();
  }
});

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    ReviewSystem.closeModal();
    ReviewSystem.closeReplyModal();
  }
});
