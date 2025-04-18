<?php

namespace App\Domain\Sigaa;

use Exception;
use Illuminate\Support\Str;
use QueryPath\DOMQuery;

class TeacherScraping extends BaseScraping
{
    /**
     * Realiza o web scraping de professores no site SIGAA
     *
     * @throws Exception
     */
    public function scrapingByProgram(int $programId): array
    {
        $dom = $this->getDOMQuery('https://sigaa.ufba.br/sigaa/public/programa/equipe.jsf', ['id' => $programId]);
        $items = $dom->find('#equipePrograma table#table_lt tr')->getIterator();
        $teachers = [];
        foreach ($items as $item) {
            if ($item->hasClass('campos')) {
                continue;
            }
            $teachers[] = $this->extractTeacher($item);
        }

        return $teachers;
    }

    /**
     * Extrai os dados de professor da linha da tabela HTML.
     *
     * @param DOMQuery $item
     * @return array{name: string, siape: string, relation: string, level: string, telephone: string, lattes: string, email: string}
     */
    private function extractTeacher(DOMQuery $item): array
    {
        $nameQp = $item->find('td')->eq(0);
        $name = $nameQp->text();
        $name = Str::of($name)->replace([' ', ','], '')->trim()->title()->value();

        $siapeUrl = $nameQp->find('a')->first()->attr('href');
        $siape = $this->getSiapeIdFromUrl($siapeUrl);

        $relation = $item->find('td')->eq(1)->text();
        $relation = Str::of($relation)->trim()->title()->value() ?: null;

        $level = $item->find('td')->eq(2)->text();
        $level = Str::of($level)->trim()->title()->value() ?: null;

        $telephone = $item->find('td')->eq(3)->text();
        $telephone = Str::of($telephone)->trim()->value() ?: null;

        $lattes_url = $item->find('td')
            ->eq(4)
            ->find('a')
            ->first()
            ->attr('href');
        $lattes_url = Str::of($lattes_url)->trim()->lower()->value() ?: null;

        $email = $item->find('td')
            ->eq(5)
            ->find('a')
            ->first()
            ->attr('href');
        $email = Str::of($email)->trim()->lower()->after('mailto:')->value() ?: null;

        return compact('name', 'relation', 'level', 'telephone', 'lattes_url', 'email', 'siape');
    }
}
