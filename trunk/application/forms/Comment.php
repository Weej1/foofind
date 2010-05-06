<?php

class Form_Comment extends Zend_Form {
    public function init() {
        $this->setMethod ( 'post' );
        $text = $this->addElement ( 'textarea', 'text', array (
            'validators' => array (array ('StringLength', false, array (10, 500 ) ) ), 'required' => true )
         );

        $this->getElement('text')->addFilter(new Zend_Filter_StringTrim());
        // add the submit button
        $this->addElement ( 'submit', 'submit', array ('label' => 'Post comment', 'class'=>'magenta awesome') );
    }
}
