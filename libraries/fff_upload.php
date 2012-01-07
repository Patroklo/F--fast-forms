<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class fff_upload extends CI_Upload
{
	public function __construct($props = array())
	{
		parent::__construct($props);
	}

	//there is a bug in codeigniter that lets you upload files with the same filename and extension but with the 
	//extenions in lower or upper case. With this all the files will be lowercase.
	public function do_upload($field = 'userfile')
	{
		$retorno = parent::do_upload($field);  
		if($this->orig_name!='')
		{
			$new_filename = str_replace($this->file_ext, '', $this->file_name);  
			$this->file_ext = strtolower($this->file_ext);
			$new_filename .= $this->file_ext;
	        rename($this->upload_path.$this->file_name, $this->upload_path.$new_filename);
			$this->file_name = $new_filename;	
			$this->orig_name = $new_filename;
			$this->client_name = $new_filename;
		}
		
		return $retorno;
	}
	
	 	
}
