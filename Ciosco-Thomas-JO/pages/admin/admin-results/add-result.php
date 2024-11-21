<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n’est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_athlete = filter_input(INPUT_POST, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_VALIDATE_INT);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-result.php");
        exit();
    }

    if (empty($id_athlete) || empty($id_epreuve) || empty($resultat)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: add-result.php");
        exit();
    }

    try {
        $queryCheck = "SELECT * FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
        $statementCheck->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Un résultat pour cet athlète et cette épreuve existe déjà.";
            header("Location: add-result.php");
            exit();
        } else {
            $query = "INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:id_athlete, :id_epreuve, :resultat)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":id_athlete", $id_athlete, PDO::PARAM_INT);
            $statement->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);
            $statement->bindParam(":resultat", $resultat, PDO::PARAM_STR);

            if ($statement->execute()) {
                $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
                header("Location: manage-results.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du résultat.";
                header("Location: add-result.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-result.php");
        exit();
    }
}

// Récupérer la liste des athlètes
$athletes = [];
try {
    $athleteQuery = "SELECT id_athlete, prenom_athlete, nom_athlete FROM ATHLETE ORDER BY nom_athlete, prenom_athlete";
    $athleteStatement = $connexion->prepare($athleteQuery);
    $athleteStatement->execute();
    $athletes = $athleteStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Récupérer la liste des épreuves
$epreuves = [];
try {
    $epreuveQuery = "SELECT id_epreuve, nom_epreuve FROM EPREUVE ORDER BY nom_epreuve";
    $epreuveStatement = $connexion->prepare($epreuveQuery);
    $epreuveStatement->execute();
    $epreuves = $epreuveStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>Ajouter un Résultat - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Résultat</h1>
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
        <form action="add-result.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce résultat ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required>
                <option value="">Sélectionnez un athlète</option>
                <?php foreach ($athletes as $athlete): ?>
                    <option value="<?php echo htmlspecialchars($athlete['id_athlete']); ?>">
                        <?php echo htmlspecialchars($athlete['prenom_athlete'] . ' ' . $athlete['nom_athlete']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required>
                <option value="">Sélectionnez une épreuve</option>
                <?php foreach ($epreuves as $epreuve): ?>
                    <option value="<?php echo htmlspecialchars($epreuve['id_epreuve']); ?>">
                        <?php echo htmlspecialchars($epreuve['nom_epreuve']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" required>

            <input type="submit" value="Ajouter le Résultat">
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
