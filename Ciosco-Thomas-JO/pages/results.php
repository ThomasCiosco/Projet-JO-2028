<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/normalize.css">
    <link rel="stylesheet" href="../css/styles-computer.css">
    <link rel="stylesheet" href="../css/styles-responsive.css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <title>Résultats des Épreuves - Jeux Olympiques - Los Angeles 2028</title>
</head>
<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../pages/sports.php">Sports</a></li>
                <li><a href="events.php">Calendrier des épreuves</a></li>
                <li><a href="results.php">Résultats</a></li>
                <li><a href="login.php">Accès administrateur</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Résultats des Épreuves</h1>

        <?php
        require_once("../database/database.php");

        try {
            // Requête pour récupérer les résultats des épreuves, les athlètes, et les informations des épreuves
            $query = "SELECT 
                        E.nom_epreuve, E.date_epreuve, E.heure_epreuve,
                        A.nom_athlete, A.prenom_athlete,
                        P.resultat
                      FROM 
                        PARTICIPER P
                      INNER JOIN ATHLETE A ON P.id_athlete = A.id_athlete
                      INNER JOIN EPREUVE E ON P.id_epreuve = E.id_epreuve
                      ORDER BY E.date_epreuve, E.heure_epreuve";

            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table>";
                echo "<tr>
                        <th class='color'>Épreuve</th>
                        <th class='color'>Date</th>
                        <th class='color'>Heure</th>
                        <th class='color'>Athlète</th>
                        <th class='color'>Résultat</th>
                      </tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['date_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['heure_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['prenom_athlete'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['nom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['resultat'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun résultat trouvé pour les épreuves.</p>";
            }
        } catch (PDOException $e) {
            // Gestion d'erreur
            echo "<p style='color: red;'>Erreur : Impossible de récupérer les résultats. Veuillez réessayer plus tard.</p>";
            error_log("Erreur PDO : " . $e->getMessage());
        }

        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../index.php">Retour Accueil</a>
        </p>
    </main>
    <footer>
        <figure>
            <img src="../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
