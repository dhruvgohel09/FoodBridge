const foodName = document.querySelector('.food-name-field input');
document.querySelectorAll('.suggestions button').forEach((suggestion) => {
  suggestion.addEventListener('click', () => {
    foodName.value = suggestion.textContent.replace(/^\+\s*/, '');
    foodName.focus();
  });
});

const photoInput = document.querySelector('#food-photo');
const uploadMessage = document.querySelector('.upload-box > small');
photoInput.addEventListener('change', () => {
  const [file] = photoInput.files;
  if (file && file.size > 5 * 1024 * 1024) {
    photoInput.setCustomValidity('Choose an image smaller than 5 MB.');
    photoInput.reportValidity();
    photoInput.setCustomValidity('');
    photoInput.value = '';
    return;
  }
  uploadMessage.textContent = file ? file.name : 'Drag & drop file here, or click to browse (PNG, JPG up to 5MB)';
});
