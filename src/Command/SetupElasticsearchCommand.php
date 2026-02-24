<?php

namespace App\Command;

use App\Service\SearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:elasticsearch:setup',
    description: 'Configure et indexe les produits dans Elasticsearch'
)]
class SetupElasticsearchCommand extends Command
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reindex', null, InputOption::VALUE_NONE, 'Réindexer tous les produits')
            ->setHelp('Cette commande permet de configurer Elasticsearch et d\'indexer les produits');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Configuration Elasticsearch');

        try {
            $io->section('Indexation des produits dans Elasticsearch...');
            
            $this->searchService->indexProducts();
            
            $io->success('Produits indexés avec succès dans Elasticsearch !');
            
            if ($input->getOption('reindex')) {
                $io->note('Réindexation terminée');
            }

            $io->section('Test de recherche...');
            
            // Test de recherche
            $results = $this->searchService->search('test', '', 5);
            
            $io->table(
                ['ID', 'Nom', 'Type', 'Prix', 'Score'],
                array_map(function($result) {
                    return [
                        $result['id'],
                        $result['name'],
                        $result['type'],
                        $result['price'] . ' €',
                        $result['score'] ?? 'N/A'
                    ];
                }, $results)
            );

            $io->success('Elasticsearch est prêt !');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la configuration Elasticsearch: ' . $e->getMessage());
            
            $io->section('Dépannage');
            $io->text([
                '1. Vérifiez qu\'Elasticsearch est installé et démarré:',
                '   - Docker: docker run -d --name elasticsearch -p 9200:9200 -e "discovery.type=single-node" elasticsearch:8.11.0',
                '   - Ou téléchargez depuis: https://www.elastic.co/downloads/elasticsearch',
                '',
                '2. Vérifiez la configuration dans .env:',
                '   ELASTICSEARCH_HOST=http://localhost:9200',
                '',
                '3. Testez la connexion:',
                '   curl http://localhost:9200'
            ]);
            
            return Command::FAILURE;
        }
    }
}
