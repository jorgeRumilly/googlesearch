<?php

class GoogleCustomSearch
{
    // Clé API et ID moteur de recherche
    private static string $apiKey = 'AIzaSyA5Iuc4V29gFJwIE_r1feYJzrM7JFYQifc';
    private static string $cx     = '97d3c1375e318414b';

    // Dossier des logs
    private static string $logDir = __DIR__ . '/logs/';

    /**
     * Lance une recherche Google Custom Search
     *
     * @param array $params
     * @return array|null
     */
    public static function search(array $params): ?array
    {
        $terms = [];

        if (!empty($params['ean']))         $terms[] = '"' . $params['ean'] . '"';
        if (!empty($params['ref_fab']))     $terms[] = '"' . $params['ref_fab'] . '"';
        if (!empty($params['marque']))      $terms[] = '"' . $params['marque'] . '"';
        if (!empty($params['texte_libre'])) $terms[] = '"' . $params['texte_libre'] . '"';

        if (!empty($params['use_intitle']) && !empty($params['ref_fab'])) {
            $terms[] = 'intitle:' . $params['ref_fab'];
        }

        if (!empty($params['exclude_sites']) && is_array($params['exclude_sites'])) {
            foreach ($params['exclude_sites'] as $site) {
                $terms[] = '-site:' . $site;
            }
        }

        $query = implode(' ', $terms);

        $url = 'https://www.googleapis.com/customsearch/v1?'
            . 'q=' . urlencode($query)
            . '&key=' . self::$apiKey
            . '&cx='  . self::$cx;

        if (!empty($params['country'])) {
            $url .= '&gl=' . $params['country'];
        }

        $response = @file_get_contents($url);

        if (!$response) {
            self::logUsage($params, false);
            return null;
        }

        $data = json_decode($response, true);

        // Logging des stats générales
        self::logUsage($params, true);

        // Sauvegarde du retour enrichi (params + response)
        self::saveRawResponse($params, $data);

        return $data;
    }

    /**
     * Log journalier de l'activité (compteur + historique)
     */
    private static function logUsage(array $params, bool $success): void
    {
        $date = date('Y-m-d');
        $filename = self::$logDir . "$date.json";

        if (!file_exists(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        $logData = file_exists($filename)
            ? json_decode(file_get_contents($filename), true)
            : ['date' => $date, 'total_requests' => 0, 'requests' => []];

        $logData['total_requests']++;

        $logData['requests'][] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'params'    => $params,
            'success'   => $success
        ];

        file_put_contents($filename, json_encode($logData, JSON_PRETTY_PRINT));
    }

    /**
     * Sauvegarde la réponse API complète dans un fichier daté + horodaté
     */
    private static function saveRawResponse(array $params, array $data): void
    {
        $now = microtime(true);
        $dt = \DateTime::createFromFormat('U.u', number_format($now, 6, '.', ''));
        $timestamp = $dt->format("Y-m-d_H-i-s-v");

        $payload = [
            'timestamp' => $dt->format("Y-m-d H:i:s.v"),
            'params'    => $params,
            'response'  => $data
        ];

        $rawDir = self::$logDir . 'raw/';
        if (!file_exists($rawDir)) {
            mkdir($rawDir, 0755, true);
        }

        $filename = $rawDir . $timestamp . '.json';
        file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * Recharge un fichier brut de réponse API et retourne le bloc "response"
     *
     * @param string $filename Nom du fichier (avec ou sans chemin)
     * @return array|null
     */
    public static function loadFromFile(string $filename): ?array
    {
        $path = $filename;

        if (!file_exists($path) && file_exists(self::$logDir . 'raw/' . $filename)) {
            $path = self::$logDir . 'raw/' . $filename;
        }

        if (!file_exists($path)) {
            echo "❌ Fichier non trouvé : $path\n";
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        if (!isset($json['response'])) {
            echo "⚠️ Fichier invalide ou mal formé.\n";
            return null;
        }

        return $json['response'];
    }

    /**
     * Filtre les résultats pour ne garder que les fiches produit probables
     * et en extraire un prix si disponible.
     *
     * @param array $items Liste des items Google
     * @return array Liste filtrée et enrichie (title, link, snippet, price)
     */
    public static function filterProductItems(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $isProduct = false;
            $price = null;

            $pagemap = $item['pagemap'] ?? [];

            // og:type = product
            if (!empty($pagemap['metatags'][0]['og:type']) && strtolower($pagemap['metatags'][0]['og:type']) === 'product') {
                $isProduct = true;
            }

            // section product présente
            if (!empty($pagemap['product'])) {
                $isProduct = true;
            }

            // section offer avec prix
            if (!empty($pagemap['offer'])) {
                foreach ($pagemap['offer'] as $offer) {
                    if (!empty($offer['price'])) {
                        $isProduct = true;
                        $price = $offer['price'];
                        break;
                    }
                }
            }

            // metatag price:amount
            if (!$price && !empty($pagemap['metatags'][0]['product:price:amount'])) {
                $price = $pagemap['metatags'][0]['product:price:amount'];
            }

            if ($isProduct) {
                $results[] = [
                    'title'   => $item['title']   ?? '',
                    'link'    => $item['link']    ?? '',
                    'snippet' => $item['snippet'] ?? '',
                    'price'   => $price
                ];
            }
        }

        return $results;
    }

}
