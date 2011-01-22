<?php

class Form_Comment extends Zend_Form {
    public function init() {
        $this->setMethod ( 'post' );
        $this->addElement ( 'textarea', 't', array (
            'validators' => array (array ('StringLength', false, array (1, 5000 ) ) ), 'required' => true )
         );

        $text = $this->getElement('t');
        $val = $text->getValidator("StringLength")->setEncoding('UTF-8');
        $text->addFilter(new Zend_Filter_StringTrim());
        $text->addFilter(new Zend_Filter_PregReplace(array('match'=>array("/\\\\([^\\\\])/",'/[\\n\\r\\f]+/'),
                                                           'replace'=>array('$1',"\\n"))));
        

        // add the submit button
        $this->addElement ( 'submit', 'submit', array ('label' => 'Post comment', 'class'=>'magenta awesome', 'onclick' =>'this.disabled=true;') );
    }
}
