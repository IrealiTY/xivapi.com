<?php

namespace App\Command;

use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\Data\FileSystem;
use App\Service\Data\FileSystemCache;

trait CommandHelperTrait
{
    public $schemaFilename = __DIR__ . '/../../data/gametools/SaintCoinach.Cmd/ex.json';
    /** @var string */
    protected $rootDirectory;
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var SymfonyStyle */
    protected $io;
    /** @var string */
    protected $version;
    /** @var Carbon */
    protected $startTime;
    /** @var array */
    protected $filenames = [];
    /** @var array */
    protected $schema = [];

    /**
     * Setup the input and output variables
     */
    protected function setSymfonyStyle(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Provide a nice title
     */
    protected function title($title): void
    {
        $bar     = str_pad('', 50, '-', STR_PAD_LEFT);
        $titleA  = str_pad('xivapi.com', 50, ' ', STR_PAD_BOTH);
        $titleB  = str_pad($title, 50, ' ', STR_PAD_BOTH);

        $this->io->text([
            "<fg=yellow>+{$bar}+</>",
            "<fg=yellow>|{$titleA}|</>",
            "<fg=yellow>|{$titleB}|</>",
            "<fg=yellow>+{$bar}+</>",
            ''
        ]);
    }

    /**
     * Start a clock
     */
    protected function startClock(): void
    {
        $this->startTime = Carbon::now();
    }

    /**
     * End the clock!
     */
    protected function endClock(): void
    {
        $duration = $this->startTime->diff(Carbon::now())->format('%y year, %m months, %d days, %h hours, %i minutes and %s seconds');
        $this->io->text([
            "",
            "Duration: <info>{$duration}</info>",
            "",
        ]);
    }

    /**
     * Print "complete" with a tick!
     */
    protected function complete(): self
    {
        $this->io->text([
            '✓ Complete', ''
        ]);
        
        return $this;
    }

    protected function eraseLines($lines = 0): self
    {
        $this->io->write("\x0D");
        $this->io->write("\x1B[2K");
        $this->io->write(str_repeat("\x1B[1A\x1B[2K", $lines));
        
        return $this;
    }

    /**
     * Checks the content schema
     */
    protected function checkSchema(): void
    {
        $this->io->text("<fg=cyan>Checking: ex.json ...</>");

        // obtain version
        if (file_exists($this->schemaFilename)) {
            $schema = json_decode(file_get_contents($this->schemaFilename));
        } else {
            die("Could not find EX.JSON file");
        }

        // verify version
        if ($this->version != $schema->version) {
            if (!$this->input->getArgument('fast')) {
                if ($this->io->confirm("Schema version ({$schema->version}) does not match game version ({$this->version}), stop importing? Y/N", false)) {
                    $this->io->error('Build stopped due to schema version mismatch');
                    return;
                }
            }
        }

        // restructure schema so we can easily reference it
        foreach ($schema->sheets as $i => $sheet) {
            $this->schema[$sheet->sheet] = $sheet;
        }
        unset($schema);
        $this->complete();
    }

    /**
     * Check game version (or ask for it)
     */
    protected function checkVersion(): void
    {
        // grab version from ex file
        $json = json_decode(file_get_contents($this->schemaFilename));
        $version = $json->version;

        // set version based on input argument
        $this->version = $this->input->getArgument('fast') ? $version : $this->io->ask('Version?', $version);
        $this->io->text([
            "Version: <comment>{$this->version}</comment>", ""
        ]);
    }

    /**
     * Checks the cache is built
     */
    protected function checkCache(): void
    {
        $this->io->text("<fg=cyan>Building memory cache ...</>");
        foreach (FileSystem::list($this->version) as $type => $files) {
            foreach ($files as $filename) {
                $this->filenames[] = $filename;

                FileSystemCache::add(
                    $filename,
                    FileSystem::load($filename, 'json')
                );
            }
        }
    }
}
