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
            if (isset($add[$key]))
                $pair = '&'.$key.'='.urlencode($add[$key]);
            else if (isset($delete[$key]))
                $pair = '';
            else
                $pair = '&'.$key.'='.urlencode($val);

            $res .= $pair;
        }
        $res[0]='?';
        return $res;
    }
}
