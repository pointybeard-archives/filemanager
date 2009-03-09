<?php

	require_once('lib/class.file.php');

	Class extension_filemanager extends Extension{

		public function about(){
			return array('name' => 'File Manager',
						 'version' => '0.8',
						 'release-date' => '2008-01-31',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://www.pointybeard.com',
										   'email' => 'alistair@pointybeard.com'),
						 'description' => 'Upload, Edit and manage files and folders.'
				 		);
		}
		
		public static function baseURL(){
			return URL . '/symphony/extension/filemanager/';
		}
		
		public function fetchNavigation(){
			return array(
				array(
					'location' => 100,
					'name' => 'File Manager',
					'children' => array(
						array(
							'name' => 'Browse',
							'link' => '/browse/'
						)
					)
				)
				
			);
		}

		public function getSubscribedDelegates(){
			return array(
						
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),	
						
						array(

							'page' => '/system/preferences/',
							'delegate' => 'Save',
							'callback' => 'savePreferences'

						),										
			);
		}

		public function savePreferences($context){

			$conf = array_map('trim', $context['settings']['filemanager']);
			
			$context['settings']['filemanager'] = array(
														  'show-hidden' => (isset($conf['show-hidden']) ? 'yes' : 'no'),
														  'start-location' => '/' . trim($conf['start-location'], '/ '),
														);

		}

		public function appendPreferences($context){
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'File Manager'));

			$label = Widget::Label('Root Browse Location');
			$label->appendChild(Widget::Input('settings[filemanager][start-location]', General::Sanitize(Administration::instance()->Configuration->get('start-location', 'filemanager'))));		
			$group->appendChild($label);
			
			$group->appendChild(new XMLElement('p', 'This path is relative to the root Symphony installation folder, <code>'.DOCROOT.'</code>', array('class' => 'help')));

			$label = Widget::Label();
			$input = Widget::Input('settings[filemanager][show-hidden]', 'yes', 'checkbox');
			if(Administration::instance()->Configuration->get('show-hidden', 'filemanager') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Show Hidden Files and Folders');
			$group->appendChild($label);

			$group->appendChild(new XMLElement('p', 'Hidden files will not be included in archives unless this is checked.', array('class' => 'help')));
						
			$context['wrapper']->appendChild($group);
						
		}
		
		public static function getFileMIMEType($file){

			$types = array(
				'/.(jpg|jpeg)$/i'  => 'image/jpeg',
				'/.gif$/i'         => 'image/gif',
				'/.png$/i'         => 'image/png',
				'/.pdf$/i'         => 'application/pdf'
			);

			foreach ($types as $pattern => $mimetype) {
				if (preg_match($pattern, $file)) return $mimetype;
			}

			return 'application/octet-stream';
	
		}
		
		public function download($file){
			
			$file = DOCROOT . $this->getStartLocation() . $file;
			
			if(!file_exists($file)) 
				$this->_Parent->customError(E_USER_ERROR, 'File Not Found', 'The file you requested, <code>'.$file.'</code>, does not exist.', false, true, 'error', array('header' => 'HTTP/1.0 404 Not Found'));
				
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Type: ' . self::getFileMIMEType($file));
			header('Content-Disposition: attachment; filename=' . basename($file) . ';');
			header('Content-Length: ' . filesize($file));

			readfile($file);

			break;				
				
		}
		
		public function getStartLocation(){
			return Administration::instance()->Configuration->get('start-location', 'filemanager');
		}
		
		public static function buildBreadCrumbs(array $crumbs){

			if(empty($crumbs)) return;
			
			$ArrayObject = new ArrayObject($crumbs);
			$Iterator = $ArrayObject->getIterator();
			
			$result = NULL;
			
			while($Iterator->valid()){
				$result .= $Iterator->current() . '/';
				$Iterator->next();
			}
			
			return $result;
		}
		
		public function findAvailableArchiveName($path){

			$count = 0;
			$filename = NULL;
			
			if(is_dir($path)):
				
				do{
					
					$filename = 'Archive' . ($count == 0 ? NULL : "-$count") . '.zip';
					$count++;
					
				}while(file_exists($path . '/' . $filename));
				
			
			
			else:
				
				do{
					
					$filename = basename($path) . ($count == 0 ? NULL : "-$count") . '.zip';
					$count++;
					
				}while(file_exists(dirname($path) . '/' . $filename));
				
			endif;
			
			return $filename;
			
		}
		
		public function createArchive(array $files, $path=NULL){
			
			require_once(TOOLKIT . '/class.archivezip.php');
			$archive = new ArchiveZip;
			
			$flag = (Administration::instance()->Configuration->get('show-hidden', 'filemanager') == 'yes' ? ArchiveZip::IGNORE_HIDDEN : NULL);
			
			$root = DOCROOT . $this->getStartLocation();
			
			foreach($files as $f){
				
				if(is_dir($root . $f)) $archive->addDirectory($root . $f, $root . rtrim($path, '/'), $flag);
				else $archive->addFromFile($root . $f, basename($f));

			}
			
			$zip_file = $root . rtrim($path, '/') . '/' . $this->findAvailableArchiveName($root . (count($files) > 1 || is_dir($root . $files[0]) ? rtrim($path, '/') : $f));
			
			$archive->save($zip_file);
			
			return (@file_exists($zip_file) ? $zip_file : NULL);
			
		}
		
		public function buildTableRow(DirectoryIterator $file, $includeParentDirectoryDots=true){

			if(!$file->isDot() && substr($file->getFilename(), 0, 1) == '.' && Administration::instance()->Configuration->get('show-hidden', 'filemanager') != 'yes') return;
			elseif($file->isDot() && !$includeParentDirectoryDots && $file->getFilename() == '..') return;
			elseif($file->getFilename() == '.') return;
			
			$relpath = str_replace(DOCROOT . $this->getStartLocation(), NULL, $file->getPathname());
			
			if(!$file->isDir()){
				
				//if(File::fileType($file->getFilename()) == self::CODE)
				//	$download_uri = self::baseURL() . 'edit/?file=' . urlencode($relpath);
					
				//else
					$download_uri = self::baseURL() . 'download/?file=' . urlencode($relpath);
			}
			
			else $download_uri = self::baseURL() . 'browse' . $relpath . '/';
			
			if(!$file->isDot()){
				$td1 = Widget::TableData(Widget::Anchor($file->getFilename(), $download_uri, NULL, 'file-type ' . ($file->isDir() ? 'folder' : File::fileType($file->getFilename()))));
	
				//$group = (function_exists('posix_getgrgid') ? posix_getgrgid($file->getGroup()) : $file->getGroup());
				//$owner = (function_exists('posix_getpwuid') ? posix_getpwuid($file->getOwner()) : $file->getOwner());

				$group = $file->getGroup();
				$owner = $file->getOwner();

				$td3 = Widget::TableData(File::getReadablePerm($file->getPerms()), NULL, NULL, NULL, array('title' => File::getOctalPermission($file->getPerms()) . ', ' . (isset($owner['name']) ? $owner['name'] : $owner) . ':' . (isset($group['name']) ? $group['name'] : $group)));
				
				if($file->isWritable())
					$td4 = Widget::TableData(Widget::Anchor('Edit', self::baseURL() . 'properties/?file=' . urlencode($relpath)));
					
				else
					$td4 = Widget::TableData('-', 'inactive');	
				
			}
			
			else{
				$td1 = Widget::TableData(Widget::Anchor('&crarr;', $download_uri));
				$td3 = Widget::TableData('-', 'inactive');
				$td4 = Widget::TableData('-', 'inactive');
			}

			$td2 = Widget::TableData(($file->isDir() ? '-' : General::formatFilesize($file->getSize())), ($file->isDir() ? 'inactive' : NULL));
	
			
			$startlocation = DOCROOT . $this->getStartLocation();
			
			if(!$file->isDot()) $td4->appendChild(Widget::Input('items['.str_replace($startlocation, '', $file->getPathname()) . ($file->isDir() ? '/' : NULL).']', NULL, 'checkbox'));
			
			return Widget::TableRow(array($td1, $td2, $td3, $td4));
						
		}
		

		
		private static function __countItemsInDirectory(DirectoryIterator $dir){
			$count = iterator_count($dir);
			return $count . ' item' . ($count > 1 ? 's' : NULL);
		}
	}
	
?>