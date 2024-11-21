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

// Vérifiez si l'ID de l'athlète est passé en paramètre
if (!isset($_GET['id_athlete']) || !is_numeric($_GET['id_athlete'])) {
    $_SESSION['error'] = "L'athlète à modifier n'est pas valide.";
    header("Location: manage-athletes.php");
    exit();
}

// Récupérer l'athlète depuis la base de données
$id_athlete = $_GET['id_athlete'];
try {
    $queryAthlete = "SELECT * FROM ATHLETE WHERE id_athlete = :id_athlete";
    $statementAthlete = $connexion->prepare($queryAthlete);
    $statementAthlete->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
    $statementAthlete->execute();
    $athlete = $statementAthlete->fetch(PDO::FETCH_ASSOC);
    if (!$athlete) {
        $_SESSION['error'] = "L'athlète demandé n'existe pas.";
        header("Location: manage-athletes.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-athletes.php");
    exit();
}

// Vérifiez si le formulaire est soumis pour la mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_athlete = filter_input(INPUT_POST, 'nom_athlete', FILTER_SANITIZE_STRING);
    $prenom_athlete = filter_input(INPUT_POST, 'prenom_athlete', FILTER_SANITIZE_STRING);
    $id_pays = filter_input(INPUT_POST, 'id_pays', FILTER_SANITIZE_NUMBER_INT);
    $id_genre = filter_input(INPUT_POST, 'id_genre', FILTER_SANITIZE_NUMBER_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: modify-athlete.php?id_athlete=" . $id_athlete);
        exit();
    }

    // Vérifiez si tous les champs sont bien renseignés
    if (empty($nom_athlete) || empty($prenom_athlete) || empty($id_pays) || empty($id_genre)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-athlete.php?id_athlete=" . $id_athlete);
        exit();
    }

    try {
        // Requête pour mettre à jour l'athlète
        $query = "UPDATE ATHLETE SET nom_athlete = :nom_athlete, prenom_athlete = :prenom_athlete, 
                  id_pays = :id_pays, id_genre = :id_genre WHERE id_athlete = :id_athlete";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(":nom_athlete", $nom_athlete, PDO::PARAM_STR);
        $statement->bindParam(":prenom_athlete", $prenom_athlete, PDO::PARAM_STR);
        $statement->bindParam(":id_pays", $id_pays, PDO::PARAM_INT);
        $statement->bindParam(":id_genre", $id_genre, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'athlète a été mis à jour avec succès.";
            header("Location: manage-athletes.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour de l'athlète.";
            header("Location: modify-athlete.php?id_athlete=" . $id_athlete);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: modify-athlete.php?id_athlete=" . $id_athlete);
        exit();
    }
}

// Récupération des pays et genres pour le formulaire
try {
    $queryPays = "SELECT id_pays, nom_pays FROM PAYS ORDER BY nom_pays";
    $statementPays = $connexion->prepare($queryPays);
    $statementPays->execute();
    $pays = $statementPays->fetchAll(PDO::FETCH_ASSOC);

    $queryGenres = "SELECT id_genre, nom_genre FROM GENRE ORDER BY nom_genre";
    $statementGenres = $connexion->prepare($queryGenres);
    $statementGenres->execute();
    $genres = $statementGenres->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Modifier un Athlète - Jeux Olympiques - Los Angeles 2028</title>
</head>
<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-EPREUVEs.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier l'Athlète</h1>
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
        <form action="modify-athlete.php?id_athlete=<?php echo htmlspecialchars($id_athlete); ?>" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet athlète ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="nom_athlete">Nom de l'Athlète :</label>
            <input type="text" name="nom_athlete" id="nom_athlete" value="<?php echo htmlspecialchars($athlete['nom_athlete']); ?>" required>

            <label for="prenom_athlete">Prénom de l'Athlète :</label>
            <input type="text" name="prenom_athlete" id="prenom_athlete" value="<?php echo htmlspecialchars($athlete['prenom_athlete']); ?>" required>

            <label for="id_pays">Pays de l'Athlète :</label>
            <select name="id_pays" id="id_pays" required>
                <option value="">Sélectionnez un pays</option>
                <?php foreach ($pays as $pay): ?>
                    <option value="<?php echo htmlspecialchars($pay['id_pays']); ?>"
                        <?php echo $athlete['id_pays'] == $pay['id_pays'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pay['nom_pays']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_genre">Genre de l'Athlète :</label>
            <select name="id_genre" id="id_genre" required>
                <option value="">Sélectionnez un genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo htmlspecialchars($genre['id_genre']); ?>"
                        <?php echo $athlete['id_genre'] == $genre['id_genre'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre['nom_genre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Mettre à jour l'Athlète</button>
        </form>
    </main>
</body>
</html>
