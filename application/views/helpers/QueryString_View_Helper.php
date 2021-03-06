<?php

class QueryString_View_Helper extends Zend_View_Helper_Abstract
{
    private $params = array();

    public function setParams($new_params)
    {
        $this->params = $new_params;
    }

    public function qs($add, $delete=array())
    {
        $res = '';
        foreach ($this->params as $key => $val)
        {
            $pair = '&'.$key.'='.urlencode($val);
            try {
                $del = $delete[$key];
                if ($del) $pair = '';
            } catch (Exception $ex) {}
            
            try {
                $new = $add[$key];
                if ($new) $pair = '&'.$key.'='.urlencode($new);
            } catch (Exception $ex) {}
            $res .= $pair;
        }
        $res[0]='?';
        return $res;
    }
}
