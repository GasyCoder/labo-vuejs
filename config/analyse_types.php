<?php

return [
    'MULTIPLE' => [
        'label_metier' => "Groupe d'analyses complexes",
        'description' => "Permet de regrouper plusieurs sous-types d'analyses au sein d'un même bloc.",
        'exemple' => 'Bilan hépatique complet',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => true],
    ],
    'TEST' => [
        'label_metier' => 'Test qualitatif rapide',
        'description' => "Saisie d'un résultat de test simple (ex: Positif/Négatif ou texte court).",
        'exemple' => 'Test de grossesse',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'CULTURE' => [
        'label_metier' => 'Culture bactériologique',
        'description' => 'Saisie spécifique pour les résultats de culture avec antibiogramme.',
        'exemple' => 'ECBU (Culture)',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'DOSAGE' => [
        'label_metier' => 'Dosage numérique',
        'description' => "Saisie d'une valeur numérique précise avec unité de mesure.",
        'exemple' => 'Glycémie (1.05 g/l)',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'COMPTAGE' => [
        'label_metier' => 'Numération / Comptage',
        'description' => "Comptage d'éléments cellulaires ou particulaires.",
        'exemple' => 'Globules rouges',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'MULTIPLE_SELECTIF' => [
        'label_metier' => "Groupe d'analyses à choix",
        'description' => 'Permet de choisir une ou plusieurs analyses parmi une liste définie.',
        'exemple' => 'Recherche de germes spécifiques',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => true],
    ],
    'INPUT' => [
        'label_metier' => 'Saisie libre (Texte)',
        'description' => 'Champ de texte ouvert pour tout type de résultat non structuré.',
        'exemple' => 'Commentaire ou observation',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'SELECT' => [
        'label_metier' => 'Liste de choix (Unique)',
        'description' => "Sélection d'une valeur unique parmi une liste prédéfinie.",
        'exemple' => 'Couleur des urines (Jaune, Ambre, etc.)',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => false],
    ],
    'NEGATIF_POSITIF_1' => [
        'label_metier' => 'Négatif / Positif simple',
        'description' => 'Boutons de choix rapide entre Négatif et Positif.',
        'exemple' => 'Hépatite B',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => false],
    ],
    'NEGATIF_POSITIF_2' => [
        'label_metier' => 'Négatif / Positif avec Valeur',
        'description' => 'Choix Négatif/Positif complété par une valeur numérique de référence.',
        'exemple' => 'Sérologie avec titre',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => false],
    ],
    'NEGATIF_POSITIF_3' => [
        'label_metier' => 'Négatif / Positif avec Précision',
        'description' => 'Choix Négatif/Positif avec une liste de choix pour préciser le résultat.',
        'exemple' => "Recherche d'Ag (Préciser le type)",
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => true],
    ],
    'INPUT_SUFFIXE' => [
        'label_metier' => 'Saisie avec Suffixe',
        'description' => "Valeur libre accompagnée d'un texte fixe à la fin.",
        'exemple' => 'Temps de saignement (... secondes)',
        'flags' => ['uses_ref' => true, 'uses_suffix' => true, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'LEUCOCYTES' => [
        'label_metier' => 'Formule Leucocytaire',
        'description' => 'Saisie structurée pour les différents types de globules blancs.',
        'exemple' => 'NFS (Leucocytes)',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'ABSENCE_PRESENCE_2' => [
        'label_metier' => 'Absence / Présence avec Valeur',
        'description' => 'Choix Absence/Présence complété par une précision libre.',
        'exemple' => 'Protéinurie (Absence ou Présence avec g/l)',
        'flags' => ['uses_ref' => true, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => false],
    ],
    'GERME' => [
        'label_metier' => 'Isolement de Germe',
        'description' => "Saisie pour l'identification précise d'un micro-organisme isolé.",
        'exemple' => 'Staphylococcus aureus',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'LABEL' => [
        'label_metier' => 'Titre / Séparateur',
        'description' => 'Simple texte servant à organiser le compte-rendu. Ne permet aucune saisie de résultat.',
        'exemple' => 'EXAMEN CYTO-BACTERIOLOGIQUE',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => true, 'is_choice' => false, 'is_multi_choice' => false],
    ],
    'SELECT_MULTIPLE' => [
        'label_metier' => 'Liste de choix (Multiple)',
        'description' => 'Sélection de plusieurs valeurs parmi une liste prédéfinie.',
        'exemple' => 'Aspect des urines (Trouble, Hématique...)',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => true, 'is_multi_choice' => true],
    ],
    'FV' => [
        'label_metier' => 'Flore Vaginale (Score)',
        'description' => "Saisie spécifique pour le score de Nugent et l'équilibre de la flore.",
        'exemple' => 'Score de Nugent',
        'flags' => ['uses_ref' => false, 'uses_suffix' => false, 'is_title' => false, 'is_choice' => false, 'is_multi_choice' => false],
    ],
];
