<?php
/**
 * This is the search form.
 */

class Form_Search extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'get' );

               // $this->setAction('/search/files/q/', $_POST['q']);
                // add an email element
                $this->addElement ( 'text', 'q', array (
                    'required' => false,
                    'filters' => array ('StringTrim' ),
                    //'validators' => array ('EmailAddress' )
                     ) );


                // add the submit button
                $this->addElement ( 'submit', 'submit', array ('label' => 'Search' ) );
        }
}

