<?php

class PageController extends Zend_Controller_Action
{

        /**
         * The default action - show the different pages
         */

        public function init(){
            $this->_helper->layout()->setLayout('page');
        }

        /*default action */
         public function indexAction(){

             $this->_redirect('/page/submit', $options);
       }
       

       public function submitAction(){
           
       }

       public function tosAction(){

       }

       public function privacyAction(){

       }

       
}

