<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si les ID de l'athlète et de l'épreuve sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve manquant.";
    header("Location: manage-results.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si les ID sont valides
if (!$id_athlete || !$id_epreuve) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve invalide.";
    header("Location: manage-results.php");
    exit();
}

// Récupérez le résultat actuel pour affichage dans le formulaire
try {
    $queryResult = "SELECT * FROM PARTICIPER WHERE id_athlete = :idAthlete AND id_epreuve = :idEpreuve";
    $statementResult = $connexion->prepare($queryResult);
    $statementResult->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
    $statementResult->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
    $statementResult->execute();

    if ($statementResult->rowCount() > 0) {
        $result = $statementResult->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Résultat non trouvé.";
        header("Location: manage-results.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-results.php");
    exit();
}

// Récupération des listes d'athlètes et d'épreuves
$athletes = $connexion->query("SELECT id_athlete, prenom_athlete, nom_athlete FROM ATHLETE")->fetchAll(PDO::FETCH_ASSOC);
$epreuves = $connexion->query("SELECT id_epreuve, nom_epreuve FROM EPREUVE")->fetchAll(PDO::FETCH_ASSOC);

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_athlete = filter_input(INPUT_POST, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_VALIDATE_INT);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification des entrées
    if (!$id_athlete || !$id_epreuve || empty($resultat)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    try {
        $query = "UPDATE PARTICIPER SET resultat = :resultat WHERE id_athlete = :idAthlete AND id_epreuve = :idEpreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":resultat", $resultat, PDO::PARAM_STR);
        $statement->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du résultat.";
            header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-result.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
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
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Résultat</h1>

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

        <form action="modify-result.php?id_athlete=<?php echo $id_athlete; ?>&id_epreuve=<?php echo $id_epreuve; ?>" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce résultat?')">
            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required>
                <?php foreach ($athletes as $athlete) { ?>
                    <option value="<?php echo $athlete['id_athlete']; ?>" <?php echo ($athlete['id_athlete'] == $result['id_athlete']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($athlete['prenom_athlete'] . " " . $athlete['nom_athlete']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required>
                <?php foreach ($epreuves as $epreuve) { ?>
                    <option value="<?php echo $epreuve['id_epreuve']; ?>" <?php echo ($epreuve['id_epreuve'] == $result['id_epreuve']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($epreuve['nom_epreuve']); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" value="<?php echo htmlspecialchars($result['resultat']); ?>" required>

            <input type="submit" value="Modifier le résultat">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
