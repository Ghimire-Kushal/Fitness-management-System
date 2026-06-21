<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

// --- Add plan ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $plan_name     = trim($_POST['plan_name'] ?? '');
    $duration_type = $_POST['duration_type'] ?? '';
    $price         = trim($_POST['price'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    if (!$plan_name || !in_array($duration_type, ['monthly', 'yearly']) || !is_numeric($price)) {
        set_flash('error', 'Name, valid duration type and numeric price are required.');
    } else {
        db_exec(
            'INSERT INTO membership_plans (plan_name, duration_type, price, description) VALUES (?,?,?,?)',
            [$plan_name, $duration_type, $price, $description]
        );
        set_flash('success', "Plan \"$plan_name\" added.");
    }
    redirect_to('/admin/plans.php');
}

// --- Edit plan -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $plan_id       = (int)($_POST['plan_id'] ?? 0);
    $plan_name     = trim($_POST['plan_name'] ?? '');
    $duration_type = $_POST['duration_type'] ?? '';
    $price         = trim($_POST['price'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    if (!$plan_name || !in_array($duration_type, ['monthly', 'yearly']) || !is_numeric($price)) {
        set_flash('error', 'Name, valid duration type and numeric price are required.');
    } elseif ($plan_id > 0) {
        db_exec(
            'UPDATE membership_plans SET plan_name=?, duration_type=?, price=?, description=? WHERE plan_id=?',
            [$plan_name, $duration_type, $price, $description, $plan_id]
        );
        set_flash('success', "Plan \"$plan_name\" updated.");
    }
    redirect_to('/admin/plans.php');
}

// --- Delete plan ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    if ($plan_id > 0) {
        $plan = db_query('SELECT plan_name FROM membership_plans WHERE plan_id=?', [$plan_id], true);
        // Expire memberships on this plan before deleting
        db_exec("UPDATE memberships SET status='expired' WHERE plan_id=?", [$plan_id]);
        db_exec('DELETE FROM membership_plans WHERE plan_id=?', [$plan_id]);
        set_flash('success', "Plan \"{$plan['plan_name']}\" deleted.");
    }
    redirect_to('/admin/plans.php');
}

$plans      = db_query('SELECT * FROM membership_plans ORDER BY duration_type, price');
$edit_id    = (int)($_GET['edit'] ?? 0);
$edit_plan  = $edit_id ? db_query('SELECT * FROM membership_plans WHERE plan_id=?', [$edit_id], true) : null;

$title = 'Membership plans';
include __DIR__ . '/../includes/header.php';
?>

<h1>Membership plans</h1>
<p class="subtitle">Add, edit or remove the plans members can choose from.</p>

<!-- Add / Edit form -->
<div class="card">
  <h2><?= $edit_plan ? 'Edit plan' : 'Add plan' ?></h2>
  <form method="post" action="<?= htmlspecialchars(url_path('/admin/plans.php')) ?>">
    <input type="hidden" name="action" value="<?= $edit_plan ? 'edit' : 'add' ?>">
    <?php if ($edit_plan): ?>
      <input type="hidden" name="plan_id" value="<?= $edit_plan['plan_id'] ?>">
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px">
      <div>
        <label for="plan_name">Plan name</label>
        <input type="text" id="plan_name" name="plan_name" required
               value="<?= htmlspecialchars($edit_plan['plan_name'] ?? '') ?>"
               >
      </div>
      <div>
        <label for="duration_type">Duration</label>
        <select id="duration_type" name="duration_type">
          <option value="monthly" <?= ($edit_plan['duration_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
          <option value="yearly"  <?= ($edit_plan['duration_type'] ?? '') === 'yearly'  ? 'selected' : '' ?>>Yearly</option>
        </select>
      </div>
      <div>
        <label for="price">Price (Rs.)</label>
        <input type="number" id="price" name="price" step="0.01" min="0" required
               value="<?= htmlspecialchars($edit_plan['price'] ?? '') ?>"
               >
      </div>
      <div>
        <label for="description">Description</label>
        <input type="text" id="description" name="description"
               value="<?= htmlspecialchars($edit_plan['description'] ?? '') ?>"
               >
      </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:10px">
      <button class="btn" type="submit"><?= $edit_plan ? 'Save changes' : 'Add plan' ?></button>
      <?php if ($edit_plan): ?>
        <a class="btn btn-plain" href="<?= htmlspecialchars(url_path('/admin/plans.php')) ?>">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Plans table -->
<div class="card">
  <h2>Current plans</h2>
  <?php if ($plans): ?>
    <table>
      <tr><th>Plan</th><th>Type</th><th>Price</th><th>Description</th><th>Actions</th></tr>
      <?php foreach ($plans as $p): ?>
      <tr>
        <td><strong><?= htmlspecialchars($p['plan_name']) ?></strong></td>
        <td><?= htmlspecialchars($p['duration_type']) ?></td>
        <td>Rs. <?= number_format($p['price'], 2) ?></td>
        <td class="muted"><?= htmlspecialchars($p['description'] ?? '—') ?></td>
        <td class="row-actions" style="display:flex;gap:8px;align-items:center">
          <a class="btn btn-small btn-plain"
             href="<?= htmlspecialchars(url_path('/admin/plans.php?edit=' . $p['plan_id'])) ?>">Edit</a>
          <form method="post" action="<?= htmlspecialchars(url_path('/admin/plans.php')) ?>"
                onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($p['plan_name'])) ?>? Active memberships on this plan will be expired.')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="plan_id" value="<?= $p['plan_id'] ?>">
            <button class="btn btn-small btn-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No plans yet. Add one above.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
