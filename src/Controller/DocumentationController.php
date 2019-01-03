<?php

namespace App\Controller;

use App\Service\Docs\Docs;
use App\Service\Docs\Icons;
use App\Service\ThirdParty\GitHub;
use App\Service\ThirdParty\DigitalOcean;
use App\Service\ThirdParty\Vultr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class DocumentationController extends Controller
{
    /** @var EntityManagerInterface */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * @Route("/docs", name="docs")
     * @Route("/docs/{page}", name="docs_page")
     */
    public function docs(Request $request, $page = null)
    {
        $file = strtolower($page ? $page : 'Welcome');
        $file = str_ireplace([' ','-'], '_', $file);

        $response = [
            'page' => $page,
            'file' => $file,
        ];
    
        // change logs
        if ($file == 'costs') {
            $response['vultr'] = Vultr::costs();
            $response['digitalocean'] = DigitalOcean::costs();
        }

        // change logs
        if ($file == 'change_logs') {
            $response['commits'] = GitHub::getGithubCommitHistory();
        }

        // icon
        if ($file == 'icons') {
            $response['images'] = (new Icons())->get($request->get('set'));
        }

        return $this->render('docs/pages/'. $file .'.html.twig', $response);
    }

    /**
     * @Route("/docs/download", name="docs_download")
     */
    public function download(Request $request)
    {
        if ($request->get('set')) {
            return $this->file(
                new File(
                    (new Icons())->downloadIconSet($request->get('set'))
                )
            );
        }

        throw new NotFoundHttpException();
    }
}
