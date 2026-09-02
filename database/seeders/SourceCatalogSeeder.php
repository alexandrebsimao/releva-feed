<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SourceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->rows() as $row) {
                $source = Source::query()->updateOrCreate(
                    ['domain' => $row['domain']],
                    [
                        'name' => $row['source_name'],
                        'slug' => $this->sourceSlug($row['source_name'], $row['domain']),
                        'priority' => $row['priority'],
                        'active' => $row['active'],
                    ],
                );

                $source->feeds()->updateOrCreate(
                    ['url' => $row['url']],
                    [
                        'name' => $row['feed_name'],
                        'category' => $row['category'],
                        'active' => $row['active'],
                    ],
                );
            }
        });
    }

    private function sourceSlug(string $name, string $domain): string
    {
        $base = Str::slug($name) ?: Str::slug($domain);
        $slug = $base;
        $suffix = 2;

        while (Source::query()->where('slug', $slug)->where('domain', '!=', $domain)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /** @return list<array{source_name: string, domain: string, feed_name: string, category: string, url: string, priority: int, active: bool}> */
    private function rows(): array
    {
        return [
            ['source_name' => 'Agência Brasil', 'domain' => 'agenciabrasil.ebc.com.br', 'feed_name' => 'Últimas Notícias', 'category' => 'general', 'url' => 'https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml', 'priority' => 10, 'active' => true],
            ['source_name' => 'G1', 'domain' => 'g1.globo.com', 'feed_name' => 'Geral', 'category' => 'general', 'url' => 'https://g1.globo.com/dynamo/rss2.xml', 'priority' => 10, 'active' => true],
            ['source_name' => 'Folha de S.Paulo', 'domain' => 'folha.uol.com.br', 'feed_name' => 'Em cima da hora', 'category' => 'general', 'url' => 'https://feeds.folha.uol.com.br/emcimadahora/rss091.xml', 'priority' => 9, 'active' => true],
            ['source_name' => 'Folha de S.Paulo', 'domain' => 'folha.uol.com.br', 'feed_name' => 'Poder', 'category' => 'politics', 'url' => 'https://feeds.folha.uol.com.br/poder/rss091.xml', 'priority' => 8, 'active' => true],
            ['source_name' => 'Folha de S.Paulo', 'domain' => 'folha.uol.com.br', 'feed_name' => 'Mercado', 'category' => 'economy', 'url' => 'https://feeds.folha.uol.com.br/mercado/rss091.xml', 'priority' => 8, 'active' => true],
            ['source_name' => 'UOL', 'domain' => 'uol.com.br', 'feed_name' => 'Notícias', 'category' => 'general', 'url' => 'https://rss.uol.com.br/feed/noticias.xml', 'priority' => 9, 'active' => true],
            ['source_name' => 'CNN Brasil', 'domain' => 'cnnbrasil.com.br', 'feed_name' => 'Geral', 'category' => 'general', 'url' => 'https://www.cnnbrasil.com.br/feed/', 'priority' => 9, 'active' => true],
            ['source_name' => 'ND Mais', 'domain' => 'ndmais.com.br', 'feed_name' => 'Geral', 'category' => 'santa_catarina', 'url' => 'https://ndmais.com.br/feed/', 'priority' => 10, 'active' => true],
            ['source_name' => 'Gazeta do Povo', 'domain' => 'gazetadopovo.com.br', 'feed_name' => 'Últimas Notícias', 'category' => 'general', 'url' => 'https://www.gazetadopovo.com.br/feed/rss/ultimas-noticias.xml', 'priority' => 10, 'active' => true],
            ['source_name' => 'Gazeta do Povo', 'domain' => 'gazetadopovo.com.br', 'feed_name' => 'República', 'category' => 'politics', 'url' => 'https://www.gazetadopovo.com.br/feed/rss/republica.xml', 'priority' => 9, 'active' => true],
            ['source_name' => 'Gazeta do Povo', 'domain' => 'gazetadopovo.com.br', 'feed_name' => 'Economia', 'category' => 'economy', 'url' => 'https://www.gazetadopovo.com.br/feed/rss/economia.xml', 'priority' => 8, 'active' => true],
            ['source_name' => 'Gazeta do Povo', 'domain' => 'gazetadopovo.com.br', 'feed_name' => 'Santa Catarina', 'category' => 'santa_catarina', 'url' => 'https://www.gazetadopovo.com.br/feed/rss/santa-catarina.xml', 'priority' => 10, 'active' => true],
            ['source_name' => 'Revista Oeste', 'domain' => 'revistaoeste.com', 'feed_name' => 'Geral', 'category' => 'politics', 'url' => 'https://revistaoeste.com/feed/', 'priority' => 9, 'active' => true],
            ['source_name' => 'Poder360', 'domain' => 'poder360.com.br', 'feed_name' => 'Geral', 'category' => 'politics', 'url' => 'https://www.poder360.com.br/feed/', 'priority' => 9, 'active' => true],
            ['source_name' => 'Metrópoles', 'domain' => 'metropoles.com', 'feed_name' => 'Geral', 'category' => 'general', 'url' => 'https://www.metropoles.com/feed', 'priority' => 8, 'active' => true],
            ['source_name' => 'Veja', 'domain' => 'veja.abril.com.br', 'feed_name' => 'Geral', 'category' => 'general', 'url' => 'https://veja.abril.com.br/feed/', 'priority' => 8, 'active' => true],
            ['source_name' => 'CartaCapital', 'domain' => 'cartacapital.com.br', 'feed_name' => 'Geral', 'category' => 'politics', 'url' => 'https://www.cartacapital.com.br/feed/', 'priority' => 7, 'active' => true],
            ['source_name' => 'O Antagonista', 'domain' => 'oantagonista.com', 'feed_name' => 'Geral', 'category' => 'politics', 'url' => 'https://www.oantagonista.com/feed/', 'priority' => 7, 'active' => true],
            ['source_name' => 'Congresso em Foco', 'domain' => 'congressoemfoco.uol.com.br', 'feed_name' => 'Geral', 'category' => 'politics', 'url' => 'https://congressoemfoco.uol.com.br/feed/', 'priority' => 8, 'active' => true],
            ['source_name' => 'Câmara dos Deputados', 'domain' => 'camara.leg.br', 'feed_name' => 'Últimas Notícias', 'category' => 'politics', 'url' => 'https://www.camara.leg.br/noticias/rss/ultimas-noticias', 'priority' => 7, 'active' => true],
            ['source_name' => 'Agência Senado', 'domain' => 'senado.leg.br', 'feed_name' => 'Notícias', 'category' => 'politics', 'url' => 'https://www12.senado.leg.br/noticias/rss', 'priority' => 7, 'active' => true],
            ['source_name' => 'Exame', 'domain' => 'exame.com', 'feed_name' => 'Geral', 'category' => 'economy', 'url' => 'https://exame.com/feed/', 'priority' => 9, 'active' => true],
            ['source_name' => 'InfoMoney', 'domain' => 'infomoney.com.br', 'feed_name' => 'Geral', 'category' => 'economy', 'url' => 'https://www.infomoney.com.br/feed/', 'priority' => 9, 'active' => true],
            ['source_name' => 'Money Times', 'domain' => 'moneytimes.com.br', 'feed_name' => 'Geral', 'category' => 'economy', 'url' => 'https://www.moneytimes.com.br/feed/', 'priority' => 8, 'active' => true],
            ['source_name' => 'Tecnoblog', 'domain' => 'tecnoblog.net', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://tecnoblog.net/feed/', 'priority' => 10, 'active' => true],
            ['source_name' => 'Canaltech', 'domain' => 'canaltech.com.br', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://canaltech.com.br/rss/', 'priority' => 10, 'active' => true],
            ['source_name' => 'TecMundo', 'domain' => 'tecmundo.com.br', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://rss.tecmundo.com.br/feed', 'priority' => 8, 'active' => true],
            ['source_name' => 'Olhar Digital', 'domain' => 'olhardigital.com.br', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://olhardigital.com.br/feed/', 'priority' => 8, 'active' => true],
            ['source_name' => 'TudoCelular', 'domain' => 'tudocelular.com', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://www.tudocelular.com/feed/', 'priority' => 7, 'active' => true],
            ['source_name' => 'Oficina da Net', 'domain' => 'oficinadanet.com.br', 'feed_name' => 'Geral', 'category' => 'technology', 'url' => 'https://www.oficinadanet.com.br/rss/geral', 'priority' => 8, 'active' => true],
            ['source_name' => 'Folha de S.Paulo', 'domain' => 'folha.uol.com.br', 'feed_name' => 'Tecnologia', 'category' => 'technology', 'url' => 'https://feeds.folha.uol.com.br/tec/rss091.xml', 'priority' => 8, 'active' => true],
            ['source_name' => 'UOL', 'domain' => 'uol.com.br', 'feed_name' => 'Tecnologia', 'category' => 'technology', 'url' => 'https://tecnologia.uol.com.br/ultnot/index.xml', 'priority' => 7, 'active' => true],
            ['source_name' => 'AutoPapo', 'domain' => 'autopapo.com.br', 'feed_name' => 'Geral', 'category' => 'automotive', 'url' => 'https://autopapo.com.br/feed/', 'priority' => 10, 'active' => true],
            ['source_name' => 'Motor1 Brasil', 'domain' => 'motor1.uol.com.br', 'feed_name' => 'Geral', 'category' => 'automotive', 'url' => 'https://motor1.uol.com.br/rss/articles/all/', 'priority' => 10, 'active' => true],
            ['source_name' => 'Motor1 Brasil', 'domain' => 'motor1.uol.com.br', 'feed_name' => 'Elétricos e Híbridos', 'category' => 'electric_vehicles', 'url' => 'https://motor1.uol.com.br/rss/category/eletricos-veiculos-hibridos/', 'priority' => 9, 'active' => true],
            ['source_name' => 'Quatro Rodas', 'domain' => 'quatrorodas.abril.com.br', 'feed_name' => 'Geral', 'category' => 'automotive', 'url' => 'https://quatrorodas.abril.com.br/rss/', 'priority' => 10, 'active' => true],
            ['source_name' => 'InsideEVs Brasil', 'domain' => 'insideevs.uol.com.br', 'feed_name' => 'Geral', 'category' => 'electric_vehicles', 'url' => 'https://insideevs.uol.com.br/rss/articles/all/', 'priority' => 9, 'active' => true],
            ['source_name' => 'BBC News Brasil', 'domain' => 'bbc.com', 'feed_name' => 'Português', 'category' => 'world', 'url' => 'https://feeds.bbci.co.uk/portuguese/rss.xml', 'priority' => 8, 'active' => true],
            ['source_name' => 'DW Brasil', 'domain' => 'dw.com', 'feed_name' => 'Brasil', 'category' => 'world', 'url' => 'https://rss.dw.com/rdf/rss-br-top', 'priority' => 7, 'active' => true],
        ];
    }
}
