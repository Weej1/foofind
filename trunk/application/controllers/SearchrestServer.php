<?php

require_once APPLICATION_PATH.'/controllers/SearchController.php';
require_once APPLICATION_PATH.'/models/ContentType.php';



class SearchrestServer 
{
   
   
    public function getSearch($q,$src,$opt,$type,$size,$year,$brate,$page)
    //public function getSearch()
    {

        
        global $content;

        //********************************************************************************************************
        $srcs = array();
        $src2 = ($src=='')?'wftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        

       $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => (int) $page);

       //var_dump($conds);die();
        
        
       $SphinxPaginator = new Sphinx_Paginator('idx_files, idx_files_week');
       $SphinxPaginator->setFilters($conds);
       
       

        if ($SphinxPaginator !== null) {
                //paginator
                $paginator = new Zend_Paginator($SphinxPaginator);

                $paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
       

                //setting the paginator cache
                $fO = array('lifetime' => 3600, 'automatic_serialization' => true);
                $bO = array('cache_dir'=>'/tmp');
                $cache = Zend_Cache::factory('Core', 'File', $fO, $bO);

                $paginator->setCache($cache);
                $paginator->setCurrentPageNumber($page);

                $paginatorArray = $paginator->getCurrentItems();

                foreach ($paginatorArray as $i => $value)
                 {

                //we have to unset params with @, this dont like xml

               $paginatorArray2[$i]['idfile'] = $paginatorArray[$i]['idfile'];
               $paginatorArray2[$i]['size'] = $paginatorArray[$i]['size'];
               $paginatorArray2[$i]['type'] = $paginatorArray[$i]['type'];
               $paginatorArray2[$i]['filename'] ='<![CDATA['.$paginatorArray[$i]['filename'] .']]>';
               $paginatorArray2[$i]['artist'] = '<![CDATA['.$paginatorArray[$i]['artist'] .']]>';
               $paginatorArray2[$i]['link'] = '<![CDATA['.$paginatorArray[$i]['link'] .']]>';
               $paginatorArray2[$i]['dlink'] = '<![CDATA['.'http://foofind.com/en/download/'.$paginatorArray[$i]['link'] .']]>';
               //var_dump($paginatorArray[$i]);

               //$paginatorArray2[$i] = $paginatorArray[$i];

                 }

                 //var_dump( $paginatorArray);
                 //var_dump( $paginatorArray2);
                 //die();

                if ($conds['type']!=null && $SphinxPaginator->count()==0)
                {
                    $conds['type']=null;
                    $SphinxPaginator->setFilters($conds);
                    $noTypeCount = $SphinxPaginator->justCount();
                }

               
        }

       
       return $paginatorArray2;
       
         

    }

    
}


