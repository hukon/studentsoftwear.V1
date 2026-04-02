<?php
require_once __DIR__ . '/config.php';
requireLogin();
$username = htmlspecialchars($_SESSION['username'] ?? 'Professeur');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système d'Évaluation - Menu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding-top: 20px;
        }
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 16px;
            border-radius: 6px;
            background: rgba(255,255,255,0.2);
            transition: background 0.3s;
        }
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .menu-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .menu-card h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .menu-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }
        .menu-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-card.primary h3,
        .menu-card.primary p {
            color: white;
        }
        .info-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .info-box h2 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        .info-box li {
            padding: 8px 0;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-box li:last-child {
            border-bottom: none;
        }
        .info-box li::before {
            content: "✓ ";
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            .header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Retour au tableau de bord</a>
        
        <div class="header">
            <h1>📊 Système d'Évaluation</h1>
            <p>Évaluez et suivez les compétences linguistiques de vos élèves</p>
        </div>

        <div class="info-box">
            <h2>Fonctionnalités du système</h2>
            <ul>
                <li>Évaluation basée sur les critères du Ministère de l'Éducation Nationale</li>
                <li>Grille d'évaluation A-B-C-D pour 4 compétences principales</li>
                <li>Tableau de bord analytique avec statistiques et graphiques</li>
                <li>Export PDF des grilles d'évaluation individuelles</li>
                <li>Suivi de progression par élève et par classe</li>
            </ul>
        </div>

        <div class="menu-grid">
            <a href="evaluation_dashboard.php" class="menu-card primary">
                <div class="icon">📈</div>
                <h3>Tableau de Bord</h3>
                <p>Visualisez les statistiques, la distribution des niveaux et les performances par classe</p>
            </a>

            <a href="evaluate_students.php" class="menu-card">
                <div class="icon">✏️</div>
                <h3>Évaluer les Élèves</h3>
                <p>Saisissez les évaluations pour chaque élève selon les 4 compétences linguistiques</p>
            </a>

            <a href="export_pdf_with_evaluation.php" class="menu-card">
                <div class="icon">📄</div>
                <h3>Export PDF</h3>
                <p>Générez des rapports PDF détaillés avec toutes les grilles d'évaluation</p>
            </a>
        </div>

        <div class="info-box" style="margin-top: 30px;">
            <h2>Les 4 compétences évaluées</h2>
            <ul>
                <li><strong>Compréhension et Communication Orales</strong> - Thème, unités de sens, expression</li>
                <li><strong>Lecture</strong> - Correspondance graphie/phonie, fluidité, intonation</li>
                <li><strong>Compréhension de l'Écrit</strong> - Thème général, champ lexical, informations</li>
                <li><strong>Production Écrite</strong> - Pertinence, cohérence, correction, lisibilité</li>
            </ul>
        </div>
    </div>
</body>
</html>