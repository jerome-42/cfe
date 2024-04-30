CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value NUMERIC) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value TEXT) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value INTERVAL) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION setVarInData(data JSONB, key TEXT, value JSONB) RETURNS JSONB AS $$
BEGIN
IF value IS NULL THEN
   data := data - key;
ELSE
   data := data || CONCAT('{"', key, '": ', to_json(value), '}')::jsonb;
END IF;
RETURN data;
END;
$$ LANGUAGE plpgsql VOLATILE;


-- date_debut est inclusif
-- date_fin est inclusif
-- select * from statsMachines('2024-01-01', '2024-12-31');
-- D-KBJP          | {"R": {"nb_vol": 2, "temps_vol": "09:02:00"}, "global": {"nb_vol": 2, "temps_vol": "09:02:00"}, "1 Vol en solo": {"nb_vol": 2, "temps_vol": "09:02:00"}}
-- D-KOCM          | {"M": {"nb_vol": 3, "temps_vol": "06:38:00"}, "R": {"nb_vol": 2, "temps_vol": "06:27:00"}, "global": {"nb_vol": 5, "temps_vol": "13:05:00"}, "1 Vol en solo": {"nb_vol": 5, "temps_vol": "13:05:00"}}
--  F-CHIC         | {"R": {"ca": 3605.72, "nb_vol": 89, "temps_vol": "47:18:00"}, "T": {"ca": 625.22, "nb_vol": 39, "temps_vol": "10:38:00"}, "global": {"ca": 4230.94, "nb_vol": 128, "temps_vol": "57:56:00"}, "41 VI perso": {"nb_vol": 1, "temps_vol": "00:21:00"}, "1 Vol en solo": {"nb_vol": 11, "temps_vol": "05:26:00"}, "3 Vol partagé": {"nb_vol": 4, "temps_vol": "03:57:00"}, "2 Vol d'instruction": {"nb_vol": 110, "temps_vol": "47:21:00"}, "9 Vol journée découverte": {"nb_vol": 2, "temps_vol": "00:51:00"}}
CREATE OR REPLACE FUNCTION statsMachines(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_vol record;
  js jsonb;
  types_vol TEXT[];
  type_vol TEXT;
  machines TEXT[];
  mise_en_l_air TEXT;
  mises_en_l_air TEXT[] := '{"R", "T", "M"}';
  sub_json jsonb;
BEGIN
  FOR r IN SELECT vfr_vol.nom_type_vol FROM vfr_vol WHERE date_vol BETWEEN date_debut AND date_fin GROUP BY vfr_vol.nom_type_vol
  LOOP
    types_vol := array_append(types_vol, r.nom_type_vol);
  END LOOP;

  FOR r IN SELECT vfr_vol.id_aeronef, vfr_vol.immatriculation FROM vfr_vol
    JOIN aeronef ON aeronef.id_aeronef = vfr_vol.id_aeronef
    WHERE
    date_fin BETWEEN date_debut AND date_fin AND aeronef.actif IS true
    GROUP BY vfr_vol.id_aeronef, vfr_vol.immatriculation ORDER BY vfr_vol.immatriculation
  LOOP
    immatriculation := r.immatriculation;
    stats := '{}';

    -- nb vol & CA global
    SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
      SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
        SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
        SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef;
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, 'global', sub_json);

    -- stats par moyen de mise en l'air
    FOREACH mise_en_l_air IN ARRAY mises_en_l_air
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
          SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
          SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.mode_decollage = mise_en_l_air;
      IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, mise_en_l_air, sub_json);
      END IF;
    END LOOP;
      

    -- stats par type de vol
    FOREACH type_vol IN ARRAY types_vol
    LOOP
      SELECT INTO r_vol COUNT(*) AS nb_vol, SUM(temps_vol) AS temps_vol,
        SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) +
          SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) +
          SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca FROM vfr_vol
          WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_aeronef = r.id_aeronef AND vfr_vol.nom_type_vol = type_vol;
      IF r_vol.nb_vol > 0 OR r_vol.ca > 0 THEN
        sub_json := '{}';
        sub_json := setVarInData(sub_json, 'nb_vol', r_vol.nb_vol);
        sub_json := setVarInData(sub_json, 'temps_vol', r_vol.temps_vol);
        sub_json := setVarInData(sub_json, 'ca', r_vol.ca);
        stats := setVarInData(stats, type_vol, sub_json);
      END IF;
    END LOOP;

    -- TODO stats sur les vols propriétaires
    -- TODO stats sur les vols au forfait
    return NEXT;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- select * from statsMisesEnLAir('2024-01-01', '2024-12-31');
--  immatriculation |                                                                                                                                                                     stats                                                                                                                                                                      
-- -----------------+------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
--  F-JDTX          | {"750m": {"ca": 79.80, "nb_vol": 3}, "global": {"ca": 585.00, "nb_vol": 26}, "Remorqué standard - 500m": {"ca": 505.20, "nb_vol": 23}}
--  F-GEKY          | {"750m": {"ca": 2920.34, "nb_vol": 97}, "1000m": {"ca": 378.27, "nb_vol": 11}, "global": {"ca": 10479.80, "nb_vol": 405}, "voltige - 1300m": {"ca": 136.01, "nb_vol": 3}, "Demi-remorqué - 250m": {"ca": 164.43, "nb_vol": 11}, "Dépannage Etrépagny": {"ca": 58.00, "nb_vol": 1}, "Remorqué standard - 500m": {"ca": 6822.75, "nb_vol": 282}}
--  treuil          | {"ca": 1308.00, "nb_vol": 165}
CREATE OR REPLACE FUNCTION statsMisesEnLAir(date_debut date, date_fin date) returns table (
  immatriculation varchar,
  stats jsonb
  ) AS
$$
DECLARE
  r record;
  r_vol record;
  js jsonb;
  sub_js jsonb;
  machines TEXT[];
  sub_json jsonb;
BEGIN
  FOR r IN SELECT aeronef.id_aeronef, aeronef.immatriculation FROM aeronef WHERE actif IS TRUE AND remorqueur IS TRUE
  LOOP
    immatriculation := r.immatriculation;
    stats := '{}';
    SELECT INTO r_vol COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef;
    sub_js := '{}';
    sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
    sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
    stats := setVarInData(stats, 'global', sub_js);
    FOR r_vol IN SELECT libelle_remorque, COUNT(*) AS nb_vol,
      SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_remorque_co, 0)) AS ca FROM vfr_vol
        WHERE date_vol BETWEEN date_debut AND date_fin AND vfr_vol.id_remorqueur = r.id_aeronef GROUP BY libelle_remorque
    LOOP
      sub_js := '{}';
      sub_js := setVarInData(sub_js, 'nb_vol', r_vol.nb_vol);
      sub_js := setVarInData(sub_js, 'ca', r_vol.ca);
      stats := setVarInData(stats, r_vol.libelle_remorque, sub_js);
    END LOOP;

    return NEXT;
  END LOOP;

  immatriculation := 'treuil';
  stats := '{}';
  SELECT INTO r_vol COUNT(*) AS nb_vol,
    SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_treuil_co, 0)) AS ca FROM vfr_vol
      WHERE date_vol BETWEEN date_debut AND date_fin AND mode_decollage = 'T';
  stats := setVarInData(stats, 'nb_vol', r_vol.nb_vol);
  stats := setVarInData(stats, 'ca', r_vol.ca);
  return NEXT;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- on retourne le id_tarif_type_date qui correspond à un vol
CREATE OR REPLACE FUNCTION getTarifTypeCondId(input_id_aeronef INT, input_id_tarif_type INT, input_date_vol DATE) RETURNS NUMERIC AS $$
DECLARE
  r record;
  last_id NUMERIC;
BEGIN
  FOR r IN SELECT id_tarif_type_date, tarif_type_date.date_application FROM tarif_type_date
    JOIN tarif_cat_aeronef ON tarif_cat_aeronef.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
    JOIN aeronef_situation ON aeronef_situation.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
    WHERE aeronef_situation.id_aeronef = input_id_aeronef AND tarif_type_date.id_tarif_type = input_id_tarif_type
    ORDER BY date_application ASC
  LOOP
    IF r.date_application >= input_date_vol THEN
      RETURN last_id;
    END IF;
    last_id := r.id_tarif_type_date;
  END LOOP;
  RETURN last_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION getTarifDetails(input_id_aeronef INT, input_id_tarif_type INT, input_date_vol DATE) RETURNS INT AS $$
DECLARE
  r RECORD;
  last_id INT;
BEGIN
  FOR r IN
    SELECT id_tarif_type_date, tarif_type_date.date_application FROM tarif_type_date
      JOIN tarif_cat_aeronef ON tarif_cat_aeronef.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
      JOIN aeronef_situation ON aeronef_situation.id_tarif_cat_aeronef = tarif_type_date.id_tarif_cat_aeronef
      WHERE aeronef_situation.id_aeronef = input_id_aeronef AND tarif_type.id_tarif_type = id_tarif_type
      ORDER BY date_application ASC
  LOOP
    IF r.date_application >= input_date_vol THEN
      RETURN last_id;
    END IF;
    last_id := r.id_tarif_type_date;
  END LOOP;
  RETURN last_id;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION getPrixHorairePourVol(input_nom_type VARCHAR, input_id_aeronef INT, input_date_vol DATE) RETURNS RECORD AS $$
DECLARE
  v_id_tarif_type INT := NULL;
  v_parent_id INT;
  r RECORD;
  v_id_tarif_type_cond INT;
  r_tarif_type_cond RECORD;
  ret RECORD;
BEGIN
  SELECT INTO r id_tarif_type, id_tarif_type_maitre FROM tarif_type WHERE nom_type = input_nom_type LIMIT 1;
  v_id_tarif_type := r.id_tarif_type;
  v_parent_id := r.id_tarif_type_maitre;
  --DEBUG RAISE NOTICE 'le tarif de base est: % (id: % parent: %)', input_nom_type, v_id_tarif_type, v_parent_id;
  WHILE true LOOP
    -- on charge id_tarif_type_cond
    v_id_tarif_type_cond := getTarifTypeCondId(input_id_aeronef, v_id_tarif_type, input_date_vol);
    --DEBUG RAISE NOTICE 'id_tarif_type_cond: %', v_id_tarif_type_cond;
    -- on a peut-être un prix
    SELECT * INTO r_tarif_type_cond FROM tarif_type_cond
    JOIN tarif_type_vol ON tarif_type_vol.id_tarif_type_vol = tarif_type_cond.id_tarif_type_vol
    WHERE id_tarif_type_date = v_id_tarif_type_cond AND tarif_type_vol.nom_type_vol = '1 Vol en solo' LIMIT 1;
    IF FOUND THEN
      --DEBUG RAISE NOTICE 'on a trouvé un prix %/heure et id_tarif_tranche_vol: %', r_tarif_type_cond.prix_heure, r_tarif_type_cond.id_tarif_tranche_vol;
      ret := (r_tarif_type_cond.id_tarif_tranche_vol::INT, r_tarif_type_cond.prix_heure::NUMERIC);
      RETURN ret;
    END IF;
    --DEBUG RAISE NOTICE 'pas de tarif pour ce vol on charge le tarif parent';

    -- si on n'a pas trouvé, on charge le tarif parent
    IF v_parent_id IS NULL THEN -- si pas de tarif parent, on ne peut pas caluler le tarif du vol !
      SELECT * INTO r FROM aeronef WHERE id_aeronef = input_id_aeronef;
      RAISE WARNING 'pas de tarif pour nom_type=% id_aeronef=% [% - %] date_vol=%', input_nom_type, input_id_aeronef, r.immatriculation, r.nom_type, input_date_vol;
      RETURN NULL;
    END IF;

    -- on charge le tarif parent
    SELECT INTO r id_tarif_type, id_tarif_type_maitre, nom_type FROM tarif_type WHERE id_tarif_type = v_parent_id LIMIT 1;
    --DEBUG RAISE NOTICE '% chargé', r.nom_type;
    v_id_tarif_type := r.id_tarif_type;
    v_parent_id := r.id_tarif_type_maitre;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

-- TODO déplacer catégorie ici
CREATE OR REPLACE FUNCTION calculPrixVol(input_id_pilote INT, input_id_aeronef INT, input_date_vol DATE, temps_vol INTERVAL) RETURNS NUMERIC AS $$
DECLARE
  nom_type TEXT;
  r_pilote record;
  r_prix RECORD;
  r_tarif_type_cond record;
  r_tranche_item record;
  prix NUMERIC := 0;
  temps_vol_dans_item INTERVAL;
  tarif record;
BEGIN
  -- on récupère la catégorie du pilote (-25 ans ou +25 ans)
  SELECT * INTO r_pilote FROM vfr_pilote WHERE id_personne = input_id_pilote LIMIT 1;
  --RAISE NOTICE 'categorie: % %: %', r_pilote.nom, r_pilote.prenom, r_pilote.cat_age;
  IF r_pilote.cat_age = '-25 ans' THEN
    nom_type = 'Tarif général junior';
  ELSE
    nom_type = 'Tarif général';
  END IF;

  RAISE NOTICE 'id_pilote=% categorie: %', input_id_pilote, nom_type;

  SELECT * INTO r_prix FROM getPrixHorairePourVol(nom_type, input_id_aeronef, input_date_vol) AS (id_tarif_tranche_vol INT, prix_heure NUMERIC);
  RAISE NOTICE 'prix heure de vol: %', r_prix;
  IF r_prix IS NULL THEN
    RETURN 0;
  END IF;

  FOR r_tranche_item IN SELECT * FROM tarif_tranche_item WHERE id_tarif_tranche = r_prix.id_tarif_tranche_vol
  LOOP
    IF temps_vol > '0:0:0'::interval THEN
      IF temps_vol > r_tranche_item.plafond THEN
        temps_vol_dans_item := r_tranche_item.plafond;
        temps_vol := temps_vol - r_tranche_item.plafond;
      ELSE
        temps_vol_dans_item := temps_vol;
        temps_vol := 0;
      END IF;
      RAISE NOTICE 'prix pour % coef %: %', temps_vol_dans_item, r_tranche_item.coefficient, r_prix.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
      prix := prix + r_prix.prix_heure * r_tranche_item.coefficient * EXTRACT(epoch FROM temps_vol_dans_item)/3600;
    END IF;
  END LOOP;

  return ROUND(prix, 2);
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION calculVolsSiHorsForfait(input_id_pilote INT, annee INT) RETURNS NUMERIC AS $$
DECLARE
  r_vol record;
  id_tarif_type_date NUMERIC;
  prix_vols NUMERIC := 0;
  prix_du_vol NUMERIC;
BEGIN
  -- prix_vol est à 0 lorsque le vol est gratuit et c'est saisi par les secrétaires (par exemple: casse cable, vol d'essai)
  FOR r_vol IN SELECT * FROM vfr_vol
    WHERE saison = annee and id_cdt_de_bord = input_id_pilote and prix_vol_cdb = 0 AND prix_vol IS NULL
  LOOP
    RAISE NOTICE 'calcul du prix pour date=% pilote=[%] id_aeronef=% (%): temps_vol=%', r_vol.date_vol, r_vol.cdt_de_bord, r_vol.id_aeronef, r_vol.immatriculation, r_vol.temps_vol;
    prix_du_vol := calculPrixVol(r_vol.id_cdt_de_bord, r_vol.id_aeronef, r_vol.date_vol, r_vol.temps_vol);
    RAISE NOTICE 'prix_du_vol: %', prix_du_vol;
    prix_vols := prix_vols + prix_du_vol;
  END LOOP;

  RETURN prix_vols;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION statsForfait(annee int) returns table (
  nom_forfait varchar,
  date_debut date,
  date_fin date,
  montant_forfait numeric,
  conso_forfait interval,
  prix_vols numeric,
  pilote varchar,
  ca_eleve numeric,
  ca_cdb numeric, -- prix mise en l'air + prix vols hors forfait
  ca_co numeric,
  ca_cette_annee numeric,
  ca_si_pas_forfait numeric
  ) AS
$$
DECLARE
  r_forfait record;
  r_vol record;
  ca NUMERIC;
BEGIN
  -- parmis les forfaits des pilotes, il y a des forfaits stage découverte, JD ...
  -- les JD ... ont des hrs_cellules fixés et pas illimités
  -- donc pour sortir les forfaits qui intéressant il faut filtrer par hrs_cellules
  FOR r_forfait IN SELECT * FROM vfr_forfait_pilote
    JOIN vfr_gv_personne ON vfr_forfait_pilote.id_personne = vfr_gv_personne.id_personne
    WHERE EXTRACT(YEAR FROM vfr_forfait_pilote.date_debut) = annee AND vfr_forfait_pilote.hrs_cellule = '999:00:00' ORDER BY vfr_forfait_pilote.nom_forfait
  LOOP
    nom_forfait := r_forfait.nom_forfait;
    date_debut := r_forfait.date_debut;
    date_fin := r_forfait.date_fin;
    montant_forfait := r_forfait.montant;
    conso_forfait := r_forfait.conso_hrs_cellule;
    prix_vols := calculVolsSiHorsForfait(r_forfait.id_personne, annee);
    pilote := CONCAT(r_forfait.prenom, ' ', r_forfait.nom)::varchar;

    -- CA élève
    SELECT INTO r_vol SUM(COALESCE(prix_vol_elv, 0)) + SUM(COALESCE(prix_treuil_elv, 0)) + SUM(COALESCE(prix_remorque_elv, 0)) + SUM(COALESCE(prix_moteur_elv, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_eleve
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_eleve := r_vol.ca;

    SELECT INTO r_vol SUM(COALESCE(prix_vol_cdb, 0)) + SUM(COALESCE(prix_treuil_cdb, 0)) + SUM(COALESCE(prix_remorque_cdb, 0)) + SUM(COALESCE(prix_moteur_cdb, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_cdt_de_bord
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_cdb := r_vol.ca;

    SELECT INTO r_vol SUM(COALESCE(prix_vol_co, 0)) + SUM(COALESCE(prix_treuil_co, 0)) + SUM(COALESCE(prix_remorque_co, 0)) + SUM(COALESCE(prix_moteur_co, 0)) AS ca
    FROM vfr_vol
    JOIN pilote ON pilote.id_personne = vfr_vol.id_co_pilote
    WHERE saison = annee AND pilote.id_personne = r_forfait.id_personne;
    ca_co := r_vol.ca;

    ca_cette_annee := COALESCE(ca_eleve, 0) + COALESCE(ca_cdb, 0) + COALESCE(ca_co, 0) + COALESCE(montant_forfait, 0);
    ca_si_pas_forfait := COALESCE(ca_eleve, 0) + COALESCE(ca_cdb, 0) + COALESCE(ca_co, 0) + COALESCE(prix_vols, 0);

    RETURN next;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION statsMembre(annee INT) returns table (
  nom varchar,
  stats jsonb
  ) AS
$$
DECLARE
  sub_json jsonb;
  r RECORD;
  r2 RECORD;
  r_type_vol RECORD;
  cout_vol_si_machine_club NUMERIC := 0;
  loyer NUMERIC;
  montant_vol NUMERIC;
  prix_du_vol NUMERIC;
  a_deduire NUMERIC;
  nb_vols NUMERIC;
  temps_vols INTERVAL;
BEGIN
  FOR r IN SELECT pi.id_pilote, pe.id_personne, pe.nom, pe.prenom, pi.cat_age, pi.id_compte, pi.licence_saison, pi.licence_nom, pi.solde
    FROM vfr_pilote pi
    JOIN gv_personne pe ON pe.id_personne = pi.id_personne
    WHERE pilote_actif_3 IS TRUE AND pi.id_compte = 2300
    ORDER BY pe.nom -- TODO randomize
    LOOP
    stats := '{}';
    -- TODO anonymisation
    nom := r.nom;
    IF r.prenom IS NOT NULL THEN
      nom := r.prenom || ' ' || r.nom;
    END IF;
    RAISE NOTICE '%', nom;
    stats := setVarInData(stats, 'solde', r.solde);
    stats := setVarInData(stats, 'id_compte', r.id_compte);
    -- pour les privés qui pratiquent la rétrocession, comme c'est un jeu à somme nulle, on la sort
    -- des montants facturés
    -- 1/ TODO sortir les vols où c'est le propriétaire qui vole sur sa machine -> ça donne le montant perçu par la location de la machine par les propriétaires
    -- 2/ TODO isoler les vols où c'est le propriétaire qui vole sur sa machine -> ça donne le montant qu'aurait payé le propriétaire pour voler sur la machine si la machine était club
    -- pour chaque vol on va voir si le pilote est propriétaire de la machine
    cout_vol_si_machine_club := 0;
    loyer := 0;
    a_deduire := 0; -- le pilote a des retrocessions vers son propre compte
    -- c'est un jeu à somme nulle, donc on doit déduire ces montants
    FOR r2 IN SELECT
        tv.nom_type_vol,
        cp_piece_ligne.montant,
        pvc.id_personne AS id_cdt_de_bord,
        piloteEstProprietaireDeMachine(pvc.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS cdt_est_proprietaire,
        piloteProprietaireDeMachinePourcentage(pvc.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS cdt_pourcentage,
        pvo.id_personne AS id_co,
        piloteEstProprietaireDeMachine(pvo.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS co_est_proprietaire,
        piloteProprietaireDeMachinePourcentage(pvo.id_personne, vol.id_aeronef, vol_pilote.date_vol) AS co_pourcentage,
	vol.id_aeronef, vol_pilote.date_vol
        FROM cp_piece
        JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
        JOIN vol_pilote ON vol_pilote.id_vol_pilote = cp_piece.id_vol_pilote
        JOIN vol ON vol.id_vol = vol_pilote.id_vol
        JOIN tarif_type_vol tv ON vol.id_tarif_type_vol = tv.id_tarif_type_vol
        LEFT JOIN vol_pilote pvc ON vol_pilote.id_vol = pvc.id_vol AND pvc.fonction = 1
        LEFT JOIN vol_pilote pvo ON vol_pilote.id_vol = pvo.id_vol AND pvo.fonction = 2
        WHERE cp_piece_ligne.id_compte = r.id_compte AND sens = 'C'
        AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee AND cp_piece.type = 'RETRO_CELLULE'
    LOOP
      --DEBUG RAISE NOTICE '%', r2;
      IF r2.cdt_est_proprietaire IS TRUE OR r2.co_est_proprietaire IS TRUE THEN
        a_deduire := a_deduire + r2.montant;
        -- le pilote est propriaitaire donc on calcule le coût de ces vols
        IF r2.cdt_est_proprietaire THEN
          prix_du_vol := (100 * r2.montant) / r2.cdt_pourcentage;
          RAISE NOTICE 'proprio cdb: % montant=% pourcentage=% prix_vol: %', r2, r2.montant, r2.cdt_pourcentage, prix_du_vol;
        ELSE
          prix_du_vol := (100 * r2.montant) / r2.co_pourcentage;
          RAISE NOTICE 'proprio co: % montant=% pourcentage=% prix_vol: %', r2, r2.montant, r2.co_pourcentage, prix_du_vol;
        END IF;
        IF r2.nom_type_vol = '3 Vol partagé' THEN
          RAISE NOTICE 'vol partagé donc prix_du_vol / 2';
          prix_du_vol := prix_du_vol / 2;
        END IF;
        cout_vol_si_machine_club := cout_vol_si_machine_club + ROUND(prix_du_vol, 2);

      ELSE -- le pilote n'est pas propriétaire donc c'est un loyer
        loyer := loyer + r2.montant;
      END IF;
    END LOOP;
    loyer := ROUND(loyer, 2);
    IF loyer > 0 THEN
      -- les pilotes non-propriétaires qui volent sur des machines proprio ont payés au proprio
      stats := setVarInData(stats, 'loyer', loyer);
    END IF;

    -- pour ceux qui n'ont pas de rétrocession il faut calculer le prix de leur vol comme si leurs machines
    -- étaient club
    FOR r2 IN SELECT * FROM vfr_vol
      WHERE saison = annee
      AND id_cdt_de_bord = r.id_personne
      AND prix_vol_cdb = 0
      AND prix_vol IS NULL
      AND piloteEstProprietaireDeMachine(id_cdt_de_bord, id_aeronef, date_vol) IS TRUE
    LOOP
      RAISE NOTICE 'calcul du prix pour date=% pilote=[%] id_aeronef=% (%): temps_vol=%', r2.date_vol, r2.cdt_de_bord, r2.id_aeronef, r2.immatriculation, r2.temps_vol;
      prix_du_vol := calculPrixVol(r.id_pilote, r2.id_aeronef, r2.date_vol, r2.temps_vol);
      RAISE NOTICE 'prix: %', prix_du_vol;
      cout_vol_si_machine_club := cout_vol_si_machine_club + prix_du_vol;
    END LOOP;


    cout_vol_si_machine_club := ROUND(cout_vol_si_machine_club, 2);
    IF cout_vol_si_machine_club > 0 THEN
      -- combien le propriétaire payerait si sa machine appartenait au club
      stats := setVarInData(stats, 'cout_vol_si_machine_club', cout_vol_si_machine_club);
    END IF;
    SELECT INTO r2 SUM(montant) AS montant FROM cp_piece
      JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
      WHERE id_compte = r.id_compte AND sens = 'D' AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee;
    stats := setVarInData(stats, 'debit', r2.montant - a_deduire);

    SELECT INTO r2 SUM(montant) AS montant FROM cp_piece
      JOIN cp_piece_ligne ON cp_piece_ligne.id_piece = cp_piece.id_piece
      WHERE id_compte = r.id_compte AND sens = 'C' AND EXTRACT(YEAR FROM cp_piece_ligne.date_piece) = annee;
    stats := setVarInData(stats, 'crédit', r2.montant - a_deduire);

    nb_vols := 0;
    temps_vols := 0;
    FOR r_type_vol IN SELECT vfr_vol.nom_type_vol
      FROM vfr_vol
      WHERE EXTRACT(YEAR FROM date_vol) = annee
      AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
      GROUP BY vfr_vol.nom_type_vol
    LOOP
      sub_json := '{}';
      -- heures de vols
      SELECT INTO r2 COUNT(*) AS nombre, SUM(temps_vol) AS duree
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND nom_type_vol = r_type_vol.nom_type_vol;

      sub_json := setVarInData(sub_json, 'nb_vol', r2.nombre);
      sub_json := setVarInData(sub_json, 'duree_vol', r2.duree);
      stats := setVarInData(stats, r_type_vol.nom_type_vol, sub_json);
      nb_vols := nb_vols + r2.nombre;
      temps_vols := temps_vols + r2.duree;
    END LOOP;
    stats := setVarInData(stats, 'nb_vol', nb_vols);
    stats := setVarInData(stats, 'duree_vol', temps_vols);

    -- nombre de remorqués
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'R';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_remorques', r2.nombre);
      END IF;

    -- nombre de treuillées
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'T';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_treuillees', r2.nombre);
      END IF;

    -- nombre autonome
      SELECT INTO r2 COUNT(*) AS nombre
        FROM vfr_vol
        WHERE EXTRACT(YEAR FROM date_vol) = annee
        AND (id_cdt_de_bord = r.id_personne OR id_co_pilote = r.id_personne OR id_eleve = r.id_personne)
        AND mode_decollage = 'M';
      IF r2.nombre > 0 THEN
        stats := setVarInData(stats, 'nb_autonome', r2.nombre);
      END IF;

    RETURN next;
  END LOOP;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION piloteEstProprietaireDeMachine(input_id_personne INT, input_id_aeronef INT, input_date_vol DATE) RETURNS BOOLEAN AS $$
DECLARE
  r RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
  LOOP
    IF input_date_vol >= r.date_application THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN false;
  END IF;
  SELECT INTO r * FROM aeronef_situation_benef
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
    AND aeronef_situation_benef.id_personne = input_id_personne;
  --DEBUG RAISE NOTICE 'id_aeronef_situation: %: %', v_id_aeronef_sitation, r;
  IF FOUND THEN
    RETURN TRUE;
  END IF;
  RETURN FALSE;
END;
$$ LANGUAGE plpgsql VOLATILE;

CREATE OR REPLACE FUNCTION piloteProprietaireDeMachinePourcentage(input_id_personne INT, input_id_aeronef INT, input_date_vol DATE) RETURNS NUMERIC AS $$
DECLARE
  r RECORD;
  last_id_aeronef_situation NUMERIC;
  v_id_aeronef_sitation NUMERIC;
BEGIN
  FOR r IN SELECT * FROM aeronef_situation
    WHERE aeronef_situation.id_aeronef = input_id_aeronef
  LOOP
    IF input_date_vol >= r.date_application THEN
      v_id_aeronef_sitation = r.id_aeronef_situation;
    END IF;
  END LOOP;
  IF v_id_aeronef_sitation IS NULL THEN
    RETURN 0;
  END IF;
  SELECT INTO r * FROM aeronef_situation_benef
    WHERE aeronef_situation_benef.id_aeronef_situation = v_id_aeronef_sitation
    AND aeronef_situation_benef.id_personne = input_id_personne;
  --DEBUG RAISE NOTICE 'id_aeronef_situation: %: %', v_id_aeronef_sitation, r;
  IF FOUND THEN
    RETURN r.pourcentage;
  END IF;
  RETURN 0;
END;
$$ LANGUAGE plpgsql VOLATILE;