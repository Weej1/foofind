<?php
/**
 * This is the search form.
 */

class Form_Search extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'get' );
                
               //$this->setAction('/search/index');
                // add an email element
                $this->addElement ( 'text', 'q', array (
                    'required' => false,
                    'filters' => array ('StringTrim' )
                     ) );

                    $this->removeDecorator('HtmlTag');
                    $this->removeDecorator('DtDdWrapper');
                   // $this->groupname->removeDecorator('DtDdWrapper');
                // add the submit button
                $this->addElement ( 'submit', 'submit', array ('label' => 'Search' ) );
        }
}

