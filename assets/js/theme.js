/**
 * E&N School Supplies — Theme (Dark/Light Mode) Manager
 *
 * Priority: force_dark_mode (system setting) > user preference > OS preference
 */

const ThemeManager = (() => {
  const STORAGE_KEY = 'en_theme';
  const html = document.documentElement;

  function getOSPreference() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'light';
  }

  function getSavedTheme() {
    return localStorage.getItem(STORAGE_KEY);
  }

  function saveTheme(theme) {
    localStorage.setItem(STORAGE_KEY, theme);
  }

  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    // Update toggle button icon if it exists
    const toggleBtn = document.getElementById('theme-toggle');
    if (toggleBtn) {
      toggleBtn.innerHTML = theme === 'dark' ? '&#9728;' : '&#127769;';
      toggleBtn.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    }
  }

  function getCurrentTheme() {
    return html.getAttribute('data-theme') || 'light';
  }

  function toggle() {
    const current = getCurrentTheme();
    const next = current === 'dark' ? 'light' : 'dark';
    saveTheme(next);
    applyTheme(next);

    // Persist to server if logged in
    if (typeof fetch !== 'undefined' && document.body.dataset.userId) {
      fetch(getBasePath() + 'api/profile/update.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken()
        },
        body: JSON.stringify({ theme_preference: next })
      }).catch(() => {});
    }
  }

  function init() {
    // Check if force dark mode is set (injected by PHP as data attribute)
    const forceDark = document.body.dataset.forceDarkMode === '1';

    if (forceDark) {
      applyTheme('dark');
      return;
    }

    // Check user's DB preference (injected as data attribute)
    const dbPref = document.body.dataset.themePreference;

    if (dbPref && dbPref !== 'auto') {
      applyTheme(dbPref);
      saveTheme(dbPref);
    } else {
      const saved = getSavedTheme();
      if (saved) {
        applyTheme(saved);
      } else {
        applyTheme(getOSPreference());
      }
    }

    // Listen for OS theme changes
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        const saved = getSavedTheme();
        const dbPref2 = document.body.dataset.themePreference;
        if (!saved && (!dbPref2 || dbPref2 === 'auto')) {
          applyTheme(e.matches ? 'dark' : 'light');
        }
      });
    }

    // Bind toggle button
    const toggleBtn = document.getElementById('theme-toggle');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', toggle);
    }
  }

  function getBasePath() {
    return document.body.dataset.basePath || '../';
  }

  function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  return { init, toggle, getCurrentTheme, applyTheme };
})();

document.addEventListener('DOMContentLoaded', ThemeManager.init);
