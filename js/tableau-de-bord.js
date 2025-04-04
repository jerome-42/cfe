let getStatsTableauDeBordAnnuel = function() {
    let key = $('#moyenneAnnees').val();
    return stats.tableauDeBordAnnuel[key];
};

let getStatsByMonth = function(s, mois) {
    if (mois < 10)
        mois = '0'+mois;
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i].d.indexOf('-'+mois+'-') !== -1) {
            //DEBUG console.log(s.data[i].stats, i, mois);
            return s.data[i].stats;
        }
    }
    throw new Error("impossible de trouver le mois "+mois+" dans stats");
};

let getDateFromD = function(dString) {
    let d = new Date(0);
    let s = Date.parse(dString);
    d.setUTCSeconds(s/1000);
    return d;
};

let tooltipDisplay_percent = function(context, targetLabels, formatter) {
    let label = context.dataset.label || '';
    if (label) {
        label += ': ';
    }
    let value = context.dataset.data[context.dataIndex];
    if (formatter === undefined)
        label += value;
    else
        label += formatter(value, context);

    let targetDataset; let targetLabel;
    if (context.datasetIndex == 0) {
        targetDataset = 1;
        targetLabel = targetLabels[1];
    }
    if (context.datasetIndex == 1) {
        targetDataset = 0;
        targetLabel = targetLabels[0];
    }
    if (context.datasetIndex == 2) {
        targetDataset = 3;
        targetLabel = targetLabels[3];
    }
    if (context.datasetIndex == 3) {
        targetDataset = 2;
        targetLabel = targetLabels[2];
    }
    if (context.datasetIndex == 4) {
        targetDataset = 5;
        targetLabel = targetLabels[5];
    }
    if (context.datasetIndex == 5) {
        targetDataset = 4;
        targetLabel = targetLabels[4];
    }

    let targetValue = context.chart.getDatasetMeta(targetDataset)._dataset.data[context.dataIndex];
    if (targetValue === undefined)
        return label;
    let moyenne = Math.round((value - targetValue) / targetValue * 100);
    if (moyenne > 0)
        moyenne = 'en progression de '+moyenne+' % par rapport à '+targetLabel;
    if (moyenne < 0)
        moyenne = 'en retrait de '+Math.abs(moyenne)+' % par rapport à '+targetLabel;
    if (moyenne !== 0)
        return label + ' ' + moyenne;
    else
        return label;
};

let displayBudgetRevenus = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('budgetRevenus').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus activité générale '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.revenus_generales,
                },
                {
                    label: 'Revenus HdV '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs,
                },
                {
                    label: 'Revenus envols '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.revenus_moyens_lancement,
                },
                {
                    label: 'Subvention mairie '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.revenus_mairie,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenus',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayBudgetDepenses = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('budgetDepenses').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Dépenses activité générale '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.depenses_generales,
                },
                {
                    label: 'Dépenses HdV '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs,
                },
                {
                    label: 'Dépenses envols '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.depenses_moyens_lancement,
                },
                {
                    label: 'Dépenses budget mairie '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.depenses_mairie,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Dépenses',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayBudgetResultat = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('budgetResultat').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let resultatGeneral = [];
    let resultatHdv = [];
    let resultatEnvols = [];
    let resultatMairie = [];
    let resultatGlobal = [];
    for (let i = 0; i < 12; i++) {
        resultatGeneral[i] = getStatsTableauDeBordAnnuel().data.revenus_generales[i] - getStatsTableauDeBordAnnuel().data.depenses_generales[i];
        resultatHdv[i] = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs[i] - getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs[i];
        resultatEnvols[i] = getStatsTableauDeBordAnnuel().data.revenus_moyens_lancement[i] - getStatsTableauDeBordAnnuel().data.depenses_moyens_lancement[i];
        resultatMairie[i] = getStatsTableauDeBordAnnuel().data.revenus_mairie[i] - getStatsTableauDeBordAnnuel().data.depenses_mairie[i];
        resultatGlobal[i] = resultatGeneral[i] + resultatHdv[i] + resultatEnvols[i] + resultatMairie[i];
    }
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Résultat activité générale '+getStatsTableauDeBordAnnuel().params.annee,
                    data: resultatGeneral,
                    hidden: true,
                },
                {
                    label: 'Résultat HdV '+getStatsTableauDeBordAnnuel().params.annee,
                    data: resultatHdv,
                    hidden: true,
                },
                {
                    label: 'Résultat envols '+getStatsTableauDeBordAnnuel().params.annee,
                    data: resultatEnvols,
                    hidden: true,
                },
                {
                    label: 'Résultat mairie '+getStatsTableauDeBordAnnuel().params.annee,
                    data: resultatMairie,
                    hidden: true,
                },
                {
                    label: 'Résultat global '+getStatsTableauDeBordAnnuel().params.annee,
                    data: resultatGlobal,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Résultat',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayBudgetResultatBarGraph = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('budgetResultatBarGraph').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let revenusGeneral = null;
    let depensesGeneral = null;
    let revenusHdV = null;
    let depensesHdv = null;
    let revenusEnvols = null;
    let depensesEnvols = null;
    let revenusMairie = null;
    let depensesMairie = null;
    for (let i = 0; i < 12; i++) {
        if (getStatsTableauDeBordAnnuel().data.revenus_generales[i] != null)
            revenusGeneral = getStatsTableauDeBordAnnuel().data.revenus_generales[i];
        if (getStatsTableauDeBordAnnuel().data.depenses_generales[i] != null)
            depensesGeneral = getStatsTableauDeBordAnnuel().data.depenses_generales[i];
        if (getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs[i] != null)
            revenusHdV = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs[i];
        if (getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs[i] != null)
            depensesHdV = getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs[i];
        if (getStatsTableauDeBordAnnuel().data.revenus_moyens_lancement[i] != null)
            revenusEnvols = getStatsTableauDeBordAnnuel().data.revenus_moyens_lancement[i];
        if (getStatsTableauDeBordAnnuel().data.depenses_moyens_lancement[i] != null)
            depensesEnvols = getStatsTableauDeBordAnnuel().data.depenses_moyens_lancement[i];
        if (getStatsTableauDeBordAnnuel().data.revenus_mairie[i] != null)
            revenusMairie = getStatsTableauDeBordAnnuel().data.revenus_mairie[i];
        if (getStatsTableauDeBordAnnuel().data.depenses_mairie[i] != null)
            depensesMairie = getStatsTableauDeBordAnnuel().data.depenses_mairie[i];
    }
    let data1 = [ revenusGeneral, revenusEnvols, revenusHdV, revenusMairie ];
    let data2 = [ depensesGeneral, depensesEnvols, depensesHdV, depensesMairie ];
    let data3 = [ revenusGeneral - depensesGeneral,
                       revenusEnvols - depensesEnvols,
                       revenusHdV - depensesHdV,
                       revenusMairie - depensesMairie ];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Activité Générale', 'Envols', 'HdV', 'mairie',
            ],
            datasets: [
                {
                    data: data1,
                    label: 'Revenus',
                    backgroundColor: '#069bff',
                },
                {
                    data: data2,
                    label: 'Dépenses',
                    backgroundColor: '#01cf31',
                },
                {
                    data: data3,
                    label: 'Résultats',
                    backgroundColor: '#8c7977',
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Budget '+getStatsTableauDeBordAnnuel().params.annee,
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            },
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
        },
    });
};

let displayRevenusMoyensLancement = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('revenusMoyensLancement').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.revenus_moyens_lancement;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus moyens de lancement '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenus moyens de lancement',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayDepensesMoyensLancement = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('depensesMoyensLancement').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.depenses_moyens_lancement;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Dépenses moyens de lancement '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Dépenses moyens de lancement',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayCFE = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('cfe').getContext('2d');
    let dataCetteAnnee = statsLocales.cfe.declarationsCFE;
    let dataNAnneesPrecedantes = statsLocales.cfe.declarationsCFE_n_anneesPrecedantes;
    let labelNAnneesPrecedantes = 'Moyenne sur les '+statsLocales.cfe.moyenne_sur_nb_annee+' dernières années';
    if (statsLocales.cfe.moyenne_sur_nb_annee === 1)
        labelNAnneesPrecedantes = "Heures de CFE "+(statsLocales.cfe.annee-1);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Heures de CFE '+statsLocales.cfe.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: labelNAnneesPrecedantes,
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heure de CFE réalisées',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    }
                },
            }
        }
    });
};

let displayRevenusMairie = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('revenusMairie').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.revenus_mairie;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus budget mairie '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Budget mairie',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayDepensesMairie = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('depensesMairie').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.depenses_mairie;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Dépenses budget mairie '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Dépenses budget mairie',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayLicence = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('licence').getContext('2d');
    const passion_plus_25 = 'Passion +25 ans (Annuelle)';
    const passion_moins_25 = 'Passion -25 ans (Annuelle)';
    const asso = 'Asso - Non volants (Annuelle)';
    const duo = 'Duo (Annuelle)';
    const decouverte_3j = 'Découverte 3 jours (consécutifs ou non)';
    const decouverte_6j = 'Découverte 6 jours (consécutifs ou non)';
    const decouverte_12j = 'Découverte 12 jours (consécutifs ou non)';
    const esport = 'esport';
    let dataCetteAnnee = [
        stats.tableauDeBord.data.licence_cette_annee[passion_plus_25],
        stats.tableauDeBord.data.licence_cette_annee[passion_moins_25],
        stats.tableauDeBord.data.licence_cette_annee[asso],
        stats.tableauDeBord.data.licence_cette_annee[duo],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_3j],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_6j],
        stats.tableauDeBord.data.licence_cette_annee[decouverte_12j],
        stats.tableauDeBord.data.licence_cette_annee[esport],
    ];
    dataCetteAnnee.push(dataCetteAnnee.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.licence_annee_derniere[passion_plus_25],
        stats.tableauDeBord.data.licence_annee_derniere[passion_moins_25],
        stats.tableauDeBord.data.licence_annee_derniere[asso],
        stats.tableauDeBord.data.licence_annee_derniere[duo],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_3j],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_6j],
        stats.tableauDeBord.data.licence_annee_derniere[decouverte_12j],
        stats.tableauDeBord.data.licence_annee_derniere[esport],
    ];
    dataAnneeDerniere.push(dataAnneeDerniere.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.licence_annee_derniere_complete[passion_plus_25],
        stats.tableauDeBord.data.licence_annee_derniere_complete[passion_moins_25],
        stats.tableauDeBord.data.licence_annee_derniere_complete[asso],
        stats.tableauDeBord.data.licence_annee_derniere_complete[duo],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_3j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_6j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[decouverte_12j],
        stats.tableauDeBord.data.licence_annee_derniere_complete[esport],
    ];
    dataAnneeDerniereComplete.push(dataAnneeDerniereComplete.reduce(function(accumulator, a) {
        if (a !== undefined)
            return accumulator + a;
        return accumulator;
    }, 0));
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Passion +25',
                'Passion -25',
                'Asso',
                'Duo',
                'Découverte 3j',
                'Découverte 6j',
                'Découverte 12j',
                'Esport',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Licences',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayMoyensLancement = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancements').getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.T,
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.R,
        stats.tableauDeBord.data.mise_en_l_air_cette_annee.T + stats.tableauDeBord.data.mise_en_l_air_cette_annee.R,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.T,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.R,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere.T + stats.tableauDeBord.data.mise_en_l_air_annee_derniere.R,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.T,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.R,
        stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.T + stats.tableauDeBord.data.mise_en_l_air_annee_derniere_complete.R,
    ];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'Treuillées',
                'Remorqués',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Lancements hors autonomes',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHdv = function(subStats, target, title, max) {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById(target).getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data[subStats+'_cette_annee'].cdb,
        stats.tableauDeBord.data[subStats+'_cette_annee'].instruction,
        stats.tableauDeBord.data[subStats+'_cette_annee'].total,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data[subStats+'_annee_derniere'].cdb,
        stats.tableauDeBord.data[subStats+'_annee_derniere'].instruction,
        stats.tableauDeBord.data[subStats+'_annee_derniere'].total,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].cdb,
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].instruction,
        stats.tableauDeBord.data[subStats+'_annee_derniere_complete'].total,
    ];
    //DEBUG console.log(data);
    let opts = {
        type: 'bar',
        data: {
            labels: [
                'CDB',
                'Instruction',
                'Total',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: title,
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    };
    if (max !== undefined)
        opts.options.scales = { y: { suggestedMax: max }};
    return new Chart(ctx, opts);
};

let displayViClub = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('vi_club').getContext('2d');
    let dataCetteAnnee = [
        stats.tableauDeBord.data.vi_club_cette_annee.nb_vi,
    ];
    let dataAnneeDerniere = [
        stats.tableauDeBord.data.vi_club_annee_derniere.nb_vi,
    ];
    let dataAnneeDerniereComplete = [
        stats.tableauDeBord.data.vi_club_annee_derniere_complete.nb_vi,
    ];
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'VI Club',
            ],
            datasets: [
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee)),
                    data: dataCetteAnnee,
                },
                {
                    label: formatter.format(getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere)),
                    data: dataAnneeDerniere,
                },
                {
                    label: getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_annee_derniere).getFullYear(),
                    data: dataAnneeDerniereComplete,
                }
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'VI CLUB',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let resolvePtr = function(d, ptr) {
    if (d === undefined || d === null)
        return null;
    let keys = ptr.split('.');
    let key = keys.shift();
    if (d[key] === undefined)
        return null;
    if (keys.length === 0)
        return d[key];
    return resolvePtr(d[key], keys.join('.'));
};

let getColumns = function(columnsDefs, partialStats) {
    let columns = [];

    columnsDefs.forEach(function(e) {
        if (e.target !== undefined)
            columns.push(e);
        if (e.targets !== undefined) {
            //DEBUG console.log('on traite', e);
            let subColumnsName = {};
            partialStats.data.forEach(function(l) {
                let ptr = resolvePtr(l, e.targets);
                //DEBUG console.log('après resolvePtr', ptr);
                if (ptr !== null) {
                    Object.keys(ptr).forEach(function(key) {
                        subColumnsName[key] = key;
                    });
                }
            });
            let subColumnsNameArray = Object.keys(subColumnsName).sort(function(a, b) {
                if (e.sortPriority !== undefined) {
                    if ($.inArray(a, e.sortPriority) != -1 && $.inArray(b, e.sortPriority) != -1)
                        return a > b;
                    if ($.inArray(a, e.sortPriority) != -1)
                        return -1;
                    if ($.inArray(b, e.sortPriority) != -1)
                        return 1;
                }
                return a > b;
            });
            subColumnsNameArray.forEach(function(l) {
                let target = e.targets+'.'+l;
                if (e.postfix !== undefined)
                    target += '.'+e.postfix;
                let columnAlreadyExists = false;
                columns.forEach(function(c2) {
                    if (c2.target == target)
                        columnAlreadyExists = true;
                });
                if (columnAlreadyExists === false)
                    columns.push({ 'label': e.label + ' ' + l, 'target': target  });
            });
        }
    });
    return columns;
};

let findStats = function(s, masterKey, value) {
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i][masterKey] === value)
            return s.data[i];
    }
    return null;
};

// ANNUEL
let displayLicenceAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('licenceAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.licences;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.licences_n_annees_precedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Licences '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Licences',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                    }
                },
            }
        }
    });
};

let displayValoInfraAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoFraisInfraAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.revenus_generales;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.revenus_generales_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus généraux '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenus généraux',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayDepensesGeneralesAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('depensesGeneralesAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.depenses_generales;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.depenses_generales_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Dépenses générales '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Dépenses générales',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayRevenusHdV = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('revenusHdV').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.revenus_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus HdV '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenus HdV',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayDepensesHdV = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('depensesHdV').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.depenses_entretien_planeurs_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Dépenses HdV '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Dépenses HdV',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayHDVClubAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvClubAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV machines club CDB '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVClubCDB,
                },
                {
                    label: 'Moyenne CDB sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVClubCDB_n_anneesPrecedantes,
                },
                {
                    label: 'HDV instruction sur machines club '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVClubInstruction,
                },
                {
                    label: 'Moyenne instruction sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVClubInstruction_n_anneesPrecedantes,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol machines CLUB',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVClubCDBAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvClubCDBAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.HDVClubCDB;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.HDVClubCDB_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV machines club CDB '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol machines CLUB en CDB',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVClubInstructionAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvClubInstructionAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.HDVClubInstruction;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.HDVClubInstruction_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV d\'instruction sur machines club '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol instruction sur machines CLUB',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVBanaliseAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvBanaliseAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV machines banalisées CDB '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseCDB,
                },
                {
                    label: 'HDV machines banalisées CDB moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseCDB_n_anneesPrecedantes,
                },
                {
                    label: 'HDV machines banalisées instruction '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseInstruction,
                },
                {
                    label: 'HDV machines banalisées instruction moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseInstruction_n_anneesPrecedantes,
                },
                {
                    label: 'HDV non-propriétaire sur machines banalisées '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseNonProprietaire,
                },
                {
                    label: 'HDV moyenne non-propriétaire sur machines banalisée sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseNonProprietaire_n_anneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol machines banalisées',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee,
                                  'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee,
                                  'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVBanaliseNonProprietaireAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvBanaliseNonProprietaireAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV non-propriétaire sur machines banalisées '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseNonProprietaire,
                },
                {
                    label: 'HDV moyenne non-propriétaire sur machines banalisée sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.HDVBanaliseNonProprietaire_n_anneesPrecedantes,
                },
            ],
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol de pilotes non-propriétaire sur machines banalisées',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVPilotesDansForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvPilotesDansForfait').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.HDVPilotesDansForfait;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.HDVPilotesDansForfait_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV des pilotes au forfait '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol des pilotes au forfait sur les machines incluses dans le forfait (consommation des forfaits)',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayHDVPilotesHorsForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('hdvPilotesHorsForfait').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.HDVPilotesHorsForfait;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.HDVPilotesHorsForfait_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'HDV hors forfait '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Heures de vol hors forfait (vols solo, instruction, partagé, VI club, VI perso ...)',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayLancementEtValoRemorqueAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancementEtValoRemorqueAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Nombre de remorqué avec correction '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.lancementRCorrigeCumul,
                    yAxisID: 'y',
                },
                {
                    label: 'Moyenne de remorqué sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.lancementRCorrigeCumul_n_anneesPrecedantes,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            scales: {
                y: {
                    position: 'left',
                },
                y1: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                },
            }, 
           responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Nombre de remorqués',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee,
                                  'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        if (context.dataset.currency !== undefined)
                            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                        return value;
                    }
                },
            }
        }
    });
};

let displayRemorqueAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancementRemorqueAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Nombre de remorqué sans correction '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.lancementRCumul,
                    yAxisID: 'y',
                },
                {
                    label: 'Moyenne de remorqué sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.lancementRCumul_n_anneesPrecedantes,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            scales: {
                y: {
                    position: 'left',
                },
                y1: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                },
            }, 
           responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Nombre de remorqués non corrigé',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                },
            }
        }
    });
};

let displayVentilationSelonRemorqueur = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('ventilationSelonRemorqueur').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let datasets = [];
    Object.keys(getStatsTableauDeBordAnnuel().data.nbRemorquesParRemorqueur).forEach(function(immatriculation) {
        datasets.push({
            label: 'Nombre de remorqué '+immatriculation,
            data: getStatsTableauDeBordAnnuel().data.nbRemorquesParRemorqueur[immatriculation],
        });
    });
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: datasets,
        },
        options: {
            //maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Nombre de remorqués par remorqueur '+getStatsTableauDeBordAnnuel().params.annee,
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ]);
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                },
            }
        }
    });
};

let displayLancementAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('lancementAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Nombre de remorqué '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.lancementR,
                },
                {
                    label: 'Nombre de treuillées '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.lancementT,
                },
                {
                    label: 'Nombre de lancement autonome '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.lancementA,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Détails par type de mise en l\'air',
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return value;
                    }
                },
            }
        }
    });
};

let displayValoCelluleEtForfaitAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoCelluleEtForfaitAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus heure de vol '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_hdv,
                    yAxisID: 'y',
                },
                {
                    label: 'Moyenne revenus heure de vol sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.valo_hdv_n_anneesPrecedantes,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenus forfaits '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_forfait,
                    yAxisID: 'y',
                },
                {
                    label: 'Moyenne revenus forfaits sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.valo_forfait_n_anneesPrecedantes,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                    position: 'left',
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu heures de vol et forfaits',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee,
                                  'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function(value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayValoJdStageAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoJdStageAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus journées découvertes et stages '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_jdStages,
                    yAxisID: 'y',
                },
                {
                    label: 'Moyenne revenus journées découvertes et stages sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: getStatsTableauDeBordAnnuel().data.valo_jdStages_n_anneesPrecedantes,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                    position: 'left',
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu JD et stages',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                  getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function(value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayValoVolAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoVolAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenus forfaits '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_forfait,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenus heure de vol pilotes '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_cellulePilotes,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenus heure de vol instruction '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_celluleInstruction,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenus VI '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_VI,
                    yAxisID: 'y',
                },
                {
                    label: 'Revenus journées découvertes et stages '+getStatsTableauDeBordAnnuel().params.annee,
                    data: getStatsTableauDeBordAnnuel().data.valo_jdStages,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                    position: 'left',
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Détails des revenus heures de vol '+getStatsTableauDeBordAnnuel().params.annee,
                    font: { size: 24 },
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function(value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let displayValoMoteurAnnuel = function() {
    let formatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
    var ctx = document.getElementById('valoMoteurAnnuel').getContext('2d');
    if (Chart.getChart(ctx) !== undefined)
        Chart.getChart(ctx).destroy();
    let dataCetteAnnee = getStatsTableauDeBordAnnuel().data.valo_moteur;
    let dataNAnneesPrecedantes = getStatsTableauDeBordAnnuel().data.valo_moteur_n_anneesPrecedantes;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
            ],
            datasets: [
                {
                    label: 'Revenu temps moteur SF28 '+getStatsTableauDeBordAnnuel().params.annee,
                    data: dataCetteAnnee,
                },
                {
                    label: 'Moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                    data: dataNAnneesPrecedantes,
                },
            ],
        },
        options: {
            scales: {
                y: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, ticks) {
                            return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' €';
                        },
                    },
                },
            },
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenu temps moteur',
                    font: { size: 24 },
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return tooltipDisplay_percent(
                                context,
                                [ 'la moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',
                                getStatsTableauDeBordAnnuel().params.annee ],
                                function (value, context) {
                                    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                                });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: 'black',
                    font: {
                        weight: 'bold',
                    },
                    formatter: function (value, context) {
                        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
                    }
                },
            }
        }
    });
};

let saveFile = function(filename, data) {
    const blob = new Blob([data], {type: 'text/csv'});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob, filename);
    }
    else{
        const elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }
};

let downloadDataAsCSV = function() {
    let zip = new JSZip();
    Object.keys(getStatsTableauDeBordAnnuel().data).forEach(function(key) {
        if (key === 'nbRemorquesParRemorqueur')
            return;
        if (key.indexOf('_n_anneesPrecedantes') != -1)
            return;
        let data = getStatsTableauDeBordAnnuel().data[key];
        let dataAnneesPrecedantes = null;
        if (getStatsTableauDeBordAnnuel().data[key+'_n_anneesPrecedantes'] !== undefined)
            dataAnneesPrecedantes = getStatsTableauDeBordAnnuel().data[key+'_n_anneesPrecedantes'];
        let csvContent = '';
        // header
        let line = [ '' ];
        for (let i = 0; i < 12; i++)
            line.push(i+1);
        csvContent += line.join(',')+'\n';
        // data
        line = [ key + ' annee '+getStatsTableauDeBordAnnuel().params.annee ];
        for (let i = 0; i < 12; i++)
            if (data[i] !== undefined)
                line.push(data[i]);
        csvContent += line.join(',')+'\n';
        // dataAnneesPrecedantes
        if (dataAnneesPrecedantes !== null) {
            line = [ key + ' moyenne sur les '+getStatsTableauDeBordAnnuel().data.moyenne_sur_nb_annee+' dernières années',];
            for (let i = 0; i < 12; i++)
                if (dataAnneesPrecedantes[i] !== undefined)
                    line.push(dataAnneesPrecedantes[i]);
            csvContent += line.join(',')+'\n';
        }
        zip.file(key+'.csv', csvContent);
    });
    zip.file('statsMachines-'+stats.statsMachines.year+'.csv', getCSV_statsMachines(stats.statsMachines));
    zip.file('statsMachines-'+stats.statsMachinesPrecedente.year+'.csv', getCSV_statsMachines(stats.statsMachinesPrecedente));
    zip.generateAsync({ type: 'blob' }).then(function(content) {
        saveFile('data.zip', content);
    });
};

let getCSV_statsMachines = function(statsMachines) {
    let csvContent = '';
    // header
    let line = [ 'immatriculation', 'nb places',
                 'type', 'nb vol', 'temps vol', 'revenus cellule', 'revenus moteur', 'revenu mise en l\'air', 'frais hangar', 'décollage autonome', 'revenu cellule si la machine est club', 'CA' ];
    csvContent += line.join(',')+'\n';
    for (var j = 0; j < statsMachines.data.length; j++) {
        var statsMachine = statsMachines.data[j];
        line = [
            statsMachine.immatriculation, statsMachine.stats.nb_place,
            statsMachine.stats.situation, // type
            statsMachine.stats.global.nb_vol, statsMachine.stats.global.temps_vol,
            numToFrenchXlsFmt(statsMachine.stats.global.revenus_cellule),
            numToFrenchXlsFmt(statsMachine.stats.global.revenus_moteur),
            numToFrenchXlsFmt(statsMachine.stats.revenus_mise_en_l_air),
            numToFrenchXlsFmt(statsMachine.stats.frais_hangar),
            numToFrenchXlsFmt(statsMachine.stats.revenu_decollage_autonome),
            numToFrenchXlsFmt(statsMachine.stats.global.ca_si_club),
        ];
        let total = 0;
        [ statsMachine.stats.global.revenus_cellule, statsMachine.stats.global.revenus_moteur,
          statsMachine.stats.frais_hangar,
          statsMachine.stats.revenu_decollage_autonome ].forEach(function(revenu) {
              if (revenu !== undefined)
                  total += revenu;
          });
        line.push(numToFrenchXlsFmt(total));
        csvContent += line.join(',')+'\n';
    };
    return csvContent;
};

// excel veut les nombres avec des , et pas des .
// on transforme
let numToFrenchXlsFmt = function(n) {
    if (n === undefined)
        return n;
    n = ''+n;
    if (n.indexOf('.') === -1)
        return n;
    return '"'+ n.replace('.', ',')+'"';
};

$(document).ready(function() {
    Chart.register(ChartDataLabels);

    let formatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    let d = getDateFromD(stats.tableauDeBord.data.dates.pas_apres_cette_date_cette_annee);
    $('#activity').text("Activité arrêtée au "+formatter.format(d));
    displayLicence();
    displayMoyensLancement();
    let hdv_club_et_banalise = displayHdv('hdv_club_et_banalise', 'hdv_club_et_banalise', 'Heures de vol club+banalisé');
    displayHdv('hdv_club', 'hdv_club', 'Heures de vol club', hdv_club_et_banalise.scales.y.max);
    displayViClub();

    Object.keys(stats.tableauDeBordAnnuel).forEach(function(key) {
        let begin = stats.tableauDeBordAnnuel[key].params.annee - key;
        let end = stats.tableauDeBordAnnuel[key].params.annee - 1;
        if (parseInt(key) === 1)
            $('#moyenneAnnees').append($('<option>', { value: key }).text("Comparer par rapport à l'année dernière ("+end+")"));
        else
            $('#moyenneAnnees').append($('<option>', { value: key }).text("Comparer sur les "+key+" dernières années ("+begin+" - "+end+")"));
    });
    $('#moyenneAnnees').val(2);
    $('#moyenneAnnees').change(function() {
        displayBudgetRevenus();
        displayBudgetDepenses();
        displayBudgetResultat();
        displayBudgetResultatBarGraph();
        displayLicenceAnnuel();
        displayValoInfraAnnuel();
        displayDepensesGeneralesAnnuel();
        displayDepensesHdV();
        displayRevenusHdV();
        displayHDVClubAnnuel();
        displayHDVClubCDBAnnuel();
        displayHDVClubInstructionAnnuel();

        displayHDVBanaliseAnnuel();
        displayHDVBanaliseNonProprietaireAnnuel();

        displayHDVPilotesDansForfaitAnnuel();
        displayHDVPilotesHorsForfaitAnnuel();

        displayLancementEtValoRemorqueAnnuel();
        displayRemorqueAnnuel();
        displayVentilationSelonRemorqueur();
        displayLancementAnnuel();

        displayValoCelluleEtForfaitAnnuel();
        displayValoJdStageAnnuel();
        displayValoVolAnnuel();
        displayValoMoteurAnnuel();
        displayRevenusMoyensLancement();
        displayDepensesMoyensLancement();
        displayRevenusMairie();
        displayDepensesMairie();
    });
    $('#moyenneAnnees').trigger('change');

    displayCFE();

    $('#downloadDataSource').click(function() {
        downloadDataAsCSV();
    });
});
