<?php

class Form_Comment extends Zend_Form {
    public function init() {
        $this->setMethod ( 'post' );
        $this->addElement ( 'textarea', 'text', array (
            'validators' => array (array ('StringLength', false, array (10, 500 ) ) ), 'required' => true )
         );

        $text = $this->getElement('text');
        $val = $text->getValidator("StringLength")->setEncoding('UTF-8');
        $text->addFilter(new Zend_Filter_StringTrim());
        $text->addFilter(new Zend_Filter_StripTags());
        
        // add the submit button
        $this->addElement ( 'submit', 'submit', array ('label' => 'Post comment', 'class'=>'magenta awesome') );
    }
}
