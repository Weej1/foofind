<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Metadata_Paginator implements Zend_Paginator_Adapter_Interface {

    public function __construct($key)
    {
        include_once 'sas.php';
        $this->key = $key;
    }

    public function getItems($offset, $itemCountPerPage)
    {
        $metadata = new Zend_Db_Table('ff_metadata');
        if ($this->key)
            $query = "SELECT m.*, round(count(*)/1000*me.Count) Count FROM (select * FROM ff_metadata where crcKey=$this->key limit 1000) m inner join ff_metadata_encoded me on m.crcKey=me.crcKey group by valueMD order by count desc limit $offset, $itemCountPerPage";
        else
            $query = "SELECT me.*, (select keyMD from ff_metadata m where m.crcKey=me.crcKey limit 1) KeyMD, (select valueMD from ff_metadata m where m.crcKey=me.crcKey limit 1) ValueMD FROM ff_metadata_encoded me order by count desc limit $offset, $itemCountPerPage;";
        return $metadata->getAdapter()->query($query)->fetchAll();
    }

    public function count()
    {
        $metadata = new Zend_Db_Table('ff_metadata');
        if ($this->key)
            $query = "SELECT SQL_CACHE Count c FROM ff_metadata_encoded where crcKey=$this->key";
        else
            $query = "SELECT SQL_CACHE count(*) c FROM ff_metadata_encoded";

        $count = $metadata->getAdapter()->query($query)->fetch();
        return $count["c"];
    }
}
class MetadataController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        $page = $this->_getParam("page");
        $key = $this->_getParam("key");

        $metadata = new Zend_Db_Table('ff_metadata');

        $MetadataPaginator = new Metadata_Paginator($key);

        $paginator = new Zend_Paginator($MetadataPaginator);
        $paginator->setItemCountPerPage(20);
        $paginator->setCurrentPageNumber($page);

        $this->view->key = $key;
        $this->view->paginator = $paginator;
    }
}
