/**
 * E&N School Supplies — Reusable Pagination Component
 *
 * Usage:
 *   Pagination.render({
 *     container: '#pagination',
 *     currentPage: 1,
 *     totalPages: 10,
 *     onPageChange: (page) => loadData(page)
 *   });
 */

const Pagination = (() => {

  function render({ container, currentPage, totalPages, onPageChange }) {
    const el = typeof container === 'string' ? document.querySelector(container) : container;
    if (!el || totalPages <= 1) {
      if (el) el.innerHTML = '';
      return;
    }

    el.innerHTML = '';
    el.className = 'pagination';

    // Previous button
    const prevBtn = createButton('&#8249;', currentPage <= 1, () => onPageChange(currentPage - 1));
    el.appendChild(prevBtn);

    // Page number buttons
    const pages = getPageNumbers(currentPage, totalPages);
    pages.forEach(p => {
      if (p === '...') {
        const dots = document.createElement('span');
        dots.className = 'page-btn';
        dots.style.cursor = 'default';
        dots.style.border = 'none';
        dots.innerHTML = '&hellip;';
        el.appendChild(dots);
      } else {
        const btn = createButton(p, false, () => onPageChange(p));
        if (p === currentPage) btn.classList.add('active');
        el.appendChild(btn);
      }
    });

    // Next button
    const nextBtn = createButton('&#8250;', currentPage >= totalPages, () => onPageChange(currentPage + 1));
    el.appendChild(nextBtn);
  }

  function createButton(label, disabled, onClick) {
    const btn = document.createElement('button');
    btn.className = 'page-btn';
    btn.innerHTML = label;
    btn.disabled = disabled;
    if (!disabled) {
      btn.addEventListener('click', onClick);
    }
    return btn;
  }

  function getPageNumbers(current, total) {
    const pages = [];
    const delta = 2;

    if (total <= 7) {
      for (let i = 1; i <= total; i++) pages.push(i);
      return pages;
    }

    pages.push(1);

    if (current > delta + 2) {
      pages.push('...');
    }

    const start = Math.max(2, current - delta);
    const end = Math.min(total - 1, current + delta);

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    if (current < total - delta - 1) {
      pages.push('...');
    }

    pages.push(total);

    return pages;
  }

  return { render };
})();
