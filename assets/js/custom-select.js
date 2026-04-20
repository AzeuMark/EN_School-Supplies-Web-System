/**
 * E&N School Supplies — Custom Select Dropdown Component
 *
 * Usage:
 *   <div class="custom-select" data-name="category_id" data-placeholder="Select category">
 *     <div class="custom-select-option" data-value="1">Notebooks</div>
 *     <div class="custom-select-option" data-value="2">Writing Instruments</div>
 *   </div>
 *
 * The component creates a hidden <input> with the selected value.
 */

const CustomSelect = (() => {

  function init() {
    document.querySelectorAll('.custom-select:not(.cs-initialized)').forEach(buildSelect);
  }

  function buildSelect(wrapper) {
    wrapper.classList.add('cs-initialized');

    const name = wrapper.dataset.name || 'select';
    const placeholder = wrapper.dataset.placeholder || 'Select...';
    const options = Array.from(wrapper.querySelectorAll('.custom-select-option'));

    // Clear and rebuild
    wrapper.innerHTML = '';
    wrapper.style.position = 'relative';

    // Hidden input
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = name;
    wrapper.appendChild(hiddenInput);

    // Trigger button
    const trigger = document.createElement('div');
    trigger.className = 'cs-trigger';
    trigger.innerHTML = `<span class="cs-label">${escapeHtml(placeholder)}</span><span class="cs-arrow">&#9662;</span>`;
    wrapper.appendChild(trigger);

    // Dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'cs-dropdown';

    // Search input
    const searchWrap = document.createElement('div');
    searchWrap.className = 'cs-search-wrap';
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'cs-search';
    searchInput.placeholder = 'Search...';
    searchWrap.appendChild(searchInput);
    dropdown.appendChild(searchWrap);

    // Options list
    const optionsList = document.createElement('div');
    optionsList.className = 'cs-options';

    options.forEach(opt => {
      const item = document.createElement('div');
      item.className = 'cs-option';
      item.dataset.value = opt.dataset.value;
      item.textContent = opt.textContent;

      item.addEventListener('click', () => {
        hiddenInput.value = item.dataset.value;
        trigger.querySelector('.cs-label').textContent = item.textContent;
        trigger.classList.add('cs-has-value');
        closeDropdown(wrapper, dropdown);

        // Dispatch change event
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      });

      optionsList.appendChild(item);
    });

    dropdown.appendChild(optionsList);
    wrapper.appendChild(dropdown);

    // Toggle dropdown on trigger click
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = dropdown.classList.contains('cs-open');
      closeAllDropdowns();
      if (!isOpen) {
        dropdown.classList.add('cs-open');
        searchInput.value = '';
        filterOptions(optionsList, '');
        setTimeout(() => searchInput.focus(), 50);
      }
    });

    // Search filtering
    searchInput.addEventListener('input', () => {
      filterOptions(optionsList, searchInput.value);
    });

    // Prevent click inside dropdown from closing it
    dropdown.addEventListener('click', (e) => e.stopPropagation());
  }

  function filterOptions(optionsList, query) {
    const q = query.toLowerCase();
    optionsList.querySelectorAll('.cs-option').forEach(opt => {
      const match = opt.textContent.toLowerCase().includes(q);
      opt.style.display = match ? '' : 'none';
    });
  }

  function closeDropdown(wrapper, dropdown) {
    dropdown.classList.remove('cs-open');
  }

  function closeAllDropdowns() {
    document.querySelectorAll('.cs-dropdown.cs-open').forEach(d => {
      d.classList.remove('cs-open');
    });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Set value programmatically
  function setValue(wrapper, value, label) {
    const hidden = wrapper.querySelector('input[type="hidden"]');
    const trigger = wrapper.querySelector('.cs-label');
    if (hidden) hidden.value = value;
    if (trigger) {
      trigger.textContent = label || value;
      trigger.parentElement.classList.add('cs-has-value');
    }
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', closeAllDropdowns);

  return { init, setValue };
})();

document.addEventListener('DOMContentLoaded', CustomSelect.init);
