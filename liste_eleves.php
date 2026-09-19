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

$classe_filter = isset($_GET['classe']) ? $_GET['classe'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_class_label'])) {
    $new_class_label = trim($_POST['new_class_label']);
    if ($new_class_label !== '') {
        $code = strtolower($new_class_label);
        $code = preg_replace('/\s+/', '_', $code);
        $stmt = $pdo->prepare("INSERT IGNORE INTO classes (code, label) VALUES (:code, :label)");
        $stmt->execute([
            ':code' => $code,
            ':label' => $new_class_label
        ]);
    }
    header("Location: liste_eleves.php");
    exit;
}

try {
    $stmt_classes = $pdo->query("SELECT classe, COUNT(*) AS total FROM eleves GROUP BY classe");
    $class_counts = [];
    while ($row_class = $stmt_classes->fetch(PDO::FETCH_ASSOC)) {
        $class_counts[$row_class['classe']] = (int) $row_class['total'];
    }
} catch (PDOException $e) {
    $class_counts = [];
}

try {
    $stmt_classes_def = $pdo->query("SELECT code, label FROM classes ORDER BY label");
    $classes_def = $stmt_classes_def->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $classes_def = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?php echo htmlspecialchars($school_name); ?></title>
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

<div class="container container-large">
    <div class="admin-header">
        <h2>Tableau de Bord Administration</h2>
        <div class="header-actions">
            <a href="parametres.php" class="admin-nav-btn"><i class="fas fa-cog"></i> Paramètres</a>
            <a href="logout.php" class="admin-nav-btn" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </div>

    <div class="class-summary">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-layer-group" style="margin-right: 8px; color: var(--primary-light);"></i> Vue par classe</h3>
        <div class="class-card-grid">
            <?php
            foreach ($classes_def as $code => $label) {
                $count = $class_counts[$code] ?? 0;
                $active_class = ($classe_filter === $code) ? ' class-card-active' : '';
                // Simulation d'un pourcentage de saisie (pour le design)
                $progress = ($count > 0) ? min(100, $count * 5) : 0; 
                ?>
                <a href="?classe=<?php echo urlencode($code); ?>" class="class-card<?php echo $active_class; ?>">
                    <div class="class-card-header">
                        <span class="class-card-label"><?php echo htmlspecialchars($label); ?></span>
                        <span class="class-card-count"><?php echo $count; ?></span>
                    </div>
                    <div class="class-card-progress-box">
                        <div class="class-card-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $count; ?> élève<?php echo $count > 1 ? 's' : ''; ?> enregistré<?php echo $count > 1 ? 's' : ''; ?></span>
                </a>
                <?php
            }
            ?>
        </div>
        
        <form method="POST" action="" class="add-class-premium">
            <i class="fas fa-plus-circle" style="font-size: 1.5rem; color: var(--primary-light);"></i>
            <input type="text" name="new_class_label" class="input-tech" placeholder="Nouvelle classe (ex: 3ème A)" required style="flex-grow: 1;">
            <button type="submit" class="btn-add-premium">Ajouter la classe</button>
        </form>
    </div>
    
    <?php if ($classe_filter === ''): ?>
        <div class="no-result-center">
            <p class="intro-text">Choisissez une classe ci-dessus pour afficher la liste des élèves et gérer leurs résultats.</p>
        </div>
    <?php else: ?>
        <form method="GET" action="" class="filter-form">
            <label for="filtre_classe" class="m-0">Classe sélectionnée :</label>
            <select name="classe" id="filtre_classe">
                <?php foreach ($classes_def as $code => $label): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>" <?php if($classe_filter === $code) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-edit">Changer</button>
        </form>

        <div class="text-center mb-2">
            <?php
            $ajout_url = 'ajout_eleve.php';
            if ($classe_filter !== '') {
                $ajout_url .= '?classe=' . urlencode($classe_filter);
            }
            ?>
            <a href="<?php echo $ajout_url; ?>" class="btn btn-add">+ Ajouter un élève</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom & Prénom</th>
                    <th>Classe</th>
                    <th>Moy. Seq. 1</th>
                    <th>Moy. Seq. 2</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once 'db.php';

                try {
                    $sql = "SELECT * FROM eleves WHERE classe = :classe ORDER BY nom";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':classe' => $classe_filter]);
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $classe_label = $classes_def[$row['classe']] ?? $row['classe'];
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nom'] . ' ' . $row['prenom']) . "</td>";
                        echo "<td><span class='badge'>" . htmlspecialchars($classe_label) . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['moyenne_trimestrielle']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['moyenne_sequentielle']) . "</td>";
                        echo "<td>";
                        echo "<a href='modifier_eleve.php?id=" . $row['id'] . "' class='btn-edit'>Modifier</a> ";
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    if ($stmt->rowCount() == 0) {
                        echo "<tr><td colspan='6' class='text-center'>Aucun élève enregistré pour cette classe.</td></tr>";
                    }

                } catch(PDOException $e) {
                    echo "<tr><td colspan='6'>Erreur : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="text-center mt-2">
        <a href="index.php" class="btn btn-secondary btn-logout">Retour à l'accueil Parents</a>
    </div>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Plateforme de gestion des résultats scolaires.
</footer>

</body>
</html>
