<?php

namespace App\Command;

use App\Service\GamePatch\Patch;
use App\Service\GamePatch\PatchContent;
use App\Service\Redis\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Pretty much all this but option 4 is useless, it's handled manually
 */
class ManagePatchCommand extends Command
{
    use CommandHelperTrait;

    protected function configure()
    {
        $this
            ->setName('app:patch')
            ->setDescription('Manage Patch List')
            ->addArgument('option', InputArgument::OPTIONAL, 'Skip option')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title('Patch Management');

        $option = $input->getArgument('option') ?: false;
        
        if (!$option ) {
            // info
            $this->printPatchTable();
    
            // interactive
            $this->io->section('Patch Options:');
            $this->io->text([
                '1. Add a new patch',
                '2. Edit an existing patch',
                '3. Delete an existing patch',
                '4. Attach patches to Game Content',
                '5. Fix from XIVDB v2 legacy'
            ]);
            
            $option = $this->io->ask('What would you like to do? (enter number of the menu item)');
        }
        

        // ask a question
        switch($option) {
            case 1:
                $this->addPatch();
                break;

            case 2:
                $this->editPatch();
                break;

            case 3:
                $this->deletePatch();
                break;

            case 4:
                $this->attachPatches();
                break;
    
            case 5:
                $csv = file_get_contents(__DIR__.'/resources/xiv_recipes.csv');
                $csv = array_filter(explode(PHP_EOL, $csv));
                unset($csv[0]);
    
                (new PatchContent())->fixFromLegacy('Recipe', $csv);
                break;
        }
    }

    /**
     * Print a nice patch table
     */
    private function printPatchTable()
    {
        $tableHeadings = [
            'ID',
            'Version',
            'ExVersion',
            'IsExpansion',
            'Name',
            'Date'
        ];

        $tableData = [];
        foreach((new Patch())->get() as $patch) {
            $tableData[] = [
                $patch->ID,
                $patch->Version,
                $patch->ExVersion,
                $patch->IsExpansion ? '✓' : ' ',
                $patch->Name_en,
                date('Y, F j', $patch->ReleaseDate)
            ];
        }

        $this->io->table($tableHeadings, $tableData);
    }

    /**
     * Add a patch to the json
     */
    private function addPatch()
    {
        $exVersion = (new Cache())->get('ids_ExVersion');
        $exVersion = end($exVersion);
        
        $this->io->section('Add Patch');

        // Ask away
        $version     = $this->io->ask('What is the patch version? Eg: 2.35, 3.05, 4.3, etc');
        $name        = $this->io->ask("What is the Name of the patch?", null);
        $banner      = $this->io->ask('What is the url for the banner?', null);
        $exVersion   = $this->io->ask('What ExVersion does the patch belong to?', $exVersion);
        $isExpansion = $this->io->confirm('Is this an expansion? Y/N', false);

        // Creating a new patch
        $this->io->text('Creating patch ...');
        (new Patch())->create($version, $name, $banner, $exVersion, $isExpansion);
        $this->complete();
    }

    /**
     * Edit a patch in the json
     */
    private function editPatch()
    {
        $this->io->section('Edit a Patch');

        // ask which patch to edit
        $patchNumber = $this->io->ask("Which patch to edit? (Use the table above to get the ID)");

        // grab patch we're editing
        $patch = (new Patch())->getPatchAtID($patchNumber);
        $this->io->text("<comment>Patch: {$patch->Name_en}</comment>");

        // ask away
        $patch->Version      = $this->io->ask('What is the patch version? Eg: 2.35, 3.05, 4.3, etc', $patch->Version);
        $patch->Name_en      = $this->io->ask("What is the ENGLISH Name of the patch?", $patch->Name_en);
        $patch->Name_de      = $this->io->ask("What is the GERMAN Name of the patch?", $patch->Name_de);
        $patch->Name_fr      = $this->io->ask("What is the FRENCH Name of the patch?", $patch->Name_fr);
        $patch->Name_ja      = $this->io->ask("What is the JAPANESE Name of the patch?", $patch->Name_ja);
        $patch->Name_cn      = $this->io->ask("What is the CHINESE Name of the patch?", $patch->Name_cn);
        $patch->Name_kr      = $this->io->ask("What is the KOREAN Name of the patch?", $patch->Name_kr);
        $patch->Banner       = $this->io->ask('What is the url for the banner?', $patch->Banner);
        $patch->ExVersion    = $this->io->ask("What ExVersion does the patch belong to?", $patch->ExVersion);
        $patch->IsExpansion  = $this->io->confirm('Is this an expansion? Y/N', $patch->IsExpansion);
        $patch->ReleaseDate  = $this->io->ask('Patch release date as a unix timestamp', $patch->ReleaseDate);

        (new Patch())->update($patch);
        $this->complete();
    }

    /**
     * Delete a patch in the json
     */
    private function deletePatch()
    {
        $this->io->section('Delete a patch');

        // ask which patch to delete
        $patchNumber = $this->io->ask("Which patch to delete? (Use the table above to get the ID)");

        // grab patch we're editing
        $patch = (new Patch())->getPatchAtID($patchNumber);
        $this->io->text("<comment>Patch: {$patch->Name_en}</comment>");

        // Confirm deletion
        if (strtoupper($this->io->ask('Delete this patch? Y/N', 'Y')) == 'Y') {
            $this->io->text('Deleting Patch: '. $patch->ID);

            (new Patch())->delete($patch);
            $this->complete();
        }
    }

    /**
     * Attach patches onto existing game content
     */
    private function attachPatches()
    {
        (new PatchContent())->init($this->io)->handle();
    }
}
