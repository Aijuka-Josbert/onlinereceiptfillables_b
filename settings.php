<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();

$settings = getCompany($pdo);
$success = $error = '';
$csrf_token = generateCSRFToken();

// Palettes offered as one-click presets — the same 3 colors (paper,
// ink, accent) a user could also pick freely with the color inputs.
$PALETTES = [
    ['name' => 'Slate Blue',    'paper' => '#EEF3F7', 'ink' => '#1B2733', 'accent' => '#2F6690'],
    ['name' => 'Forest Green',  'paper' => '#F2F6EE', 'ink' => '#1E2A1A', 'accent' => '#2F6B4F'],
    ['name' => 'Amber Gold',    'paper' => '#FBF3E2', 'ink' => '#2A2013', 'accent' => '#A5461F'],
    ['name' => 'Charcoal Mono', 'paper' => '#F4F4F2', 'ink' => '#1A1A1A', 'accent' => '#1A1A1A'],
    ['name' => 'Burgundy',      'paper' => '#F8EEEC', 'ink' => '#2B1414', 'accent' => '#7A2331'],
    ['name' => 'Deep Teal',     'paper' => '#EAF5F3', 'ink' => '#132420', 'accent' => '#0F6F60'],
    ['name' => 'Royal Plum',    'paper' => '#F4EEF6', 'ink' => '#231A2B', 'accent' => '#6A3E82'],
    ['name' => 'Classic Cream', 'paper' => '#F8F3E9', 'ink' => '#1E1B16', 'accent' => '#A53F2F'],
];

function validHex($v, $fallback) {
    $v = trim((string)$v);
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? $v : $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed.');
    }
    $company_name = trim($_POST['company_name']);
    $tagline = trim($_POST['tagline'] ?? '');
    $address = trim($_POST['address']);
    $pobox = trim($_POST['pobox']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $tin = trim($_POST['tin']);
    $registration_number = trim($_POST['registration_number']);
    $logo = trim($_POST['logo'] ?? '');

    // Logo: keep whatever is already saved unless the admin uploads a new
    // file or explicitly asks to remove it. An upload takes priority over
    // the pasted link when both are present.
    $logo_data = $settings['logo_data'] ?? null;
    if (!empty($_POST['remove_logo'])) {
        $logo_data = null;
        $logo = '';
    } elseif (!empty($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['logo_file']['tmp_name'];
        // Prefer the fileinfo extension; fall back to getimagesize() if
        // fileinfo isn't enabled on this PHP install (not all XAMPP/WAMP
        // setups have it on by default).
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        } else {
            $info = @getimagesize($tmpPath);
            $mime = $info['mime'] ?? false;
        }
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        if ($mime && in_array($mime, $allowed, true) && $_FILES['logo_file']['size'] <= 2 * 1024 * 1024) {
            $bytes = file_get_contents($tmpPath);
            $logo_data = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        } else {
            $error = 'Logo must be a PNG, JPG, GIF or WEBP image under 2MB.';
        }
    }

    // Per-document theme colors
    $dn_paper = validHex($_POST['dn_paper'] ?? '', $settings['dn_paper']);
    $dn_ink = validHex($_POST['dn_ink'] ?? '', $settings['dn_ink']);
    $dn_accent = validHex($_POST['dn_accent'] ?? '', $settings['dn_accent']);
    $rc_paper = validHex($_POST['rc_paper'] ?? '', $settings['rc_paper']);
    $rc_ink = validHex($_POST['rc_ink'] ?? '', $settings['rc_ink']);
    $rc_accent = validHex($_POST['rc_accent'] ?? '', $settings['rc_accent']);
    $pf_paper = validHex($_POST['pf_paper'] ?? '', $settings['pf_paper']);
    $pf_ink = validHex($_POST['pf_ink'] ?? '', $settings['pf_ink']);
    $pf_accent = validHex($_POST['pf_accent'] ?? '', $settings['pf_accent']);

    if (empty($company_name)) {
        $error = 'Company name is required.';
    } elseif (!$error) {
        try {
            $stmt = $pdo->prepare("UPDATE settings SET company_name=?, tagline=?, address=?, pobox=?, phone=?, email=?, website=?, tin=?, registration_number=?, logo=?, logo_data=?,
                dn_paper=?, dn_ink=?, dn_accent=?, rc_paper=?, rc_ink=?, rc_accent=?, pf_paper=?, pf_ink=?, pf_accent=? WHERE id=1");
            $stmt->execute([
                $company_name, $tagline, $address, $pobox, $phone, $email, $website, $tin, $registration_number, $logo, $logo_data,
                $dn_paper, $dn_ink, $dn_accent, $rc_paper, $rc_ink, $rc_accent, $pf_paper, $pf_ink, $pf_accent,
            ]);
            $success = 'Settings updated successfully!';
            $settings = getCompany($pdo);
        } catch (PDOException $e) {
            // Most likely cause: the new columns (tagline, logo_data,
            // dn_paper, etc.) don't exist yet in this database — run
            // migration.sql first. Showing the message here instead of
            // letting it bubble up avoids a blank HTTP 500.
            $error = "Couldn't save settings — database error: " . $e->getMessage()
                . ". If this mentions an unknown column, run migration.sql against your database first.";
        }
    }
}

$pageTitle = 'Settings';
include 'includes/header.php';
?>
<h2>Company Settings</h2>
<?php if ($success): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif; ?>
<form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

    <h5 class="mb-3"><i class="fas fa-building"></i> Letterhead</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?= esc($settings['company_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tagline / Line of Business</label>
            <input type="text" name="tagline" class="form-control" value="<?= esc($settings['tagline'] ?? '') ?>" placeholder="e.g. Suppliers of Milling Equipment">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Company Logo</label>
        <div class="logo-controls">
            <label class="upload-btn" style="cursor:pointer;">
                <i class="fas fa-upload"></i> Upload image
                <input type="file" name="logo_file" accept="image/*" hidden>
            </label>
            <label class="upload-btn ghost" style="cursor:pointer;">
                <input type="checkbox" name="remove_logo" value="1" style="width:auto;margin-right:6px;"> Remove current logo
            </label>
        </div>
        <input type="text" name="logo" class="form-control mt-2" value="<?= esc($settings['logo'] ?? '') ?>" placeholder="or paste an image link (https://...)">
        <?php $currentLogo = companyLogoSrc($settings); ?>
        <div class="logo-preview-row" <?= $currentLogo ? '' : 'style="display:none"' ?>>
            <img src="<?= esc($currentLogo) ?>" alt="Current logo">
            <span><?= !empty($settings['logo_data']) ? 'Using uploaded image.' : 'Using linked image.' ?> This appears on every document.</span>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= esc($settings['address']) ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">P.O. Box</label>
        <input type="text" name="pobox" class="form-control" value="<?= esc($settings['pobox']) ?>">
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= esc($settings['phone']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= esc($settings['email']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Website</label>
            <input type="text" name="website" class="form-control" value="<?= esc($settings['website']) ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">TIN (optional)</label>
            <input type="text" name="tin" class="form-control" value="<?= esc($settings['tin']) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Registration No. (optional)</label>
            <input type="text" name="registration_number" class="form-control" value="<?= esc($settings['registration_number']) ?>">
        </div>
    </div>

    <hr class="my-4">
    <h5 class="mb-1"><i class="fas fa-palette"></i> Document Colors</h5>
    <p class="text-muted" style="font-size:13px;">Each document type keeps its own background, text, and accent color, so a Delivery Note, Receipt, and Proforma Invoice are easy to tell apart at a glance — on screen and when printed.</p>

    <?php
    $docs = [
        'dn' => 'Delivery Note',
        'rc' => 'Receipt',
        'pf' => 'Proforma Invoice',
    ];
    foreach ($docs as $key => $label):
    ?>
    <div class="theme-card" data-doc="<?= $key ?>">
        <h5><?= esc($label) ?></h5>
        <div class="swatch-row" data-doc="<?= $key ?>">
            <?php foreach ($PALETTES as $p): ?>
                <button type="button" class="swatch" title="<?= esc($p['name']) ?>"
                    style="background:<?= $p['paper'] ?>; box-shadow: inset 0 0 0 3px <?= $p['accent'] ?>;"
                    data-paper="<?= $p['paper'] ?>" data-ink="<?= $p['ink'] ?>" data-accent="<?= $p['accent'] ?>"
                    onclick="applyPalette('<?= $key ?>', this)"></button>
            <?php endforeach; ?>
        </div>
        <div class="color-pick-row">
            <label class="color-pick"><span>Background</span>
                <input type="color" name="<?= $key ?>_paper" value="<?= esc($settings[$key . '_paper']) ?>">
            </label>
            <label class="color-pick"><span>Text</span>
                <input type="color" name="<?= $key ?>_ink" value="<?= esc($settings[$key . '_ink']) ?>">
            </label>
            <label class="color-pick"><span>Accent</span>
                <input type="color" name="<?= $key ?>_accent" value="<?= esc($settings[$key . '_accent']) ?>">
            </label>
        </div>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-save"></i> Save Settings</button>
</form>

<script>
// One-click palette presets for the Document Colors section above.
// Still fully overridable via the three color pickers per document.
function applyPalette(doc, btn) {
    const card = btn.closest('.theme-card');
    card.querySelector(`input[name="${doc}_paper"]`).value = btn.dataset.paper;
    card.querySelector(`input[name="${doc}_ink"]`).value = btn.dataset.ink;
    card.querySelector(`input[name="${doc}_accent"]`).value = btn.dataset.accent;
}
// Preview the logo file immediately after choosing one, before saving.
document.querySelector('input[name="logo_file"]').addEventListener('change', function (e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
        const row = document.querySelector('.logo-preview-row');
        row.style.display = 'flex';
        row.querySelector('img').src = ev.target.result;
        row.querySelector('span').textContent = 'New logo selected — click Save Settings to apply.';
    };
    reader.readAsDataURL(file);
});
</script>

<?php include 'includes/footer.php'; ?>