<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $idPays = filter_input(INPUT_POST, 'idPays', FILTER_SANITIZE_NUMBER_INT);
    $idGenre = filter_input(INPUT_POST, 'idGenre', FILTER_SANITIZE_NUMBER_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-athlete.php");
        exit();
    }

    // Vérifiez si le nom ou le prénom de l'athlète est vide
    if (empty($nomAthlete) || empty($prenomAthlete)) {
        $_SESSION['error'] = "Le nom et le prénom de l'athlète ne peuvent pas être vides.";
        header("Location: add-athlete.php");
        exit();
    }

    try {
        // Requête pour ajouter un athlète
        $query = "INSERT INTO ATHLETE (nom_athlete, prenom_athlete, id_pays, id_genre) 
                  VALUES (:nomAthlete, :prenomAthlete, :idPays, :idGenre)";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statement->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
        $statement->bindParam(":idPays", $idPays, PDO::PARAM_INT);
        $statement->bindParam(":idGenre", $idGenre, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'athlète a été ajouté avec succès.";
            header("Location: manage-athletes.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'athlète.";
            header("Location: add-athlete.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-athlete.php");
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
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
    <title>Ajouter un Athlète - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Athlète</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="add-athlete.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet athlète ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="nomAthlete">Nom de l'Athlète :</label>
            <input type="text" name="nomAthlete" id="nomAthlete" required>
            
            <label for="prenomAthlete">Prénom de l'Athlète :</label>
            <input type="text" name="prenomAthlete" id="prenomAthlete" required>
            
            <label for="idPays">Pays :</label>
            <select name="idPays" id="idPays" required>
                <?php
                // Affichage des pays dans un menu déroulant
                $queryPays = "SELECT * FROM PAYS";
                $stmtPays = $connexion->prepare($queryPays);
                $stmtPays->execute();
                while ($rowPays = $stmtPays->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . $rowPays['id_pays'] . "'>" . htmlspecialchars($rowPays['nom_pays'], ENT_QUOTES, 'UTF-8') . "</option>";
                }
                ?>
            </select>
            
            <label for="idGenre">Genre :</label>
            <select name="idGenre" id="idGenre" required>
                <?php
                // Affichage des genres dans un menu déroulant
                $queryGenre = "SELECT * FROM GENRE";
                $stmtGenre = $connexion->prepare($queryGenre);
                $stmtGenre->execute();
                while ($rowGenre = $stmtGenre->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . $rowGenre['id_genre'] . "'>" . htmlspecialchars($rowGenre['nom_genre'], ENT_QUOTES, 'UTF-8') . "</option>";
                }
                ?>
            </select>
            
            <input type="submit" value="Ajouter l'Athlète">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
