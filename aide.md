# 📘 Aide - Google Custom Search Product Tracker

Ce projet permet d'interroger automatiquement l'API Google Custom Search pour :
- Rechercher des produits à partir d'un EAN, d'une référence fabricant, d'une marque, etc.
- Exclure certains domaines des résultats (ex : domomat.com)
- Filtrer les résultats pour ne garder que les **fiches produits probables**
- Extraire automatiquement les prix quand ils sont disponibles
- Enregistrer tous les appels API dans des fichiers avec un horodatage précis (millisecondes)
- Recharger un fichier de réponse pour retraitement sans refaire de requête

---

## 📁 Fichiers principaux

### `GoogleCustomSearch.php`

Classe statique qui :
- Gère l'appel API à Google
- Logue les appels (compteur + détails par jour)
- Sauvegarde la réponse complète (`params` + `response`) dans `logs/raw/YYYY-MM-DD_HH-mm-ss-ms.json`
- Permet de recharger un fichier de résultat
- Fournit un filtre pour isoler les vrais produits dans les résultats Google

### `run_custom_seach.php`

Script d’exécution :
- Crée les paramètres
- Lance la recherche via `GoogleCustomSearch::search($params)`
- Ou recharge un fichier via `GoogleCustomSearch::loadFromFile()`
- Applique le filtre produit si `$filtrerProduits = true`

---

## ⚙️ Structure des paramètres d'entrée

```php
$params = [
    'ean'           => 'Code EAN (ex: 3606481465795)',
    'ref_fab'       => 'Référence fabricant (ex: S520052)',
    'marque'        => 'Nom de la marque (ex: Schneider Electric)',
    'texte_libre'   => 'Texte libre complémentaire (ex: "prise")',
    'exclude_sites' => ['domomat.com', 'amazon.fr'], // sites à exclure
    'country'       => 'Code pays (ex: fr)',
    'use_intitle'   => true // Pour forcer la présence de la ref dans le <title>
];
```

---

## 🔍 Filtrage "produit"

Le filtrage détecte automatiquement les fiches produit via :
- `pagemap.metatags[0]['og:type'] === "product"`
- ou présence de `pagemap.product`
- ou présence de `pagemap.offer[].price`
- ou métadonnée `product:price:amount`

Si un prix est détecté, il est récupéré automatiquement.

---

## 📂 Dossiers générés

- `logs/YYYY-MM-DD.json` : log journalier (compteur + détails des requêtes)
- `logs/raw/YYYY-MM-DD_HH-mm-ss-ms.json` : réponse API complète (avec paramètres utilisés)

---

## ♻️ Relecture d’un fichier sans refaire de requête

```php
$response = GoogleCustomSearch::loadFromFile('2025-04-16_15-40-12-123.json');
$produits = GoogleCustomSearch::filterProductItems($response['items']);
```

---

## 💡 À venir (pistes possibles)
- Ajout d’un **score de pertinence produit**
- Mise en cache avec expiration
- Interface de visualisation HTML
- Limiteur de quota quotidien intelligent

---
