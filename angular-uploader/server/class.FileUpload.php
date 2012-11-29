<?php

if(!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE',2147483647); //2147483647 2GB //104857600 100mb

class FileUpload
{
	
	var $max_file_size = UPLOAD_MAX_SIZE;
	
	var $allowed_file_endings = array();

	/*
	 * the target directory for the upload file
	 */	
	var $file_directory;
	
	/*
	 * the target directory for the upload file without applying function realpath
	 */	
	var $original_file_directory;

	/*
	 * the name of the upload file
	 */		
	var $file_name;

	/*
	 * the file ending for temporary upload files
	 */	
	var $tmp_file_ending = '.tmp';
	
	/*
	 * the current size of the tmp file
	 */
	var $tmp_current_size;
	
	/*
	 * the overall size of the tmp file due to its headers
	 */
	var $tmp_overall_size;

	/*
	 * the checksum of the tmp file due to its headers
	 */
	var $tmp_checksum;

	/*
	 * the target overall size as received from the client, specified with FileUpload($path,$size,$checksum)
	 */
	var $target_overall_size = 0;

	/*
	 * the target checksum as received from the client, specified with FileUpload($path,$size,$checksum)
	 */
	var $target_checksum;
	
	/*
	 * the offset where the file pointer currently resides
	 */
	#var $current_offset;	

	/*
	 * the offset specifying insertion position for new bytes to write
	 */
	var $insert_offset;	
	
	/*
	 * the resource handle for the tmp file created with open resp. file_open
	 */
	var $handle;
	
	/*
	 * id of last error
	 */
	var $error_id = 0;	
	
	/**
	 * Tmp file header:
	 * 
	 * last 8 bytes
	 * first 4 bytes are adler32 of the file (uint)
	 * second 4 bytes are the (expected) fileSize (int) // max value = 2.147.483.647 , i.e. 2gb
	 *
	 */
	
	/**
	 *
	 */
	function FileUpload($path='')
	{
		if ($path) $this->original_file_directory = $path;
		if($path) $this->set_target_directory($path);
	}	

	// MAIN METHODS
	/**
	 *
	 */
	function set_max_file_size($size=UPLOAD_MAX_SIZE)
	{
		$this->max_file_size = $size;
	}

	/**
	 *
	 */
	function set_target_directory($path)
	{
		$this->file_directory = realpath($path) . DIRECTORY_SEPARATOR;
	}

	/**
	 *
	 */
	function set_allowed_file_endings($allowed)
	{
		$this->allowed_file_endings = $allowed;
	}

	/**
	 *
	 */
	function set_target_file($file_name)
	{
		if($file_name && preg_match('#[^/\\:]#i',$file_name) )
		{
			$this->file_name = $file_name;
			
			$p = strrpos($file_name,'.');
			
			$this->file_ending = ($p===false) ? '' : substr($file_name, $p+1);
		}
	}

	/**
	 *
	 */
	function set_target_checksum($file_checksum)
	{
		if($file_checksum && preg_match('#[a-z0-9]+#i',$file_checksum) )
		{
			$this->target_checksum = $file_checksum;
		}
	}

	/**
	 *
	 */
	function set_target_size($file_size)
	{
		if($file_size && preg_match('#[0-9]+#',$file_size) )
		{
			$this->target_overall_size = intval($file_size);
		}
	}

	/**
	 *
	 */
	function parse_request()
	{
		if((get_magic_quotes_gpc() || @ini_get('magic_quotes_sybase')) && !function_exists('strip_magic_quotes'))
		{
			function strip_magic_quotes( $value )
			{
				return ( is_array($value) ) ? array_map('strip_magic_quotes', $value) : stripslashes($value);
			}
			$_GET 		= strip_magic_quotes($_GET);
		}
		
		switch(true)
		{
			case($this->parse_file_check()):
				return;
			case($this->parse_ordninary_upload()):
				return;
			case($this->parse_big_file_upload()):
				return;
				
			default:
				echo "<error id=\"400\">no request</error>\n";
		}
	}


	/**
	 *
	 */
	function upload_is_allowed($overwrite=false)
	{
		switch(true)
		{
			case(!$this->file_path_exists()):
				$this->error(405);
				return false;
			
			case(!$overwrite && $this->target_file_exists()):
				$this->error(410);
				return false;
			
			case($this->target_overall_size>0 && $this->target_overall_size > $this->max_file_size):
				$this->error(420);
				return false;
				
			case($this->target_overall_size > disk_free_space("/")):
				$this->error(425);
				return false;
			
			case($this->allowed_file_endings && !in_array($this->file_ending, $this->allowed_file_endings)):
				$this->error(430);
				return false;
				
			default:
				return true;
			
		}
	}

	/**
	 *
	 */
	function parse_file_check()
	{
		// request is no fileCheck
		if(!isset($_GET['fileAction'])) return false;
		if(!$_GET['fileAction'] || $_GET['fileAction'] != 'check') return false;
		
		$this->set_target_file( $_GET['fileName'] );
		$this->set_target_size( $_GET['fileSize'] );
		$this->set_target_checksum( $_GET['fileChecksum'] );
		
		$overwrite = (isset($_GET['fileOverwrite']) && $_GET['fileOverwrite']==1);
		//Fix undefined File variable
		$file = $this->file_directory . $this->file_name . $this->tmp_file_ending;
		
		if($this->file_name)
		{
			$zlib = function_exists('gzuncompress') ? '1' : '0';
			
			if(!$this->upload_is_allowed($overwrite))
			{
				$err = $this->last_error();
				echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
			}
			// if a temporary upload file (partially uploaded) exists
			// => get insert offset
			else if($this->tmp_file_exists())
			{
				switch(false)
				{
					case($this->file_open($file)):
						break;
					case($this->tmp_size >0):
						break;
					case($this->read_header()):
						break;
					case($offset = ftell($this->handle)):
						break;
				}
				
				$this->file_close();
				
				// if an error occured reading the tmp file header
				$err = $this->last_error();
				if( ($err) )
				{
					echo  '<error id="'.$err['id'].' fname="'.$this->file_name.'"">'.$err['msg']."</error>\n";
				}
				else if($this->tmp_checksum)
				{
					echo '<success offset="'. $offset .'" compression="'.$zlib.'" fname="'.$this->file_name.'" checksum="'. $this->tmp_checksum ."\" />\n";
				}
				else
				{
					echo '<success offset="'. $offset .'" fname="'.$this->file_name.'" compression="'.$zlib."\" />\n";
				}
			}
			else
			{
				echo '<success fname="'.$this->file_name.'" compression="'.$zlib."\" />\n";
			}
			
			return true;
		}
		
		return false;
	}

	/**
	 *
	 * returns true if the request was parsed and false if not
	 */
	function parse_ordninary_upload()
	{
		// request is no fileCheck
		if(!$_FILES['Filedata']) return false;
		
		// dont use $_FILES['Filedata']['name'] as it is erroneous
		// if ' is in a name and it gets slashed by addslashes (magic_quotes)
		$this->set_target_file( $_GET['fileName'] );
		if (isset($_GET['fileSize'])) $this->set_target_size( $_GET['fileSize'] );
		
		$overwrite = (isset($_GET['fileOverwrite']) && $_GET['fileOverwrite']==1);
		$exists = $this->target_file_exists();
		
		if(!$this->upload_is_allowed($overwrite))
		{
			$err = $this->last_error();
			echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
			return true;
		}
		
		switch(true)
		{
			// if file exists and could not be deleted
			case($exists && !$this->delete_target_file()):
			// if uploaded file could not be moved
			case(!move_uploaded_file($_FILES['Filedata']['tmp_name'],$this->file_directory.$this->file_name)):
				$err = $this->last_error();
				echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
				break;
			
			default:
				echo "<success fname='".$this->file_name."'/>\n"; 
		}
		
		// return true as we did parse the request
		return true;	
	}

	/**
	 *
	 */
	function parse_big_file_upload()
	{
		if(!isset($_GET['fileAction'])) return false;
		if($_GET['fileAction'] != 'upload') return false;
		
		$zlib = function_exists('gzuncompress') ? '1' : '0';
		
		$this->set_target_file( $_GET['fileName'] );
		$this->set_target_size( $_GET['fileSize'] );
		$this->set_target_checksum( $_GET['fileChecksum'] );
		
		$compressed = (isset($_GET['fileCompressed']) && $_GET['fileCompressed']=='1');
		
		$overwrite = (isset($_GET['fileOverwrite']) && $_GET['fileOverwrite']==1);
		$exists = $this->target_file_exists();
		
		switch(true)
		{
			// if upload is not allowed
			case(!$this->upload_is_allowed($overwrite)):
			// if upload is allowed, but file exists and could not be deleted
			case($exists && !$this->delete_target_file()):
				$err = $this->last_error();
				echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
				return true;
			
		}
		
		$offset = intval($_GET['fileOffset']);
		$data 	= @file_get_contents('php://input');
		
		if($compressed)
		{
			if($zlib)
			{
				$data = gzuncompress($data);
			}
			else
			{
				$this->error(560);
				$err = $this->last_error();
				echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
				return true;
			}
		}
		
		switch(true)
		{
			# if we could not open the file
			case(!$this->open()):
			# if we could not write to the file
			case($data && !$this->write($data,$offset)):
			# if we could not close the file
			case(!$this->close()):
				$err = $this->last_error();
				echo  '<error fname="'.$this->file_name.'" id="'.$err['id'].'">'.$err['msg']."</error>\n";
				return true;
		}
		
		// no error with writing to file
		if($this->is_complete())
		{
			echo '<success fname="'.$this->file_name.'" offset="'.$this->insert_offset.'" compression="'.$zlib."\" completed=\"1\" />\n";
		}
		# else: chunk successfully added
		else
		{
			echo '<success fname="'.$this->file_name.'" offset="'.$this->insert_offset.'" compression="'.$zlib."\" />\n";
		}
		
		// return true as we did parse the request
		return true;
	}
	
	// MAIN INTERNAL METHODS
	/**
	 *
	 */
	function open()
	{
		$file = $this->file_directory . $this->file_name . $this->tmp_file_ending;
		
		if($this->file_open($file))
		{
			
			if($this->tmp_size >0) // file already existed
			{
				$this->read_header();
				
				switch(true)
				{
					// if we received a checksum and it matches the tmp files checksum => resume upload
					case(  $this->tmp_checksum && $this->tmp_size 
						&& $this->tmp_checksum == $this->target_checksum 
						&& $this->tmp_overall_size == $this->target_overall_size ):
					
					// if we received no checksum and the file has no checksum (no checksum check at all) => resume upload
					case( !$this->tmp_checksum && !$this->target_checksum ):
					
						$this->insert_offset = ftell($this->handle);
						break;
						
					default:
						ftruncate($this->handle,0); // delete file contents
						$this->insert_offset = 0;
				}
				
			}
			else
			{
				$this->insert_offset = 0;
			}
	
			return true;
		}
		
		$this->error(510);
		return false;
	}

	/**
	 *
	 */
	function write($bytes, $offset=0)
	{
		$write_bytes = '';
		$length = strlen($bytes);
		
		switch(true)
		{
			case($offset == $this->insert_offset):
				$write_bytes = $bytes;
				break;
				
			case($offset < $this->insert_offset && $offset + $length > $this->insert_offset):	
				$length = $this->insert_offset-$offset;
				$write_bytes = substr($bytes,$length);
				break;
		}
		
		if(strlen($write_bytes) >0)
		{
			switch(true)
			{
				// if data to write exceeds max file size
				case($this->insert_offset+$length > $this->max_file_size):
					$this->error(420);
					return false;		
				
				// if file write fails
				case(!$this->file_write($write_bytes, $this->insert_offset)):
					$this->error(520);
					return false;
			}
			
			$this->insert_offset = ftell($this->handle);
		}
		
		return true;
	}

	/**
	 *
	 */
	function close()
	{
		if(!$this->handle) return;
		
		if($this->insert_offset == $this->target_overall_size) // completed
		{
			
			if( fseek($this->handle,0,SEEK_END)===false )
			{
				$this->error(515);
				return false;
			}
			
			$fullsize = ftell($this->handle);
			
			# remove possible header
			if($fullsize > $this->insert_offset && !ftruncate($this->handle,$this->insert_offset))
			{
				$this->error(525);
				return false;
			}
			
			# close file handle
			if( !$this->file_close() )
			{
				$this->error(540);
				return false;
			}
			
			# rename .tmp
			if( !rename($this->file_directory.$this->file_name.$this->tmp_file_ending, $this->file_directory.$this->file_name) )
			{
				$this->error(550);
				return false;
			}
			
			return true;
		}
		else
		{
			fseek($this->handle,$this->insert_offset,SEEK_SET); // position pointer at insert offset and add header
			
			switch(false)
			{
				// position pointer at insert offset
				case( fseek($this->handle,$this->insert_offset,SEEK_SET)!==false ):
					$this->error(515);
					return false;
				
				// add header at insert offset 
				case( $this->write_header($this->target_overall_size,$this->target_checksum) ):
					$this->error(530);
					return false;
				
				// close file
				case( $this->file_close() ):
					$this->error(540);
					return false;
					
				default:
					return true;
			}
		}
	}
	
	/**
	 *
	 */
	function file_open($path)
	{	
		if($path && realpath(dirname($path)))
		{
			$fh = file_exists($path)
			      ? fopen($path, 'r+b')
				  : fopen($path, 'wb');
			
			if($fh)
			{
				$this->handle 	= $fh;
				$this->tmp_size = filesize($path);
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 */
	function file_write($bytes, $offset=0)
	{
		switch(true)
		{
			// if handle doesn't exist
			case(!$this->handle):
			// if seeking in file fails
			case(fseek($this->handle, $offset, SEEK_SET)===false):
			// if writing to file fails
			case(fwrite($this->handle,$bytes)===false):
				return false;
		}
		
		return true;
	}

	/**
	 *
	 */
	function file_close()
	{	
		if($this->handle)
		{
			return fclose($this->handle);
		}	
	}


	/**
	 *
	 */
	function read_header()
	{
		if($this->handle)
		{
			fseek($this->handle,-15,SEEK_END);
			
			$text = fread($this->handle, 7);
			
			if($text == 'tmpfile')
			{
				$checksum 	= fread($this->handle, 4);
				$checksum	= unpack('V', $checksum);
				$checksum	= dechex($checksum[1]);
				
				$size 		= fread($this->handle, 4);
				$size		= unpack('I',$size);
				$size		= $size[1];
				
				$this->tmp_checksum 	= $checksum;
				$this->tmp_overall_size	= $size;
				
				fseek($this->handle,-15,SEEK_END); // reset pointer to position before header
				
				return true;	
			}
			
			fseek($this->handle,0,SEEK_END); // position pointer at the end of the file
		}
		
		return false;
	}

	/**
	 * write header at current file pointer position
	 */
	function write_header($size,$checksum)
	{
		if($this->handle)
		{
			# prepare binary header data 
			$checksum 	= pack('V',hexdec($checksum));
			$size 		= pack('I',$size);

			return ( fwrite($this->handle, 'tmpfile' . $checksum . $size) !== false);
		}
		return false;
	}

	/**
	 *
	 */
	function is_complete()
	{
		return ($this->insert_offset && $this->target_overall_size && $this->insert_offset==$this->target_overall_size);
	}
	
	
	/**
	 *
	 */
	function file_path_exists()
	{	
		if (!is_dir($this->original_file_directory)) {
			if (!mkdir($this->original_file_directory, 0775)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 *
	 */
	function target_file_exists()
	{
		return file_exists($this->file_directory.$this->file_name);
	}

	/**
	 *
	 */
	function delete_target_file()
	{
		if( !unlink($this->file_directory.$this->file_name) )
		{
			$this->error(555);
			return false;
		}
		return true;
	}

	/**
	 *
	 */
	function tmp_file_exists()
	{
		return file_exists($this->file_directory.$this->file_name.$this->tmp_file_ending);
	}

	/**
	 *
	 */
	function error($id)
	{
		$this->error_id = $id;
	}

	/**
	 *
	 */
	function last_error()
	{
		if($this->error_id >0)
		{
			$err = array('id'=>$this->error_id);
			
			switch($this->error_id)
			{
				case(405):
					$err['msg'] = 'Directory '.$this->original_file_directory.' does not exists';
					break;
				
				case(410):
					$err['msg'] = 'File already exists';
					break;
				
				case(420):
					$err['msg'] = 'File size exceeds maximum';
					break;
					
				case(425):
					$err['msg'] = 'File size exceeds available free space';
					break;
				
				case(430):
					$err['msg'] = 'File type not allowed';
					break;
				
				case(510):
					$err['msg'] = 'Could not open file for writing';
					break;
				
				case(515):
					$err['msg'] = 'File seek operation failed';
					break;
				
				case(520):
					$err['msg'] = 'Could not write to file';
					break;
				
				case(525):
					$err['msg'] = 'Truncate file failed';
					break;
				
				case(530):
					$err['msg'] = 'Could not write temporary file header';
					break;
				
				case(540):
					$err['msg'] = 'File could not be closed';
					break;
				
				case(550):
					$err['msg'] = 'Temporary file could not be renamed to original';
					break;
				
				case(555):
					$err['msg'] = 'File could not be deleted';
					break;
				
				case(560):
					$err['msg'] = 'Data compression not supported (ZLib)';
					break;
					
				default:
					$err['msg'] = 'unknown error';
			}
			
			return $err;
		}
		return NULL;
	}

}

?>