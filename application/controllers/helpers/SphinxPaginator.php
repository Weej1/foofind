<?php
require_once APPLICATION_PATH.'../../library/Sphinx/sphinxapi.php';

class SphinxPaginator implements Zend_Paginator_Adapter_Interface {
    public function __construct($table)
    {
        $this->table = $table;

        $config = Zend_Registry::get('config');
        $sphinxServer = $config->sphinx->server;

        $this->tcount = 0;

        $this->cl = new SphinxClient();
        $this->cl->SetServer( $sphinxServer, 3312 );
        $this->cl->SetMatchMode( SPH_MATCH_EXTENDED2 );
        $this->cl->SetRankingMode( SPH_RANK_SPH04 );

        // search field weights
        $weights = array();

        // filenames
        $weights["fn1"] = 20;
        for ($i = 2; $i < 21; $i++)
            $weights["fn$i"] = 1;

        /*/ metadata
        $weights['mta'] = 0;   //artist
        $weights['mtc'] = 0;   //composer
        $weights['mtf'] = 0;   //folder
        $weights['mti'] = 0;   // archive folders and files
        $weights['mtk'] = 0;   // video keywords
        $weights['mtl'] = 0;   // album
        $weights['mtt'] = 0;   // title */

        $this->cl->SetFieldWeights($weights);
        $this->cl->SetSelect("*, @weight as sw, w*w*ATAN(@weight/40000) as fw");
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "fw DESC" );
        $this->cl->SetMaxQueryTime(1000);

        $this->urls = array();
    }

    public function setFileUtils($fileutils)
    {
        $this->fileutils  = $fileutils;
    }

    public function setFilters($conditions)
    {
        global $content;

        if(!is_array($conditions) AND !empty($conditions))
            $conditions = array( $conditions );

        $this->cl->ResetFilters();
        $this->cl->SetFilter('bl', array(0));

        $this->type = $conditions['type'];
        if ($this->type)
        {
            $types = Model_Files::ct2ints($this->type);
            if ($types) $this->cl->SetFilter('ct', $types);
        }

        $this->src = $conditions['src'];
        if ($this->src)
        {
            $this->srcs = array();
            foreach (str_split("swftge") as $s)
            {
                if (strstr($this->src, $s)) $this->srcs = array_merge($this->srcs, Model_Files::src2ints($s));
            }
            if (count($this->srcs)>0) $this->cl->SetFilter('t', $this->srcs);
        }

        $this->size = $conditions['size'];
        if ($this->size)
        {
            switch ($this->size)
            {
                case 1:
                    $this->cl->SetFilterRange('z', 1, 1048576);
                    break;
                case 2:
                    $this->cl->SetFilterRange('z', 1, 10485760);
                    break;
                case 3:
                    $this->cl->SetFilterRange('z', 1, 104857600);
                    break;
                case 4:
                    $this->cl->SetFilterRange('z', 0, 104857600, true);
                    break;
            }
        }

        $this->brate = $conditions['brate'];
        if ($this->brate)
        {
            switch ($this->brate)
            {
                case 1:
                    $this->cl->SetFilterRange('mab', 0, 127, true);
                    break;
                case 2:
                    $this->cl->SetFilterRange('mab', 0, 191, true);
                    break;
                case 3:
                    $this->cl->SetFilterRange('mab', 0, 255, true);
                    break;
                case 4:
                    $this->cl->SetFilterRange('mab', 0, 319, true);
                    break;
            }
        }

        $this->year = $conditions['year'];
        if ($this->year)
        {
            switch ($this->year)
            {
                case 1:
                    $this->cl->SetFilterRange('may', 0, 59);
                    break;
                case 2:
                    $this->cl->SetFilterRange('may', 60, 69);
                    break;
                case 3:
                    $this->cl->SetFilterRange('may', 70, 79);
                    break;
                case 4:
                    $this->cl->SetFilterRange('may', 80, 89);
                    break;
                case 5:
                    $this->cl->SetFilterRange('may', 90, 99);
                    break;
                case 6:
                    $this->cl->SetFilterRange('may', 100, 109);
                    break;
                case 7:
                    $nowy = (int)date('Y');
                    $this->cl->SetFilterRange('may', $nowy-1, $nowy);
                    break;
            }
        }

        $this->query = preg_replace("/[\W_]-[\W_]/iu", " ", $conditions['q']);
    }

    public function justCount()
    {
        $start_time = microtime(true);
        $this->cl->SetLimits( 0, 1, 1);
        $this->cl->SetMaxQueryTime(100);

        $result = $this->cl->Query( $this->query, $this->table );

        $this->time += (microtime(true) - $start_time);

        if ( $result === false )
            return null;
        else
            return $result['total_found'];
    }

    public function getItems($offset, $itemCountPerPage)
    {

        global $content;
        $this->cl->SetLimits( $offset, $itemCountPerPage, MAX_RESULTS, MAX_HITS);
        $result = $this->cl->Query( $this->query, $this->table );

        $docs = array();
        if ( $result !== false  ) {

            if ( $this->cl->GetLastWarning() ) {
              //echo "WARNING: " . $this->cl->GetLastWarning() . "";
            }

            $this->tcount = $result["total_found"];
            $this->time = $result["time"];

            if (!empty($result["matches"]) ) {
                $ids = array();
                foreach ( $result["matches"] as $doc => $docinfo )
                {
                    $uri = $this->fileutils->longs2uri($docinfo["attrs"]["uri1"], $docinfo["attrs"]["uri2"], $docinfo["attrs"]["uri3"]);
                    $hexuri = $this->fileutils->uri2hex($uri);
                    $docs[$hexuri] = array();
                    $docs[$hexuri]["search"] = $docinfo['attrs'];
                    $docs[$hexuri]["search"]["id"] = $doc;
                    $ids []= new MongoId($hexuri);
                }
                $fmodel = new Model_Files();
                $files = $fmodel->getFiles( $ids );
                foreach ($files as $file) {
                    $hexuri = $file['_id']->__toString();
                    $obj = $docs[$hexuri];
                    $obj['file'] = $file;

                    $this->fileutils->chooseFilename($obj, $this->query);
                    $this->fileutils->buildSourceLinks($obj);
                    $this->fileutils->chooseType($obj, $this->type);
                    $docs[$hexuri] = $obj;
                }
                foreach ($docs as $hexuri => $doc)
                {
                    if (!isset($doc['file']) || $doc['file']['bl']!=0) {
                        if (isset($doc["search"]) && isset($doc['file']) && $doc['file']['bl']!=0) $this->cl->UpdateAttributes("idx_files", array("bl"), array($doc["search"]["id"] => array(3)));
                        $this->tcount--;
                        unset($docs[$hexuri]);
                    }
                    else {
                        foreach ($docs[$hexuri]['view']['sources'] as $type => $src)
                        {
                            if($src['icon']=="web") {
                                $this->urls = array_merge($this->urls, $src['urls']);
                            }
                        }
                    }
                }
            }
        }

        return $docs;
    }

    public function getUrls()
    {
        return $this->urls;
    }

    public function count()
    {
        return min($this->tcount, MAX_RESULTS);
    }
}