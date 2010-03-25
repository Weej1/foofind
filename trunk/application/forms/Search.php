<?php
/**
 * This is the search form.
 */

require_once ( APPLICATION_PATH . '/models/ContentType.php' );


class Form_Search extends Zend_Form {

        public function init() {
                global $content;
                // set the method for the display form to POST
                $this->setMethod ( 'get' );
                $this->setAttrib("class", "searchbox");
                // add an email element
                $this->addElement ( 'text', 'q', array (
                    'required' => false,
                    'filters' => array ('StringTrim' )
                     ) );

                    $this->removeDecorator('HtmlTag');
                    $this->removeDecorator('DtDdWrapper');
                    $this->removeDecorator('dd');


                // add the submit button
                $this->addElement ( 'submit', 'submit', array (
                    'label' => 'Search',
                    'class' => 'large magenta awesome') );
        }

}

