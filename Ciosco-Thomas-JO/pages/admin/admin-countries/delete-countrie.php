<?php
session_start();
require_once("../../../database/database.php");

// Protection CSRF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header('Location: ../../../index.php');
        exit();
    }
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Génération du token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si l'ID de la contrie est fourni dans l'URL
if (!isset($_GET['id_contrie'])) {
    $_SESSION['error'] = "ID de la contrie manquant.";
    header("Location: manage-countries.php");
    exit();
} else {
    $id_contrie = filter_input(INPUT_GET, 'id_contrie', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de la contrie est un entier valide
    if ($id_contrie === false) {
        $_SESSION['error'] = "ID de la contrie invalide.";
        header("Location: manage-countries.php");
        exit();
    } else {
        try {
            // Préparez la requête SQL pour supprimer la contrie
            $sql = "DELETE FROM CONTRIE WHERE id_contrie = :id_contrie";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_contrie', $id_contrie, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "La contrie a été supprimée avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-countries.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de la contrie : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-countries.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
