<?php

class Zend_Controller_Action_Helper_Checklang extends Zend_Controller_Action_Helper_Abstract {

    static $langcodes = array('en'=>1, 'es'=>2, 'fr'=>3, 'it'=>4, 'pt'=>5, 'tr'=>6, 'zh'=>7, 'ca'=>8, 'gl'=>9);

    function init()
    {
        Zend_Registry::set('languages', array('en'=>'English', 'es'=>'Español', 'fr'=>'Français', 'it'=>'Italiano', 'pt'=>'Português', 'tr'=>'Türkçe', 'zh'=>html_entity_decode('&#31616;&#20307;&#20013;&#25991;', ENT_COMPAT, 'UTF-8'), 'ca'=>'Català', 'gl'=>'Galego' ));
        $activelangs = array('en'=>0, 'es'=>0, 'fr'=>1, 'it'=>1, 'pt'=>1, 'tr'=>1, 'zh'=>1, 'ca'=>1, 'gl'=>1);
        Zend_Registry::set('activelangs', $activelangs);

        $this->lang = $this->getRequest()->getParam("language");
        if ($this->lang == null)
        {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) $this->lang = $auth->getIdentity()->lang;
        }
        if ($this->lang == null && isset($_COOKIE['lang']))
            $this->lang = $_COOKIE['lang'];

        $locale = new Zend_Locale ($this->lang);

        if (!isset($activelangs[$locale->getLanguage()])) {
            $locale->setLocale ('en');
        }
        $this->lang = $locale->getLanguage();
        $this->langtest = $activelangs[$this->lang]==1;
        $options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
        $translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', $this->lang, $options );

        if ($translate->isAvailable ( $this->lang )) {
            $translate->setLocale ( $locale );
            Zend_Form::setDefaultTranslator ( $translate );
            Zend_Registry::set ( 'Zend_Translate', $translate );
        } else {
            header('Location: /en');
            exit;
        }

        if ($this->langtest && (!isset($_COOKIE['langtest']) || $_COOKIE['langtest']!="0")) {
            $controller = $this->getActionController();

            if (isset($controller->view)) {
                $controller->view->extra .= " advices";
                if (!$controller->view->advices) $controller->view->advices = array();
                $controller->view->advices["langtest"] = $controller->view->translate("BetaTranslation", "/{$this->lang}/page/translate?lang={$this->lang}");
                $controller->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.advice.js');
            }
        }
    }

    function check(){
        return $this->lang;
    }
    
    function getcode($lang = null)
    {
        if (!isset($lang)) $lang = $this->lang;
        return Zend_Controller_Action_Helper_Checklang::$langcodes[$lang];
    }
}

