<?php

require_once APPLICATION_PATH.'/controllers/SearchController.php';
require_once APPLICATION_PATH.'/controllers/helpers/Fileutils.php';


class FoofindApi
{

    public function getSearch($q, $lang, $src, $type, $size, $year, $brate, $results)
    {
        $this->_helper->fileutils = new Zend_Controller_Action_Helper_Fileutils();

        if ($results > 40) $results = 10;

        if(!$src)
        {
            if ($_COOKIE['src']) $src = $_COOKIE['src'];
        } else {
            setcookie( 'src', $src, null, '/' );
        }
        $srcs = array();
        $src2 = ($src=='')?'wftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' =>  (int) $year, 'brate' => $brate, 'results' => (int) $results);


        $oCache = Zend_Registry::get('cache');

        $key = "srh_".$lang."_".md5("m$q s$src2 o$opt t$type s$size y$year b$brate p$page");
        $existsCache = $oCache->test($key);
        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            $paginator = $oCache->load( $key  );
            $paginator->getAdapter()->setFileUtils($this->_helper->fileutils);
        } else {
            $SphinxPaginator = new Sphinx_Paginator('idx_files');
            $SphinxPaginator->setFileUtils($this->_helper->fileutils);
            $SphinxPaginator->setFilters($conds);

            $paginator = new Zend_Paginator($SphinxPaginator);
            $paginator->setDefaultScrollingStyle('Elastic');
            $paginator->setItemCountPerPage($results);
            $paginator->setCurrentPageNumber($page);
            $paginator->getCurrentItems();

            $paginator->tcount = $SphinxPaginator->tcount;
            $paginator->time = $SphinxPaginator->time;
            if ($conds['type']!=null && $SphinxPaginator->count()==0)
            {
                $conds['type']=null;
                $SphinxPaginator->setFilters($conds);
                $paginator->noTypeCount = $SphinxPaginator->justCount();
            } else {
                $paginator->noTypeCount = "";
            }


            $paginator->getAdapter()->setFileUtils(null);
            $oCache->save( $paginator, $key );
        }

        $paginatorArray = $paginator->getCurrentItems();

        foreach ($paginatorArray as $i => $value)
        {
            $paginatorArray2[$i]['size'] = $paginatorArray[$i]['file']['z'];
            $paginatorArray2[$i]['type'] = $paginatorArray[$i]['view']['type'];

            $paginatorArray2[$i]['dlink'] = '<![CDATA['.'http://foofind.com/'.$lang.'/download/'.$paginatorArray[$i]['view']['url'] .'/'.htmlentities($paginatorArray[$i]['view']['fn'], ENT_QUOTES, "UTF-8").'.html]]>';

            //extract and reformat md data subarray (notation : is not xml complaint)
            foreach ($paginatorArray[$i]['file']['md'] as $key => $value)
            {
                if (strpos($key,":")!==false)
                {
                    $key = explode(":", $key);
                    $key = $key[1];
                }
                
                $paginatorArray2[$i]['md'][$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
            }
        }

        return array_values($paginatorArray2);
    }
}