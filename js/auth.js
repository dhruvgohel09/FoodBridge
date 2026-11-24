// ============================================================
// FOOD BRIDGE - CLIENT-SIDE AUTH & UNIVERSAL FORM VALIDATION
// Eliminates browser tooltip popups; renders inline red outlines
// and helper error text directly below invalid fields.
// ============================================================

// 1. Mobile Menu Toggle
const menuToggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.app-header-nav, .nav');
if (menuToggle && navigation) {
  menuToggle.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
    navigation.classList.toggle('is-open', !isOpen);
  });
  navigation.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Open navigation menu');
      navigation.classList.remove('is-open');
    });
  });
}

// 2. Password Visibility Toggle
document.querySelectorAll('.password-toggle').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const input = toggle.parentElement.querySelector('input');
    if (!input) return;
    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(showPassword));
    toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
    toggle.innerHTML = `<i class="fa-regular ${showPassword ? 'fa-eye-slash' : 'fa-eye'}" aria-hidden="true"></i>`;
  });
});

// 3. OTP Digit Input Auto-Advance & Combined Value
const otpDigits = Array.from(document.querySelectorAll('.otp-digit'));
const fullOtpInput = document.querySelector('#full-otp-input');
const otpForm = document.querySelector('form[data-auth-form="otp"]');

function updateFullOtp() {
  if (fullOtpInput && otpDigits.length > 0) {
    fullOtpInput.value = otpDigits.map((d) => d.value).join('');
  }
}

if (otpDigits.length > 0) {
  // Focus the first input automatically
  otpDigits[0].focus();

  otpDigits.forEach((input, index) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(-1);
      updateFullOtp();
      clearOtpError();
      if (input.value && otpDigits[index + 1]) {
        otpDigits[index + 1].focus();
      }
      // If all 6 digits entered, auto-submit
      const allFilled = otpDigits.every((d) => d.value.length === 1);
      if (allFilled && otpForm) {
        updateFullOtp();
        setTimeout(() => {
          otpForm.submit();
        }, 200);
      }
    });

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Backspace' && !input.value && otpDigits[index - 1]) {
        otpDigits[index - 1].focus();
      }
    });

    input.addEventListener('paste', (event) => {
      const pastedCode = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, otpDigits.length);
      if (!pastedCode) return;
      event.preventDefault();
      pastedCode.split('').forEach((digit, digitIndex) => {
        if (otpDigits[digitIndex]) {
          otpDigits[digitIndex].value = digit;
        }
      });
      updateFullOtp();
      clearOtpError();
      const nextIndex = Math.min(pastedCode.length, otpDigits.length - 1);
      otpDigits[nextIndex].focus();

      if (pastedCode.length === 6 && otpForm) {
        setTimeout(() => {
          otpForm.submit();
        }, 200);
      }
    });
  });
}

function showOtpError(message) {
  const container = document.querySelector('.otp-inputs');
  if (!container) return;
  container.classList.add('has-error');
  container.querySelectorAll('.otp-digit').forEach((d) => {
    d.style.setProperty('border', '1.5px solid #dc2626', 'important');
    d.style.setProperty('box-shadow', '0 0 0 3px rgba(220, 38, 38, 0.15)', 'important');
  });
  let errSpan = container.nextElementSibling;
  if (errSpan && !errSpan.classList.contains('field-error-text')) {
    errSpan = null;
  }
  if (errSpan) {
    errSpan.innerHTML = message;
    errSpan.style.setProperty('display', 'block', 'important');
    errSpan.style.setProperty('color', '#dc2626', 'important');
    errSpan.style.setProperty('font-size', '12px', 'important');
    errSpan.style.setProperty('font-weight', '600', 'important');
    errSpan.style.setProperty('text-align', 'center', 'important');
  } else {
    errSpan = document.createElement('span');
    errSpan.className = 'field-error-text';
    errSpan.innerHTML = message;
    errSpan.style.setProperty('display', 'block', 'important');
    errSpan.style.setProperty('color', '#dc2626', 'important');
    errSpan.style.setProperty('font-size', '12px', 'important');
    errSpan.style.setProperty('font-weight', '600', 'important');
    errSpan.style.setProperty('text-align', 'center', 'important');
    container.insertAdjacentElement('afterend', errSpan);
  }
}

function clearOtpError() {
  const container = document.querySelector('.otp-inputs');
  if (!container) return;
  container.classList.remove('has-error');
  container.querySelectorAll('.otp-digit').forEach((d) => {
    d.style.removeProperty('border');
    d.style.removeProperty('box-shadow');
  });
  const next = container.nextElementSibling;
  if (next && next.classList.contains('field-error-text')) {
    next.remove();
  }
}

// 4. Resend OTP Cooldown Timer
const resendBtn = document.querySelector('#resend-otp-btn');
const resendText = document.querySelector('#resend-text');
if (resendBtn && resendText) {
  let cooldown = parseInt(resendBtn.getAttribute('data-cooldown') || '0', 10);
  if (cooldown > 0) {
    resendBtn.style.pointerEvents = 'none';
    resendBtn.style.opacity = '0.6';
    const interval = setInterval(() => {
      cooldown--;
      if (cooldown > 0) {
        resendText.textContent = `Resend Code (${cooldown}s)`;
      } else {
        clearInterval(interval);
        resendText.textContent = 'Resend Code';
        resendBtn.style.pointerEvents = 'auto';
        resendBtn.style.opacity = '1';
      }
    }, 1000);
    resendText.textContent = `Resend Code (${cooldown}s)`;
  }
}

// ============================================================
// 5. UNIVERSAL INLINE FIELD VALIDATION ENGINE
// ============================================================

function getFieldError(input) {
  if (!input || input.disabled || input.type === 'hidden' || input.type === 'submit' || input.type === 'button') {
    return null;
  }

  const name = (input.name || '').toLowerCase();
  const type = (input.type || '').toLowerCase();
  const form = input.form;
  const isRequired = input.required || input.hasAttribute('required');

  // Checkbox required check
  if (type === 'checkbox') {
    if (isRequired && !input.checked) {
      return 'Please accept this requirement to continue.';
    }
    return null;
  }

  const rawVal = input.value;
  const val = rawVal.trim();

  // If required and empty
  if (isRequired && !val) {
    if (type === 'email' || name === 'email') {
      return 'Please enter your email address.';
    }
    if (name === 'identifier') {
      return 'Please enter your registered email or phone.';
    }
    if (name === 'password' || type === 'password') {
      return 'Please enter your password.';
    }
    if (name === 'confirm_password') {
      return 'Please confirm your password.';
    }
    if (type === 'tel' || name === 'phone' || name === 'contact_phone') {
      return 'Please enter your 10-digit mobile number.';
    }
    if (name === 'name' || name === 'full_name') {
      return 'Please enter your full name.';
    }
    if (name === 'city' || name === 'city_id') {
      return 'Please select your city.';
    }
    if (name === 'food_name') {
      return 'Please enter the food name or description.';
    }
    if (name === 'food_type') {
      return 'Please select the food category.';
    }
    if (name === 'quantity') {
      return 'Please enter the quantity.';
    }
    if (name === 'serves_count' || name === 'people_count') {
      return 'Please enter the estimated number of people.';
    }
    if (name === 'available_date' || name === 'required_date') {
      return 'Please select a valid date.';
    }
    if (name === 'available_time') {
      return 'Please select an available pickup time.';
    }
    if (name === 'pickup_address') {
      return 'Please provide a complete pickup address.';
    }
    return 'This field is required.';
  }

  // Value is non-empty: validate format
  if (val) {
    // Email format validation (Image 2 exact 2-line format)
    if (type === 'email' || name === 'email' || (name === 'identifier' && (val.includes('@') || /[a-zA-Z]/.test(val)))) {
      const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailRegex.test(val)) {
        return 'The email address you entered is not valid.<br>Please check your spelling and try again. Thanks!';
      }
    }

    // Phone format validation (10 digits)
    if (type === 'tel' || name === 'phone' || name === 'contact_phone' || (name === 'identifier' && /^\d+$/.test(val))) {
      const digitsOnly = val.replace(/\D/g, '');
      if (digitsOnly.length !== 10) {
        return 'Please enter a valid 10-digit mobile number.';
      }
    }

    // Password length validation on register / reset
    if (name === 'password') {
      const isRegisterOrReset = form && (
        form.matches('[data-auth-form="register"], [data-auth-form="reset"]') ||
        form.classList.contains('register-form') ||
        form.classList.contains('reset-form')
      );
      if (isRegisterOrReset && val.length < 6) {
        return 'Password must be at least 6 characters long.';
      }
    }

    // Confirm password match validation
    if (name === 'confirm_password' && form) {
      const passwordInput = form.querySelector('[name="password"]');
      if (passwordInput && passwordInput.value !== rawVal) {
        return 'Passwords do not match. Please check and try again.';
      }
    }

    // Serves / People count positive integer check
    if (name === 'serves_count' || name === 'people_count') {
      const count = parseInt(val, 10);
      if (isNaN(count) || count <= 0) {
        return 'Must serve at least 1 person.';
      }
    }

    // Pickup address minimum length
    if (name === 'pickup_address' && val.length < 5) {
      return 'Please provide a complete pickup address (at least 5 characters).';
    }
  }

  return null;
}

function showFieldError(input, message) {
  const wrap = input.closest('.input-wrap') || input.closest('.input-icon-wrap') || input;
  wrap.classList.add('has-error');
  input.classList.add('has-error');

  // Direct inline styles to guarantee red border & red focus outline
  wrap.style.setProperty('border', '1.5px solid #dc2626', 'important');
  wrap.style.setProperty('box-shadow', '0 0 0 3px rgba(220, 38, 38, 0.15)', 'important');
  wrap.style.setProperty('background-color', '#ffffff', 'important');

  // Find or create .field-error-text directly after wrap
  let errSpan = wrap.nextElementSibling;
  if (errSpan && !errSpan.classList.contains('field-error-text')) {
    errSpan = null;
  }

  if (errSpan) {
    errSpan.innerHTML = message;
    errSpan.style.setProperty('display', 'block', 'important');
    errSpan.style.setProperty('color', '#dc2626', 'important');
    errSpan.style.setProperty('font-size', '12px', 'important');
    errSpan.style.setProperty('font-weight', '600', 'important');
    errSpan.style.setProperty('margin-top', '5px', 'important');
    errSpan.style.setProperty('margin-bottom', '5px', 'important');
    errSpan.style.setProperty('line-height', '1.4', 'important');
    errSpan.style.setProperty('text-align', 'left', 'important');
  } else {
    errSpan = document.createElement('span');
    errSpan.className = 'field-error-text';
    errSpan.innerHTML = message;
    errSpan.style.setProperty('display', 'block', 'important');
    errSpan.style.setProperty('color', '#dc2626', 'important');
    errSpan.style.setProperty('font-size', '12px', 'important');
    errSpan.style.setProperty('font-weight', '600', 'important');
    errSpan.style.setProperty('margin-top', '5px', 'important');
    errSpan.style.setProperty('margin-bottom', '5px', 'important');
    errSpan.style.setProperty('line-height', '1.4', 'important');
    errSpan.style.setProperty('text-align', 'left', 'important');
    wrap.insertAdjacentElement('afterend', errSpan);
  }
}

function clearFieldError(input) {
  const wrap = input.closest('.input-wrap') || input.closest('.input-icon-wrap') || input;
  wrap.classList.remove('has-error');
  input.classList.remove('has-error');

  wrap.style.removeProperty('border');
  wrap.style.removeProperty('box-shadow');
  wrap.style.removeProperty('background-color');

  const next = wrap.nextElementSibling;
  if (next && next.classList.contains('field-error-text')) {
    next.remove();
  }
}

// Attach event listeners to all forms
function initFormValidation() {
  document.querySelectorAll('form').forEach((form) => {
    // Disable native browser validation balloons everywhere
    form.noValidate = true;

    const fields = Array.from(form.querySelectorAll('input, select, textarea'));

    fields.forEach((field) => {
      // Validate on blur
      field.addEventListener('blur', () => {
        if (field.value.trim() || field.required) {
          const err = getFieldError(field);
          if (err) {
            showFieldError(field, err);
          } else {
            clearFieldError(field);
          }
        }
      });

      // Clear/update error on input in real time
      field.addEventListener('input', () => {
        const wrap = field.closest('.input-wrap') || field.closest('.input-icon-wrap') || field;
        if (wrap.classList.contains('has-error') || field.classList.contains('has-error')) {
          const err = getFieldError(field);
          if (err) {
            showFieldError(field, err);
          } else {
            clearFieldError(field);
          }
        }

        // Special case: if editing password, revalidate confirm_password if present
        if (field.name === 'password') {
          const confirmField = form.querySelector('[name="confirm_password"]');
          if (confirmField && confirmField.value) {
            const confirmWrap = confirmField.closest('.input-wrap') || confirmField;
            if (confirmWrap.classList.contains('has-error')) {
              const cErr = getFieldError(confirmField);
              if (cErr) showFieldError(confirmField, cErr);
              else clearFieldError(confirmField);
            }
          }
        }
      });

      field.addEventListener('change', () => {
        const err = getFieldError(field);
        if (err) {
          showFieldError(field, err);
        } else {
          clearFieldError(field);
        }
      });
    });

    // On Submit: Validate all inputs
    form.addEventListener('submit', (event) => {
      let firstInvalid = null;
      let hasError = false;

      // Check OTP if on OTP form
      if (form.matches('[data-auth-form="otp"]') || form.querySelector('.otp-inputs')) {
        updateFullOtp();
        const otpVal = fullOtpInput ? fullOtpInput.value : otpDigits.map((d) => d.value).join('');
        if (otpVal.length < 6) {
          event.preventDefault();
          showOtpError('Please enter the complete 6-digit verification code.');
          if (otpDigits[0]) otpDigits[0].focus();
          return;
        } else {
          clearOtpError();
        }
      }

      fields.forEach((field) => {
        const err = getFieldError(field);
        if (err) {
          hasError = true;
          showFieldError(field, err);
          if (!firstInvalid) firstInvalid = field;
        } else {
          clearFieldError(field);
        }
      });

      if (hasError) {
        event.preventDefault();
        if (firstInvalid) {
          firstInvalid.focus();
        }
        return false;
      }

      // Update full OTP before submitting
      updateFullOtp();
    });
  });
}

// Run on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFormValidation);
} else {
  initFormValidation();
}
