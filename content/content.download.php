<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionFileManagerDownload extends AdministrationPage{

		function __construct(&$parent){
			parent::__construct($parent);
			
			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			
			$file = $_REQUEST['file'];
			
			$FileManager->download($file);
			
			exit();
		}
		
	}
?>