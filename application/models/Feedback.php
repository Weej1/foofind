<?php

class Model_Feedback
{
    private function prepareConnection()
    {
        if (!isset($this->db))
        {
            $db = Zend_Registry::get("db_feedback");
            if (!$db->connected) $db->connect();
            $this->db = $db->foofind;
        }
    }

    public function saveSubmittedLinks($urls)
    {
        include('Net/URL2.php');

        $this->prepareConnection();

        if (!is_array($urls)) $urls = array($urls);

        foreach ($urls as $url)
        {
            if (strlen($url)<3) continue;
            $un = new Net_URL2(trim($url));
            $this->db->old_links->save(array("_id"=>$un->getNormalizedURL()));
        }
    }

    public function saveVisitedLinks($urls)
    {
        include('Net/URL2.php');

        $this->prepareConnection();

        if (!is_array($urls)) $urls = array($urls);

        foreach ($urls as $url)
        {
            if (strlen($url)<3) continue;
            $un = new Net_URL2(trim($url));
            $this->db->vlinks->save(array("_id"=>$un->getNormalizedURL()));
        }
    }
}

