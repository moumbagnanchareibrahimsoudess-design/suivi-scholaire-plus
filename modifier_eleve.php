<?php
require_once 'auth_check.php';
require_once 'db.php';

$stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('logo_path', 'school_name', 'logo_shape')");
$stmt_settings->execute();
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

$logo = $settings['logo_path'] ?? '';
$school_name = $settings['school_name'] ?? "Nom de l'établissement";
$logo_shape = $settings['logo_shape'] ?? 'round';
$logo_class = ($logo_shape === 'square') ? 'logo-square' : '';

try {
    $stmt_classes = $pdo->query("SELECT code, label FROM classes");
    $classes_map = [];
    while ($row = $stmt_classes->fetch(PDO::FETCH_ASSOC)) {
        $classes_map[$row['code']] = $row['label'];
    }
} catch (PDOException $e) {
    $classes_map = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier les notes</title>
    <link rel="stylesheet" href="style.css">
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
    </div>
</header>

<div class="container">
    <h2>Actualisation des résultats</h2>

    <?php

    try {
        // Traitement du formulaire de mise à jour
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE eleves SET moyenne_trimestrielle = :mt, moyenne_sequentielle = :ms WHERE id = :id");
            $stmt->execute([
                ':mt' => $_POST['moyenne_trimestrielle'],
                ':ms' => $_POST['moyenne_sequentielle'],
                ':id' => $_POST['id']
            ]);
            
            // Redirection vers la liste après succès
            header("Location: liste_eleves.php");
            exit();
        }

        // Récupération des données de l'élève
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM eleves WHERE id = :id");
            $stmt->execute([':id' => $_GET['id']]);
            $eleve = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$eleve) {
                echo '<div class="message error">Élève introuvable.</div>';
                exit;
            }
        } else {
            echo '<div class="message error">ID non spécifié.</div>';
            exit;
        }

    } catch(PDOException $e) {
        echo '<div class="message error">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit;
    }
    ?>

    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $eleve['id']; ?>">

        <div class="form-group">
            <label>Nom et prénom (non modifiables)</label>
            <input type="text" value="<?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?>" readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label>Classe (non modifiable)</label>
            <input type="text" value="<?php echo htmlspecialchars($classes_map[$eleve['classe']] ?? $eleve['classe']); ?>" readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label for="moyenne_trimestrielle">Moyenne Séquence 1</label>
            <input type="number" id="moyenne_trimestrielle" name="moyenne_trimestrielle" step="0.01" min="0" max="20" value="<?php echo htmlspecialchars($eleve['moyenne_trimestrielle']); ?>" required>
        </div>

        <div class="form-group">
            <label for="moyenne_sequentielle">Moyenne Séquence 2</label>
            <input type="number" id="moyenne_sequentielle" name="moyenne_sequentielle" step="0.01" min="0" max="20" value="<?php echo htmlspecialchars($eleve['moyenne_sequentielle']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="liste_eleves.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Interface d’administration des résultats scolaires.
</footer>

</body>
</html>
