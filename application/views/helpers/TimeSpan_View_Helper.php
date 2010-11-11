<?php

class TimeSpan_View_Helper extends Zend_View_Helper_Abstract
{
    static $worldBeginning = null;
    function __construct()
    {
        $this->dateParts = array("y"=>"year", "M"=>"month", "d"=>"day", "h"=>"hour", "m"=>"minute", "s"=>"second");
        $this->now = Zend_Date::now()->toValue();
        if ($worldBeginning==null) $worldBeginning = new Zend_Date(0);
    }

    function show_date_span($date)
    {
        $span = new Zend_Date($this->now-$date);
        foreach ($this->dateParts as $p=>$desc)
        {
            $diff = (int)$span->toValue($p) - $this->worldBeginning->toValue($p);
            if ($diff>0) break;
        }
        if ($diff>1) $desc.="s";
        return sprintf($this->view->translate("since"), $diff, $this->view->translate($desc));
    }
}