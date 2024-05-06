let getStatsByMonth = function(s, mois) {
    if (mois < 10)
        mois = '0'+mois;
    for (let i = 0; i < s.data.length; i++) {
        if (s.data[i].d.indexOf('-'+mois+'-')) {
            return s.data[i].stats;
        }
    }
    throw new Error("impossible de trouver le mois "+mois+" dans stats");
};

let displayMoyensLancement = function() {
    let getNbVols = function(s, mois) {
        let s2 = getStatsByMonth(s, mois);
        return s2.M.nb_vol + s2.R.nb_vol + s2.T.nb_vol;
    };
    let data = {
        'février 2023': getNbVols(stats.statsAuCoursAnneePrecedente, 2),
        'février 2024': getNbVols(stats.statsAuCoursAnnee, 2),
    };
    console.log(data);
};

$(document).ready(function() {
    displayMoyensLancement();
});
