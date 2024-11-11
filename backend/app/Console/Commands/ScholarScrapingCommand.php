<?php

namespace App\Console\Commands;

use App\Models\Conference;
use App\Models\StratumQualis;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use QueryPath\DOMQuery;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\DomCrawler\Crawler;

class ConferenceScrapingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scholar:productions-scraping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrai dados de produções do Google Scholar dos professores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->getOutput()->info('Coletando dados do Google Scholar...');
        $ufba_teachers = User::where('type', '=', 'professor')->get()->pluck('name');
        $not_found_teachers = [];

        foreach(['Ricardo Rios'] as $ufba_teacher) {
            $this->getOutput()->info('Tentando buscar dados do professor ' . $ufba_teacher . "... \n");

            $name_queries = $this->getNameQueriesFromTeacherName($ufba_teacher);
            $is_ufba_teacher = FALSE;
    
            foreach ($name_queries as $name_query) {
                $authors_list = $this->getAuthorsByQuery($name_query);    
                $author_page_url = NULL;

                foreach ($authors_list as $author) {
                    $author_page_url = $this->getAuthorPageUrl($author);

                    if (!$author_page_url) {
                        continue;
                    }
                    
                    $is_ufba_teacher = TRUE;
                    break;
                }

                if ($is_ufba_teacher){
                    $this->getOutput()->info("\nBuscando artigos do professor " . $ufba_teacher . "\n");
                    $this->saveTeacherArticles($author_page_url);
                    break;
                }
            }

            if (!$is_ufba_teacher) {
                array_push($not_found_teachers, $ufba_teacher);
            }

            continue;
        }
        
        print_r("\nProfessores não encontrados \n");
        print_r(join(" ", $not_found_teachers));
        print_r("\n\n");

        return 0;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getAuthorsByQuery($string): DOMQuery
    {
        $client = new Client();
        $query = str_replace(' ', '+', $string);
        $url = 'https://scholar.google.com/citations?hl=en&view_op=search_authors&mauthors=' . $query;
        $html = $client->get($url);

        return html5qp($html->getBody()->getContents())->find('.gs_scl');
    }

    protected function getAuthorPageDomQuery($query): DOMQuery
    {
        $client = new Client();
        $url = 'https://scholar.google.com' . $query;
        $html = $client->get($url);

        return html5qp($html->getBody()->getContents());
    }

    private function isUfbaTeacher(DOMQuery $author_dom) {
        $description = $author_dom->find('.gsc_prf_il');
        
        if ($description && str_contains(strtolower($description->text()), 'ufba')) {
            return TRUE;
        }

        return FALSE;
    }   

    private function getNameQueriesFromTeacherName($teacher_name) {
        $combinations = [$teacher_name];

        $name_parts = explode(' ', $teacher_name);

        if (count($name_parts) <= 2) {
            return $combinations;
        }

        $first_name = $name_parts[0];

         for ($i = 1; $i < count($name_parts); $i++) {
            if (in_array(strtolower($name_parts[$i]), ["de", "do", "da", "dos", "das"])) {
                continue;
            }

            $query = $first_name . ' ' . $name_parts[$i];
            $combinations[] = $query;
        }

        return $combinations;
    }

    private function getAuthorPageUrl($author_div) {
        $profile_link = $author_div->find('h3.gs_ai_name a');
        
        if (!$profile_link->length) {
            return FALSE;
        }
        
        $author_page_dom = $this->getAuthorPageDomQuery($profile_link->attr('href'));
                    
        if ($this->isUfbaTeacher($author_page_dom)) {
            return $profile_link->attr('href');
        }

        return FALSE;
    }

    private function saveTeacherArticles($author_url) {
        $client = PantherClient::createChromeClient(null, ['--no-sandbox', '--disable-dev-shm-usage', '--headless', '--remote-debugging-port=9222']);
        $page_url = 'https://scholar.google.com' . $author_url;

        print_r($page_url);

        $client->request('GET', $page_url);
        $crawler = $client->waitFor('#gsc_bpf');

        $show_more_button = $client->getCrawler()->filter('#gsc_bpf_more');

        if ($show_more_button->count() > 0) {
            print_r("\n\nBotão encontrado\n\n");
            $show_more_button->click();
            sleep(5);
        }

        $form_action = $author_url . '&view_op=list_works';



        $crawler->filterXPath("//form[@action='{$form_action}']")->each(function (Crawler $row){
            $rows_on_node = $row->filter('table tr')->count();
            if ($rows_on_node > 0) {
                $row->filter('table tr')->each(function (Crawler $table_row){
                   if ($table_row->filter('td')->count()) {
                    print_r("\n\n td value: " . $table_row->filter('td')->text() . "\n\n");
                    // Aqui temos os artigos
                   }
                });
            }    
        });
    }
}
