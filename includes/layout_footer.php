  </main><!-- /.page-content -->

  <script src="<?= $basePath ?>assets/js/global.js"></script>
  <script src="<?= $basePath ?>assets/js/theme.js"></script>
  <script src="<?= $basePath ?>assets/js/sidebar.js"></script>
  <script src="<?= $basePath ?>assets/js/custom-select.js"></script>
  <script src="<?= $basePath ?>assets/js/pagination.js"></script>

  <?php if (!empty($extraJs)): ?>
    <?php foreach ((array)$extraJs as $js): ?>
      <script src="<?= $basePath . $js ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>

  <script>
    // Profile dropdown toggle
    const profileTrigger = document.getElementById('profile-trigger');
    const profileDropdown = document.getElementById('profile-dropdown');
    if (profileTrigger && profileDropdown) {
      profileTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('open');
      });
      document.addEventListener('click', () => {
        profileDropdown.classList.remove('open');
      });
    }
  </script>
</body>
</html>
