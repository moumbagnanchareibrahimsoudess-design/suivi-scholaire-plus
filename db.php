<?php
// db.php - Connexion à la base de données
// Ce fichier est inclus dans toutes les pages qui ont besoin de la base de données.
// Cela évite de répéter le code de connexion partout (Principe DRY : Don't Repeat Yourself).

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Suivi Scolaire+');
}

$host = 'localhost';
$dbname = 'ecole_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Configuration pour afficher les erreurs SQL (très utile pour le débogage)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // AUTO-UPDATE: Rend le champ prénom facultatif si ce n'est pas déjà le cas
    // Cette partie s'exécute automatiquement pour mettre à jour la base de données existante
    try {
        $pdo->exec("ALTER TABLE eleves MODIFY prenom VARCHAR(50) NULL");
    } catch (PDOException $e) {
        // Ignore si déjà fait
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT
    )");

    // Insertion des paramètres par défaut s'ils n'existent pas
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('logo_path', 'assets/logo_test.svg')");
    $stmt->execute();
    
    // Forcer la mise à jour pour le test
    $pdo->exec("UPDATE settings SET setting_value = 'assets/logo_test.svg' WHERE setting_key = 'logo_path' AND (setting_value = 'assets/logo_default.png' OR setting_value = 'assets/logo_test.png')");
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('school_name', 'Mon Établissement Scolaire')");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('logo_shape', 'round')");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('academic_year', '2024-2025')");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('term_label', '1er Trimestre')");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('school_location', 'Ville - Région')");
    $stmt->execute();

    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        label VARCHAR(100) NOT NULL
    )");

    $stmt = $pdo->prepare("INSERT IGNORE INTO classes (code, label) VALUES ('1ere_tia', 'Première TIA')");
    $stmt->execute();

    $stmt = $pdo->prepare("INSERT IGNORE INTO classes (code, label) VALUES ('term_ti', 'Terminale TI')");
    $stmt->execute();

} catch(PDOException $e) {
    // En cas d'erreur, on arrête tout et on affiche le message
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
