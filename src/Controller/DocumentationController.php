<?php

namespace App\Controller;

use App\Service\Docs\Docs;
use App\Service\Docs\ChangeLogs;
use App\Service\Docs\Icons;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/docs/{filename}", name="docs_file")
     */
    public function docs(Request $request, $filename = null)
    {
        $filename = ($filename ? $filename : 'Welcome');

        // change logs
        if (strtolower($filename) == 'changelogs') {
            return $this->render('docs/docs_changelog.html.twig', [
                'commits' => (new ChangeLogs())->get(),
                'filename'   => $filename,
                'navigation' => Docs::LIST
            ]);
        }

        // icon
        if (strtolower($filename) == 'icons') {
            if ($request->get('download')) {
                return $this->file(
                    new File(
                        (new Icons())->downloadIconSet($request->get('set'))
                    )
                );
            }

            return $this->render('docs/docs_icons.html.twig', [
                'images'     => (new Icons())->get($request->get('set')),
                'set'        => $request->get('set'),
                'filename'   => $filename,
                'navigation' => Docs::LIST
            ]);
        }

        return $this->render('docs/docs.html.twig', [
            'markdown'   => (new Docs($this->em))->getMarkdown($filename),
            'filename'   => $filename,
            'navigation' => Docs::LIST
        ]);
    }
}
