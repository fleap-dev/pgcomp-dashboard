<?php

namespace App\Domain\Lattes;

use App\Domain\Lattes\Exceptions\InvalidXml;
use App\Models\Journal;
use DOMDocument;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Str;
use ZipArchive;

class LattesZipXml
{
    protected function __construct(protected string $storagePath)
    {
    }

    /**
     * @return array{
     *     lattes_id: string,
     *     lattes_updated_at: Carbon,
     *     productions: array<int, array{
     *          title: string,
     *          year: string,
     *          publisher_id: string,
     *          publisher_type: string,
     *          doi: string,
     *          sequence_number: string
     *      }>
     * }
     * @throws InvalidXml
     */
    public static function extractProductions(string $storagePath): array
    {
        $loadXml = new static($storagePath);
        $xml = $loadXml->loadFile();
        $lattesUpdatedAt = "{$xml->attributes()['DATA-ATUALIZACAO']}{$xml->attributes()['HORA-ATUALIZACAO']}";
        $data = [
            'lattes_id' => (string)$xml->attributes()['NUMERO-IDENTIFICADOR'],
            'name' => (string)$xml->{'DADOS-GERAIS'}->attributes()['NOME-COMPLETO'],
            'lattes_updated_at' => Carbon::createFromFormat('dmYHis', $lattesUpdatedAt),
            'productions' => [],
        ];

        /** @var SimpleXMLElement $item */
        foreach ($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'ARTIGOS-PUBLICADOS'}->{'ARTIGO-PUBLICADO'} as $item) {
            $title = (string)$item->{'DADOS-BASICOS-DO-ARTIGO'}->attributes()['TITULO-DO-ARTIGO'];
            $year = (string)$item->{'DADOS-BASICOS-DO-ARTIGO'}->attributes()['ANO-DO-ARTIGO'];
            $doi = (string)$item->{'DADOS-BASICOS-DO-ARTIGO'}->attributes()['DOI'];
            $issn = (string)$item->{'DETALHAMENTO-DO-ARTIGO'}->attributes()['ISSN'];
            $sequence_number = (string)$item->attributes()['SEQUENCIA-PRODUCAO'];
            $publisher_id = null;
            $publisher_type = null;

            if ($issn) {
                $issn = Str::of($issn)->trim()->remove('-')->value();
                $publisher_id = Journal::where('issn', $issn)->first()?->id;
                $publisher_type = $publisher_id ? Journal::class : null;
            }
            // @todo check conference!

            $production = compact('title', 'year', 'publisher_id', 'publisher_type', 'doi', 'sequence_number');
            $data['productions'][] = $production;
        }

        return $data;
    }

    /**
     * @throws InvalidXml
     * @throws Exception
     */
    private function loadFile(): SimpleXMLElement
    {
        $mimeType = Storage::mimeType($this->storagePath);
        if ($mimeType === 'application/zip') {
            $file = $this->extractXmlFromZip();
        } elseif (in_array($mimeType, ['application/xml', 'text/xml'])) {
            $file = Storage::get($this->storagePath);
        } else {
            throw new Exception("Invalid MIME type {$mimeType}.");
        }
        $this->validate($file);

        return simplexml_load_string($file);
    }

    /**
     * @throws Exception
     */
    private function extractXmlFromZip(): ?string
    {
        $zip = new ZipArchive();
        if (!$zip->open(Storage::path($this->storagePath))) {
            throw new Exception('Invalid Zip file.');
        }
        $file = $zip->getFromName('curriculo.xml');
        $zip->close();
        if (empty($file)) {
            throw new Exception('curriculo.xml not found in zip file.');
        }

        return $file;
    }

    /**
     * @throws InvalidXml
     */
    private function validate(string $file): void
    {
        $xml = new DOMDocument();
        $xml->loadXML($file);

        $xsdPath = app_path('Domain/Lattes/xml_cvbase_src_main_resources_CurriculoLattes.xsd');

        $oldUseInternalErrors = libxml_use_internal_errors(true);
        $isValid = $xml->schemaValidate($xsdPath);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($oldUseInternalErrors);

        if (!$isValid) {
            throw new InvalidXml($errors);
        }
    }
}
