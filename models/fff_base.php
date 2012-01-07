<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include_once(APPPATH.'third_party/fff/config/fff_constants.php');

class fff_base extends CI_Model{
		
	//contains all the column config of the table
	private $column_data;
	//contains the generral table config, like upload max weight, and other limits
	private $table_data;
	//contains the field names that will appear in the forms, it's used to make the form loading faster
	private $form_fields;
	//contains the basic data about the html attr names of the forms
	private $html_fields = array(
								'html_group' => 'group_',
								'html_field' => '',
								'html_submit_button' => 'Enviar',
								'html_validation_box' => 'validation',
								'html_help_text' => array('start' => '<div class="help">', 'end' => '</div>'),
								'html_title_text' => array('start' => '<h2 class="h2_verde">', 'end' => '</h2>'),
								'html_field_group' => 'box',
								'html_error_title' => 'h2_error',
								'html_error_box' => 'box_error'
								);
	//gets data like the form insert and edit urls and that.							
	private $form_config;
	/*array(
			'form_insert' => url
			'form_edit' => url
			'select_needles' => array with needels of the select forms that we will need for loading them
			)
	 */
	//boolean that indicates if the model does or doesn't have data loaded
	public $carga = false;
	
	
	/**
	 *  gets the config data needed to start the model like read the column and table data
	 *	$config['table'] = the name of the table that will be loaded
	 * @return boolean
	 * @author Patroklo
	 */
	public function __construct($config = array())
	{

		if(!empty($config) && is_array($config))
		{
			if(!isset($config['table']))
			{
				throw new Exception('You must provide a valid tablename in order to load it\'s json configuration.');
			}
			
			if(!isset($config['fields']))
			{
					//first we get the table info and config from the database.		
					$query = $this->db->get_where(FFF_JSON_TABLE, array('table' => $config['table']));
					//if there are no rows with the tablename it will throw an exception
					if($query->num_rows() == 0)
					{
						throw new Exception('The table'.$config['table']."doesn't exist in the json configuration table.");
					}
					
					$comparer = array();
					//and we load it into the $column_data and $table_data objects
					foreach($query->result_array() as $row)
					{
						if($row['column'] != '')
							{
								$columnName = $row['column'];	
								//we decode de json data and addit into the object that will contain all the config from the columns of this table
								$this->column_data->$columnName = $this->jsonDecode($row['json_data'], $columnName);
								$comparer[] = $columnName;
								
								//we add, in case the field is form = true, the column into the form array that will
								//use to make the web forms
								if($this->column_data->$columnName->basic_properties->form == true)
								{
									if(!isset($this->column_data->$columnName->field_properties->FORM->form_group))
										{
											$this->form_fields[$columnName][] = $columnName;
										}
									else
										{
											$groupName = $this->column_data->$columnName->field_properties->FORM->form_group;
											$this->form_fields[$groupName][] = $columnName;
										}
								}
								
							}
						else
							{
								$this->table_data->config = $this->jsonDecode($row['json_data'], $config['table']);
							}
					}

					//comparador de columnas para que vea si las columnas de la tabla de json y las de la tabla original son las mismas.
					$field_list = $this->db->list_fields($config['table']);
					if(array_diff($field_list, $comparer))
					{
						throw new Exception('The table columns and the config columns mismatch.');
					}


			}
			else 
			{
					foreach($config['fields'] as $columnName => $field)
					{
						$this->column_data->$columnName = $this->arrayToObject($field);

						if($this->column_data->$columnName->basic_properties->form == true)
						{
							if(!isset($this->column_data->$columnName->field_properties->FORM->form_group))
								{
									$this->form_fields[$columnName][] = $columnName;
								}
							else
								{
									$groupName = $this->column_data->$columnName->field_properties->FORM->form_group;
									$this->form_fields[$groupName][] = $columnName;
								}
						}
					}
					
					if(isset($config['config_table']))
					{
						$this->table_data->config = $this->arrayToObject($config['config_table']);
					}
			}
					
			
			$this->table_data->tableName = $config['table'];
			
			//once we have the columns and table configs loaded, we start loading the other config data for the forms
			if(isset($config['form_insert']))
			{
				$this->form_config->form_insert = $config['form_insert'];
			}
			if(isset($config['form_edit']))
			{
				$this->form_config->form_edit = $config['form_edit'];
			}
			if(isset($config['select_needles']))
			{
				$this->form_config->select_needles = $config['select_needles'];
			}
			
			if(isset($config['html_fields']))
			{
				foreach($config['html_fields'] as $key => $data)
				{
						$this->html_fields[$key] = $data;
				}
			}
			
		}
			


		$this->load->helper('fff_html_helper');

		return true;
	}


	/**
	 *  passes an array o an object
	 *
	 * @return object
	 * @author  Richard Castera & Patroklo
	 */
	function arrayToObject($array) 
		{
		    if(!is_array($array)) {
		        return $array;
		    }
		    
		    $object = new stdClass();
		    if (is_array($array) && count($array) > 0) {
		      foreach ($array as $name=>$value) {
		         $name = trim($name);
		         if (!empty($name) or $name !== 0) {
		            $object->$name = $this->arrayToObject($value);
		         }
		      }
		      return $object;
		    }
		    else {
		      return new stdClass();
		    }
		}

	/**
	 * getter of $this->column_data
	 *
	 * @return column_data
	 * @author  patroklo
	 */
	function columnDataGetter() 
	{
		return $this->column_data;
	}
	/**
	 * getter of $this->table_data
	 *
	 * @return table_data
	 * @author  patroklo
	 */
	function tableDataGetter() 
	{
		return $this->table_data;
	}
	/**
	 * crea las reglas de inserción del formulario
	 * sents the form's rules of insertion
	 *
	 * @return string
	 * @author  Patroklo
	 */
	function rulesInsert() 
	{
		$this->load->library('form_validation');
		$this->load->library('fff_form_validation');
		$config = array();
		foreach($this->form_fields as $fields)
		{
			foreach($fields as $column)
			{
				$data = $this->column_data->$column;
				if(isset($data->basic_properties->validation->new))
				{
						
					$config[] = array(
										'field' => $data->basic_properties->column,
										'label' => $data->basic_properties->form_name,
										'rules' => $data->basic_properties->validation->new
										);
				}
				else
				{
					throw new Exception("The column ".$row->column.' doesn\'t have insert validation info.');
				}
			}
		}
		$this->uploadConfig();
		$this->fff_form_validation->set_rules($config); 
		
		return $config;

	}
	/**
	 * crea las reglas de edición del formulario
	 * sents the form's rules of edition
	 *
	 * @return string
	 * @author  Patroklo
	 */
	function rulesEdit() 
	{
		$this->load->library('form_validation');
		$this->load->library('fff_form_validation');
		$config = array();
		foreach($this->form_fields as $fields)
		{
			foreach($fields as $column)
			{
				$data = $this->column_data->$column;
				if(isset($data->basic_properties->validation->edit))
				{
						
					$config[] = array(
										'field' => $data->basic_properties->column,
										'label' => $data->basic_properties->form_name,
										'rules' => $data->basic_properties->validation->edit
										);
				}
				else
				{
					throw new Exception("The column ".$row->column.' doesn\'t have insert validation info.');
				}
			}
		}
		$this->uploadConfig();
		$this->fff_form_validation->set_rules($config); 
		return $config;
		
	}

	/**
	 * decodifica el json enviado desde el constructor y envía una excepción en caso de error
	 * decodes the json sent from the constructor and throws an exception in case of error
	 *
	 * @return array
	 * @author  Patroklo
	 */
	function jsonDecode($json, $name, $is_array = false)
	{
		if(!is_string($json))
		{
			throw new Exception('The variable must be a string value.');
		}
		
		$result = json_decode($json, $is_array);
		if($result == NULL and ($json !== '' or $json !== NULL))
		{
			throw new Exception('Error in the json data of '.$name);
		}
		else
		{
			return $result;
		}
	}	
	
	/**
	 * lista todos los campos del formulario en formato html
	 * list all the form fields in html format
	 *
	 * @return array
	 * @author  Patroklo
	 */	
	function listAllFields($config = array())
	{
		//you can pass the url where the form will be submitted via the config array, or you can configure that
		//url in the _construct phase with an insert and edit url
		if(!is_array($config))
		{
			throw new Exception('The config data is not an array');
		}
		if(isset($config['form_open']))
		{
			
			$form_dir = $config['form_open'];
		}
		else 
		{
			if($this->carga == false)
			{
				if(isset($this->form_config->form_insert))
				{
					$form_dir = $this->form_config->form_insert;
				}
				else
				{
					throw new Exception('There is no direction for the form insert method.');
				}
			}
			else
			{
				if(isset($this->form_config->form_edit))
				{
					$form_dir = $this->form_config->form_edit;
				}
				else
				{
					throw new Exception('There is no direction for the form edit method.');
				}
			}
		}
			$res = '<div id="'.$this->table_data->tableName.'" class="'.$this->table_data->tableName.'">';

			if(isset($this->table_data->config->upload) && ($this->table_data->config->upload == TRUE))
			{
				$res.= form_open_multipart($form_dir);
			}
			else 
			{
				$res.= form_open($form_dir);
			}
			
			
			//we put the validation errors field
			$res.= '<div id="validation_errors" class="'.$this->html_fields['html_validation_box'].'">'.$this->validation_errors().'</div>';
			
			foreach($this->form_fields as $key => $fields)
			{
				$res.= '<div id="'.$this->html_fields['html_group'].$key.'" class="'.$this->html_fields['html_group'].$key.' '.$this->html_fields['html_field_group'].'">';
					foreach($fields as $column)
					{
							$res.= $this->listField($column);
					}
				$res.= '</div>';
			}
			$res.='<div class="submit_button"><input type="submit" value="'.$this->html_fields['html_submit_button'].'" /></div></form></div>';
		return $res;
	}
	
	/**
	 * list the html of only one field
	 *
	 * @return html
	 * @author  patroklo
	 */
	function listField($fieldName) 
	{
		
		//we try to populate the form field. If there is a post for this input we will return it 
		//also, if we have loaded a row from the table, we will post the content for this field except if there is a post for that field
		//because we will count that as if the user has tried to change the value.
		if(isset($this->column_data->$fieldName))
		{
			$dataPost = $this->input->post($fieldName, true);
			if($dataPost !== false)
			{
				$value = $dataPost;
			}
			else
			{
				if($this->carga == false)
				{
					$value = '';
				}
				else
				{
					if(isset($this->carga[$fieldName]))
					{
						$value = $this->carga[$fieldName];
					}
					else
					{
						$value = '';
					}
				}
			}
			
			//in some cases we will have to pass the tablename, like in selects:
			if($this->column_data->$fieldName->basic_properties->type == 'select')
			{
				$this->column_data->$fieldName->basic_properties->tableName = $this->table_data->tableName;
				if(isset($this->column_data->$fieldName->field_properties->FORM->BBDD_select) && isset($this->column_data->$fieldName->field_properties->FORM->BBDD_select->where))
				{
					foreach($this->column_data->$fieldName->field_properties->FORM->BBDD_select->where as $dato)
					{
						if(!isset($dato->key))
						{

							if(!isset($this->form_config->select_needles) || !isset($this->form_config->select_needles[$dato->field]))
							{
								throw new Exception('The where needle '.$dato->field.' of the select field '.$fieldName.' don\'t exists in the form.');
							}
							else
							{
								$colName = $dato->field;
								$this->column_data->$fieldName->basic_properties->needles->$colName = $this->form_config->select_needles[$dato->field];
							}
						}
						
						
					}
				}
			}
		
			return fff_summon_input($this->column_data->$fieldName, $value, $this->html_fields);	
		}
		else
		{
			throw new Exception('The field'.$fieldName.' don\'t exists in the form.');
		}
	}
	
	
	/**
	 * inserts the data from values or post in all the columns with BBDD = true
	 * it can come from the $values array or via post
	 *
	 * @return id of the inserted row or FALSE
	 * @author  Patroklo
	 */
	function insert($values = false) 
	{
		$see_array = false;
		if(is_array($values))
		{
			$see_array = true;
		}
		$data = array();
		foreach($this->column_data as $key => $row)
		{
			if(isset($row->basic_properties->BBDD) && ($row->basic_properties->BBDD == true))
			{	
				//if $values is an array and exists this key in it, it will use it instead of any post or upload data for this column
				//the $see_array it's for using a boolean comparation instead of a is_array() function each time because it's faster.
				
				if($see_array && isset($values[$key]))
				{
					$data[$key] = $values[$key];
				}
				elseif((isset($row->basic_properties->type)) && ($row->basic_properties->type == 'upload') && (isset($_FILES[$key]['name'])))
				{
				//if this row is an upload AND the file exists for this col, we will upload the file with the given configuration
					$this->fff_upload->do_upload($key);
					if(!empty($this->fff_upload->error_msg))
					{
						foreach($this->fff_upload->error_msg as $error)
						{
							throw new Exception($error);
						}
					}
					$file_data = $this->fff_upload->data();
					$data[$key] = $file_data['file_name'];
				}
				elseif($this->input->post($key, true) !== false)
				{
				//if the data of the column is not given in values and it's not an upload, we will try to get it via POST
					$data[$key] = $this->input->post($key, true);
				}
			}
		}
		
		if(!empty($data))
		{
			$this->db->insert($this->table_data->tableName, $data); 
			return $this->carga($this->db->insert_id());
		}
		else
		{
			return FALSE;
		}
		
	}
	
	/**
	 * loads a row in the table, if exists it will return the array data, if doesn't it will return false
	 * you can send into $fields an array of "field_name" => "data" or only a number, then it will search this value in the "id" field
	 * THIS FUNCTION WILL WORK AS IF ONLY ONE ROW WAS RETURNED
	 *
	 * @return array or false
	 * @author  Patroklo
	 */
	function carga($fields, $limit = 1, $offset = 0) 
	{
		if(is_array($fields))
		{
			$needle = $fields;
		}
		else
		{
			$needle = array('id' => $fields);
		}
		
		$query = $this->db->get_where($this->table_data->tableName, $needle, $limit, $offset);
		if($query->num_rows() > 0)
		{
			$row = $query->row_array();
			$this->carga = $row;
			return $this->carga;
		}
		else 
		{
			return false;	
		}
	}
	
	/**
	 * will edit the data loaded from the form. As in Insert, it will search first in the array passed and then in the $POST for each BBDD = true column, so if
	 * you want to have a null value in any of them and you pass data via post, you'll have to overwrite it via $values array
	 * $needle is a field or array of fields that will be passed directly as the where for the update with the format array('columnName' => 'data')
	 * it has the same restrictions as the where in codeigniter.
	 *
	 * @return data of the new updated row
	 * @author  Patroklo
	 */
	function edit($values = false, $needle = false) 
	{
		if($needle == false || !is_array($needle))
		{	if($this->carga == false)
			{
				throw new Exception('A needle is needed for updating data in the table '.$this->table_data->tableName);
			}
		else
			{
				$needle = array('id' => $this->carga['id']);
			}	
			
		}
		$see_array = false;
		if(is_array($values))
		{
			$see_array = true;
		}
		$data = array();
		foreach($this->column_data as $key => $row)
		{
			if(isset($row->basic_properties->BBDD) && ($row->basic_properties->BBDD == true))
			{
				//if $values is an array and exists this key in it, it will use it instead of any post or upload data for this column
				//the $see_array it's for using a boolean comparation instead of a is_array() function each time because it's faster.
				if($see_array && isset($values[$key]))
				{
					$data[$key] = $values[$key];
				}
				elseif(isset($row->basic_properties->type) && ($row->basic_properties->type == 'upload') && (isset($_FILES[$key]['name'])) && $_FILES[$key]['error'] != 4)
				{
				//if this row is an upload AND the file exists for this col, we will upload the file with the given configuration
					$this->fff_upload->do_upload($key);
					if(!empty($this->fff_upload->error_msg))
					{
						foreach($this->fff_upload->error_msg as $error)
						{
							throw new Exception($error);
						}
					}
					$file_data = $this->fff_upload->data();
					$values[$key] = $file_data['file_name'];
				}
				elseif($this->input->post($key, true) !== false)
				{
				//if the data of the column is not given in values and it's not an upload, we will try to get it via POST
					$data[$key] = $this->input->post($key, true);
				}
			}
		}
		
		if(!empty($data))
		{
			$this->db->where($needle); 
			$this->db->update($this->table_data->tableName, $data); 
			return $this->carga($needle);
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * configs the codeigniter uploader if the $this->table_data->config has the upload to true.
	 * it can accept data sent via the input $config array and from de table_data config
	 *
	 * @return void
	 * @author  patroklo
	 */
	function uploadConfig($manual_config = array()) 
	{
		if(isset($this->table_data->config->upload) && ($this->table_data->config->upload == TRUE))
		{
			$config = array();
			//is the table_data has config info for the upload, adds it into the config array
			if(isset($this->table_data->config->upload_config) && (is_object($this->table_data->config->upload_config)))
			{
				foreach($this->table_data->config->upload_config as $key => $data)
				{
					$config[$key] = $data;
				}
			}
			//is we have sent config info for the upload manually, adds it into the config file
			if((is_array($manual_config)) && (!empty($manual_config)))
			{
				foreach($manual_config as $key => $data)
				{
					$config[$key] = $data;
				}
			}
			
			if(count($config) > 0)
			{
				$this->load->library('upload');
				$this->load->library('fff_upload', $config);
				return TRUE;
			}
			
		}
		else 
		{
			return false;	
		}
	}
	
	/**
	 * makes the upload of the columns that are of type "upload"
	 * it sends the value data in a format that the insert and edit functions will recognize automatically
	 * and make the necesary database changes, but it's strongly recommended to change this data into a 
	 * more suitable form for the purposes of the programa
	 *
	 * @return void
	 * @author  patroklo
	 */
	function makeUpload($fields = false) 
	{
		//note that thanks to the form_validation addon we don't have to worry about size or mime limitations, here we will only worry about 
		//the upload
		$values = array();

		if($fields !== false)
		{
			if(!is_array($fields))
			{
				if(isset($this->column_data->$fields))
				{
					$fields = $this->column_data->$fields;
				}
			}
			else 
			{
				foreach($fields as $field)
				{
					if(isset($this->column_data->$fields))
					{
						$fields = $this->column_data->$fields;
					}
				}

			}
		}
		else 
		{
			$fields = $this->column_data;	
		}
		
		foreach ($fields as $key => $value) 
		{
			if(($value->basic_properties->form == TRUE) && ($value->basic_properties->type == 'upload') && (isset($_FILES[$key]['name'])))
			{

				if(isset($_FILES[$key]['error']) && $_FILES[$key]['error'] !== 4)
				{
					$this->fff_upload->do_upload($key);
					if(!empty($this->fff_upload->error_msg))
						{
							foreach($this->fff_upload->error_msg as $error)
							{
								throw new Exception($error);
							}
						}
					$values[$key] = $this->fff_upload->data();
				}
				
			}
		}
		if(empty($values))
		{
			return false;
		}
		return $values;
	}
		/**
		 * since validation_error() from form_helper no longer works thanks to the third party addon
		 * we sill use this function instead of the original.
		 * 
		 * 
		 * @return void
		 * @author patroklo
		 */
		
		function validation_errors($prefix = '', $suffix = '')
		{
			$retorno = $this->fff_form_validation->error_string($prefix, $suffix);
			if($retorno != '')
			{
				
			 return '<h2 class="'.$this->html_fields['html_error_title'].'">ERROR</h2><div class="'.$this->html_fields['html_error_box'].'">'.$retorno.'</div>';
			}
			else
			{
				return '';
			}
		}
		
		/**
		 * deletes the rows given in the $needle as an associative array
		 *
		 * @return number of affected rows
		 * @author  patroklo
		 */
		function delete($needle = false) 
		{
			if($needle == false )
			{
				if(!$this->carga)
				{
					throw new Exception('A needle is needed for deleting data in the table '.$this->table_data->tableName);
				}
				else
				{
					$needle = $this->carga;
					$this->carga = false;	
				}
			}
			elseif(!is_array($needle))
			{
				$needle = array('id' => $needle);
			}
			
			$this->db->delete($this->table_data->tableName, $needle);
			return $this->db->affected_rows();

		}
		
	
}
