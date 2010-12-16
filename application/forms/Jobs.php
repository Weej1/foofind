<?php

/**
 * This is the main Contact form.
 */

class Form_Jobs extends Zend_Form {

        public function init() {
                // set the method for the display form to POST
                $this->setMethod ( 'post' );

                // add an email element
                $this->addElement ( 'text', 'email', array ('label' => 'Email:', 'required' => true, 'filters' => array ('StringTrim' ), 'validators' => array ('EmailAddress' ) ) );
                $this->addElement ( 'text', 'offer', array ('label' => 'Oferta:', 'required' => true, 'filters' => array ('StringTrim' ) ) );

                $this->addElement ( 'textarea', 'message', 
                        array ('label' => 'Mensaje:', 'validators' => array (array ('StringLength', false, array (20, 20000 ) ) ), 'required' => true,'rows' => 10,'cols' => 60 )

                );

                $this->setAttrib('enctype', 'multipart/form-data');
                $this->addElement('file', 'cv', array('label'=>'Curriculum Vitae (opcional, extensiones permitidas: pdf, doc, odt, html, htm, txt, rtf o zip):',
                    'destination' => "/tmp/attach",
                    'validators' => array(
                            array('Size', false, 2048000),
                            array('Extension', false, 'pdf,doc,odt,html,htm,txt,rtf,zip'),
                            array('Count', false, 1)
                )));

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


