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
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_SANITIZE_NUMBER_INT);
    $date_EPREUVE = filter_input(INPUT_POST, 'date_EPREUVE', FILTER_SANITIZE_STRING);
    $heure_EPREUVE = filter_input(INPUT_POST, 'heure_EPREUVE', FILTER_SANITIZE_STRING);
    $id_lieu = filter_input(INPUT_POST, 'id_lieu', FILTER_SANITIZE_NUMBER_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-event.php");
        exit();
    }

    // Vérifiez si tous les champs sont bien renseignés
    if (empty($id_epreuve) || empty($date_EPREUVE) || empty($heure_EPREUVE) || empty($id_lieu)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-event.php");
        exit();
    }

    try {
        // Vérifier si l'épreuve existe déjà
        $queryExist = "SELECT * FROM EPREUVE WHERE id_epreuve = :id_epreuve";
        $statementExist = $connexion->prepare($queryExist);
        $statementExist->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);
        $statementExist->execute();
        $epreuveExist = $statementExist->fetch(PDO::FETCH_ASSOC);

        if (!$epreuveExist) {
            $_SESSION['error'] = "L'épreuve à mettre à jour n'existe pas.";
            header("Location: add-event.php");
            exit();
        }

        // Récupérer le sport associé à l'épreuve
        $id_sport = $epreuveExist['id_sport']; // On utilise l'id_sport de l'épreuve existante

        // Requête pour mettre à jour l'événement (sans utiliser datetime)
        $query = "UPDATE EPREUVE SET date_EPREUVE = :date_EPREUVE, heure_EPREUVE = :heure_EPREUVE, id_lieu = :id_lieu, id_sport = :id_sport 
                  WHERE id_epreuve = :id_epreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":id_epreuve", $id_epreuve, PDO::PARAM_INT);
        $statement->bindParam(":date_EPREUVE", $date_EPREUVE, PDO::PARAM_STR);
        $statement->bindParam(":heure_EPREUVE", $heure_EPREUVE, PDO::PARAM_STR);
        $statement->bindParam(":id_lieu", $id_lieu, PDO::PARAM_INT);
        $statement->bindParam(":id_sport", $id_sport, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'événement a été mis à jour avec succès.";
            header("Location: manage-events.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour de l'événement.";
            header("Location: add-event.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-event.php");
        exit();
    }
}

// Récupération des épreuves depuis la base de données pour les options du formulaire
try {
    $queryEpreuves = "SELECT id_epreuve, nom_epreuve FROM EPREUVE ORDER BY nom_epreuve";
    $statementEpreuves = $connexion->prepare($queryEpreuves);
    $statementEpreuves->execute();
    $epreuves = $statementEpreuves->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Récupération des lieux pour le formulaire
try {
    $queryLieux = "SELECT id_lieu, nom_lieu FROM LIEU ORDER BY nom_lieu";
    $statementLieux = $connexion->prepare($queryLieux);
    $statementLieux->execute();
    $lieux = $statementLieux->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Ajouter un Événement - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter un Événement</h1>
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
        <form action="add-event.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ou mettre à jour cet événement ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required>
                <option value="">Sélectionnez une épreuve</option>
                <?php foreach ($epreuves as $epreuve): ?>
                    <option value="<?php echo htmlspecialchars($epreuve['id_epreuve']); ?>">
                        <?php echo htmlspecialchars($epreuve['nom_epreuve']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_EPREUVE">Date de l'Événement :</label>
            <input type="date" name="date_EPREUVE" id="date_EPREUVE" required>

            <label for="heure_EPREUVE">Heure de l'Événement :</label>
            <input type="time" name="heure_EPREUVE" id="heure_EPREUVE" required>

            <label for="id_lieu">Lieu de l'Événement :</label>
            <select name="id_lieu" id="id_lieu" required>
                <option value="">Sélectionnez un lieu</option>
                <?php foreach ($lieux as $lieu): ?>
                    <option value="<?php echo htmlspecialchars($lieu['id_lieu']); ?>">
                        <?php echo htmlspecialchars($lieu['nom_lieu']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Ajouter l'événement</button>
        </form>
    </main>
</body>
</html>
