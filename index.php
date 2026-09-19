<?php
require_once 'db.php';

// Récupération des paramètres
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_path'");
$stmt->execute();
$logo = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'school_name'");
$stmt->execute();
$school_name = $stmt->fetchColumn() ?: "Nom de l'établissement";

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_shape'");
$stmt->execute();
$logo_shape = $stmt->fetchColumn() ?: 'round';
$logo_class = ($logo_shape === 'square') ? 'logo-square' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des Notes - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="site-header">
    <div class="site-header-inner">
        <div class="site-brand">
            <?php if ($logo && file_exists($logo)): ?>
                <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" class="app-logo <?php echo $logo_class; ?>">
            <?php else: ?>
                <div class="logo-placeholder <?php echo $logo_class; ?>">LOGO</div>
            <?php endif; ?>
            <div>
                <h1 class="site-title"><?php echo htmlspecialchars($school_name); ?></h1>
                <div class="app-subtitle"><?php echo APP_NAME; ?></div>
            </div>
        </div>
        <a href="login.php" class="site-header-link">Administration</a>
    </div>
</header>

<div class="container">
    <!-- Filigrane Dynamique -->
    <?php if ($logo && file_exists($logo)): ?>
        <div class="container-watermark" style="background-image: url('<?php echo htmlspecialchars($logo); ?>');"></div>
    <?php endif; ?>

    <h2>Espace Parents</h2>
    <p class="text-center intro-text">
        Cet espace sécurisé permet aux parents et tuteurs légaux de consulter les résultats scolaires
        de leur enfant, tels que communiqués officiellement par l’établissement.
    </p>
    <form action="resultat.php" method="POST">
        <div class="form-group">
            <label for="nom"><i class="fas fa-user-graduate"></i> Nom de l'élève</label>
            <input type="text" id="nom" name="nom" required>
        </div>

        <div class="form-group">
            <label for="prenom"><i class="fas fa-user"></i> Prénom de l'élève (Facultatif)</label>
            <input type="text" id="prenom" name="prenom">
        </div>

        <div class="form-group">
            <label for="classe"><i class="fas fa-school"></i> Classe</label>
            <select id="classe" name="classe" required>
                <option value="">-- Sélectionner une classe --</option>
                <option value="1ere_tia">Première TIA</option>
                <option value="term_ti">Terminale TI</option>
            </select>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn-primary">Envoyer</button>
            <button type="reset" class="btn-secondary">Annuler</button>
        </div>
    </form>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Plateforme de consultation des résultats scolaires.
</footer>

</body>
</html>
