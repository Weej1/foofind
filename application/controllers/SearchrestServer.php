<?php

require_once APPLICATION_PATH.'/controllers/SearchController.php';
require_once APPLICATION_PATH.'/models/ContentType.php';


class SearchrestServer
{

    public function getSearch($q, $lang, $src, $type, $size, $year, $brate, $results)
    

    {

        global $content;

        //************************
        $srcs = array();
        $src2 = ($src=='')?'wftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);


        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' =>  (int) $year, 'brate' => $brate, 'results' => (int) $results);


        $SphinxPaginator = new Sphinx_Paginator('idx_files, idx_files_week');
        $SphinxPaginator->setFilters($conds);


        if ($SphinxPaginator !== null)
        {
            //paginator
            $paginator = new Zend_Paginator($SphinxPaginator);


           
            //limit numresults
            if ($results <= 40){
                  $results = $results;
            } else {
                  $results = 10;
            }
          
            $paginator->setItemCountPerPage($results);


            //setting the paginator cache
            $fO = array('lifetime' => 3600, 'automatic_serialization' => true);
            $bO = array('cache_dir'=>'/tmp');
            $cache = Zend_Cache::factory('Core', 'File', $fO, $bO);

            $paginator->setCache($cache);
            
            $paginatorArray = $paginator->getCurrentItems();

            foreach ($paginatorArray as $i => $value)
            {
                //var_dump($value);
                $paginatorArray2[$i]['size'] = $paginatorArray[$i]['attrs']['size'];
                $paginatorArray2[$i]['type'] = $paginatorArray[$i]['type'];
                
                $paginatorArray2[$i]['dlink'] = '<![CDATA['.'http://foofind.com/'.$lang.'/download/'.$paginatorArray[$i]['dlink'] .']]>';

                //extract and reformat md data subarray (notation : is not xml complaint)
                foreach ($paginatorArray[$i]['md'] as $key => $value)
                {
                    $key = explode(":", $key);
                    $key = $key[1];
                    $paginatorArray2[$i]['md'][$key] = $value;
               
                    //var_dump($key);
                 }
                //$paginatorArray2[$i]['item']['md']  = $paginatorArray[$i]['md'];
               

            }

//        var_dump( $paginatorArray);
//          var_dump( $paginatorArray2 );
//          die();

        }



        
        return array_values($paginatorArray2);

    }

}
