<?php
require_once 'auth_check.php';
require_once 'db.php';

$message = '';
$error = '';

// Traitement des modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['school_name']) && isset($_POST['logo_shape'])) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'school_name'");
        $stmt->execute([trim($_POST['school_name'])]);
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_shape', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$_POST['logo_shape'], $_POST['logo_shape']]);
    }

    if (isset($_POST['academic_year']) && isset($_POST['term_label'])) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('academic_year', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([trim($_POST['academic_year']), trim($_POST['academic_year'])]);

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('term_label', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([trim($_POST['term_label']), trim($_POST['term_label'])]);

    }

    $message = "Les paramètres ont été mis à jour.";

    // Changement de logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . "logo_" . time() . "." . $file_extension;
        $uploadOk = 1;

        // Vérifier si c'est une image (les SVG peuvent échouer à getimagesize)
        if ($file_extension !== 'svg') {
            $check = getimagesize($_FILES["logo"]["tmp_name"]);
            if ($check === false) {
                $error = "Le fichier n'est pas une image.";
                $uploadOk = 0;
            }
        }

        // Vérifier la taille (max 2Mo)
        if ($_FILES["logo"]["size"] > 2000000) {
            $error = "Désolé, le fichier est trop volumineux (max 2Mo).";
            $uploadOk = 0;
        }

        // Autoriser certains formats
        if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" && $file_extension != "svg") {
            $error = "Désolé, seuls les fichiers JPG, JPEG, PNG, GIF & SVG sont autorisés.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                // Mettre à jour la base de données
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'logo_path'");
                $stmt->execute([$target_file]);
                $message = "Le logo a été mis à jour avec succès.";
            } else {
                $error = "Désolé, une erreur est survenue lors de l'envoi du fichier.";
            }
        }
    }
}

// Récupération des paramètres actuels
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_path'");
$stmt->execute();
$current_logo = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'school_name'");
$stmt->execute();
$current_school_name = $stmt->fetchColumn() ?: "Nom de l'établissement";

$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_shape'");
$stmt->execute();
$current_logo_shape = $stmt->fetchColumn() ?: 'round';
$logo_class = ($current_logo_shape === 'square') ? 'logo-square' : '';

$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('academic_year', 'term_label')");
$stmt->execute();
$extra_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$current_academic_year = $extra_settings['academic_year'] ?? '2024-2025';
$current_term_label = $extra_settings['term_label'] ?? '1er Trimestre';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Logo Structure</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <div class="site-header-inner">
        <div class="site-brand">
            <?php if ($current_logo && file_exists($current_logo)): ?>
                <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="Logo" class="app-logo <?php echo $logo_class; ?>">
            <?php else: ?>
                <div class="logo-placeholder <?php echo $logo_class; ?>">LOGO</div>
            <?php endif; ?>
            <div>
                <h1 class="site-title"><?php echo htmlspecialchars($current_school_name); ?></h1>
                <div class="app-subtitle"><?php echo APP_NAME; ?></div>
            </div>
        </div>
    </div>
</header>

<div class="container">
        <div class="admin-header">
            <h2>Paramètres du site</h2>
            <a href="liste_eleves.php" class="btn btn-secondary btn-back">Retour</a>
        </div>

        <?php if ($message): ?>
            <div class="success-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Identité de l'établissement</h3>
            <p class="intro-text">Ces informations seront visibles par les parents sur toutes les pages publiques.</p>

            <form method="POST">
                <div class="form-group">
                    <label for="school_name">Nom de l'établissement</label>
                    <input type="text" id="school_name" name="school_name" value="<?php echo htmlspecialchars($current_school_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="logo_shape">Forme du logo</label>
                    <select id="logo_shape" name="logo_shape">
                        <option value="round" <?php echo $current_logo_shape === 'round' ? 'selected' : ''; ?>>Circulaire (Moderne)</option>
                        <option value="square" <?php echo $current_logo_shape === 'square' ? 'selected' : ''; ?>>Carré (Classique)</option>
                    </select>
                </div>
        </div>

        <div class="card">
            <h3>Informations du bulletin</h3>
            <p class="intro-text">Ces informations apparaîtront dans l’entête du bulletin imprimé.</p>

                <div class="form-group">
                    <label for="academic_year">Année scolaire</label>
                    <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($current_academic_year); ?>" required>
                </div>

                <div class="form-group">
                    <label for="term_label">Période / Trimestre</label>
                    <input type="text" id="term_label" name="term_label" value="<?php echo htmlspecialchars($current_term_label); ?>" required>
                </div>

               
                <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
            </form>
        </div>

        <div class="card">
            <h3>Logo de la structure</h3>
            <p class="intro-text">Ce logo sera affiché sur la page d'accueil des parents et sur les bulletins de notes.</p>
            
            <div class="current-logo-display">
                <p>Logo actuel :</p>
                <?php if ($current_logo && file_exists($current_logo)): ?>
                    <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="Logo actuel" class="<?php echo $logo_class; ?>">
                <?php else: ?>
                    <div class="logo-placeholder <?php echo $logo_class; ?>">AUCUN LOGO</div>
                <?php endif; ?>
            </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="logo">Choisir un nouveau logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" required>
                <small class="help-text">Formats acceptés : PNG, JPG, JPEG, GIF, SVG. Max 2Mo.</small>
            </div>
            <button type="submit" class="btn btn-primary">Mettre à jour le logo</button>
        </form>
    </div>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Interface de paramétrage de l’établissement.
</footer>

</body>
</html>
