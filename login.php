<?php
session_start();
require_once 'db.php';

$erreur = "";

$stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('logo_path', 'school_name', 'logo_shape')");
$stmt_settings->execute();
$settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

$logo = $settings['logo_path'] ?? '';
$school_name = $settings['school_name'] ?? "Nom de l'établissement";
$logo_shape = $settings['logo_shape'] ?? 'round';
$logo_class = ($logo_shape === 'square') ? 'logo-square' : '';

// ---------------------------------------------------------
// AUTO-CONFIGURATION (Pour le prototype)
// Vérifie si la table admin existe, sinon la crée et ajoute l'admin par défaut
// ---------------------------------------------------------
try {
    // Création de la table admins si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )");

    // Vérification de l'existence de l'admin par défaut
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        // Création de l'admin par défaut : admin / admin123
        $hash = password_hash("admin123", PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES ('admin', ?)");
        $insert->execute([$hash]);
    }
} catch (PDOException $e) {
    die("Erreur de configuration de la base de données : " . $e->getMessage());
}
// ---------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    
    // Pour simplifier, on suppose qu'il n'y a qu'un seul admin 'admin'
    // Dans un vrai système, on demanderait aussi le nom d'utilisateur
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['admin_logged_in'] = true;
            header("Location: liste_eleves.php");
            exit();
        } else {
            $erreur = "Mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur de connexion : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">

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

<div class="container login-container">
    <!-- Filigrane Dynamique -->
    <?php if ($logo && file_exists($logo)): ?>
        <div class="container-watermark" style="background-image: url('<?php echo htmlspecialchars($logo); ?>');"></div>
    <?php endif; ?>

    <div class="login-icon-box">
        <i class="fas fa-user-shield"></i>
    </div>
    <h2>Espace Administration</h2>
    <p class="intro-text text-center">
        Accès sécurisé réservé au personnel administratif.
    </p>
    
    <?php if ($erreur): ?>
        <div class="error-message login-error"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="password"><i class="fas fa-key"></i> Mot de passe</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required placeholder="Entrez le mot de passe">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
        </div>
        <div class="text-center">
            <button type="submit" class="btn-login-premium">Se connecter <i class="fas fa-sign-in-alt" style="margin-left: 8px;"></i></button>
        </div>
    </form>
    
    <div class="text-center mt-1">
        <a href="index.php" class="back-link-premium"><i class="fas fa-arrow-left"></i> Retour au site</a>
    </div>
</div>

<script>
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

togglePassword.addEventListener('click', function (e) {
    // basculer le type de l'input
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    // basculer l'icône
    this.classList.toggle('fa-eye-slash');
});
</script>

<footer class="site-footer">
    © <?php echo date('Y'); ?> Suivi Scolaire+ – Plateforme de consultation des résultats scolaires.
</footer>

</body>
</html>
