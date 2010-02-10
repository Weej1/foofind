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
                
               //$this->setAction('/search/index');
                // add an email element
                $this->addElement ( 'text', 'q', array (
                    'required' => false,
                    'filters' => array ('StringTrim' )
                     ) );

                    $this->removeDecorator('HtmlTag');
                    $this->removeDecorator('DtDdWrapper');

                $options = array(''=>'All');
                foreach ($content['types'] as $type => $info)
                    $options[$type] = $type;

                $typeCombo = $this->addElement("select", 'type', array('multiOptions'=>$options));
               

                   // $this->groupname->removeDecorator('DtDdWrapper');
                // add the submit button
                $this->addElement ( 'submit', 'submit', array ('label' => 'Search' ) );
        }
}

