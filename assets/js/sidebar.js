/**
 * E&N School Supplies — Sidebar & Hamburger Logic
 */

const Sidebar = (() => {
  const STORAGE_KEY = 'en_sidebar_collapsed';

  function init() {
    const hamburger = document.getElementById('hamburger-btn');
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!hamburger || !sidebar) return;

    // Restore collapsed state from localStorage (desktop only)
    if (window.innerWidth > 768) {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === '1') {
        body.classList.add('sidebar-collapsed');
      }
    }

    // Hamburger click
    hamburger.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        // Mobile: toggle mobile-open class
        sidebar.classList.toggle('mobile-open');
      } else {
        // Desktop: toggle collapsed state
        body.classList.toggle('sidebar-collapsed');
        const isCollapsed = body.classList.contains('sidebar-collapsed');
        localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
      }
    });

    // Overlay click closes mobile sidebar
    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
      });
    }

    // Close mobile sidebar on link click
    sidebar.querySelectorAll('.sidebar-item').forEach(item => {
      item.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('mobile-open');
        }
      });
    });

    // Highlight active sidebar item based on current URL
    highlightActive();
  }

  function highlightActive() {
    const currentPath = window.location.pathname;
    const items = document.querySelectorAll('.sidebar-item');

    items.forEach(item => {
      const href = item.getAttribute('href');
      if (href && currentPath.endsWith(href.replace(/^\.\//, '').replace(/^\.\.\//, ''))) {
        item.classList.add('active');
      }
    });
  }

  return { init };
})();

document.addEventListener('DOMContentLoaded', Sidebar.init);
