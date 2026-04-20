<?php
/**
 * Shared profile page content.
 * Included by admin/profile.php, staff/profile.php, customer/profile.php
 * Expects $user and $basePath to be set.
 */
?>
<div class="page-header">
  <h1>My Profile</h1>
  <p class="subtitle">Update your personal information</p>
</div>

<div style="display:grid; grid-template-columns: 280px 1fr; gap: 1.5rem; max-width: 900px;">

  <!-- Avatar Card -->
  <div class="card" style="align-self:start">
    <div class="card-body" style="text-align:center">
      <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;margin:0 auto 1rem;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#1b5e20;">
        <?php if (!empty($user['profile_image'])): ?>
          <img id="avatar-preview" src="<?= $basePath . $user['profile_image'] ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
        <?php else:
          $parts = explode(' ', $user['full_name']);
          $initials = strtoupper(substr($parts[0], 0, 1));
          if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
        ?>
          <span id="avatar-preview"><?= $initials ?></span>
        <?php endif; ?>
      </div>
      <h3 style="font-size:1rem;"><?= htmlspecialchars($user['full_name']) ?></h3>
      <p class="text-muted" style="font-size:0.82rem;text-transform:capitalize;margin-bottom:1rem"><?= $user['role'] ?></p>

      <form id="avatar-form" enctype="multipart/form-data" style="margin-top:0.5rem;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label class="btn btn-outline btn-sm" style="cursor:pointer">
          &#128247; Change Photo
          <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png" style="display:none">
        </label>
        <p class="text-muted" style="font-size:0.72rem;margin-top:0.4rem">Max 1MB · JPG or PNG</p>
      </form>
    </div>
  </div>

  <!-- Profile Form Card -->
  <div class="card">
    <div class="card-header">
      <h3 style="font-size:1rem">Personal Information</h3>
    </div>
    <div class="card-body">
      <form id="profile-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input class="form-input" type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-input" type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number</label>
          <input class="form-input" type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0">

        <div class="form-group">
          <label class="form-label" for="password">New Password <span class="text-muted" style="font-weight:400">(leave blank to keep current)</span></label>
          <input class="form-input" type="password" id="password" name="password" placeholder="Min. 4 characters">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>

</div>

<style>
  @media (max-width: 640px) {
    div[style*="grid-template-columns: 280px"] {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const basePath = getBasePath();

  // Avatar upload
  const avatarInput = document.getElementById('avatar-input');
  if (avatarInput) {
    avatarInput.addEventListener('change', async () => {
      const file = avatarInput.files[0];
      if (!file) return;
      if (file.size > 1048576) {
        Toast.error('File too large. Max 1MB.');
        return;
      }
      const fd = new FormData();
      fd.append('avatar', file);
      fd.append('csrf_token', getCSRFToken());

      try {
        const data = await apiFetch('api/profile/upload_avatar.php', { body: fd });
        if (data.success) {
          Toast.success(data.message);
          // Reload to show new avatar
          setTimeout(() => location.reload(), 800);
        }
      } catch (e) {}
    });
  }

  // Profile form
  const profileForm = document.getElementById('profile-form');
  if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(profileForm);
      const payload = Object.fromEntries(fd);

      try {
        const data = await apiFetch('api/profile/update.php', {
          body: JSON.stringify(payload)
        });
        if (data.success) {
          Toast.success(data.message);
          setTimeout(() => location.reload(), 800);
        }
      } catch (e) {}
    });
  }
});
</script>
