<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_utilisateur'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_utilisateur = filter_input(INPUT_GET, 'id_utilisateur', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'utilisateur est un entier valide
if (!$id_utilisateur && $id_utilisateur !== 0) {
    $_SESSION['error'] = "ID de l'utilisateur invalide.";
    header("Location: manage-users.php");
    exit();
}

// Récupérez les informations de l'utilisateur pour affichage dans le formulaire
try {
    $queryUser = "SELECT * FROM UTILISATEUR WHERE id_utilisateur = :idUtilisateur";
    $statementUser = $connexion->prepare($queryUser);
    $statementUser->bindParam(":idUtilisateur", $id_utilisateur, PDO::PARAM_INT);
    $statementUser->execute();

    if ($statementUser->rowCount() > 0) {
        $user = $statementUser->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et sécuriser les entrées
    $nomUtilisateur = filter_input(INPUT_POST, 'nomUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomUtilisateur = filter_input(INPUT_POST, 'prenomUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification si le nom, prénom, et login sont vides
    if (empty($nomUtilisateur) || empty($prenomUtilisateur) || empty($login)) {
        $_SESSION['error'] = "Le nom, le prénom et le login ne peuvent pas être vides.";
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }

    try {
        $query = "UPDATE UTILISATEUR SET nom_utilisateur = :nomUtilisateur, prenom_utilisateur = :prenomUtilisateur, login = :login WHERE id_utilisateur = :idUtilisateur";

        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomUtilisateur", $nomUtilisateur, PDO::PARAM_STR);
        $statement->bindParam(":prenomUtilisateur", $prenomUtilisateur, PDO::PARAM_STR);
        $statement->bindParam(":login", $login, PDO::PARAM_STR);
        $statement->bindParam(":idUtilisateur", $id_utilisateur, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'utilisateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur.";
            header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier un Utilisateur - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-users.php">Gestion Utilisateurs</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Utilisateur</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-user.php?id_utilisateur=<?php echo $id_utilisateur; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet utilisateur?')">
            <label for="nomUtilisateur">Nom de l'utilisateur :</label>
            <input type="text" name="nomUtilisateur" id="nomUtilisateur" value="<?php echo htmlspecialchars($user['nom_utilisateur']); ?>" required>

            <label for="prenomUtilisateur">Prénom de l'utilisateur :</label>
            <input type="text" name="prenomUtilisateur" id="prenomUtilisateur" value="<?php echo htmlspecialchars($user['prenom_utilisateur']); ?>" required>

            <label for="login">Login :</label>
            <input type="text" name="login" id="login" value="<?php echo htmlspecialchars($user['login']); ?>" required>

            <input type="submit" value="Modifier l'utilisateur">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des utilisateurs</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
