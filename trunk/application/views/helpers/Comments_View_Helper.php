<?php

class Comments_View_Helper extends Zend_View_Helper_Abstract
{
    
    function format_comment($text)
    {
        return preg_replace_callback('/#(\d+)/', array($this, 'replace_callback'), $text);
    }

    function replace_callback($number)
    {
        if ($number[1]>$this->view->count) return "#{$number[1]}";
        $num = $this->view->count-$number[1];
        $this->view->comments_refs[] = $number[1];
        return "<a class='ttlink' tooltip='{$number[1]}' href='?page=".(floor($num/$this->view->paginator->getItemCountPerPage())+1)."#c{$number[1]}\'>#{$number[1]}</a>";
    }
}
