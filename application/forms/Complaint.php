<?php

/**
 * This is the main Complaint form.
 */

class Form_Complaint extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'post' );

                // add an name element
                $this->addElement ( 'text', 'name', array ('label' => 'Your name:', 'required' => true, 'filters' => array ( 'StringTrim' ), 'validators' => array ('alnum') ) );
                
                //add surname
                $this->addElement ( 'text', 'surname', array ('label' => 'Your surname:', 'filters' => array ( 'StringTrim' ), 'validators' => array ('alnum', array ('regex', false, array ('/^[a-z]/i' ) ), array ('StringLength', false, array (3, 20 ) ) ), 'required' => true ) );

                //add company
                $this->addElement ( 'text', 'company', array ('label' => 'Your company:', 'filters' => array ( 'StringTrim' ), 'validators' => array ('alnum', array ('regex', false, array ('/^[a-z]/i' ) ), array ('StringLength', false, array (3, 20 ) ) ), 'required' => false ) );

                // add an email element
                $this->addElement ( 'text', 'email', array ('label' => 'Your email:', 'required' => true, 'filters' => array ('StringTrim' ), 'validators' => array ('EmailAddress' ) ) );

                //add phonenumber
                $this->addElement ( 'text', 'phonenumber', array ('label' => 'Your phone number:', 'filters' => array ('StringTrim', 'StringToLower' ), 'validators' => array ('alnum', array ('StringLength', false, array (9, 30 ) ) ), 'required' => false ) );

                $this->addElement ( 'textarea', 'message', 
                        array ('label' => 'Your message:', 'validators' => array (array ('StringLength', false, array (20, 2000 ) ) ), 'required' => true,'rows' => 4,'cols' => 60 )

                 );

                $this->addElement ( 'captcha', 'captcha', array ('label' => 'Please, insert the 5 characters shown:', 'required' => true,
                    'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                     'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                     'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );

                // add the submit button
                $this->addElement ( 'submit', 'submit', array (
                    'label' => 'Send',
                    'class' => 'large magenta awesome') );
        }
}


