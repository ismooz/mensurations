// Gestion des onglets
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        currentTab = this.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(currentTab).style.display = 'block';
        
        if (currentTab === 'measurements' || currentTab === 'body-comp') {
            loadMeasurementsList();
        } else if (currentTab === 'dashboard') {
            // Redessiner le graphique du poids quand on revient au dashboard
            setTimeout(() => {
                drawChart('chartCanvas', 'Poids');
            }, 100);
        }
    });
});// Gestion des onglets
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        currentTab = this.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(currentTab).style.display = 'block';
        
        if (currentTab === 'measurements' || currentTab === 'body-comp') {
            loadMeasurementsList();
        } else if (currentTab === 'dashboard') {
            // Redessiner le graphique du poids quand on revient au dashboard.
let currentPeriod = 'all';
let currentTab = 'dashboard';
let currentMeasurementType = null;
let editingMeasurement = null;
let currentView = 'main'; // 'main' ou 'detail'

// Fonctions utilitaires
function formatValue(value, type) {
    const unit = type === 'Poids' ? 'kg' : (type.includes('masse') || type.includes('Eau') ? '%' : 'cm');
    return parseFloat(value).toFixed(2).replace('.', ',') + ' ' + unit;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
}

// API calls
async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Erreur réseau');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Erreur API:', error);
        return null;
    }
}

// Gestion des onglets
document.querySelectorAll('.nav-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        currentTab = this.dataset.tab;
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(currentTab).style.display = 'block';
        
        if (currentTab === 'measurements' || currentTab === 'body-comp') {
            loadMeasurementsList();
        }
    });
});

// Gestion des périodes - mise à jour pour gérer les deux sélecteurs
function initPeriodSelectors() {
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Mettre à jour tous les boutons de période dans la vue courante
            const container = this.closest('.main-view, .detail-view');
            container.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            
            // Activer le bouton cliqué
            this.classList.add('active');
            currentPeriod = this.dataset.period;
            
            // Rafraîchir les données selon la vue
            if (currentView === 'detail' && currentMeasurementType) {
                loadMeasurementDetails(currentMeasurementType);
            } else if (currentView === 'main' && currentTab === 'dashboard') {
                // Recharger uniquement le graphique du poids
                drawChart('chartCanvas', 'Poids');
            }
        });
    });
}

// Modal
const modal = document.getElementById('addModal');
const addBtn = document.getElementById('addBtn');
const closeBtn = document.getElementById('closeBtn');
const deleteBtn = document.getElementById('deleteBtn');

addBtn.addEventListener('click', () => {
    editingMeasurement = null;
    document.getElementById('modalTitle').textContent = 'Ajouter une mensuration';
    document.getElementById('measurementForm').reset();
    document.getElementById('measurementId').value = '';
    document.getElementById('measurementType').disabled = false;
    document.getElementById('deleteBtn').style.display = 'none';
    
    const now = new Date();
    document.getElementById('measurementDate').value = now.toISOString().split('T')[0];
    document.getElementById('measurementTime').value = now.toTimeString().slice(0, 5);
    
    modal.style.display = 'block';
});

closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

window.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

// Retour
document.getElementById('backBtn').addEventListener('click', (e) => {
    e.preventDefault();
    document.querySelector('.main-view').style.display = 'block';
    document.getElementById('detailView').style.display = 'none';
    currentMeasurementType = null;
    currentView = 'main';
    
    // Réinitialiser le sélecteur de période principal
    const mainPeriodBtns = document.querySelector('.main-view').querySelectorAll('.period-btn');
    mainPeriodBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === currentPeriod);
    });
});

// Formulaire
document.getElementById('measurementForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        type: document.getElementById('measurementType').value,
        value: document.getElementById('measurementValue').value,
        date: document.getElementById('measurementDate').value,
        time: document.getElementById('measurementTime').value,
        notes: document.getElementById('measurementNotes').value
    };
    
    if (editingMeasurement) {
        data.id = document.getElementById('measurementId').value;
    }
    
    const result = await apiCall(editingMeasurement ? 'update_measurement' : 'add_measurement', data);
    
    if (result && result.success) {
        modal.style.display = 'none';
        if (currentMeasurementType) {
            loadMeasurementDetails(currentMeasurementType);
        }
        updateDashboard();
        loadMeasurementsList();
    }
});

// Suppression
deleteBtn.addEventListener('click', async () => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette mesure ?')) return;
    
    const result = await apiCall('delete_measurement', {
        id: document.getElementById('measurementId').value
    });
    
    if (result && result.success) {
        modal.style.display = 'none';
        loadMeasurementDetails(currentMeasurementType);
        updateDashboard();
    }
});

// Import CSV
document.getElementById('importForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) return;
    
    const formData = new FormData();
    formData.append('action', 'import_csv');
    formData.append('csvFile', file);
    
    const resultDiv = document.getElementById('importResult');
    resultDiv.textContent = 'Import en cours...';
    resultDiv.className = 'import-result';
    resultDiv.style.display = 'block';
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.className = 'import-result success';
            resultDiv.textContent = `Import réussi ! ${result.imported} mesures importées.`;
            if (result.errors > 0) {
                resultDiv.textContent += ` ${result.errors} erreurs.`;
            }
            fileInput.value = '';
            updateDashboard();
            loadMeasurementsList();
        } else {
            resultDiv.className = 'import-result error';
            resultDiv.textContent = result.message || 'Erreur lors de l\'import';
        }
    } catch (error) {
        resultDiv.className = 'import-result error';
        resultDiv.textContent = 'Erreur lors de l\'import';
    }
});

// Chargement des données
async function updateDashboard() {
    const latest = await apiCall('get_latest_values');
    if (!latest) return;
    
    const latestMap = {};
    latest.forEach(m => {
        latestMap[m.measurement_type] = m;
    });
    
    // Mise à jour des cartes stats
    const statsMapping = {
        'Poids': 'Poids',
        'Poitrine': 'Poitrine',
        'Taille': 'Taille',
        'Bras': 'Bras (Gauche)'
    };
    
    document.querySelectorAll('.stat-card').forEach(card => {
        const label = card.querySelector('.stat-label').textContent;
        const type = statsMapping[label];
        
        if (latestMap[type]) {
            const valueEl = card.querySelector('.stat-value');
            const unit = card.querySelector('.stat-unit').textContent;
            valueEl.innerHTML = parseFloat(latestMap[type].value).toFixed(2).replace('.', ',') + `<span class="stat-unit">${unit}</span>`;
        }
    });
    
    // Graphique du poids - avec delay pour s'assurer que le canvas est visible
    if (latestMap['Poids']) {
        document.querySelector('.chart-value').textContent = formatValue(latestMap['Poids'].value, 'Poids');
    }
    
    // Dessiner le graphique avec un petit délai pour s'assurer que tout est chargé
    setTimeout(() => {
        drawChart('chartCanvas', 'Poids');
    }, 100);
}values');
    if (!latest) return;
    
    const latestMap = {};
    latest.forEach(m => {
        latestMap[m.measurement_type] = m;
    });
    
    // Mise à jour des cartes stats
    const statsMapping = {
        'Poids': 'Poids',
        'Poitrine': 'Poitrine',
        'Taille': 'Taille',
        'Bras': 'Bras (Gauche)'
    };
    
    document.querySelectorAll('.stat-card').forEach(card => {
        const label = card.querySelector('.stat-label').textContent;
        const type = statsMapping[label];
        
        if (latestMap[type]) {
            const valueEl = card.querySelector('.stat-value');
            const unit = card.querySelector('.stat-unit').textContent;
            valueEl.innerHTML = parseFloat(latestMap[type].value).toFixed(2).replace('.', ',') + `<span class="stat-unit">${unit}</span>`;
        }
    });
    
    // Graphique du poids - avec delay pour s'assurer que le canvas est visible
    if (latestMap['Poids']) {
        document.querySelector('.chart-value').textContent = formatValue(latestMap['Poids'].value, 'Poids');
    }
    
    // Dessiner le graphique avec un petit délai pour s'assurer que tout est chargé
    setTimeout(() => {
        drawChart('chartCanvas', 'Poids');
    }, 100);
}values');
    if (!latest) return;
    
    const latestMap = {};
    latest.forEach(m => {
        latestMap[m.measurement_type] = m;
    });
    
    // Mise à jour des cartes stats
    const statsMapping = {
        'Poids': 'Poids',
        'Poitrine': 'Poitrine',
        'Taille': 'Taille',
        'Bras': 'Bras (Gauche)'
    };
    
    document.querySelectorAll('.stat-card').forEach(card => {
        const label = card.querySelector('.stat-label').textContent;
        const type = statsMapping[label];
        
        if (latestMap[type]) {
            const valueEl = card.querySelector('.stat-value');
            const unit = card.querySelector('.stat-unit').textContent;
            valueEl.innerHTML = parseFloat(latestMap[type].value).toFixed(2).replace('.', ',') + `<span class="stat-unit">${unit}</span>`;
        }
    });
    
    // Graphique du poids
    if (latestMap['Poids']) {
        document.querySelector('.chart-value').textContent = formatValue(latestMap['Poids'].value, 'Poids');
        drawChart('chartCanvas', 'Poids');
    }
}

async function loadMeasurementsList() {
    const latest = await apiCall('get_latest_values');
    if (!latest) return;
    
    const latestMap = {};
    latest.forEach(m => {
        latestMap[m.measurement_type] = m;
    });
    
    // Types de circonférences
    const circumferenceTypes = [
        'Cou', 'Épaules', 'Poitrine', 'Bras (Gauche)', 'Bras (Droite)',
        'Avant-bras (Gauche)', 'Avant-bras (Droite)', 'Taille', 'Hanches',
        'Cuisse (Gauche)', 'Cuisse (Droite)', 'Mollet (Gauche)', 'Mollet (Droite)'
    ];
    
    // Types de composition
    const compositionTypes = [
        'Poids', 'Indice de masse grasse', 'Eau corporelle', 'Masse musculaire'
    ];
    
    // Mise à jour de la liste des circonférences
    const measurementsList = document.getElementById('measurementsList');
    measurementsList.innerHTML = '';
    
    circumferenceTypes.forEach(type => {
        const item = document.createElement('div');
        item.className = 'measurement-item';
        item.dataset.type = type;
        
        const displayName = type.replace(' (Gauche)', ' Gauche').replace(' (Droite)', ' Droite');
        const value = latestMap[type] ? formatValue(latestMap[type].value, type) : '--';
        
        item.innerHTML = `
            <div class="measurement-name">
                <div class="measurement-icon">📏</div>
                ${displayName}
            </div>
            <div class="measurement-value">${value}</div>
        `;
        
        item.addEventListener('click', () => showMeasurementDetails(type));
        measurementsList.appendChild(item);
    });
    
    // Mise à jour de la liste de composition
    const bodyCompList = document.getElementById('bodyCompList');
    bodyCompList.innerHTML = '';
    
    const icons = {
        'Poids': '⚖️',
        'Indice de masse grasse': '📊',
        'Eau corporelle': '💧',
        'Masse musculaire': '💪'
    };
    
    compositionTypes.forEach(type => {
        const item = document.createElement('div');
        item.className = 'measurement-item';
        item.dataset.type = type;
        
        const value = latestMap[type] ? formatValue(latestMap[type].value, type) : '--';
        
        item.innerHTML = `
            <div class="measurement-name">
                <div class="measurement-icon">${icons[type]}</div>
                ${type}
            </div>
            <div class="measurement-value">${value}</div>
        `;
        
        item.addEventListener('click', () => showMeasurementDetails(type));
        bodyCompList.appendChild(item);
    });
}

async function showMeasurementDetails(type) {
    currentMeasurementType = type;
    currentView = 'detail';
    document.querySelector('.main-view').style.display = 'none';
    document.getElementById('detailView').style.display = 'block';
    
    document.getElementById('detailTitle').textContent = `Évolution - ${type}`;
    
    // Synchroniser le sélecteur de période de la vue détail
    const detailPeriodBtns = document.getElementById('detailView').querySelectorAll('.period-btn');
    detailPeriodBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === currentPeriod);
    });
    
    await loadMeasurementDetails(type);
}

async function loadMeasurementDetails(type) {
    const measurements = await apiCall('get_measurements', {
        type: type,
        period: currentPeriod
    });
    
    if (!measurements || measurements.length === 0) {
        document.getElementById('detailValue').textContent = '--';
        document.getElementById('measurementHistory').innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">Aucune mesure enregistrée</p>';
        return;
    }
    
    // Valeur la plus récente
    const latest = measurements[0];
    document.getElementById('detailValue').textContent = formatValue(latest.value, type);
    
    // Afficher l'historique
    const historyContainer = document.getElementById('measurementHistory');
    historyContainer.innerHTML = '';
    
    measurements.forEach(m => {
        const item = document.createElement('div');
        item.className = 'history-item';
        item.innerHTML = `
            <div>
                <div class="history-date">${formatDate(m.measurement_date)} à ${m.measurement_time.slice(0, 5)}</div>
                ${m.notes ? `<div class="history-note">${m.notes}</div>` : ''}
            </div>
            <div class="history-value">${formatValue(m.value, type)}</div>
        `;
        
        item.addEventListener('click', () => editMeasurement(m));
        historyContainer.appendChild(item);
    });
    
    // Dessiner le graphique
    drawChart('detailChart', type);
}

function editMeasurement(measurement) {
    editingMeasurement = measurement;
    document.getElementById('modalTitle').textContent = 'Modifier la mensuration';
    document.getElementById('measurementId').value = measurement.id;
    document.getElementById('measurementType').value = measurement.measurement_type;
    document.getElementById('measurementType').disabled = true;
    document.getElementById('measurementValue').value = measurement.value;
    document.getElementById('measurementDate').value = measurement.measurement_date;
    document.getElementById('measurementTime').value = measurement.measurement_time;
    document.getElementById('measurementNotes').value = measurement.notes || '';
    document.getElementById('deleteBtn').style.display = 'block';
    modal.style.display = 'block';
}

async function drawChart(canvasId, type) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    const measurements = await apiCall('get_measurements', {
        type: type,
        period: currentPeriod
    });
    
    if (!measurements || measurements.length === 0) {
        // S'assurer que le canvas a des dimensions
        if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
            setTimeout(() => drawChart(canvasId, type), 100);
            return;
        }
        
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#999';
        ctx.font = '14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Aucune donnée à afficher', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // S'assurer que le canvas a des dimensions avant de dessiner
    if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
        setTimeout(() => drawChart(canvasId, type), 100);
        return;
    }
    
    // Trier par date chronologique
    measurements.sort((a, b) => {
        const dateA = new Date(a.measurement_date + ' ' + a.measurement_time);
        const dateB = new Date(b.measurement_date + ' ' + b.measurement_time);
        return dateA - dateB;
    });
    
    // Clear canvas
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    
    const padding = 40;
    const chartWidth = canvas.width - 2 * padding;
    const chartHeight = canvas.height - 2 * padding;
    
    // Calculer les échelles
    const values = measurements.map(m => parseFloat(m.value));
    const minValue = Math.min(...values) * 0.95;
    const maxValue = Math.max(...values) * 1.05;
    const valueRange = maxValue - minValue || 1;
    
    // Grille
    ctx.strokeStyle = '#f0f0f0';
    ctx.lineWidth = 1;
    
    for (let i = 0; i <= 5; i++) {
        const y = padding + (i * chartHeight / 5);
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(canvas.width - padding, y);
        ctx.stroke();
    }
    
    // Ligne de données
    if (measurements.length > 1) {
        ctx.strokeStyle = '#e74c3c';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        measurements.forEach((point, index) => {
            const x = padding + (index / (measurements.length - 1)) * chartWidth;
            const y = padding + chartHeight - ((parseFloat(point.value) - minValue) / valueRange) * chartHeight;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
    }
    
    // Points
    ctx.fillStyle = '#e74c3c';
    measurements.forEach((point, index) => {
        const x = measurements.length === 1 ? canvas.width / 2 : padding + (index / (measurements.length - 1)) * chartWidth;
        const y = padding + chartHeight - ((parseFloat(point.value) - minValue) / valueRange) * chartHeight;
        
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, Math.PI * 2);
        ctx.fill();
    });
    
    // Labels Y
    ctx.fillStyle = '#666';
    ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
    
    for (let i = 0; i <= 5; i++) {
        const value = minValue + (valueRange * (1 - i / 5));
        const y = padding + (i * chartHeight / 5);
        ctx.textAlign = 'right';
        ctx.fillText(value.toFixed(1), padding - 10, y + 4);
    }
    
    // Labels X
    ctx.textAlign = 'center';
    if (measurements.length <= 7) {
        measurements.forEach((point, index) => {
            const x = measurements.length === 1 ? canvas.width / 2 : padding + (index / (measurements.length - 1)) * chartWidth;
            const date = new Date(point.measurement_date);
            const label = date.getDate() + '/' + (date.getMonth() + 1);
            ctx.fillText(label, x, canvas.height - padding + 20);
        });
    } else {
        const step = Math.ceil(measurements.length / 6);
        for (let i = 0; i < measurements.length; i += step) {
            const x = padding + (i / (measurements.length - 1)) * chartWidth;
            const date = new Date(measurements[i].measurement_date);
            const label = date.getDate() + '/' + (date.getMonth() + 1);
            ctx.fillText(label, x, canvas.height - padding + 20);
        }
    }
}

// Export des données
function exportData(format = 'csv') {
    const params = new URLSearchParams({
        format: format
    });
    
    if (currentMeasurementType) {
        params.append('type', currentMeasurementType);
    }
    
    window.location.href = `export.php?${params.toString()}`;
}

// Initialisation
window.addEventListener('load', () => {
    currentView = 'main';
    initPeriodSelectors();
    updateDashboard();
    
    // Gérer les liens directs vers une mesure spécifique
    if (window.location.hash) {
        const type = decodeURIComponent(window.location.hash.substring(1));
        setTimeout(() => {
            const measurementItem = document.querySelector(`[data-type="${type}"]`);
            if (measurementItem) {
                measurementItem.click();
            }
        }, 500);
    }
    
    // Gérer le redimensionnement
    window.addEventListener('resize', () => {
        setTimeout(() => {
            if (currentMeasurementType) {
                drawChart('detailChart', currentMeasurementType);
            } else if (currentTab === 'dashboard') {
                drawChart('chartCanvas', 'Poids');
            }
        }, 100);
    });
});