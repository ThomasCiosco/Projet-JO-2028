<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du lieu est fourni dans l'URL
if (!isset($_GET['id_place'])) {
    $_SESSION['error'] = "ID du lieu manquant.";
    header("Location: manage-places.php");
    exit();
}

$id_place = filter_input(INPUT_GET, 'id_place', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du lieu est un entier valide
if (!$id_place && $id_place !== 0) {
    $_SESSION['error'] = "ID du lieu invalide.";
    header("Location: manage-places.php");
    exit();
}

// Récupérez les informations du lieu pour affichage dans le formulaire
try {
    $queryPlace = "SELECT * FROM LIEU WHERE id_place = :idPlace";
    $statementPlace = $connexion->prepare($queryPlace);
    $statementPlace->bindParam(":idPlace", $id_place, PDO::PARAM_INT);
    $statementPlace->execute();

    if ($statementPlace->rowCount() > 0) {
        $place = $statementPlace->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Lieu non trouvé.";
        header("Location: manage-places.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-places.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et sécuriser les entrées
    $nomLieu = filter_input(INPUT_POST, 'nomLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $adresseLieu = filter_input(INPUT_POST, 'adresseLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $cpLieu = filter_input(INPUT_POST, 'cpLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $villeLieu = filter_input(INPUT_POST, 'villeLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $capaciteLieu = filter_input(INPUT_POST, 'capaciteLieu', FILTER_SANITIZE_NUMBER_INT);

    // Vérification si les champs sont vides
    if (empty($nomLieu) || empty($adresseLieu) || empty($cpLieu) || empty($villeLieu) || empty($capaciteLieu)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-place.php?id_place=$id_place");
        exit();
    }

    try {
        $query = "UPDATE LIEU SET 
                    nom_lieu = :nomLieu, 
                    adresse_lieu = :adresseLieu, 
                    cp_lieu = :cpLieu, 
                    ville_lieu = :villeLieu, 
                    capacite_lieu = :capaciteLieu 
                  WHERE id_place = :idPlace";

        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomLieu", $nomLieu, PDO::PARAM_STR);
        $statement->bindParam(":adresseLieu", $adresseLieu, PDO::PARAM_STR);
        $statement->bindParam(":cpLieu", $cpLieu, PDO::PARAM_STR);
        $statement->bindParam(":villeLieu", $villeLieu, PDO::PARAM_STR);
        $statement->bindParam(":capaciteLieu", $capaciteLieu, PDO::PARAM_INT);
        $statement->bindParam(":idPlace", $id_place, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le lieu a été modifié avec succès.";
            header("Location: manage-places.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du lieu.";
            header("Location: modify-place.php?id_place=$id_place");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-place.php?id_place=$id_place");
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
    <title>Modifier un Lieu - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-places.php">Gestion des Lieux</a></li>
                <li><a href="manage-users.php">Gestion Utilisateurs</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Lieu</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p class="success-message">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-place.php?id_place=<?php echo $id_place; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce lieu?')">
            <label for="nomLieu">Nom du lieu :</label>
            <input type="text" name="nomLieu" id="nomLieu" value="<?php echo htmlspecialchars($place['nom_lieu']); ?>" required>

            <label for="adresseLieu">Adresse du lieu :</label>
            <input type="text" name="adresseLieu" id="adresseLieu" value="<?php echo htmlspecialchars($place['adresse_lieu']); ?>" required>

            <label for="cpLieu">Code Postal :</label>
            <input type="text" name="cpLieu" id="cpLieu" value="<?php echo htmlspecialchars($place['cp_lieu']); ?>" required>

            <label for="villeLieu">Ville :</label>
            <input type="text" name="villeLieu" id="villeLieu" value="<?php echo htmlspecialchars($place['ville_lieu']); ?>" required>

            <label for="capaciteLieu">Capacité du lieu :</label>
            <input type="number" name="capaciteLieu" id="capaciteLieu" value="<?php echo htmlspecialchars($place['capacite_lieu']); ?>" required>

            <input type="submit" value="Modifier le lieu">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
