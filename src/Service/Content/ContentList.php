<?php

namespace App\Service\Content;

use App\Entity\App;
use App\Service\Common\Arrays;
use App\Service\Common\Language;
use App\Service\Redis\Cache;
use App\Service\Apps\AppManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentList
{
    /** @var Request */
    private $request;
    /** @var Cache */
    private $cache;
    /** @var AppManager */
    private $appManager;
    /** @var string */
    private $name;
    /** @var array */
    private $ids;
    
    public function __construct(Cache $cache, AppManager $appManager)
    {
        $this->cache = $cache;
        $this->appManager = $appManager;
    }
    
    public function get(Request $request, string $name)
    {
        $this->request = $request;
        $this->name    = $name;

        $this->ids = $this->request->get('ids')
            ? explode(',', $this->request->get('ids'))
            : $this->cache->get("ids_{$this->name}");
        
        if (!$this->ids) {
            throw new NotFoundHttpException('No content ids found for: '. $this->name);
        }
        
        array_walk($this->ids, function(&$val) {
            $val = $val === 0 || (int)$val ? $val : false;
        });
        
        $this->ids = array_filter($this->ids, function ($value) {
            return $value !== '' && $value !== null;
        });
        
        // no ids? end
        if (!$this->ids) {
            return null;
        }
        
        // max_items (alias limit, deprecate max_items)
        $maxItems = $this->request->get('max_items') ?: $this->request->get('limit');
        $maxItems = intval($maxItems ?: 100) ?: 100;
        $maxItems = $maxItems < 3000 ? $maxItems : 3000;
        
        // ----------------------------------------------------------------------
        
        // trim ids
        $totalResults = count($this->ids);
        $pageTotal = $totalResults > 0 ? ceil($totalResults / $maxItems) : 0;
        $page = $this->request->get('page') ?: 1;
        $page = $page >= 1 ? $page : 1;
        $pageNext = ($page + 1) <= $pageTotal ? ($page + 1) : 1;
        $pagePrev = $page-1 > 0 ? $page-1 : 1;

        // sort ids
        asort($this->ids);
        $this->ids = array_splice($this->ids, ($page-1) * $maxItems, $maxItems);
        
        // pagination data
        $pagination = [
            'Page'           => $page,
            'PageTotal'      => $pageTotal,
            'PageNext'       => $pageNext,
            'PagePrev'       => $pagePrev,
            'Results'        => count($this->ids),
            'ResultsPerPage' => $maxItems,
            'ResultsTotal'   => $totalResults,
        ];
    
        // no ids? end
        if (!$this->ids) {
            throw new NotFoundHttpException("No content available on page: {$page} for: {$this->name}");
        }

        // get list data

        // temp hack...
        $columns = $this->request->get('columns')
            ? array_unique(explode(',', $this->request->get('columns')))
            : ["ID","Name","Icon","Url"];

        $data = [];
        foreach ($this->ids as $id) {
            $content = $this->cache->get("xiv_{$this->name}_{$id}");
            
            if ($content) {
                $content = Language::handle($content, $this->request->get('language'));

                $columns = Arrays::extractColumnsCount($content, $columns);
                $columns = Arrays::extractMultiLanguageColumns($columns);

                $data[] = Arrays::extractColumns($content, $columns);
            }

            unset($content);
        }
       
        return [
            'Pagination' => $pagination,
            'Results'    => $data
        ];
    }
}
