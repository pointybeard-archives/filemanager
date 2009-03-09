<?php

	Class File{
		
		const OCTAL = 1;
		const DEC = 2;
		const READABLE = 3;

		const IMAGE = 'image';
		const CODE = 'code';
		const CSS = 'css';
		const DOC = 'doc';
		const PDF = 'pdf';				
		const VIDEO = 'video';
		const FILE = 'file';
		const FOLDER = 'folder';
		
		private $_path;
		
		function __construct($path){
			$this->_path = $path;
		}
		
		private function __isValidFile(){
			  return($this->isFile() || $this->isDir());
		}
		
		public function isFile(){
			return file_exists($this->_path);
		}		
		
		public function isDir(){
			return is_dir($this->_path);
		}
		
		public function isReadable(){
			return is_readable($this->_path);		
		}
		
		public function isWritable(){
			return is_writable($this->_path);
		}
		
		public function name(){
			return basename($this->_path);
		}
		
		public function path(){
			return dirname($this->_path);
		}
		
		public function contents(){
			return file_get_contents($this->_path);
		}
		
		public function permissions($type=self::OCTAL, $perms=NULL){
			switch($type){
				
				case self::OCTAL:
					return self::getOctalPermission(($perms ? $perms : fileperms($this->_path)));
					break;
					
				case self::READABLE:
					return self::getReadablePerm(($perms ? $perms : fileperms($this->_path)));
					break;
				
			}
		}

		public function setName($name){
			
			if(basename($this->_path) == $name) return true;
			
			if(file_exists(dirname($this->_path) . '/' . $name)) return false;
			
			$perm = $this->permissions();
			
			file_put_contents(dirname($this->_path) . '/' . $name, $this->contents());
			
			unlink($this->_path);
			
			$this->_path = dirname($this->_path) . '/' . $name;
			$this->setPermissions($perm);
			
		}
		
		public function setContents($contents){
			return file_put_contents($this->_path, $contents);
		}
		
		public function setPermissions($perm){
			return @chmod($this->_path, intval($perm, 8));
		}

		public static function getOctalPermission($perms){
			return substr(sprintf('%o', $perms), -4);
		}
		
		public static function getReadablePerm($perms){
			
			## http://au.php.net/manual/en/function.fileperms.php
			
			if (($perms & 0xC000) == 0xC000) {
			    // Socket
			    $info = 's';
			} elseif (($perms & 0xA000) == 0xA000) {
			    // Symbolic Link
			    $info = 'l';
			} elseif (($perms & 0x8000) == 0x8000) {
			    // Regular
			    $info = '-';
			} elseif (($perms & 0x6000) == 0x6000) {
			    // Block special
			    $info = 'b';
			} elseif (($perms & 0x4000) == 0x4000) {
			    // Directory
			    $info = 'd';
			} elseif (($perms & 0x2000) == 0x2000) {
			    // Character special
			    $info = 'c';
			} elseif (($perms & 0x1000) == 0x1000) {
			    // FIFO pipe
			    $info = 'p';
			} else {
			    // Unknown
			    $info = 'u';
			}

			// Owner
			$info .= (($perms & 0x0100) ? 'r' : '-');
			$info .= (($perms & 0x0080) ? 'w' : '-');
			$info .= (($perms & 0x0040) ?
			            (($perms & 0x0800) ? 's' : 'x' ) :
			            (($perms & 0x0800) ? 'S' : '-'));

			// Group
			$info .= (($perms & 0x0020) ? 'r' : '-');
			$info .= (($perms & 0x0010) ? 'w' : '-');
			$info .= (($perms & 0x0008) ?
			            (($perms & 0x0400) ? 's' : 'x' ) :
			            (($perms & 0x0400) ? 'S' : '-'));

			// World
			$info .= (($perms & 0x0004) ? 'r' : '-');
			$info .= (($perms & 0x0002) ? 'w' : '-');
			$info .= (($perms & 0x0001) ?
			            (($perms & 0x0200) ? 't' : 'x' ) :
			            (($perms & 0x0200) ? 'T' : '-'));
			
			return $info;			
		}

		public static function getExtension($file){
		    $parts = explode('.', basename($file));
			return array_pop($parts);
		}

		public static function fileType($file){

			switch(self::getExtension($file)){
				
				case 'png':
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'bmp':
				case 'tif':					
					return self::IMAGE;
				
				case 'doc':
				case 'txt':	
				case 'rtf':
				case 'pdf':					
					return self::DOC;
					
				case 'css':
				case 'js':
				case 'xsl':
				case 'xslt':
				case 'c':
				case 'cpp':
				case 'php':	
				case 'xml':																					
					return self::CODE;					
					
				case 'avi':
				case 'mov':
				case 'mpg':
				case 'mpeg':
				case 'm4a':
				case 'divx':
				case 'xvid':
				case 'wmf':
				case 'wmv':										
				case 'aif':
				case 'flv':
					return self::VIDEO;
			
			}
				
			return self::FILE;
		
		}
		
	}

?>