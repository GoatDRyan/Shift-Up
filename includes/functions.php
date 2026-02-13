<?php

function get_trad_bdd($data, $champ, $lang) {
    $colonne = $champ . '_' . $lang;

    if (isset($data[$colonne]) && !empty($data[$colonne])) {
        return $data[$colonne];
    }

    return $data[$champ . '_fr'];
}

function get_level_data($xp) {
    $paliers = [
        0    => ['titre' => 'Graine',    'lvl' => 1],
        100  => ['titre' => 'Pousse',    'lvl' => 2],
        300  => ['titre' => 'Arbuste',   'lvl' => 3],
        600  => ['titre' => 'Arbre',     'lvl' => 4],
        1000 => ['titre' => 'Verger',    'lvl' => 5],
        2000 => ['titre' => 'Forêt',     'lvl' => 6],
        5000 => ['titre' => 'Gardien',   'lvl' => 7],
        8000 => ['titre' => 'Sage',      'lvl' => 8],
        12000 => ['titre' => 'Mythique', 'lvl' => 9],
        20000 => ['titre' => 'Légende',  'lvl' => 10],
    ];

    $current_rank = $paliers[0];
    $next_threshold = 100;
    
    foreach ($paliers as $seuil => $data) {
        if ($xp >= $seuil) {
            $current_rank = $data;
        } else {
            $next_threshold = $seuil;
            break;
        }
    }

    if ($xp >= 20000) {
        $percent = 100;
        $next_threshold = $xp;
    } else {
        $percent = ($xp / $next_threshold) * 100;
    }

    return [
        'niveau_actuel' => $current_rank['lvl'],
        'titre_actuel'  => $current_rank['titre'],
        'xp_actuel'     => $xp,
        'xp_prochain'   => $next_threshold,
        'pourcentage'   => round($percent)
    ];
}
?>