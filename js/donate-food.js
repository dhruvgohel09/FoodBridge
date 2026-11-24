// Food item suggestions auto-fill
const foodName = document.querySelector('.food-name-field input');
document.querySelectorAll('.suggestions button').forEach((suggestion) => {
  suggestion.addEventListener('click', () => {
    if (foodName) {
      foodName.value = suggestion.textContent.replace(/^\+\s*/, '');
      foodName.focus();
      // Clear error if present
      const wrap = foodName.closest('.input-wrap') || foodName;
      wrap.classList.remove('has-error');
      const err = wrap.parentElement ? wrap.parentElement.querySelector('.field-error-text') : null;
      if (err) err.remove();
    }
  });
});

// Photo upload size check with inline red message
const photoInput = document.querySelector('#food-photo');
const uploadMessage = document.querySelector('.upload-box > small');
const uploadBox = document.querySelector('.upload-box');

if (photoInput) {
  photoInput.addEventListener('change', () => {
    const [file] = photoInput.files;

    // Remove any existing error
    if (uploadBox) {
      uploadBox.classList.remove('has-error');
      const parent = uploadBox.parentElement;
      if (parent) {
        const existing = parent.querySelector('.field-error-text');
        if (existing) existing.remove();
      }
    }

    if (file && file.size > 5 * 1024 * 1024) {
      photoInput.value = '';
      if (uploadBox) {
        uploadBox.classList.add('has-error');
        const err = document.createElement('span');
        err.className = 'field-error-text';
        err.textContent = 'Please choose an image smaller than 5 MB.';
        uploadBox.insertAdjacentElement('afterend', err);
      }
      return;
    }
    if (uploadMessage) {
      uploadMessage.textContent = file ? file.name : 'Drag & drop file here, or click to browse (PNG, JPG up to 5MB)';
    }
  });
}
