<?php

namespace App\Command;

use App\Entity\MapPosition;
use App\Repository\MapPositionRepository;
use App\Service\Redis\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMapPreviewsCommand extends Command
{
    use CommandHelperTrait;
    
    /** @var EntityManagerInterface */
    private $em;
    /** @var Cache */
    private $cache;
    /** @var ImageManager */
    private $imageManager;
    /** @var MapPositionRepository */
    private $repo;
    
    public function __construct(
        ?string $name = null,
        EntityManagerInterface $em,
        Cache $cache
    ) {
        $this->em     = $em;
        $this->cache  = $cache;
        parent::__construct($name);
    }
    
    protected function configure()
    {
        $this
            ->setName('GenerateMapPreviewsCommand')
            ->setDescription('Generate map previews of positions')
            ->addArgument('a', InputArgument::OPTIONAL, 'A')
            ->addArgument('b', InputArgument::OPTIONAL, 'B')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setSymfonyStyle($input, $output);
        $this->io->title("Generic");

        //
        // Setup Intervention
        //
        // create an image manager instance with favored driver
        $this->imageManager = new ImageManager(array('driver' => 'imagick'));

        //
        // Setup repository
        //
        $this->repo = $this->em->getRepository(MapPosition::class);

        $this->handleBNPC();
    }

    private function handleBNPC()
    {
        $positions = $this->repo->findBy([
            'Type'    => 'Monster',
            'Managed' => false,
        ]);

        $this->io->text(count($positions) . ' BNPC positions to process');

        // group by BNpcName
        $bnpcs = [];

        /** @var MapPosition $pos */
        foreach ($positions as $pos) {
            $bnpcs[$pos->getBNpcNameID()][$pos->getMapID()][] = [
                $pos->getPixelX(),
                $pos->getPixelY(),
            ];
        }

        $this->io->text(count($bnpcs) .' BNPC names to process');

        foreach ($bnpcs as $bnpcNameId => $positions) {
            foreach ($positions as $mapId => $coordinates) {

                $map = (Object)[
                    'MapID'         => $mapId,
                    'BNPCNameID'    => $bnpcNameId,
                    'PositionCount' => count($coordinates),
                    'PlaceName'     => 'PlaceName',
                    'Region'        => 'Region',
                    'Zone'          => 'Zone',
                    'Image'         => '',
                ];

                // Get Aetherytes for this map
                $teleports = $this->repo->findBy([
                    'Type'  => 'Aetheryte',
                    'MapID' => $mapId,
                ]);

                // get map file
                // todo - move this to redis (at work no redis XD)
                $json = json_decode(file_get_contents('https://xivapi.com/Map/18?pretty=1'));
                $file = $json->MapFilename;

                // read image
                $this->io->text("Building image: {$file}");
                $img = $this->imageManager->make("https://xivapi.com/{$file}");

                // render teleports
                /** @var MapPosition $tele */
                foreach ($teleports as $tele) {
                    $x = $tele->getPixelX() - 62;
                    $y = $tele->getPixelY() - 62;

                    $img->insert('http://xivapi.com/c/PlaceName.png', null, $x, $y);
                }

                // set these as stupidly high
                $a = 30000;
                $b = 0;
                $c = 30000;
                $d = 0;

                // add positions
                foreach ($coordinates as $xy) {
                    [$x, $y] = $xy;

                    // draw a filled blue circle
                    $img->circle(30, $x, $y, function ($draw) {
                        $draw->background('rgba(255, 0, 0, 0.3)');
                    });

                    // compare against grid
                    $a = ($x < $a) ? $x : $a;
                    $b = ($x > $b) ? $x : $b;
                    $c = ($y < $c) ? $y : $c;
                    $d = ($y > $d) ? $y : $d;
                }

                // add padding around position framing
                $a = $a-200;
                $c = $c-200;
                $b = $b+200;
                $d = $d+200;

                // Crop
                $width  = $b - $a;
                $height = $d - $c;
                $img->crop($width, $height, $a, $c);

                // map name and region (shadow first, then text
                $img->text($json->PlaceName->Name, 10+3, 45+3, function($font) {
                    $font->file(__DIR__.'/resources/roboto/Roboto-Bold.ttf');
                    $font->size(50);
                    $font->color('#000000');
                });
                $img->text(strtoupper($json->PlaceNameRegion->Name), 10+3, 80+3, function($font) {
                    $font->file(__DIR__.'/resources/roboto/Roboto-Bold.ttf');
                    $font->size(35);
                    $font->color('#000000');
                });
                $img->text($json->PlaceName->Name, 10, 45, function($font) {
                    $font->file(__DIR__.'/resources/roboto/Roboto-Bold.ttf');
                    $font->size(50);
                    $font->color('#ffffff');
                });
                $img->text(strtoupper($json->PlaceNameRegion->Name), 10, 80, function($font) {
                    $font->file(__DIR__.'/resources/roboto/Roboto-Bold.ttf');
                    $font->size(35);
                    $font->color('#ffffff');
                });

                // Resize image in half
                $img->resize($width / 2, $height / 2);

                // save
                $img->save(__DIR__.'/test.jpg', 75);

                print_r($json);

                die;

            }

            break;
        }
    }
}
