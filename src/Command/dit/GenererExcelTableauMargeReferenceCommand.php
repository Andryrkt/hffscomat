<?php

namespace App\Command\dit;

use App\Service\atelier\dit\soumission\Devis\TraitementDeFicherService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenererExcelTableauMargeReferenceCommand extends Command
{
    protected static $defaultName = 'app:dit:generer-excel-marge-ref';

    private ?TraitementDeFicherService $traitementDeFicherService = null;

    public function __construct(?TraitementDeFicherService $traitementDeFicherService = null)
    {
        parent::__construct();
        $this->traitementDeFicherService = $traitementDeFicherService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Génère le fichier Excel du tableau de marge par référence pour un OR donné.")
            ->addArgument('num_or', InputArgument::REQUIRED, 'Numéro de l\'OR')
            ->addArgument('code_societe', InputArgument::OPTIONAL, 'Code société (ex: HF, SCT...)', 'HF')
            ->addArgument('numero_version', InputArgument::OPTIONAL, 'Numéro de version de devis', '1')
            ->setHelp(
                "Cette commande permet de générer le fichier Excel du tableau de marge par référence.\n\n" .
                "Exemples d'utilisation :\n" .
                "  php bin/console app:dit:generer-excel-marge-ref 12345\n" .
                "  php bin/console app:dit:generer-excel-marge-ref 12345 HF 1\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $numOr = (string) $input->getArgument('num_or');
        $codeSociete = (string) $input->getArgument('code_societe');
        $numeroVersion = (string) $input->getArgument('numero_version');

        $io->title('Génération du fichier Excel — Tableau de Marge Référence');
        $io->text([
            sprintf('Numéro OR       : <info>%s</info>', $numOr),
            sprintf('Code Société    : <info>%s</info>', $codeSociete),
            sprintf('Numéro Version  : <info>%s</info>', $numeroVersion),
        ]);
        $io->newLine();

        try {
            $io->text('Calcul du tableau de marge et génération du fichier Excel...');

            $service = $this->traitementDeFicherService ?? new TraitementDeFicherService();
            $resultat = $service->tableauMargeReference($numOr, $codeSociete, $numeroVersion);

            $cheminFichier = ($_ENV['BASE_PATH_FICHIER'] ?? '') . '/dit/dev/fichiers/marge_ref_' . $numOr . '.xlsx';

            $nbCat = count($resultat['tableauMargeCat'] ?? []);
            $nbMfn = count($resultat['tableauMargeMfn'] ?? []);
            $nbAutres = count($resultat['tableauMargeAutres'] ?? []);

            $io->newLine();
            $io->text([
                sprintf('  - Lignes CAT    : <info>%d</info>', $nbCat),
                sprintf('  - Lignes MFN    : <info>%d</info>', $nbMfn),
                sprintf('  - Lignes Autres : <info>%d</info>', $nbAutres),
            ]);
            $io->newLine();

            if (file_exists($cheminFichier)) {
                $io->success(sprintf('Fichier Excel généré avec succès : %s', $cheminFichier));
            } else {
                $io->warning(sprintf('La méthode a été exécutée. Chemin prévu du fichier : %s', $cheminFichier));
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Erreur lors de la génération du fichier Excel : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
