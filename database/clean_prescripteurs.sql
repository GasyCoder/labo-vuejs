-- Nettoyage et déduplication de prescripteurs
-- Compatible MySQL 8+
-- Basé sur la structure de la table `prescripteurs`
-- Ce script supprime les entrées génériques (DR, PR, SF, cliniques/structures isolées, etc.)
-- puis normalise et insère les personnes distinctes.

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_prescripteurs_raw;
CREATE TEMPORARY TABLE tmp_prescripteurs_raw (
    raw_name VARCHAR(255) NOT NULL
);

-- 1) Collez ici la liste brute complète
-- Exemple:
-- INSERT INTO tmp_prescripteurs_raw (raw_name) VALUES
-- ('DR RABARIJAONA CLARA'),
-- ('Dr RABARIJAONA Clara'),
-- ('DR RABARIJAOANA CLARA');


DROP TEMPORARY TABLE IF EXISTS tmp_prescripteurs_clean;
CREATE TEMPORARY TABLE tmp_prescripteurs_clean AS
SELECT
    TRIM(
        REGEXP_REPLACE(
            REPLACE(REPLACE(REPLACE(raw_name, '\r', ' '), '\n', ' '), '\t', ' '),
            '[[:space:]]+',
            ' '
        )
    ) AS raw_name
FROM tmp_prescripteurs_raw
WHERE raw_name IS NOT NULL
  AND TRIM(raw_name) <> '';

-- 2) Uniformiser la casse minimale
ALTER TABLE tmp_prescripteurs_clean ADD COLUMN normalized VARCHAR(255) NULL;
UPDATE tmp_prescripteurs_clean
SET normalized = UPPER(TRIM(raw_name));

-- 3) Corriger quelques fautes/variantes évidentes
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'CLINQUE', 'CLINIQUE');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'LEJOURDAIN', 'LE JOURDAIN');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'FANANTENANANA', 'FANANTENANA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'FANANATENANA', 'FANANTENANA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'AHZARAMI', 'AHZRAMI');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'AHZARMI', 'AHZRAMI');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'ANDRIAMIHAISOA', 'ANDRIAMIHARISOA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'ANDRIMIHARISOA', 'ANDRIAMIHARISOA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'RABARIJAOANA', 'RABARIJAONA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'RABARIJONA', 'RABARIJAONA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'RAKOTOAZANANY', 'RAKOTOZANANY');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'RAJONHSON', 'RAJONSON');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'CHRISTOHPE', 'CHRISTOPHE');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'JAEQUES', 'JACQUES');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'NICOALS', 'NICOLAS');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'AIMEE', 'AIMEE');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'NOMEJANAHARY', 'NOMENJANAHARY');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'FENOARIVO', 'FENORAVO');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'PIERETTE', 'PIERRETTE');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'RALAIARITINA', 'RALAIARITIANA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'DAYALI', 'DAYALJI');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'DONALDI', 'DONATIEN');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'SOANIAINA', 'SOANIAINA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'SOANIANA', 'SOANIAINA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'MAMITIANA', 'MAMIATIANA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'HARINAINA', 'HARINIAINA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'SAHONDRANIAINA', 'SAHONDRANIRINA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'SAHONDRA', 'SAHONDRANIRINA')
WHERE normalized REGEXP '^DR RAKOTOARISOA SAHONDRA$';
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'GABRIELLA', 'GABRIELLE');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'SARAH', 'SARA');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'FELICIEN', 'FELICIEN');
UPDATE tmp_prescripteurs_clean SET normalized = REPLACE(normalized, 'WILLIAMES', 'WILLIAMES');

-- 4) Supprimer ponctuation/séparateurs parasites
UPDATE tmp_prescripteurs_clean
SET normalized = TRIM(REGEXP_REPLACE(normalized, '[[:space:]]+', ' '));

-- 5) Supprimer lignes génériques ou structures non-personnes
DROP TEMPORARY TABLE IF EXISTS tmp_prescripteurs_filtered;
CREATE TEMPORARY TABLE tmp_prescripteurs_filtered AS
SELECT *
FROM tmp_prescripteurs_clean
WHERE normalized <> ''
  AND normalized NOT IN (
      'DR','PR','PROF','PROFESSEUR','SF','INF',
      'CMC','CTB','SEMS','SMIM','C-LAB','LABO +','SOS MEDECIN',
      'ESPACE MEDICAL','DR ESPACE MEDICAL',
      'CLINIQUE LE JOURDAIN','CLINIQUE FANANTENANA','CLINIQUE LE FANANTENANA',
      'ASSURANCE NY HAVANA','CLINIQUE FANANTENANANA'
  )
  AND normalized NOT REGEXP '^(DR|PR|PROF|PROFESSEUR|SF|INF)[[:space:]]*$'
  AND normalized NOT REGEXP '^(CLINIQUE|ESPACE|ASSURANCE|LABO|SOS|SEMS|SMIM|CMC|CTB)([[:space:]].*)?$';

-- 6) Déduire grade et statut
ALTER TABLE tmp_prescripteurs_filtered
    ADD COLUMN grade VARCHAR(50) NULL,
    ADD COLUMN status_enum VARCHAR(50) NULL,
    ADD COLUMN person_name VARCHAR(255) NULL,
    ADD COLUMN nom VARCHAR(255) NULL,
    ADD COLUMN prenom VARCHAR(255) NULL,
    ADD COLUMN dedupe_key VARCHAR(255) NULL;

UPDATE tmp_prescripteurs_filtered
SET grade = CASE
    WHEN normalized REGEXP '^DR[[:space:]]+' THEN 'DR'
    WHEN normalized REGEXP '^PR[[:space:]]+' THEN 'PR'
    WHEN normalized REGEXP '^PROF(ESSEUR)?[[:space:]]+' THEN 'PR'
    WHEN normalized REGEXP '^SF[[:space:]]+' THEN 'SF'
    WHEN normalized REGEXP '^INF[[:space:]]+' THEN 'INF'
    ELSE NULL
END;

UPDATE tmp_prescripteurs_filtered
SET status_enum = CASE
    WHEN grade = 'PR' THEN 'Professeur'
    WHEN grade = 'INF' OR grade = 'SF' THEN 'Infirmier'
    ELSE 'Medecin'
END;

UPDATE tmp_prescripteurs_filtered
SET person_name = TRIM(
    REGEXP_REPLACE(
        normalized,
        '^(DR|PR|PROF|PROFESSEUR|SF|INF)[[:space:]]+',
        ''
    )
);

-- 7) Écarter les noms trop courts / manifestement incomplets
DELETE FROM tmp_prescripteurs_filtered
WHERE person_name IS NULL
   OR person_name = ''
   OR person_name REGEXP '^[A-Z]{1,3}$'
   OR person_name IN (
       'AGNES','CHRISTOPHE','CYNTHIA','DELS','DINA','DONATIEN','ELIANE','FAZY','FIDELE',
       'FRANCIS','GERMAIN','GINIE','JUDICAEL','KOLOINA','LANTO','MAHAFALY','MARTIN',
       'MARTINIE','MOHAMED','NICOLAS','NJAKA','ROMULE','THEODORE','THIERRY','TOJOMAMY',
       'VELONJARA','VICTOR','VOLAREMINA','WILLY','ZAFIZARA','ZAVAISOA','PIERRETTE'
   );

-- 8) Normalisations ciblées de variantes connues
UPDATE tmp_prescripteurs_filtered SET person_name = 'ANDRIAMIHARISOA STEPHANIE' WHERE person_name IN (
    'ANDRIAMIARISOA STEPHANIE',
    'ANDRIAMIHARISOA STEPHANIE',
    'ANDRIAMIHARISOA STEPHANIE N',
    'ANDRIAMIHARISOA STEPHANIE NOROTIANA',
    'STEPHANIE',
    'STEPHANIE N'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'AHZRAMI IBRAHIM' WHERE person_name IN (
    'AHAZRAMI IBRAHIM','AHZRAMI IBDRAHIM','AHZRAMI IBRAHIM','AHZRAMI IBRAHIM ','AHZRAMI  IBRAHIM'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'FANONJOMAHASOA SAFIRY' WHERE person_name IN (
    'FANONJOMAHASOA SAFIRY','FANOJOMAHASOA SAFIRY','FANONJOMAHASAO SAFIRY','FANONJOMAHASOA SAFIDY'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RABARIJAONA CLARA' WHERE person_name IN (
    'RABARIJAONA CLARA','RABARIJAONA CLARA JOYCE','RABARIJAONA CLARA JOYCE',
    'RABARIJAONA CLARA','RABARIJAONA CLARA','RABARIJAONA CLARA',
    'RABARIJAONA CLARA','RABARIJAONA CLARA'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RAHAJARISOA ADRIENNE YVETTE' WHERE person_name IN (
    'RAHAJARISOA ADRIENNE YVETTE','RAHAJARISOA ADRIENNE YVETTE '
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RAHAMEFY ODILON' WHERE person_name IN (
    'RAHAMEFY ODILON','RAHAMEFY'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'JOLIVET RAKOTOMALALA' WHERE person_name IN (
    'JOILIVET RAKOTOMALALA','JOLIVET RAKATOMALALA','JOLIVET RAKOTOAMALALA',
    'JOLIVET RAKOTOMALAL','JOLIVET RAKOTOMALALA','RAKOTOMALALA JOLIVET'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RAKOTOARISOA CHRISTOPHE' WHERE person_name IN (
    'RAKOTOARISOA CHRISTOHPE','RAKOTOARISOA CHRISTOPHE'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RAKOTOZANANY LANTOMALALA' WHERE person_name IN (
    'RAKOTOZANANY LANTOMALALA','RAKOTOAZANANY LANTOMALALA','RAKOTOZANANY LANTOMALALA MIREILLE PIERRETTE'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RALAIZAFINDRAIBE TOJOMAMY' WHERE person_name IN (
    'RALAIZAFINDRAIBE TOJOMAMY','RALAIZAFINDRAIBE TOJOMAMAY'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RANAIVO MARIE JOELLE' WHERE person_name IN (
    'RANAIVO MARIE JOELLE','RANAIVO MARIE  JOELLE','RANAIVO MARIE JOELLE '
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RANDRIANANDRASANA JANE CLARISSE' WHERE person_name IN (
    'ANDRIANANDRASANA JANE CLARISSE','RANDRIANANDRASANA JANE CLARISSE','RANDRIANANDRASANA JANE CLARISSE'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RANDRIAVONONA MAHAVITA ELIASY' WHERE person_name IN (
    'RANDRIAVONONA MAHAVITA ELIASY'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'RAZAIMANANA GEORGES FIDELE' WHERE person_name IN (
    'RAZAIMANANA GEORGE FIDELE','RAZAIMANANA GEORGES FIDELE','RAZIMANANA GEORGES FIDELE','GEORGES FIDELE'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'REFENO VALERY' WHERE person_name IN (
    'REFENO VALERY','REFENO VALÉRY','VALERY'
);

UPDATE tmp_prescripteurs_filtered SET person_name = 'SOANIAINA LUCIA' WHERE person_name IN (
    'SOANAINA LUCIA','SOANIAINA LUCIA'
);

-- 9) Séparer nom / prénom de façon simple
-- Hypothèse: le premier token = nom, le reste = prénom
UPDATE tmp_prescripteurs_filtered
SET nom = SUBSTRING_INDEX(person_name, ' ', 1),
    prenom = NULLIF(TRIM(SUBSTRING(person_name, LENGTH(SUBSTRING_INDEX(person_name, ' ', 1)) + 1)), '');

-- 10) Clé de dédoublonnage
UPDATE tmp_prescripteurs_filtered
SET dedupe_key = REGEXP_REPLACE(CONCAT_WS(' ', nom, prenom), '[^A-Z0-9]', '');

-- 11) Prévisualisation des doublons potentiels
-- SELECT dedupe_key, COUNT(*) c, GROUP_CONCAT(DISTINCT person_name ORDER BY person_name SEPARATOR ' | ')
-- FROM tmp_prescripteurs_filtered
-- GROUP BY dedupe_key
-- HAVING COUNT(*) > 1;

-- 12) Insertion finale distincte
INSERT INTO prescripteurs (
    grade, nom, prenom, status, telephone, is_active, is_commissionned,
    notes, commission_quota, commission_pourcentage, created_at, updated_at
)
SELECT
    grade,
    nom,
    prenom,
    status_enum,
    NULL,
    1,
    1,
    CONCAT('Import nettoyé depuis liste brute: ', MIN(raw_name)),
    250000.00,
    10.00,
    NOW(),
    NOW()
FROM tmp_prescripteurs_filtered
WHERE nom IS NOT NULL
  AND nom <> ''
GROUP BY dedupe_key, grade, nom, prenom, status_enum
ORDER BY nom, prenom;

COMMIT;
