<?php
require_once '../../includes/init.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function fmtNumber($value) {
    $value = (float)$value;
    return number_format($value, 0, ',', ' ');
}

$userId = (int)$user['id'];
$companyId = (int)$user['company_id'];

$stmt = $pdo->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $companyId]);
$company = $stmt->fetch();

if (!$company) {
    die('Erreur : Entreprise introuvable pour cet administrateur.');
}

$message = '';
$messageType = '';
$openSettings = isset($_GET['settings']) && $_GET['settings'] === '1';
$activeTab = $_GET['tab'] ?? 'name';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// 5. TRAITEMENT DES PARAMÈTRES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectTab = 'name';

    try {
        if ($action === 'update_company_name') {
            $redirectTab = 'name';
            $newName = trim($_POST['company_name'] ?? '');
            if ($newName === '') throw new RuntimeException("Veuillez remplir tous les champs.");
            
            $stmt = $pdo->prepare('UPDATE companies SET nom = :nom WHERE id = :id');
            $stmt->execute(['nom' => $newName, 'id' => $companyId]);
            $_SESSION['flash_message'] = "Le nom de l’entreprise a été modifié.";
            $_SESSION['flash_type'] = 'success';
            
        } elseif ($action === 'update_email') {
            $redirectTab = 'email';
            $newEmail = trim($_POST['email'] ?? '');
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException("Veuillez fournir un email valide.");
            
            $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $stmt->execute(['email' => $newEmail, 'id' => $userId]);
            $_SESSION['flash_message'] = "L'email a été modifié.";
            $_SESSION['flash_type'] = 'success';
            
        } elseif ($action === 'update_company_code') {
            $redirectTab = 'code';
            $newCode = trim($_POST['company_code'] ?? '');
            if ($newCode === '') throw new RuntimeException("Le code ne peut pas être vide.");
            
            $stmt = $pdo->prepare('UPDATE companies SET code_invite = :code WHERE id = :id');
            $stmt->execute(['code' => $newCode, 'id' => $companyId]);
            $_SESSION['flash_message'] = "Le code entreprise a été modifié.";
            $_SESSION['flash_type'] = 'success';
            
        } elseif ($action === 'update_password') {
            $redirectTab = 'password';
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') throw new RuntimeException("Veuillez remplir tous les champs.");
            if (!password_verify($currentPassword, $user['password_hash'])) throw new RuntimeException("Mot de passe actuel incorrect.");
            if ($newPassword !== $confirmPassword) throw new RuntimeException("Les mots de passe ne correspondent pas.");

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute(['hash' => $hash, 'id' => $userId]);
            $_SESSION['flash_message'] = "Le mot de passe a été modifié.";
            $_SESSION['flash_type'] = 'success';
            
        } elseif ($action === 'update_company_logo') {
            $redirectTab = 'logo';
            if (!isset($_FILES['company_logo']) || !is_array($_FILES['company_logo'])) throw new RuntimeException("Erreur de téléchargement du logo.");
            
            $file = $_FILES['company_logo'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException("Veuillez sélectionner un logo valide.");

            $tmpPath = (string)($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) throw new RuntimeException("Erreur lors de l'upload.");

            $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath) ?: '';
            
            if (!isset($allowedMime[$mimeType])) throw new RuntimeException("Format non supporté (JPG, PNG, WEBP, GIF uniquement).");

            $uploadDir = __DIR__ . '/uploads/company_logos';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) throw new RuntimeException("Erreur serveur.");

            $filename = 'company_' . $companyId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mimeType];
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpPath, $destination)) throw new RuntimeException("Erreur lors de l'enregistrement du fichier.");

            $relativePath = 'uploads/company_logos/' . $filename;
            $stmt = $pdo->prepare('UPDATE companies SET logo_url = :logo WHERE id = :id');
            $stmt->execute(['logo' => $relativePath, 'id' => $companyId]);

            $_SESSION['flash_message'] = "Le logo de l’entreprise a été mis à jour.";
            $_SESSION['flash_type'] = 'success';
        } else {
            throw new RuntimeException("Action inconnue.");
        }

        header('Location: admin_profile.php?settings=1&tab=' . $redirectTab);
        exit;
    } catch (Throwable $ex) {
        $_SESSION['flash_message'] = $ex->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: admin_profile.php?settings=1&tab=' . $redirectTab);
        exit;
    }
}

// 6. DONNÉES D'AFFICHAGE
// Badges de l'entreprise
$stmt = $pdo->prepare("
    SELECT b.id, b.nom_fr, b.descr_fr, b.icon_url, b.xp_threshold,
           CASE WHEN ub.badge_id IS NULL THEN 0 ELSE 1 END AS obtained
    FROM badges b
    LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = :user_id
    ORDER BY b.xp_threshold ASC, b.id ASC
");
$stmt->execute(['user_id' => $userId]);
$badges = $stmt->fetchAll();

// Classement global des entreprises
$stmt = $pdo->query('SELECT id, nom, total_xp FROM companies ORDER BY total_xp DESC, id ASC');
$companies = $stmt->fetchAll();

$currentRank = 1;
foreach ($companies as $index => $row) {
    if ((int)$row['id'] === $companyId) {
        $currentRank = $index + 1;
        break;
    }
}
$topCompanies = array_slice($companies, 0, 5);

$companyNameValue = (string)($company['nom'] ?? '');
$companyCodeValue = (string)($company['code_invite'] ?? '');
$companyLogoValue = (string)($company['logo_url'] ?? '');
$userEmailValue = (string)($user['email'] ?? '');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Profil | ShiftUp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: #FF4800; }
    body { background-color: #ffffff; color: #111; font-family: 'Inter', system-ui, sans-serif; }
    .bg-primary { background-color: var(--primary); }
    .text-primary { color: var(--primary); }
    .border-primary { border-color: var(--primary); }
    .rounded-full-xl { border-radius: 9999px; }
    .card-radius { border-radius: 20px; }
    .soft-shadow { box-shadow: 0 10px 30px rgba(255, 72, 0, 0.08); }
    .badge-square { aspect-ratio: 1/1; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; }
    .badge-square:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 10px 25px rgba(255,72,0,0.2); }
    .badge-square.grayscale:hover { transform: scale(1.02); box-shadow: none; cursor: default; }
    .settings-tab.active { background-color: var(--primary); color: white; }
    
    /* Animation pour la modale du badge */
    @keyframes badgePop {
        0% { transform: scale(0.5); opacity: 0; }
        70% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(1); opacity: 1; }
    }
    .animate-badge-pop { animation: badgePop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
  </style>
</head>
<body class="min-h-screen">

<header class="bg-[#FF4800] h-24 relative shadow-lg">
  <div class="absolute left-0 top-0 bottom-0 w-24 md:w-72 bg-white flex items-center justify-center">
      <a href="admin_dashboard.php" aria-label="Accueil" class="w-16 h-16 flex items-center justify-center">
          <img src="../../img/logo/logo.png" alt="ShiftUp Logo" class="w-14 h-14 object-contain">
      </a>
  </div>
  
  <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-end pl-24 md:pl-72 pr-6">
    <nav class="flex items-center gap-8 text-[17px]">
      <a href="admin_shift_manager.php" class="font-bold text-white hover:opacity-80 transition-opacity">Shift manager</a>
      <a href="admin_gestion.php" class="font-medium text-white hover:opacity-80 transition-opacity">Gestion</a>
      <a href="admin_profile.php" class="w-11 h-11 rounded-full border-2 border-white flex items-center justify-center bg-white/20 transition">
        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
          <path d="M6 20c0-3 4-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-[1500px] mx-auto px-4 md:px-6 py-12">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-12">
    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900">Profil Entreprise</h1>
    <div class="flex items-center gap-4">
      <a href="admin_profile.php?settings=1" class="bg-[#FF4800] hover:bg-[#e64100] transition-all text-white font-bold px-8 py-4 rounded-full-xl text-lg shadow-md hover:shadow-lg">
        Paramètres
      </a>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <div class="mb-8 px-6 py-4 rounded-2xl shadow-sm border-l-4 <?= $messageType === 'error' ? 'bg-red-50 border-red-500 text-red-800' : 'bg-green-50 border-green-500 text-green-800' ?>">
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.25fr] gap-10">
    <section class="bg-white p-8 card-radius soft-shadow border border-gray-100">
      <div class="flex items-center justify-center mb-10">
        <h2 class="text-2xl font-bold text-gray-800">Vitrine à succès</h2>
      </div>

      <div class="grid grid-cols-5 gap-4 md:gap-6">
        <?php foreach ($badges as $badge): ?>
          <?php
            $obtained = (int)$badge['obtained'] === 1;
            $title = e($badge['nom_fr']);
            $desc = e($badge['descr_fr']);
            $imgSrc = !empty($badge['icon_url']) ? e($badge['icon_url']) : '';
          ?>
          
          <div class="badge-square w-full max-w-[86px] mx-auto rounded-2xl relative overflow-hidden flex items-center justify-center <?= $obtained ? 'bg-orange-50 border-2 border-[#FF4800]' : 'bg-gray-50 border border-gray-200 grayscale opacity-40' ?>" 
               <?= $obtained ? "onclick=\"openBadgeModal('{$imgSrc}', '{$title}', '{$desc}')\"" : '' ?>>
            
            <?php if ($imgSrc): ?>
              <img src="<?= $imgSrc ?>" alt="<?= $title ?>" class="w-12 h-12 object-contain">
            <?php else: ?>
              <?php if ($obtained): ?>
                <svg class="w-8 h-8 text-[#FF4800]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php else: ?>
                <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 11V8a5 5 0 0110 0v3" stroke-linecap="round" stroke-linejoin="round"/><rect x="5" y="11" width="14" height="9" rx="2" /></svg>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-white p-8 card-radius soft-shadow border border-gray-100 min-h-[600px]">
      <div class="flex items-start justify-between gap-4 mb-12">
        <h2 class="text-2xl font-bold text-gray-800">Classement Entreprise</h2>
        <div class="bg-orange-100 text-[#FF4800] px-4 py-2 rounded-full font-bold text-lg">
          Rang #<?= e($currentRank) ?>
        </div>
      </div>

      <div class="space-y-6">
        <?php foreach ($topCompanies as $index => $row): ?>
          <?php $isCurrent = (int)$row['id'] === $companyId; ?>
          <div class="flex items-center gap-4 group">
            <div class="w-12 h-12 flex-shrink-0 rounded-full <?= $isCurrent ? 'bg-[#FF4800] text-white' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center font-black text-lg shadow-sm">
              <?= e($index + 1) ?>
            </div>

            <div class="flex-1 rounded-[24px] h-20 flex items-center px-8 border-2 transition-all <?= $isCurrent ? 'border-[#FF4800] bg-orange-50/30 shadow-md' : 'border-gray-100 bg-white group-hover:border-gray-200' ?>">
              <div class="flex-1 text-xl font-bold text-gray-800">
                <?= e($isCurrent ? 'Vous' : $row['nom']) ?>
              </div>
              <div class="text-xl font-black <?= $isCurrent ? 'text-[#FF4800]' : 'text-gray-400' ?>">
                <?= e(fmtNumber($row['total_xp'])) ?> <span class="text-xs uppercase tracking-widest ml-1">pts</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<div id="badgeModal" class="fixed inset-0 bg-black/80 backdrop-blur-md hidden items-center justify-center z-[100] px-4 opacity-0 transition-opacity duration-300">
    <div class="relative bg-white rounded-[30px] p-10 max-w-sm w-full text-center shadow-2xl transform scale-95 transition-transform duration-300" id="badgeModalContent">
        <button onclick="closeBadgeModal()" class="absolute top-4 right-4 w-10 h-10 bg-gray-100 hover:bg-red-50 hover:text-red-500 rounded-full flex items-center justify-center text-gray-400 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <div class="w-40 h-40 mx-auto mb-6 relative">
            <div class="absolute inset-0 bg-orange-100 rounded-full blur-xl opacity-60 animate-pulse"></div>
            <img id="modalBadgeImg" src="" alt="Badge" class="w-full h-full object-contain relative z-10 drop-shadow-xl animate-badge-pop">
        </div>
        
        <h3 id="modalBadgeTitle" class="text-2xl font-black text-gray-900 mb-2 uppercase tracking-tight"></h3>
        <p id="modalBadgeDesc" class="text-gray-500 font-medium leading-relaxed"></p>
        
        <button onclick="closeBadgeModal()" class="mt-8 w-full bg-[#FF4800] hover:bg-orange-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95">
            Super !
        </button>
    </div>
</div>

<?php if ($openSettings): ?>
<div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 px-4">
  <div class="bg-white w-full max-w-[1100px] rounded-[30px] shadow-2xl relative overflow-hidden">
    <div class="bg-gray-50 px-10 py-6 border-b flex items-center justify-between">
        <h3 class="text-2xl font-bold">Paramètres</h3>
        <a href="admin_profile.php" class="w-12 h-12 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition-colors">
          <svg class="w-6 h-6 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
          </svg>
        </a>
    </div>

    <div class="p-8 md:p-12">
        <div class="flex flex-wrap justify-center gap-4 mb-10">
          <?php 
            $tabs = [
                'name' => 'Nom Entreprise', 
                'email' => 'Mail Contact', 
                'code' => 'Code Invite', 
                'logo' => 'Logo', 
                'password' => 'Mot de passe'
            ];
            foreach($tabs as $key => $label): 
          ?>
            <button type="button" data-tab="<?= $key ?>" class="settings-tab px-6 py-3 rounded-full font-bold transition-all border-2 <?= $activeTab === $key ? 'bg-[#FF4800] border-[#FF4800] text-white' : 'border-gray-200 text-gray-600 hover:border-[#FF4800] hover:text-[#FF4800]' ?>">
                <?= e($label) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100">
          <div id="panel-name" class="settings-panel <?= $activeTab === 'name' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_company_name">
              <label class="block font-bold text-gray-700">Changer le nom de l'entreprise</label>
              <input name="company_name" value="<?= e($companyNameValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg">Enregistrer</button>
            </form>
          </div>

          <div id="panel-email" class="settings-panel <?= $activeTab === 'email' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_email">
              <label class="block font-bold text-gray-700">Adresse email de l'administrateur</label>
              <input type="email" name="email" value="<?= e($userEmailValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg">Enregistrer</button>
            </form>
          </div>

          <div id="panel-code" class="settings-panel <?= $activeTab === 'code' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_company_code">
              <label class="block font-bold text-gray-700">Code d'invitation entreprise</label>
              <input name="company_code" value="<?= e($companyCodeValue) ?>" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none transition-all" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg">Enregistrer</button>
            </form>
          </div>

          <div id="panel-logo" class="settings-panel <?= $activeTab === 'logo' ? '' : 'hidden' ?>">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_company_logo">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <div class="text-center">
                  <span class="block mb-4 font-bold text-gray-500 uppercase text-xs">Logo Actuel</span>
                  <div class="w-48 h-48 mx-auto rounded-3xl bg-white flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300">
                    <?php if (!empty($companyLogoValue)): ?>
                      <img src="<?= e($companyLogoValue) ?>" class="w-full h-full object-contain p-4">
                    <?php else: ?>
                      <div class="text-gray-300">Aucun logo</div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="space-y-4">
                  <label for="company_logo" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-[#FF4800] bg-orange-50 rounded-3xl cursor-pointer hover:bg-orange-100 transition-colors">
                    <svg class="w-10 h-10 text-[#FF4800] mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v12m8-8l-8-8-8 8m16 12H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="text-sm font-bold text-[#FF4800]">Importer un logo</span>
                  </label>
                  <input id="company_logo" name="company_logo" type="file" class="hidden" accept="image/png, image/jpeg, image/webp">
                  <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl">Enregistrer</button>
                </div>
              </div>
            </form>
          </div>

          <div id="panel-password" class="settings-panel <?= $activeTab === 'password' ? '' : 'hidden' ?>">
            <form method="post" class="max-w-md mx-auto space-y-4">
              <input type="hidden" name="action" value="update_password">
              <input type="password" name="current_password" placeholder="Mot de passe actuel" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <input type="password" name="new_password" placeholder="Nouveau mot de passe" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <input type="password" name="confirm_password" placeholder="Confirmer mot de passe" class="w-full rounded-2xl px-6 py-4 border-2 border-gray-200 focus:border-[#FF4800] outline-none" />
              <button class="w-full bg-[#FF4800] text-white font-bold px-6 py-4 rounded-2xl shadow-lg">Enregistrer</button>
            </form>
          </div>
        </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
// Gestion des onglets des paramètres
document.querySelectorAll('.settings-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        document.querySelectorAll('.settings-tab').forEach(b => {
            b.classList.remove('bg-[#FF4800]', 'text-white', 'border-[#FF4800]');
            b.classList.add('border-gray-200', 'text-gray-600');
        });
        this.classList.add('bg-[#FF4800]', 'text-white', 'border-[#FF4800]');
        this.classList.remove('border-gray-200', 'text-gray-600');

        document.querySelectorAll('.settings-panel').forEach(function(panel) {
            panel.classList.add('hidden');
        });
        const selected = document.getElementById('panel-' + tab);
        if (selected) selected.classList.remove('hidden');
    });
});

// Modale des badges
const badgeModal = document.getElementById('badgeModal');
const badgeModalContent = document.getElementById('badgeModalContent');
const modalBadgeImg = document.getElementById('modalBadgeImg');
const modalBadgeTitle = document.getElementById('modalBadgeTitle');
const modalBadgeDesc = document.getElementById('modalBadgeDesc');

function openBadgeModal(imgSrc, title, desc) {
    if(!imgSrc) return;
    
    modalBadgeImg.src = imgSrc;
    modalBadgeTitle.textContent = title;
    modalBadgeDesc.textContent = desc;
    badgeModal.classList.remove('hidden');
    badgeModal.classList.add('flex');
    setTimeout(() => {
        badgeModal.classList.remove('opacity-0');
        badgeModalContent.classList.remove('scale-95');
        badgeModalContent.classList.add('scale-100');
    }, 10);
}

function closeBadgeModal() {
    badgeModal.classList.add('opacity-0');
    badgeModalContent.classList.remove('scale-100');
    badgeModalContent.classList.add('scale-95');
    setTimeout(() => {
        badgeModal.classList.add('hidden');
        badgeModal.classList.remove('flex');
    }, 300);
}

badgeModal.addEventListener('click', function(e) {
    if (e.target === badgeModal) {
        closeBadgeModal();
    }
});
</script>
</body>
</html>