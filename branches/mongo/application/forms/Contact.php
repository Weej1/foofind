<?php

/**
 * This is the main Contact form.
 */

class Form_Contact extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'post' );

                // add an email element
                $this->addElement ( 'text', 'email', array ('label' => 'Your email:', 'required' => true, 'filters' => array ('StringTrim' ), 'validators' => array ('EmailAddress' ) ) );

                $this->addElement ( 'textarea', 'message', 
                        array ('label' => 'Your message:', 'validators' => array (array ('StringLength', false, array (20, 2000 ) ) ), 'required' => true,'rows' => 4,'cols' => 60 )

                 );

                $this->addElement ( 'captcha', 'captcha', array ('label' => 'Please, insert the 5 characters shown:', 'required' => true,
                    'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                     'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                     'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );

                $checkboxDecorator = array(
                                'ViewHelper',
                                'Errors',
                                array(array('data' => 'HtmlTag'), array('tag' => 'span', 'class' => 'element')),
                                array('Label', array('tag' => 'dt'),
                                array(array('row' => 'HtmlTag'), array('tag' => 'span')),
                            ));

                $this->addElement('checkbox', 'agree', array(
                    'decorators' => $checkboxDecorator,
                    'required' => true,
                    'checked' =>false
                    ));

                // add the submit button
                $this->addElement ( 'submit', 'submit', array (
                    'label' => 'Send',
                    'class' => 'large magenta awesome') );
        }
}


