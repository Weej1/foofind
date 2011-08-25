<?php
require_once APPLICATION_PATH.'/models/Files.php';
require_once APPLICATION_PATH.'/controllers/helpers/SphinxPaginator.php';
require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';
require_once APPLICATION_PATH.'/views/helpers/FileUtils_View_Helper.php';
require_once APPLICATION_PATH.'../../library/Foofind/TamingTextClient.php';

define("MAX_HITS", 2000000);
define("MAX_RESULTS", 1000);
define("PAGE_SIZE", 10);

class SearchController extends Zend_Controller_Action {

    public function init() {
         //validate domain foofind
        $this->_helper->checkdomain->check();
        
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
        $this->view->lang = $this->_helper->checklang->check();
        $this->view->langcode = $this->_helper->checklang->getcode();
        
        $this->config = Zend_Registry::get('config');
    }

    public function getnames($res) {
        return $res[2];
    }

    public function getweights($res) {
        return 100*max(min(1.25, $res[0]/$this->mean), 0.75);
    }

    public function indexAction() {
        $qw = stripcslashes(strip_tags($this->_getParam('q')));
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $src = $this->_getParam('src');
        $opt = $this->_getParam('opt');
        $size = $this->_getParam('size');
        $year = $this->_getParam('year');
        $brate = $this->_getParam('brate');

        // Create a filter chain and add filters
        $encoding = array('quotestyle' => ENT_QUOTES, 'charset' => 'UTF-8');

        $f = new Zend_Filter();
        //$f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags($encoding));

        $q = $f->filter ( trim($qw ));
        $type = $f->filter ( $type );
        $src = $f->filter ( $src );
        $opt = $f->filter ( $opt );
        $size = $f->filter ( $size );
        $year = $f->filter ( $year );
        $brate = $f->filter ( $brate );

        if (!preg_match("/^Audio$/", $type)) $brate = null;
        if (!preg_match("/^Audio|Video$/", $type)) $year= null;

        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($qw);

        $form = $this->_getSearchForm();
         if (!$q) { // check if query search is empty

            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Hey! Write something' ) );
            $this->_redirect ( '/' );
            return ;
        }

        
        $form->getElement('q')->setValue(trim($q));

        $form->loadDefaultDecoratorsIsDisabled(false);

        $form->addElement("hidden", "type", array("value"=>$type));
        //$form->addElement("hidden", "src", array("value"=>$src));
        $form->addElement("hidden", "opt", array("value"=>$opt));
        
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }
                
        // assign the form to the view
        $this->view->form = $form;

        if(!$src)
        {
            if ($_COOKIE['src']) $src = $_COOKIE['src'];
        } else {
            setcookie( 'src', $src, null, '/' );
        }
        $srcs = array();
        $src2 = ($src=='')?'swftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);
        
        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);
        
        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
        
        $helper = new FileUtils_View_Helper();
        $helper->registerHelper($this->view);

        if ($page>MAX_RESULTS/PAGE_SIZE)
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Foofind does not allow browse results after page 1000.' ) );
            $this->_redirect("/{$this->view->lang}/search/".$helper->qs(array(), array('page'=>1)));
            return;
        }

        // build a caching object
        if ($this->config->cache->taming) {
            // build a caching object
            $oCache = Zend_Registry::get('cache');
            $tkey = "stm_".$this->view->lang."_".md5("m$q t$type");
            $existsTamingCache = $oCache->test($tkey);
        } else {
            $existsTamingCache = false;
        }

        if  ( $existsTamingCache  ) {
            $data = $oCache->load( $tkey );
            $this->view->tags = $data["tags"];
            $this->view->didyoumean = $data["dym"];
        } else {
            $tamingServer = explode(":", $this->config->taming->server);
            $taming = new TamingTextClient($tamingServer[0], (int)$tamingServer[1], $this->config->taming->timeout);
            $w = array("c"=>1, $this->view->lang=>200);
            if ($type) {
                foreach (Model_Files::ct2ints($type) as $cti)
                    $w[Model_Files::cti2sct($cti)] = 200;
            }
            $taming->beginTameText(trim($q)." ", $w, 20, 4, 0.7, 0);
        }

        // build a caching object
        if ($this->config->cache->searches) {
            // build a caching object
            $oCache = Zend_Registry::get('cache');
            $key = "srh_".$this->view->lang."_".md5("m$q s$src2 o$opt t$type s$size y$year b$brate p$page");
            $existsCache = $oCache->test($key);
        } else {
            $existsCache = false;
        }

        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            $paginator = $oCache->load( $key  );
            $paginator->getAdapter()->setFileUtils($this->_helper->fileutils);
        } else {
            $SphinxPaginator = new SphinxPaginator('idx_files');
            $SphinxPaginator->setFileUtils($this->_helper->fileutils);
            $SphinxPaginator->setFilters($conds);

            $paginator = new Zend_Paginator($SphinxPaginator);
            $paginator->setDefaultScrollingStyle('Elastic');
            $paginator->setItemCountPerPage(PAGE_SIZE);
            $paginator->setCurrentPageNumber($page);
            $paginator->getCurrentItems();

            $paginator->words = $SphinxPaginator->words;
            $paginator->tcount = $SphinxPaginator->tcount;
            $paginator->showImages = $SphinxPaginator->showImages;
            if (isset($SphinxPaginator->time)) $paginator->time = $SphinxPaginator->time;
            if (isset($conds['type']) && $SphinxPaginator->count()==0)
            {
                $conds['type']=null;
                $SphinxPaginator->setFilters($conds);
                $paginator->noTypeCount = $SphinxPaginator->justCount();
             } else {
                $paginator->noTypeCount = "";
             }
            
            $paginator->getAdapter()->setFileUtils(null);
            if ($this->config->cache->searches && $SphinxPaginator->canCacheResults()) $oCache->save( $paginator, $key );

            if (strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "bot")===false) {
                try {
                $urls = $SphinxPaginator->getUrls();
                if (count($urls)>0) {
                    require_once APPLICATION_PATH . '/models/Feedback.php';
                    $fbmodel = new Model_Feedback ( );
                    $fbmodel->saveVisitedLinks($urls);
                }
                } catch (Exception $e) {}
            }
        }

        if (!$existsTamingCache) {
            $tags = json_decode($taming->endTameText());

            $this->view->tags = array();
            $this->view->tags["count"] = count($tags);
            if ($this->view->tags["count"]>0) {
                $this->mean = ($tags[0][0] + $tags[$this->view->tags["count"]-1][0])/2;
                $this->view->tags["names"] = array_map(array($this,'getnames'), $tags);
                $this->view->tags["weights"] = array_map(array($this,'getweights'), $tags);
                array_multisort($this->view->tags["names"], SORT_ASC, SORT_STRING, $this->view->tags["weights"]);
            }

            $taming->beginTameText($q, $w, 1, 3, 0.8, 1, 0);
        }

        $this->view->info = array('words'=>$paginator->words,'total'=>$paginator->tcount, 'time'=>$paginator->time, 'q' => $q, 'start' => 1+($page-1)*PAGE_SIZE, 'end' => min($paginator->tcount, $page*PAGE_SIZE), 'notypecount' => $paginator->noTypeCount);
        $this->view->paginator = $paginator;

        $jquery = $this->view->jQuery();
        $jquery->enable(); // enable jQuery Core Library

        $onload = '$("#show_options").click(function() '
                  . '{'
                    . 'active = $("#show_options").attr("active")=="1";'
                    . 'switchOptions(active, true);'
                  . '});'
                  . 'switchOptions('.($opt?'false':'true').', false);'
                  . 'configTaming("'.$this->view->lang.'");';

        if ($paginator->showImages)
        {
            $onload.= "$('.thumb').mouseenter(function() {"
                            ."if (thumbani!=0) clearInterval(thumbani);"
                            ."jimage = $(this);"
                            ."jimagecount = parseInt(jimage.attr('ic'));"
                            ."thumbani = setInterval(animateImage, 500);"
                        ."});"
                        ."$('.thumb').mouseleave(function() {"
                            ."if (thumbani!=0) clearInterval(thumbani);"
                        ."});"
                        ."$('.thumb').each(function() {"
                            ."icount = parseInt($(this).attr('ic'));"
                            ."src = $(this).attr('src').slice(0,-1);"
                            ."for (i=0; i<icount; i++) $('<img/>')[0].src = src+i.toString();"
                        ."});";
        }
        $function = 'function switchOptions(active, fade) {'
                    . 'if (active) {'
                        . '$("#results").removeClass("padding");'
                        . '$("#advsearch").toggle(false);'
                        . '$("#show_options").text("'.$this->view->translate('advanced search').'");'
                    . '} else {'
                    . '$("#results").addClass("padding");'
                        . 'if (fade) $("#advsearch").fadeIn(); else $("#advsearch").toggle(true);'
                        . '$("#show_options").text("'.$this->view->translate('hide advanced search').'");'
                    . '} $("#show_options").attr("active", 1-(active?1:0));'
                  . '}';
        $jquery->addJavascript($function);
        $jquery->addOnload($onload);

        if (!$existsTamingCache)
        {
            $result = json_decode($taming->endTameText());
            if ($result && $result[0][2]!=$q) $this->view->didyoumean = $result[0][2];

            if ($this->config->cache->taming) {
                $data = array("tags"=>$this->view->tags, "dym"=>null);
                if (isset($this->view->didyoumean)) $data["dym"]=$this->view->didyoumean;
                $oCache->save( $data, $tkey );
            }
        }
    }

    /**
     *
     * @return Form_Search
     */
    protected function _getSearchForm() {
            require_once APPLICATION_PATH . '/forms/Search.php';
            $form = new Form_Search( );
            return $form;
    }


    public function index3Action() {
        $this->_helper->layout()->disableLayout();
        $this->index2Action();
    }

    public function index2Action() {

        $this->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.download.min.js');
        $qw = stripcslashes(strip_tags($this->_getParam('q')));
        $filts = $this->_getParam('s', null);
        $filts0 = $this->_getParam('s0', null);
        $filts1 = $this->_getParam('s1', null);
        $filts2 = $this->_getParam('s2', null);
        $filtt = $this->_getParam('t', null);
        $filtz = $this->_getParam('z', null);
        $filtl = $this->_getParam('l', null);
        $filty = $this->_getParam('y', null);
        $offset = (int)$this->_getParam('offset');

        // Create a filter chain and add filters
        $encoding = array('quotestyle' => ENT_QUOTES, 'charset' => 'UTF-8');

        $f = new Zend_Filter();
        //$f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags($encoding));


        $q = $f->filter ( trim($qw ));
        $filts = $f->filter ( $filts );
        $filts0 = $f->filter ( $filts0 );
        $filts1 = $f->filter ( $filts1 );
        $filts2 = $f->filter ( $filts2 );
        $filtt = $f->filter ( $filtt );
        $filtz = $f->filter ( $filtz );
        $filtl = $f->filter ( $filtl );
        $filty = $f->filter ( $filty );
        $offset = $f->filter ( $offset );

        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($qw);

        $form = $this->_getSearchForm();
        if (!$q) { // check if query search is empty
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Hey! Write something' ) );
            $this->_redirect ( '/' );
            return ;
        }
        $form->getElement('q')->setValue(trim($q));

        $form->loadDefaultDecoratorsIsDisabled(false);

        //$form->addElement("hidden", "type", array("value"=>$type));
        //$form->addElement("hidden", "src", array("value"=>$src));
        //$form->addElement("hidden", "opt", array("value"=>$opt));

        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }

        // assign the form to the view
        $this->view->form = $form;

        $conds = array('q'=>trim($q));
        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);

        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;

        $this->view->info = array('q' => $q);

        $helper = new FileUtils_View_Helper();
        $helper->registerHelper($this->view);

        require_once APPLICATION_PATH.'../../library/Sphinx/sphinxapi.php';
        $sphinxServer = $this->config->sphinx->server;

        $sphinx = new SphinxClient();
        $sphinx->SetServer( $sphinxServer, 3312 );
        $sphinx->SetMatchMode( SPH_MATCH_EXTENDED2 );
        $sphinx->SetRankingMode( SPH_RANK_SPH04 );        $weights["fn1"] = 5;
        for ($i = 2; $i < 21; $i++)
            $weights["fn$i"] = 3;

        // metadata
        $weights['mta'] = 3;   //artist
        $weights['mtc'] = 3;   //composer
        $weights['mtf'] = 3;   //folder
        $weights['mti'] = 1;   // archive folders and files
        $weights['mtk'] = 1;   // video keywords
        $weights['mtl'] = 3;   // album
        $weights['mtt'] = 3;   // title
        $weights['surl'] = 1;   // url

        $sphinx->SetFieldWeights($weights);
        $sphinx->SetSelect("*, @weight/(fc+tc) as sw");
        $sphinx->SetSortMode( SPH_SORT_EXTENDED, "w DESC, sw DESC, ls DESC" );
        $sphinx->SetFilter('bl', array(0));

        $filter = array();
        if ($filts!=null){
            $types = array(array(4,8,10,11,13,14,19,20,8,9), array(12,15,16,17,18), array(3,107,1,5,6,2));
            foreach (explode(",", $filts) as $s) $filter = array_merge($filter, $types[$s]);
        }
        if ($filts0!=null){
            $types = array(array(10), array(13), array(11), array(14),array(19), array(4), array(8,9));
            foreach (explode(",", $filts0) as $s) $filter = array_merge($filter, $types[$s]);
        }
        if ($filts1!=null){
            $types = array(array(18), array(12), array(16), array(17),array(15));
            foreach (explode(",", $filts1) as $s) $filter = array_merge($filter, $types[$s]);
        }
        if ($filts2!=null){
            $types = array(array(3,107), array(1,5,6), array(2));
            foreach (explode(",", $filts2) as $s) $filter = array_merge($filter, $types[$s]);
        }
        if (count($filter)>0) $sphinx->SetFilter('t', $filter);

        if ($filtt!=null){
            $filter = array();
            $types = array(array(1), array(2,18), array(3,9,10,11), array(5),array(4,7), array(6,8));
            foreach (explode(",", $filtt) as $t) $filter = array_merge($filter, $types[$t]);
            if (count($filter)>0) $sphinx->SetFilter('ct', $filter);
        }
        if ($filtz!=null){
            $filter = array();
            $sizes = array(array(0,1), array(1,20), array(20,100), array(100,600),array(600,10240));
            $min=10241; $max = 0;
            foreach (explode(",", $filtz) as $z)
            {
                $min = min($min, $sizes[$z][0]);
                $max = max($max, $sizes[$z][1]);
                $sizes[$z] = null;
            }
            $sphinx->SetFilterRange("z", $min*1048576, $max*1048576);
            foreach ($sizes as $z=>$range)
                if ($range!=null) $sphinx->SetFilterRange("z", $range[0]*1048576, $range[1]*1048576, true);
        }
        if ($filtl!=null){
            $filter = array();
            $lengths = array(array(0,300), array(300,1800), array(1800, 100000000));
            $min=100000001; $max = 0;
            foreach (explode(",", $filtl) as $l)
            {
                $min = min($min, $lengths[$l][0]);
                $max = max($max, $lengths[$l][1]);
                $lengths[$l] = null;
            }
            $sphinx->SetFilterRange("mal", $min, $max);
            foreach ($lengths as $l=>$range)
                if ($range!=null) $sphinx->SetFilterRange("mal", $range[0], $range[1], true);
        }
        if ($filty!=null){
            $filter = array();
            $years = array(array(0,60), array(60,80), array(80, 100), array(100,110), array(110,200));
            $min=201; $max = 0;
            foreach (explode(",", $filty) as $y)
            {
                $min = min($min, $years[$y][0]);
                $max = max($max, $years[$y][1]);
                $years[$y] = null;
            }
            $sphinx->SetFilterRange("may", $min, $max);
            foreach ($years as $y=>$range)
                if ($range!=null) $sphinx->SetFilterRange("may", $range[0], $range[1], true);
        }

        if (!is_int($offset) || $offset==0) {
            $tamingServer = explode(":", $this->config->taming->server);
            $taming = new TamingTextClient($tamingServer[0], (int)$tamingServer[1], $this->config->taming->timeout);
            $w = array("c"=>1, $this->view->lang=>200);
            if ($type) {
                foreach (Model_Files::ct2ints($type) as $cti)
                    $w[Model_Files::cti2sct($cti)] = 200;
            }

            $taming->beginTameText($q, $w, 1, 3, 0.8, 1, 0);
            $result = json_decode($taming->endTameText());
            if ($result && $result[0][2]!=$q) $didyoumean = $result[0][2];


            $taming->beginTameText(trim($q)." ", $w, 20, 4, 0.7, 0);
            $tags = json_decode($taming->endTameText());

            $this->view->tags = array();
            $this->view->tags["count"] = count($tags);
            if ($this->view->tags["count"]>0) {
                $this->mean = ($tags[0][0] + $tags[$this->view->tags["count"]-1][0])/2;
                $this->view->tags["names"] = array_map(array($this,'getnames'), $tags);
                $this->view->tags["weights"] = array_map(array($this,'getweights'), $tags);
                array_multisort($this->view->tags["names"], SORT_ASC, SORT_STRING, $this->view->tags["weights"]);
            }

            $colors = 1;
            $acum = 0.001;
            $queries = array();
            foreach ($tags as $tag)
            {
                if ($tag[0]/$acum<0.05) break;
                $acum += $tag[0];
                $queries []= $tag[2];
                $colors++;
            }
            if (isset($didyoumean)) $colors++;

            $sphinx->SetMaxQueryTime(1200/$colors);
            $sphinx->SetLimits(0, 20-$colors);
            $sphinx->AddQuery($q,"idx_files");

            $sphinx->SetLimits( 0, 2, 4);
            if (isset($didyoumean)) $sphinx->AddQuery($didyoumean,"idx_files");
            foreach ($queries as $query) $sphinx->AddQuery($query, "idx_files");

            $results = $sphinx->RunQueries();
        } else {
            $sphinx->SetMaxQueryTime(300);
            $sphinx->SetLimits($offset, 10);
            $results = array($sphinx->Query($q, "idx_files"));
        }

        $docs = array();
        $sdocs = array();
        $ids = array();

        $i=0;
        foreach ($results as $result)
        {
            if (!empty($result["matches"]) ) {

                if ($i==0) $this->view->words = $result["words"];
                $words = implode("",array_slice(array_keys($result["words"]), count($this->view->words)));
                foreach ( $result["matches"] as $doc => $docinfo )
                {
                    $uri = $this->_helper->fileutils->longs2uri($docinfo["attrs"]["uri1"], $docinfo["attrs"]["uri2"], $docinfo["attrs"]["uri3"]);
                    $hexuri = $this->_helper->fileutils->uri2hex($uri);
                    if (isset($docs[$hexuri]))
                    {
                        $docs[$hexuri]["search"]["words"] []= $words;

                    } else {
                        $docs[$hexuri] = array();
                        $class = "";
                        foreach ($docinfo['attrs'] as $key=>$val)
                        {
                            switch ($key)
                            {
                                case "ct":
                                    $class.=" c$val";
                                    break;
                                case "t":
                                    foreach ($val as $v) $class .=" t$v";
                                    break;

                                case "z":
                                    if ($val==0) break; //nothing
                                    $size = $val/1048576;
                                    if ($size<=1) $class.=" z1";
                                    if ($size>=1 && $size<=20) $class.=" z2";
                                    if ($size>=20 && $size<=100) $class.=" z3";
                                    if ($size>=100 && $size<=600) $class.=" z4";
                                    if ($size>=600) $class.=" z5";
                                    break;
                                case "mal":
                                    if ($val==0) break; //nothing
                                    elseif ($val<300) $class.=" l1";
                                    elseif ($val<1800) $class.=" l2";
                                    else $class.=" l3";
                                    break;
                                case "may":
                                    if ($val==0) break; //nothing
                                    if ($val<=60) $class.=" y1";
                                    if ($val>=60 && $val<=80) $class.=" y2";
                                    if ($val>=80 && $val<=100) $class.=" y3";
                                    if ($val>=100 && $val<=110) $class.=" y4";
                                    if ($val>=110) $class.=" y5";
                                    break;
                            }
                        }
                        $docs[$hexuri]["class"] = $class;
                        $docs[$hexuri]["search"] = $docinfo['attrs'];
                        $docs[$hexuri]["search"]["id"] = $doc;
                        $docs[$hexuri]["search"]["words"] = array($words);
                    }
                    $ids []= new MongoId($hexuri);
                }
            }
            $i++;
        }

        $fmodel = new Model_Files();
        $files = $fmodel->getFiles( $ids );
        foreach ($files as $file) {
            $hexuri = $file['_id']->__toString();
            $obj = $docs[$hexuri];
            $obj['file'] = $file;

            if (isset($file['votes'][$this->lang]['c']))
                $obj['view']['votes'] = $file['votes'][$this->lang]['c'];
            else
                $obj['view']['votes'] = array(0,0);
            $this->_helper->fileutils->chooseFilename($obj, $q);
            $this->_helper->fileutils->buildSourceLinks($obj);
            $this->_helper->fileutils->chooseType($obj, $type);

            $docs[$hexuri] = $obj;
            $sdocs[$hexuri] = $obj["search"]["w"];
        }

        arsort($sdocs);
        $this->view->docs = $docs;
        $this->view->sdocs = $sdocs;


        $jquery = $this->view->jQuery();
        $jquery->enable(); // enable jQuery Core Library
        $onload.= "$('.thumb img').live('mouseenter', function() {"
                        ."if (thumbani!=0) clearInterval(thumbani);"
                        ."jimage = $(this);"
                        ."jimagecount = parseInt(jimage.attr('ic'));"
                        ."thumbani = setInterval(animateImage, 500);"
                    ."});"
                    ."$('.thumb img').live('mouseleave', function() {"
                        ."if (thumbani!=0) clearInterval(thumbani);"
                    ."});";
        $function = 'function switchOptions(active, fade) {'
                    . 'if (active) {'
                        . '$("#results").removeClass("padding");'
                        . '$("#advsearch").toggle(false);'
                        . '$("#show_options").text("'.$this->view->translate('advanced search').'");'
                    . '} else {'
                    . '$("#results").addClass("padding");'
                        . 'if (fade) $("#advsearch").fadeIn(); else $("#advsearch").toggle(true);'
                        . '$("#show_options").text("'.$this->view->translate('hide advanced search').'");'
                    . '} $("#show_options").attr("active", 1-(active?1:0));'
                  . '}';
        $jquery->addJavascript($function);
        $jquery->addOnload($onload);
    }
}