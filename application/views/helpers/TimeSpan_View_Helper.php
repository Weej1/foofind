<?php

class TimeSpan_View_Helper extends Zend_View_Helper_Abstract
{
    function __construct()
    {
        $this->dateParts = array("y"=>"year", "M"=>"month", "d"=>"day", "h"=>"hour", "m"=>"minute", "s"=>"second");
        $this->now = Zend_Date::now()->toValue();
        $this->base = new Zend_Date(0);
    }

    function show_date_span($date)
    {
        $span = new Zend_Date($date, "yyyy-MM-dd HH:mm:ss");
        $span = new Zend_Date($this->now-$span->toValue());
        foreach ($this->dateParts as $p=>$desc)
        {
            $diff = (int)$span->toValue($p) - $this->base->toValue($p);
            if ($diff>0) break;
        }
        if ($diff>1) $desc.="s";
        return sprintf($this->view->translate("since"), $diff, $this->view->translate($desc));
    }
}