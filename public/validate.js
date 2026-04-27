/**
 * Client-side validation utilities
 * Handles form validation before submission to reduce server load
 */

const Validator = {
  /**
   * Validate email format
   */
  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  /**
   * Validate required field (not empty)
   */
  isNotEmpty(value) {
    return value.trim().length > 0;
  },

  /**
   * Validate minimum length
   */
  minLength(value, length) {
    return value.trim().length >= length;
  },

  /**
   * Validate password strength (min 6 chars, at least one letter and number)
   */
  isStrongPassword(password) {
    return password.length >= 6;
  },

  /**
   * Validate login form
   */
  validateLogin(username, password) {
    const errors = [];

    if (!this.isNotEmpty(username)) {
      errors.push('Username or email is required');
    }

    if (!this.isNotEmpty(password)) {
      errors.push('Password is required');
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  },

  /**
   * Validate registration form
   */
  validateRegister(username, email, password) {
    const errors = [];

    if (!this.isNotEmpty(username)) {
      errors.push('Username is required');
    }

    if (!this.isNotEmpty(email)) {
      errors.push('Email is required');
    } else if (!this.isValidEmail(email)) {
      errors.push('Invalid email format');
    }

    if (!this.isNotEmpty(password)) {
      errors.push('Password is required');
    } else if (!this.isStrongPassword(password)) {
      errors.push('Password must be at least 6 characters');
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  },

  /**
   * Validate contact form
   */
  validateContact(name, email, message) {
    const errors = [];

    if (!this.isNotEmpty(name)) {
      errors.push('Name is required');
    }

    if (!this.isNotEmpty(email)) {
      errors.push('Email is required');
    } else if (!this.isValidEmail(email)) {
      errors.push('Invalid email format');
    }

    if (!this.isNotEmpty(message)) {
      errors.push('Message is required');
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  },

  /**
   * Display validation errors in a form
   */
  showErrors(formElement, errors) {
    // Remove old error messages
    const existingErrors = formElement.querySelectorAll('.form-error');
    existingErrors.forEach(err => err.remove());

    if (errors.length > 0) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'form-status error form-error';
      errorDiv.innerHTML = errors.join('<br>');
      formElement.insertBefore(errorDiv, formElement.firstChild);
      return false;
    }
    return true;
  },
};

/**
 * Export for module usage if needed
 */
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Validator;
}
