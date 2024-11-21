<!DOCTYPE html>
<html lang="fr">

<head>
    <!-- Définit l'encodage des caractères en UTF-8 pour une compatibilité étendue -->
    <meta charset="UTF-8">
    <!-- Ajuste l'affichage pour les appareils mobiles avec une largeur égale à celle de l'écran -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Liens vers les feuilles de style pour normaliser et styliser la page -->
    <link rel="stylesheet" href="../css/normalize.css">
    <link rel="stylesheet" href="../css/styles-computer.css">
    <link rel="stylesheet" href="../css/styles-responsive.css">
    <!-- Favicon pour l'onglet de la page -->
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <!-- Titre de la page -->
    <title>Calendrier des Épreuves - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <!-- En-tête de la page contenant la navigation -->
    <header>
        <nav>
            <ul class="menu">
                <!-- Menu avec les liens vers les différentes sections du site -->
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../pages/sports.php">Sports</a></li>
                <li><a href="events.php">Calendrier des épreuves</a></li>
                <li><a href="results.php">Résultats</a></li>
                <li><a href="login.php">Accès administrateur</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Calendrier des Épreuves</h1>

        <?php
        // Inclusion du fichier de connexion à la base de données
        require_once("../database/database.php");

        try {
            // Préparation de la requête pour récupérer les épreuves en les triant par date et heure
            $query = "SELECT nom_epreuve, date_epreuve, heure_epreuve FROM EPREUVE ORDER BY date_epreuve, heure_epreuve";
            $statement = $connexion->prepare($query);
            // Exécution de la requête
            $statement->execute();

            // Vérifie si des résultats sont disponibles
            if ($statement->rowCount() > 0) {
                // Début du tableau pour afficher les résultats
                echo "<table>";
                echo "<tr>
                        <th class='color'>Épreuve</th>
                        <th class='color'>Date</th>
                        <th class='color'>Heure</th>
                      </tr>";

                // Boucle pour parcourir et afficher chaque ligne de résultat
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    
                    // Affiche le nom de l'épreuve
                    echo "<td>" . htmlspecialchars($row['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    // Affiche la date de l'épreuve en format français (jour/mois/année)
                    echo "<td>" . date("d/m/Y", strtotime($row['date_epreuve'])) . "</td>";
                    // Affiche l'heure de l'épreuve en format 24 heures
                    echo "<td>" . date("H:i", strtotime($row['heure_epreuve'])) . "</td>";
                    echo "</tr>";
                }

                // Fin du tableau HTML
                echo "</table>";
            } else {
                // Message si aucune épreuve n'est trouvée dans la base de données
                echo "<p>Aucune épreuve trouvée.</p>";
            }
        } catch (PDOException $e) {
            // Message d'erreur en cas de problème de connexion ou de requête
            echo "<p style='color: red;'>Erreur : Impossible de récupérer le calendrier des épreuves. Veuillez réessayer plus tard.</p>";
            // Enregistre l'erreur dans un fichier log pour le débogage
            error_log("Erreur PDO : " . $e->getMessage());
        }

        // Active l'affichage des erreurs en mode développement
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        ?>

        <!-- Lien de retour vers la page d'accueil -->
        <p class="paragraph-link">
            <a class="link-home" href="../index.php">Retour Accueil</a>
        </p>
    </main>

    <footer>
        <figure>
            <!-- Affiche le logo des Jeux Olympiques dans le pied de page -->
            <img src="../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
