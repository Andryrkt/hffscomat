<?php

namespace App\Command\informix;

use App\Model\DatabaseInformix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransferTableCommand extends Command
{
    protected static $defaultName = 'app:informix:transfer-table';

    private DatabaseInformix $connect;

    public function __construct()
    {
        parent::__construct();
        $this->connect = new DatabaseInformix();
    }

    protected function configure(): void
    {
        $dbIrium = $_ENV['DB_NAME_IRIUM'] ?? 'magix_frm3300:informix';
        $dbIps = $_ENV['DB_NAME_IPS'] ?? 'ips_scomat:informix';

        $this
            ->setDescription("Transfère les données d'une table d'une base vers une autre sur le même serveur Informix (crée la table cible si elle n'existe pas).")
            ->addArgument('table', InputArgument::OPTIONAL, 'Nom de la table à transférer', 'demande_intervention')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Base de données source', $dbIrium)
            ->addOption('target', 'c', InputOption::VALUE_REQUIRED, 'Base de données cible (destination)', $dbIps)
            ->addOption('where', 'w', InputOption::VALUE_REQUIRED, 'Condition WHERE appliquée à la sélection source (sans le mot-clé WHERE)')
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'Vide la table cible avant le transfert')
            ->setHelp(
                "Cette commande copie les lignes d'une table Informix d'une base vers une autre,\n" .
                "sur le même serveur (même DSN ODBC). Si la table n'existe pas dans la base cible,\n" .
                "elle est créée automatiquement avec la même structure que la table source\n" .
                "(via CREATE TABLE ... (LIKE ...)).\n\n" .
                "Les options --source et --target attendent le format \"base:schema\" (ex: ir_prod108_test:informix).\n\n" .
                "Exemples :\n" .
                "  php bin/console app:informix:transfer-table demande_intervention --source=ir_prod108_test:informix --target=ips_test:informix\n" .
                "  php bin/console app:informix:transfer-table demande_intervention --where=\"code_societe = 'HF'\" --truncate"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $table = strtolower($input->getArgument('table'));
        $source = $input->getOption('source');
        $target = $input->getOption('target');
        $where = $input->getOption('where');
        $truncate = (bool) $input->getOption('truncate');

        $io->title('Transfert de table Informix');

        if ($source === $target) {
            $io->error('La base source et la base cible doivent être différentes.');
            return Command::FAILURE;
        }

        $io->text([
            sprintf('Table         : <info>%s</info>', $table),
            sprintf('Source        : <info>%s</info>', $source),
            sprintf('Cible         : <info>%s</info>', $target),
            $where ? sprintf('Filtre        : <info>%s</info>', $where) : 'Filtre        : (aucun)',
            $truncate ? 'Vidage cible  : <info>oui</info>' : 'Vidage cible  : non',
        ]);
        $io->newLine();

        try {
            $this->connect->connect();

            if (!$this->tableExists($source, $table)) {
                $io->error(sprintf('La table "%s" n\'existe pas dans la base source "%s".', $table, $source));
                return Command::FAILURE;
            }

            if (!$this->tableExists($target, $table)) {
                $io->text(sprintf('La table "%s" n\'existe pas dans "%s", création en cours...', $table, $target));

                // CREATE TABLE cible toujours la base "courante" de la connexion,
                // il faut donc basculer dessus avant de créer la table.
                $this->connect->executeQuery(sprintf('DATABASE %s', $this->databaseName($target)));
                $this->connect->executeQuery(sprintf(
                    'CREATE TABLE Informix.%s (LIKE %s.%s)',
                    $table,
                    $source,
                    $table
                ));

                $io->text('Table créée avec succès.');
                $io->newLine();
            }

            if (!$io->confirm('Continuer le transfert des données ?', true)) {
                $io->text('Opération annulée. Aucune donnée transférée.');
                return Command::SUCCESS;
            }

            if ($truncate) {
                $io->text('Vidage de la table cible...');
                $this->connect->executeQuery(sprintf('DELETE FROM %s.%s', $target, $table));
            }

            $whereClause = $where ? ' WHERE ' . $where : '';
            $sql = sprintf(
                'INSERT INTO %s.%s SELECT * FROM %s.%s%s',
                $target,
                $table,
                $source,
                $table,
                $whereClause
            );

            $io->text('Transfert des données en cours...');
            $this->connect->executeQuery($sql);

            $nbLignes = $this->countRows($target, $table);
            $io->success(sprintf(
                'Transfert terminé. La table "%s" contient désormais %d ligne(s) dans "%s".',
                $table,
                $nbLignes,
                $target
            ));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Erreur lors du transfert : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function tableExists(string $database, string $table): bool
    {
        $sql = sprintf('SELECT tabid FROM %s:systables WHERE tabname = :tabname', $this->databaseName($database));
        $result = $this->connect->executeQuery($sql, ['tabname' => strtolower($table)]);
        $row = $this->connect->fetchScalarResults($result);

        return !empty($row);
    }

    private function countRows(string $database, string $table): int
    {
        $sql = sprintf('SELECT COUNT(*) AS nb FROM %s.%s', $database, $table);
        $result = $this->connect->executeQuery($sql);
        $row = $this->connect->fetchScalarResults($result);

        return (int) ($row['nb'] ?? 0);
    }

    /**
     * Extrait le nom de base "brut" d'une valeur au format "base:schema"
     * (nécessaire pour les instructions DATABASE et systables, qui ne prennent pas de schéma).
     */
    private function databaseName(string $database): string
    {
        return strstr($database, ':', true) ?: $database;
    }
}
