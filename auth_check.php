<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Si non connecté, redirection vers la page de login
    header("Location: login.php");
    exit();
}
?>