<?php

require_once ( APPLICATION_PATH . '/models/ContentType.php' );
class DownloadController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();

        $this->view->lang =  $this->_helper->checklang->check();
    }

    public function fileAction()
    {

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();


        //plugin Qs
        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';

        if ($_SERVER['HTTP_REFERER'])
        {
            parse_str(substr(strstr($_SERVER['HTTP_REFERER'], '?'), 1));

            $f = new Zend_Filter();
            $f->addFilter(new Zend_Filter_StringTrim());
            $f->addFilter(new Zend_Filter_StripTags($encoding));
            $q = $f->filter (trim(stripcslashes(strip_tags($q))));
            $type = $f->filter ( $type );
            $src = $f->filter ( $src );
            $src2 = ($src=='')?'wftge':$src;
            $form->getElement('q')->setValue($q);
            $form->addElement("hidden", "type", array("value"=>$type));
            $form->addElement("hidden", "src", array("value"=>$src));
        }


        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);

        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
    

        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        $form->loadDefaultDecoratorsIsDisabled(false);
        $form->setAction( '/'. $this->view->lang.'/search/');
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }

        // assign the form to the view
        $this->view->form = $form;


        //*************************************************************************** get file

                $id = $this->_request->getParam ( 'id' );
		$model = $this->_getModel ();
		$this->view->file = $model->getFile( $id );
                

                if ($this->view->file){ // if the id file exists then go for it

                        $this->view->metadata = $model->getMetadata( $id );
                        $this->view->sources = $model->getSources( $id );

                        $this->view->file_size = $this->_formatSize($this->view->file['Size']);
   
                        $this->view->headTitle()->append(' - '.$this->view->translate( 'download' ).' - ' );
                        $this->view->headTitle()->append($this->view->file['Filename']);

                } else {

                     $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'This link does not exist or may have been deleted!' ) );
		     $this->_redirect ( '/'.$this->view->lang );
                     return ;
                }


    }

   
    protected function _getModel() {
		if (null === $this->_model) {

			require_once APPLICATION_PATH . '/models/Download.php';
			$this->_model = new Model_Download ( );
		}
		return $this->_model;
	}



   
    /**
     *
     * @return Form_Search
     */
    protected function _getSearchForm() {
        require_once APPLICATION_PATH . '/forms/Search.php';
        $form = new Form_Search( );

        return $form;
    }

    //TODO refactor this function ,is duplicated from search controller (this sucks)
    protected function _formatSize($bytes)
    {
        $size = $bytes / 1024;
        if($size < 1024)
        {
            $size = number_format($size, 2);
            $size .= ' KB';
        }
        else
        {
            if ($size / 1024 < 1024)
            {
                $size = number_format($size / 1024, 2);
                $size .= ' MB';
            }
            else if ($size / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024, 2);
                $size .= ' GB';
            }
        }
        return $size;
    }





}