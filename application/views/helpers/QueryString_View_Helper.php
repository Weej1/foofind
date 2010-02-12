<?php

class QueryString_View_Helper extends Zend_View_Helper_Abstract
{
    private $params = array();

    public function setParams($new_params)
    {
        $this->params = $new_params;
    }

    public function qs($override)
    {
        $res = '';
        foreach ($this->params as $key => $val)
        {
            $pair = '&'.$key.'='.$val;
            try {
                $o = $override[$key];
                if ($o) $pair = '&'.$key.'='.$o;
            } catch (Exception $ex) {}
            $res .= $pair;
        }
        $res[0]='?';
        return $res;
    }
}
