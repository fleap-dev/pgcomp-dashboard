<?php

namespace App\Domain\Sigaa;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use QueryPath\DOMQuery;

abstract class BaseScraping
{
    abstract public function scrapingByProgram(int $programId): array;

    /**
     * @throws Exception
     */
    protected function getDOMQuery(string $url, array $queryParams = []): DOMQuery
    {
        $httpClient = new Client();
        try {
            $response = $httpClient->get($url, ['query' => $queryParams]);
        } catch (GuzzleException $e) {
            Log::error('Erro ao buscar dados.', func_get_args());
            Log::error($e);
            throw new Exception('Não foi possível buscar os dados');
        }
        $html = $response->getBody()->getContents();

        return html5qp($html);
    }

    protected function getSiapeIdFromUrl(?string $text): ?int
    {
        if (empty($text)) {
            return null;
        }

        $url = parse_url($text);
        if (empty($url['query'])) {
            return null;
        }

        parse_str($url['query'], $params);
        if (isset($params['siape']) && is_numeric($params['siape'])) {
            return (int)$params['siape'];
        }

        return null;
    }
}
