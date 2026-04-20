<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Staff Statistics — Admin';
$activePage = 'staff_stats';

$threshold = (int)get_setting('auto_logout_hours', '8');

// Get staff stats
$staffStats = [];
try {
    $stmt = $pdo->query(
        "SELECT u.id, u.full_name, u.email,
                COUNT(ss.id) AS total_logins,
                COALESCE(SUM(ss.duration_minutes), 0) AS total_minutes,
                MAX(ss.login_time) AS last_login,
                COALESCE(AVG(ss.duration_minutes), 0) AS avg_session,
                SUM(CASE WHEN ss.is_suspicious = 1 THEN 1 ELSE 0 END) AS suspicious_count
         FROM users u
         LEFT JOIN staff_sessions ss ON u.id = ss.user_id
         WHERE u.role = 'staff'
         GROUP BY u.id
         ORDER BY total_logins DESC"
    );
    $staffStats = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Staff Statistics</h1>
  <p class="subtitle">Auto-logout threshold: <?= $threshold ?> hours</p>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr><th>Staff Name</th><th>Total Logins</th><th>Total Time (hrs)</th><th>Last Login</th><th>Avg Session (min)</th><th>Suspicious</th></tr>
    </thead>
    <tbody>
      <?php if (empty($staffStats)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No staff data</td></tr>
      <?php else: ?>
        <?php foreach ($staffStats as $s): ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['full_name']) ?></strong><br><span class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($s['email']) ?></span></td>
            <td><?= $s['total_logins'] ?></td>
            <td><?= number_format($s['total_minutes'] / 60, 1) ?></td>
            <td><?= $s['last_login'] ? date('M d, Y h:i A', strtotime($s['last_login'])) : '—' ?></td>
            <td><?= number_format($s['avg_session'], 0) ?></td>
            <td>
              <?php if ($s['suspicious_count'] > 0): ?>
                <span class="badge badge-cancelled"><?= $s['suspicious_count'] ?> sessions</span>
                <div style="font-size:0.72rem;color:var(--danger);margin-top:0.2rem">Auto-logout by system — deducted from performance</div>
              <?php else: ?>
                <span class="text-muted">0</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Simple Charts -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-top:1.5rem;">
  <div class="card">
    <div class="card-header">Top 5 Most Active Staff</div>
    <div class="card-body">
      <?php
      $top5 = array_slice($staffStats, 0, 5);
      $maxLogins = max(array_column($top5, 'total_logins') ?: [1]);
      foreach ($top5 as $s):
        $pct = $maxLogins > 0 ? ($s['total_logins'] / $maxLogins * 100) : 0;
      ?>
        <div style="margin-bottom:0.6rem">
          <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:0.2rem">
            <span><?= htmlspecialchars($s['full_name']) ?></span>
            <span class="text-muted"><?= $s['total_logins'] ?> logins</span>
          </div>
          <div style="height:8px;background:var(--border);border-radius:999px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:var(--primary);border-radius:999px"></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($top5)): ?>
        <p class="text-muted text-center">No data</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Login Frequency (Last 7 Days)</div>
    <div class="card-body">
      <?php
      $loginFreq = [];
      try {
          $stmt = $pdo->query(
              "SELECT DATE(login_time) AS day, COUNT(*) AS cnt
               FROM staff_sessions
               WHERE login_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               GROUP BY DATE(login_time) ORDER BY day"
          );
          $loginFreq = $stmt->fetchAll();
      } catch (Exception $e) {}
      $maxFreq = max(array_column($loginFreq, 'cnt') ?: [1]);

      if (empty($loginFreq)): ?>
        <p class="text-muted text-center">No data</p>
      <?php else: ?>
        <div style="display:flex;align-items:flex-end;gap:0.4rem;height:120px">
          <?php foreach ($loginFreq as $lf):
            $h = $maxFreq > 0 ? ($lf['cnt'] / $maxFreq * 100) : 0;
          ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.2rem">
              <div style="width:100%;background:var(--primary);border-radius:4px 4px 0 0;height:<?= $h ?>%;min-height:4px"></div>
              <span style="font-size:0.65rem;color:var(--text-muted)"><?= date('D', strtotime($lf['day'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
