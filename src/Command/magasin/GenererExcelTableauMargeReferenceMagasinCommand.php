<?php

namespace App\Command\magasin;

use App\Model\magasin\devis\Soumission\SoumissionModel;
use App\Service\ExcelService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenererExcelTableauMargeReferenceMagasinCommand extends Command
{
    protected static $defaultName = 'app:magasin:generer-excel-marge-ref';

    protected function configure(): void
    {
        $this
            ->setDescription("Génère le fichier Excel du tableau de marge par référence pour un devis magasin donné.")
            ->addArgument('num_devis', InputArgument::REQUIRED, 'Numéro du devis')
            ->addArgument('code_societe', InputArgument::OPTIONAL, 'Code société (ex: HF, SCT...)', 'HF')
            ->setHelp(
                "Cette commande permet de générer le fichier Excel du tableau de marge par référence d'un devis magasin.\n\n" .
                "Exemples d'utilisation :\n" .
                "  php bin/console app:magasin:generer-excel-marge-ref 12345\n" .
                "  php bin/console app:magasin:generer-excel-marge-ref 12345 HF\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $numDevis = (string) $input->getArgument('num_devis');
        $codeSociete = (string) $input->getArgument('code_societe');

        $io->title('Génération du fichier Excel — Tableau de Marge Référence (Magasin)');
        $io->text([
            sprintf('Numéro Devis    : <info>%s</info>', $numDevis),
            sprintf('Code Société    : <info>%s</info>', $codeSociete),
        ]);
        $io->newLine();

        try {
            $io->text('Calcul du tableau de marge et génération du fichier Excel...');

            $resultat = $this->tableauMargeReference($numDevis, $codeSociete);

            $cheminFichier = ($_ENV['BASE_PATH_FICHIER'] ?? '') . '/magasin/devis/' . $numDevis . '/marge_ref_' . $numDevis . '.xlsx';
            (new ExcelService())->genererExcelTableauMargeReference($resultat, $cheminFichier);

            $io->newLine();
            $io->text([
                sprintf('  - Lignes CAT    : <info>%d</info>', count($resultat['tableauMargeCat'])),
                sprintf('  - Lignes MFN    : <info>%d</info>', count($resultat['tableauMargeMfn'])),
                sprintf('  - Lignes Autres : <info>%d</info>', count($resultat['tableauMargeAutres'])),
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

    /**
     * Même logique que DevisNegVerificationPrixController::tableauMargeReference
     * (le contrôleur ne peut pas être instancié en CLI à cause du global $container).
     */
    private function tableauMargeReference(string $numDevis, string $codeSociete): array
    {
        $soumissionModel = new SoumissionModel();

        $infoDevis = $soumissionModel->getInfoDeviSansJointure($numDevis, $codeSociete);

        $tableauMargeCat = [];
        $tableauMargeMfn = [];
        $tableauMargeAutres = [];

        foreach ($infoDevis ?: [] as $ligne) {
            $afficher = $soumissionModel->tableauDeMargeAvecReference($codeSociete, $numDevis, $ligne['ref'], $ligne['code_agence']);

            foreach ($afficher as $value) {
                if ($value['constructeur'] == 'CAT') {
                    $tableauMargeCat[] = $value;
                } elseif ($value['constructeur'] == 'MFN') {
                    $tableauMargeMfn[] = $value;
                } else {
                    $tableauMargeAutres[] = $value;
                }
            }
        }

        return [
            'tableauMargeCat' => $tableauMargeCat,
            'tableauMargeMfn' => $tableauMargeMfn,
            'tableauMargeAutres' => $tableauMargeAutres
        ];
    }
}
