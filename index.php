<?php
require_once 'config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des Mensurations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <h1>Mensurations</h1>
        <div class="header-actions">
            <a href="stats.php" class="stats-link-header">📊 Statistiques</a>
            <span class="username">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" data-tab="dashboard">Vue d'ensemble</button>
        <button class="nav-tab" data-tab="measurements">Circonférences</button>
        <button class="nav-tab" data-tab="body-comp">Composition</button>
    </div>

    <div class="container">
        <!-- Main View -->
        <div class="main-view">
            <!-- Dashboard Tab -->
            <div class="tab-content" id="dashboard">
                <!-- Period Selector for Dashboard -->
                <div class="period-selector">
                    <button class="period-btn active" data-period="all">Tout</button>
                    <button class="period-btn" data-period="730">2 ans</button>
                    <button class="period-btn" data-period="365">1 an</button>
                    <button class="period-btn" data-period="180">6 mois</button>
                    <button class="period-btn" data-period="30">1 mois</button>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Poids</div>
                        <div class="stat-value">--<span class="stat-unit">kg</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Poitrine</div>
                        <div class="stat-value">--<span class="stat-unit">cm</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Taille</div>
                        <div class="stat-value">--<span class="stat-unit">cm</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Bras</div>
                        <div class="stat-value">--<span class="stat-unit">cm</span></div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <span class="chart-title">Évolution du poids</span>
                        <span class="chart-value">-- kg</span>
                    </div>
                    <canvas id="chartCanvas"></canvas>
                </div>

                <!-- Import CSV -->
                <div class="import-section">
                    <h3>Importer des données</h3>
                    <form id="importForm" enctype="multipart/form-data">
                        <input type="file" id="csvFile" accept=".csv" required>
                        <button type="submit" class="btn-secondary">Importer CSV</button>
                    </form>
                    <div id="importResult" class="import-result"></div>
                </div>
            </div>

            <!-- Measurements Tab -->
            <div class="tab-content" id="measurements" style="display: none;">
                <div class="measurement-list" id="measurementsList">
                    <!-- Les mesures seront chargées dynamiquement -->
                </div>
            </div>

            <!-- Body Composition Tab -->
            <div class="tab-content" id="body-comp" style="display: none;">
                <div class="measurement-list" id="bodyCompList">
                    <!-- Les mesures seront chargées dynamiquement -->
                </div>
            </div>
        </div>

        <!-- Detail View -->
        <div class="detail-view" id="detailView">
            <a href="#" class="back-btn" id="backBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Retour
            </a>
            
            <!-- Period Selector for Detail View -->
            <div class="period-selector">
                <button class="period-btn active" data-period="all">Tout</button>
                <button class="period-btn" data-period="730">2 ans</button>
                <button class="period-btn" data-period="365">1 an</button>
                <button class="period-btn" data-period="180">6 mois</button>
                <button class="period-btn" data-period="30">1 mois</button>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <span class="chart-title" id="detailTitle">--</span>
                    <span class="chart-value" id="detailValue">--</span>
                </div>
                <canvas id="detailChart"></canvas>
            </div>

            <h3 style="margin: 20px 0 10px;">Historique</h3>
            <div class="measurement-history" id="measurementHistory">
                <!-- L'historique sera chargé dynamiquement -->
            </div>
        </div>
    </div>

    <!-- FAB -->
    <button class="fab" id="addBtn">+</button>

    <!-- Add/Edit Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Ajouter une mensuration</h2>
                <button class="close-btn" id="closeBtn">&times;</button>
            </div>
            <form id="measurementForm">
                <input type="hidden" id="measurementId">
                <div class="form-group">
                    <label class="form-label" for="measurementType">Type de mensuration</label>
                    <select class="form-select" id="measurementType" required>
                        <option value="">Sélectionner...</option>
                        <optgroup label="Circonférences">
                            <option value="Cou">Cou</option>
                            <option value="Épaules">Épaules</option>
                            <option value="Poitrine">Poitrine</option>
                            <option value="Bras (Gauche)">Bras Gauche</option>
                            <option value="Bras (Droite)">Bras Droite</option>
                            <option value="Avant-bras (Gauche)">Avant-bras Gauche</option>
                            <option value="Avant-bras (Droite)">Avant-bras Droite</option>
                            <option value="Taille">Taille</option>
                            <option value="Hanches">Hanches</option>
                            <option value="Cuisse (Gauche)">Cuisse Gauche</option>
                            <option value="Cuisse (Droite)">Cuisse Droite</option>
                            <option value="Mollet (Gauche)">Mollet Gauche</option>
                            <option value="Mollet (Droite)">Mollet Droite</option>
                        </optgroup>
                        <optgroup label="Composition corporelle">
                            <option value="Poids">Poids</option>
                            <option value="Indice de masse grasse">Indice de masse grasse</option>
                            <option value="Eau corporelle">Eau corporelle</option>
                            <option value="Masse musculaire">Masse musculaire</option>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="measurementValue">Valeur</label>
                    <input type="number" step="0.01" class="form-input" id="measurementValue" placeholder="Ex: 44.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="measurementDate">Date</label>
                    <input type="date" class="form-input" id="measurementDate" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="measurementTime">Heure</label>
                    <input type="time" class="form-input" id="measurementTime" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="measurementNotes">Notes (optionnel)</label>
                    <input type="text" class="form-input" id="measurementNotes" placeholder="Ajouter une note...">
                </div>
                <button type="submit" class="submit-btn">Enregistrer</button>
                <button type="button" class="delete-btn" id="deleteBtn" style="display: none;">Supprimer</button>
            </form>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>