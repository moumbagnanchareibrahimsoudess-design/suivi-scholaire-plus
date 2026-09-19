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

$classe_selectionnee = isset($_POST['classe']) ? $_POST['classe'] : (isset($_GET['classe']) ? $_GET['classe'] : '');

try {
    $stmt_classes = $pdo->query("SELECT code, label FROM classes ORDER BY label");
    $classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

if ($classe_selectionnee === '') {
    header("Location: liste_eleves.php");
    exit;
}

$classe_label = '';
if (!empty($classes)) {
    foreach ($classes as $c) {
        if ($c['code'] === $classe_selectionnee) {
            $classe_label = $c['label'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Ajouter un élève</title>
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
    <h2>Enregistrement d'un élève<?php
        if ($classe_label !== '') {
            echo ' – ' . htmlspecialchars($classe_label);
        }
    ?></h2>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once 'db.php';

        try {
            $nom = preg_replace('/\s+/', ' ', trim($_POST['nom']));
            $prenom = preg_replace('/\s+/', ' ', trim($_POST['prenom']));

            if (function_exists('mb_strtoupper')) {
                $nom = mb_strtoupper($nom, 'UTF-8');
            } else {
                $nom = strtoupper($nom);
            }

            if (function_exists('mb_convert_case')) {
                $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8');
            } else {
                $prenom = ucwords(strtolower($prenom));
            }

            $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, classe, moyenne_trimestrielle, moyenne_sequentielle) VALUES (:nom, :prenom, :classe, :mt, :ms)");
            
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':classe' => $_POST['classe'],
                ':mt' => $_POST['moyenne_trimestrielle'],
                ':ms' => $_POST['moyenne_sequentielle']
            ]);

            echo '<div class="message success">Élève ajouté avec succès !</div>';

        } catch(PDOException $e) {
            echo '<div class="message error">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" required>
        </div>

        <div class="form-group">
            <label>Classe</label>
            <input type="text" value="<?php echo htmlspecialchars($classe_label); ?>" readonly class="readonly-input">
            <input type="hidden" name="classe" value="<?php echo htmlspecialchars($classe_selectionnee); ?>">
        </div>

        <div class="form-group">
            <label for="moyenne_trimestrielle">Moyenne Séquence 1</label>
            <input type="number" id="moyenne_trimestrielle" name="moyenne_trimestrielle" step="0.01" min="0" max="20" required>
        </div>

        <div class="form-group">
            <label for="moyenne_sequentielle">Moyenne Séquence 2</label>
            <input type="number" id="moyenne_sequentielle" name="moyenne_sequentielle" step="0.01" min="0" max="20" required>
        </div>

        <?php
        $retour_url = 'liste_eleves.php';
        if ($classe_selectionnee !== '') {
            $retour_url .= '?classe=' . urlencode($classe_selectionnee);
        }
        ?>
        <div class="btn-group">
            <button type="submit">Valider l'enregistrement</button>
            <a href="<?php echo $retour_url; ?>" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </form>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Interface d’administration des résultats scolaires.
</footer>

</body>
</html>
