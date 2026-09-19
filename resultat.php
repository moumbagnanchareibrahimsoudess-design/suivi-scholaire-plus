<?php
require_once 'db.php';

$stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('logo_path', 'school_name', 'logo_shape', 'academic_year', 'term_label')");
$stmt_settings->execute();
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    
$logo = $settings['logo_path'] ?? '';
$school_name = $settings['school_name'] ?? 'Établissement';
$logo_shape = $settings['logo_shape'] ?? 'round';
$logo_class = ($logo_shape === 'square') ? 'logo-square' : '';
$academic_year = $settings['academic_year'] ?? '2024-2025';
$term_label = $settings['term_label'] ?? '1er Trimestre';

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
    <title>Résultat - Consultation des Notes</title>
    <link rel="stylesheet" href="style.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bibliothèque pour générer le PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="page-result">

<header class="site-header">
    <div class="site-header-inner">
        <div class="site-brand">
            <?php if ($logo && file_exists($logo)): ?>
                <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo Structure" class="app-logo <?php echo $logo_class; ?>">
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

    <?php
    try {
        // Vérification si le formulaire a été soumis
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nom = preg_replace('/\s+/', ' ', trim($_POST['nom']));
            $prenom = preg_replace('/\s+/', ' ', trim($_POST['prenom'] ?? ''));

            if (function_exists('mb_strtoupper')) {
                $nom = mb_strtoupper($nom, 'UTF-8');
            } else {
                $nom = strtoupper($nom);
            }

            if ($prenom !== '') {
                if (function_exists('mb_convert_case')) {
                    $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8');
                } else {
                    $prenom = ucwords(strtolower($prenom));
                }
            }
            $classe = $_POST['classe'];

            if ($prenom === '') {
                echo '<div class="no-result-center">';
                echo '  <div class="security-alert">';
                echo '    <div class="security-icon"><i class="fas fa-shield-halved"></i></div>';
                echo '    <div class="security-text">';
                echo '      <strong>Accès Interrompu</strong><br>';
                echo '      Pour des raisons de confidentialité, veuillez indiquer aussi le prénom de l\'élève pour afficher les résultats.';
                echo '    </div>';
                echo '    <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour au formulaire</a>';
                echo '  </div>';
                echo '</div>';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM eleves WHERE nom = :nom AND prenom = :prenom AND classe = :classe");
                $stmt->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':classe' => $classe
                ]);

                $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($eleves) > 0) {
                    if (count($eleves) > 1) {
                         echo '<div class="no-result-center">';
                         echo '  <div class="human-error-box">';
                         echo '    <div class="human-error-icon"><i class="fas fa-users-viewfinder"></i></div>';
                         echo '    <div class="human-error-text">';
                         echo '      <h3>Plusieurs élèves trouvés</h3>';
                         echo '      <p>Plusieurs élèves portent ces nom et prénom dans cette classe.</p>';
                         echo '      <div class="human-error-suggestion">';
                         echo '        <i class="fas fa-lightbulb"></i> Veuillez contacter l\'établissement pour obtenir un identifiant unique.';
                         echo '      </div>';
                         echo '    </div>';
                         echo '    <a href="index.php" class="btn btn-indigo"><i class="fas fa-arrow-left"></i> Retour au formulaire</a>';
                         echo '  </div>';
                         echo '</div>';
                    } else {
                        $eleve = $eleves[0];
                    
                    $classe_label = $classes_map[$eleve['classe']] ?? $eleve['classe'];
                    
                    $moyenne_trimestrielle_calc = ($eleve['moyenne_trimestrielle'] + $eleve['moyenne_sequentielle']) / 2;
                    
                    $appreciation = "";
                    $appreciation_icon = "";
                    $classe_css = "";
                    $score_color = "";
                    
                    if ($moyenne_trimestrielle_calc >= 16) {
                        $appreciation = "Excellent niveau. Les résultats sont conformes aux attentes les plus élevées.";
                        $appreciation_icon = "fa-medal";
                        $classe_css = "success-message";
                        $score_color = "#D4AF37"; // Gold pour excellence
                    } elseif ($moyenne_trimestrielle_calc >= 14) {
                        $appreciation = "Très bons résultats. Le travail fourni est régulier et sérieux.";
                        $appreciation_icon = "fa-star";
                        $classe_css = "success-message";
                        $score_color = "#2E7D32"; // Emerald
                    } elseif ($moyenne_trimestrielle_calc >= 12) {
                        $appreciation = "Résultats satisfaisants. Les efforts doivent être maintenus.";
                        $appreciation_icon = "fa-thumbs-up";
                        $classe_css = "info-message";
                        $score_color = "#1A237E"; // Indigo
                    } elseif ($moyenne_trimestrielle_calc >= 10) {
                        $appreciation = "Résultats justes. Des efforts supplémentaires sont recommandés.";
                        $appreciation_icon = "fa-circle-info";
                        $classe_css = "warning-message";
                        $score_color = "#FB8C00"; // Orange
                    } else {
                        $appreciation = "Résultats insuffisants au regard des exigences de la classe.";
                        $appreciation_icon = "fa-triangle-exclamation";
                        $classe_css = "error-message";
                        $score_color = "#E53935"; // Red
                    }

                    // Calcul de l'encouragement dynamique (IA Style)
                    $ai_text = "";
                    if ($moyenne_trimestrielle_calc >= 16) {
                        $ai_text = htmlspecialchars($eleve['prenom']) . " fait partie du top de sa classe ce trimestre ! 🚀";
                    } elseif ($moyenne_trimestrielle_calc >= 14) {
                        $ai_text = "Continue ainsi " . htmlspecialchars($eleve['prenom']) . ", tu es sur la voie de l'excellence ! 📈";
                    } elseif ($moyenne_trimestrielle_calc >= 12) {
                        $ai_text = "Un bon trimestre pour " . htmlspecialchars($eleve['prenom']) . ". Avec un peu plus d'effort, le sommet est proche ! 💪";
                    } else {
                        $ai_text = "Courage " . htmlspecialchars($eleve['prenom']) . ", chaque effort compte pour progresser au prochain trimestre. 🎯";
                    }
                    ?>
                        <div class="bulletin" id="bulletin-to-print">
                            <!-- Filigrane Dynamique -->
                            <?php if ($logo && file_exists($logo)): ?>
                                <div class="bulletin-watermark" style="background-image: url('<?php echo htmlspecialchars($logo); ?>');"></div>
                            <?php endif; ?>

                            <div class="bulletin-header">
                                <div class="bulletin-logo">
                                    <?php if ($logo && file_exists($logo)): ?>
                                        <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo">
                                    <?php else: ?>
                                        <div class="bulletin-logo-placeholder">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="bulletin-header-text">
                                    <div class="bulletin-school"><?php echo htmlspecialchars($school_name); ?></div>
                                    <div class="bulletin-title">Bulletin de résultats</div>
                                    <div class="bulletin-meta">
                                        <span><?php echo htmlspecialchars($academic_year); ?></span>
                                        <?php if ($term_label !== ''): ?>
                                            <span> - <?php echo htmlspecialchars($term_label); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <div class="result-card">
                            <div class="student-info text-center">
                                <p class="greeting">Bonjour, voici les résultats de</p>
                                <h2><?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></h2>
                                <div class="student-meta">
                                    <span class="class-badge"><?php echo htmlspecialchars($classe_label); ?></span>
                                </div>
                            </div>

                            <div class="message <?php echo $classe_css; ?> result-appreciation">
                                <i class="fas <?php echo $appreciation_icon; ?>"></i>
                                <span><?php echo htmlspecialchars($appreciation); ?></span>
                            </div>

                            <div class="score-hero">
                                <svg class="score-circle" width="160" height="160">
                                    <circle class="score-circle-bg" cx="80" cy="80" r="70"></circle>
                                    <circle class="score-circle-progress" cx="80" cy="80" r="70" id="score-progress" style="stroke: <?php echo $score_color; ?>;"></circle>
                                </svg>
                                <div class="score-content">
                                    <div class="score-value"><?php echo number_format($moyenne_trimestrielle_calc, 2); ?></div>
                                    <div class="score-max">/ 20</div>
                                </div>
                            </div>

                            <div class="ai-encouragement text-center">
                                <i class="fas fa-robot" style="margin-right: 8px; color: var(--primary-light);"></i>
                                <?php echo $ai_text; ?>
                            </div>
                            
                            <div class="btn-group mt-2">
                                <button type="button" class="btn btn-download-premium" id="download-pdf">
                                    <i class="fas fa-file-arrow-down"></i> Télécharger le Bulletin (PDF)
                                </button>
                                <a href="index.php" class="btn btn-pearl">
                                    <i class="fas fa-rotate-left"></i> Retour
                                </a>
                            </div>
                        </div>
                        </div>

                        <script>
                        // Animation de la jauge circulaire
                        window.addEventListener('load', function() {
                            const score = <?php echo $moyenne_trimestrielle_calc; ?>;
                            const progress = document.getElementById('score-progress');
                            const radius = 70;
                            const circumference = 2 * Math.PI * radius;
                            
                            const offset = circumference - (score / 20) * circumference;
                            progress.style.strokeDashoffset = offset;
                        });

                        document.getElementById('download-pdf').addEventListener('click', function () {
                            const element = document.getElementById('bulletin-to-print');
                            
                            // On active le mode PDF pour remplir tout l'espace
                            element.classList.add('pdf-mode');
                            
                            const opt = {
                                margin:       0,
                                filename:     'bulletin_<?php echo str_replace(' ', '_', $eleve['nom']); ?>.pdf',
                                image:        { type: 'jpeg', quality: 1 },
                                html2canvas:  { 
                                    scale: 4, // Augmentation de la qualité
                                    useCORS: true, 
                                    letterRendering: true,
                                    scrollY: 0,
                                    scrollX: 0
                                },
                                jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
                            };

                            // On cache les boutons dans le PDF
                            const buttons = element.querySelector('.btn-group');
                            if (buttons) buttons.style.display = 'none';

                            html2pdf().set(opt).from(element).save().then(() => {
                                // On remet l'affichage normal après génération
                                element.classList.remove('pdf-mode');
                                if (buttons) buttons.style.display = 'block';
                            });
                        });
                        </script>
                        <?php
                    }
                } else {
                    echo '<div class="no-result-center">';
                    echo '  <div class="human-error-box">';
                    echo '    <div class="human-error-icon">';
                    echo '      <i class="fas fa-magnifying-glass"></i>';
                    echo '      <i class="fas fa-question"></i>';
                    echo '    </div>';
                    echo '    <div class="human-error-text">';
                    echo '      <h3>Élève non trouvé</h3>';
                    echo '      <p>Nous n\'avons pas pu trouver de résultats avec les informations fournies.</p>';
                    echo '      <div class="human-error-suggestion">';
                    echo '        <i class="fas fa-lightbulb"></i> <strong>Conseil :</strong> Vérifiez l\'orthographe du nom ou assurez-vous d\'avoir sélectionné la bonne classe (' . htmlspecialchars($classes_map[$classe] ?? $classe) . ').';
                    echo '      </div>';
                    echo '    </div>';
                    echo '    <a href="index.php" class="btn btn-indigo"><i class="fas fa-arrow-left"></i> Retour au formulaire</a>';
                    echo '  </div>';
                    echo '</div>';
                }
            }
        }
    } catch(PDOException $e) {
        echo '<div class="message error-message">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</div>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Plateforme de consultation des résultats scolaires.
</footer>

</body>
</html>
