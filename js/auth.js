// Mobile Menu Toggle
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

// Password Visibility Toggle
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

// OTP Digit Input Auto-Advance & Combined Value
const otpDigits = Array.from(document.querySelectorAll('.otp-digit'));
const fullOtpInput = document.querySelector('#full-otp-input');
const otpForm = document.querySelector('form[data-auth-form="otp"]');

function updateFullOtp() {
  if (fullOtpInput) {
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
      if (input.value && otpDigits[index + 1]) {
        otpDigits[index + 1].focus();
      }
      // If all 6 digits entered, auto-submit after a brief 200ms pause for smoother experience
      const allFilled = otpDigits.every((d) => d.value.length === 1);
      if (allFilled && otpForm) {
        updateFullOtp();
        setTimeout(() => {
          if (otpForm.reportValidity()) {
            otpForm.submit();
          }
        }, 250);
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
      const nextIndex = Math.min(pastedCode.length, otpDigits.length - 1);
      otpDigits[nextIndex].focus();

      if (pastedCode.length === 6 && otpForm) {
        setTimeout(() => {
          if (otpForm.reportValidity()) {
            otpForm.submit();
          }
        }, 250);
      }
    });
  });
}

// Resend OTP Cooldown Timer
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

// Client-side Form Validation Check Before Native PHP Submit
document.querySelectorAll('[data-auth-form]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="confirm_password"]');

    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
      return;
    }

    if (confirmation && password && password.value !== confirmation.value) {
      event.preventDefault();
      confirmation.setCustomValidity('Passwords do not match.');
      confirmation.reportValidity();
      confirmation.setCustomValidity('');
      return;
    }

    // Update hidden OTP input if present
    updateFullOtp();
    // Allow natural HTML form submission to PHP backend
  });
});
