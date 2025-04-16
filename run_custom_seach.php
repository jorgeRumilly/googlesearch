<?php
require_once 'GoogleCustomSearch.php';

/**
 * @param $filtrerProduits (Activer ou non le filtrage produit)
 * @return void
 */
function searchByApi($filtrerProduits = true)
{

// 🔧 Paramètres de recherche
//    $params = [
//        'ean' => '3606481465795',
//        'ref_fab' => 'S520052',
//        'marque' => 'Schneider Electric',
//        'texte_libre' => 'prise',
//        'exclude_sites' => ['domomat.com', 'amazon.fr'],
//        'country' => 'fr',
//        'use_intitle' => true
//    ];
    $params = [
        'ean' => '3606481465795',
        'ref_fab' => 'S520052',
        'marque' => 'Schneider Electric',
        'texte_libre' => '',
        'exclude_sites' => ['domomat.com', 'amazon.fr'],
        'country' => 'fr',
        'use_intitle' => false
    ];

// 🔍 Requête API
    $resultats = GoogleCustomSearch::search($params);

// 📌 Application du filtre local
    if ($resultats && !empty($resultats['items'])) {
        $items = $resultats['items'];
        if ($filtrerProduits) {
            $items = GoogleCustomSearch::filterProductItems($items);
        }
        // 🖨️ Affichage des résultats
        displayItems($items);
        echo "✅ " . count($items) . " résultat(s)".(($filtrerProduits)?' après filtrage':'').".\n";
    } else {
        echo "⚠️ Aucun résultat ou erreur API.\n";
    }
}

function loadRawData($filename)
{
    $response = GoogleCustomSearch::loadFromFile($filename);
    if ($response && !empty($response['items'])) {
        echo "✅ " . count($response['items']) . " résultat(s) avant filtrage.\n";
        $produits = GoogleCustomSearch::filterProductItems($response['items']);
        displayItems($produits);
        echo "✅ " . count($produits) . " résultat(s) après filtrage.\n";
    } else {
        echo "❌ Fichier vide ou invalide.\n";
    }
}

/**
 * @param array $produits
 * @return void
 */
function displayItems(array $produits): void
{
    foreach ($produits as $p) {
        echo "🛒 {$p['title']}\n";
        echo "💶 Prix : " . ($p['price'] ?? 'n/a') . "\n";
        echo "🔗 {$p['link']}\n";
        echo "📝 {$p['snippet']}\n";
        echo str_repeat('-', 50) . "\n";
    }
}


searchByApi(true);

loadRawData('2025-04-16_14-55-16-508.json');
