<?php

/*
 * config['']
 * column: true form name of the input field
 * form_name: text that will displayed in the form as the human field name
 * help_text : text that will appear around the field with a description of some sort.
 * value
 * style
 * field_properties => array(
 * 							class: if you want to add aditional classes to the field
 * 							size
 * 							maxLength
 * 							disabled
 * 							(cualquier otro)
 * 							);
 * form_options => array(
 * 							'label_name': indicates if the label with the input's form_name it's shown or not in the form
 * 							)
 * 
 */

 			if(!function_exists('fff_summpon_input'))
			{
				function fff_summon_input($config, $value, $html_fields)
				{
					$res= '<div class="form_field">';
					
							if (isset($config->field_properties->FORM->title_text))
						  	{
						  		$res.= $html_fields['html_title_text']['start'].$config->field_properties->FORM->title_text.$html_fields['html_title_text']['end'];
						  	}
							//we add the help text for the field
							if(isset($config->field_properties->FORM->label_name) && $config->field_properties->FORM->label_name == TRUE)
							{						
								$res.= '<label for="'.$config->basic_properties->column.' '.$config->basic_properties->form_name.' ">'.$config->basic_properties->form_name.'</label>';
							}
						  	
							
						  	if (isset($config->field_properties->FORM->help_text))
						  	{
						  		$res.= $html_fields['html_help_text']['start'].$config->field_properties->FORM->help_text.$html_fields['html_help_text']['end'];
						  	}
							
							
							if(isset($config->basic_properties->type))
							{
								if($config->basic_properties->type == 'text_input')
								{
									$res.= fff_summon_text_input($config, $value);
								}
								elseif($config->basic_properties->type == 'textarea')
								{
									$res.= fff_summon_textarea($config, $value);
								}
								elseif($config->basic_properties->type == 'select')
								{
									$res.= fff_summon_select($config, $value);
								}
								elseif($config->basic_properties->type == 'radiobutton')
								{
									$res.= fff_summon_radiobutton($config, $value);
								}
								elseif($config->basic_properties->type == 'checkbox')
								{
									$res.= fff_summon_checkbox($config, $value);
								}
								elseif($config->basic_properties->type == 'password')
								{
									$res.= fff_summon_password($config, $value);
								}
								elseif($config->basic_properties->type == 'upload')
								{
									$res.= fff_summon_upload($config, $value);
								}
								else
								{
									throw new Exception('The field'.$config->basic_properties->column.' don\'t have a VALID field type ('.$config->basic_properties->type.').');	
								}
							}
							else
							{
								throw new Exception('The field'.$config->basic_properties->column.' don\'t have a field type.');	
							}
					$res.='</div>';
					return $res;
				}
			}
 
  			if(!function_exists('fff_summon_text_input'))
			{
				function fff_summon_text_input($config, $value)
				{
					
					$res= '<input type="text" name="'.form_prep($config->basic_properties->column).'" id="'.form_prep($config->basic_properties->column).'" value="'.form_prep($value).'" ';
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$res.= $key.'="'.form_prep($row).'" ';
						}
					}
					$res.= '/>';
					return $res;
				}
			}
 			
 			if(!function_exists('fff_summon_password'))
			{
				function fff_summon_password($config, $value)
				{
					
					$res= '<input type="password" name="'.form_prep($config->basic_properties->column).'" id="'.form_prep($config->basic_properties->column).'" value="'.form_prep($value).'" ';
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$res.= $key.'="'.form_prep($row).'" ';
						}
					}
					$res.= '/>';
					return $res;
				}
			}
			/*
			 * FOR textarea you must send via field_properties at least: COLS=(number) ROWS=(number)
			 */
			
 			if(!function_exists('fff_summon_textarea'))
			{
				function fff_summon_textarea($config, $value)
				{
					$res= '<textarea name="'.$config->basic_properties->column.'" id="'.$config->basic_properties->column.'" ';
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$res.= $key.'="'.form_prep($row).'" ';
						}
					}
					$res.='>'.$value.'</textarea>';
					return $res;
				}
			}
			
			
			if(!function_exists('fff_summon_select'))
			{
				function fff_summon_select($config, $value)
				{
					$res= '<select name="'.$config->basic_properties->column.'" id="'.$config->basic_properties->column.'">';
					//if there isn't any BBDD config the select will be static, but if the select column isn't an enum we will throw an exception
					if(!isset($config->field_properties->FORM->BBDD_select))
					{
						//it's an static select, we will get the enum data and use it in the 
						if(isset($config->field_properties->FORM->choices))
						{
							$list = $config->field_properties->FORM->choices;
						}
						else 
						{
							$sql = "SELECT DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$config->basic_properties->tableName."' AND COLUMN_NAME = '".$config->basic_properties->column."'";
							$ci =& get_instance();
							$query = $ci->db->query($sql);
							$row = $query->row();
							if($row->DATA_TYPE !== 'enum')
							{
								throw new Exception('The field '.$config->basic_properties->column.' don\'t have a valid enum sql field type.');	
							}
							$coincidences = array();
							 preg_match_all("#\'(.*?)\'#", $row->COLUMN_TYPE, $coincidences);
							$list = $coincidences[1];
							
						}
						foreach($list as $key => $data)
						{
							$res.= '<option value="'.($key).'" '.(($key==$value)?'selected="selected"':'').'>'.$data.'</option>';
						}
 					
					}
					else
					{
								$ci =& get_instance();
								$data = $config->field_properties->FORM->BBDD_select;

								foreach($data as $key => $dato)
								{
									if($key == 'select')
									{
										$ci->db->select($dato);
									}
									elseif($key == 'from')
									{
										$ci->db->from($dato);
									}
									elseif($key == "order")
									{
										foreach($dato as $key2 => $dato2)
										{
											$ci->db->order_by($dato2->field, $dato2->order);
										}
									}
									elseif($key == "where")
									{
										foreach($dato as $key2 => $dato2)
										{
											if(!isset( $dato2->key))
											{
												$colName = $dato2->field;
												$ci->db->where($dato2->field, $config->basic_properties->needles->$colName);
											}
											else
												{
													$ci->db->where($dato2->field, $dato2->key);
												}
											
										}
									}
									elseif($key == 'join')
									{
										
										foreach($dato as $key2 => $dato2)
										{
											$ci->db->join($dato2->table, $dato2->key);
										}
									}
								}
							$query = $ci->db->get();
							$list = $query->result_array();
						 	foreach($query->result_array() as $key => $data)
							{
								$res.= '<option value="'.current($data).'" '.((current($data)==$value)?'selected="selected"':'').'>'.next($data).'</option>';
							}
					}
					
						$res.= '</select>';
						return $res;
				}
			}

			if(!function_exists('fff_summon_radiobutton'))
			{
				function fff_summon_radiobutton($config, $value)
				{
					$res = '';
					
					$html_data = "";
					//we add the possible html data and attributes we might need
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$html_data.= $key.'="'.form_prep($row).'" ';
						}
					}
					
					//first we get the options
					if(isset($config->field_properties->FORM->choices))
						{
							if(!is_object($config->field_properties->FORM->choices))
							{
								$list = explode(',',$config->field_properties->FORM->choices);
								
							}
							else 
							{
								$list = $config->field_properties->FORM->choices;
							}
							
							foreach($list as $key => $data)
							{
								$res.= '<input type="radio" name="'.$config->basic_properties->column.'" id="'.$config->basic_properties->column.'" value="'.$key.'" '.$html_data.' '.(($key == $value)?'checked':'').'> <span class="radio_span '.$config->basic_properties->column.'">'.$data.'</span>';
							}
							
						}
					else 
						{
							throw new Exception('The field '.$config->basic_properties->column.' don\'t have a valid choices configuration.');	
						}
						
					return $res;
				}
			}


			if(!function_exists('fff_summon_checkbox'))
			{
				function fff_summon_checkbox($config,$value)
				{
					$res = '<INPUT TYPE=CHECKBOX   name="'.$config->basic_properties->column.'" id="'.$config->basic_properties->column.'"';
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$res.= $key.'="'.form_prep($row).'" ';
						}
					}
					$res.=(($value == 'on')?'checked':'').'>';
					return $res;
				}
			}
			
			
			if(!function_exists('fff_summon_upload'))
			{
				function fff_summon_upload($config,$value)
				{
					$res = '<input type="file"   name="'.$config->basic_properties->column.'" id="'.$config->basic_properties->column.'"';
					if(isset($config->field_properties->HTML))
					{
						foreach($config->field_properties->HTML as $key => $row)
						{
							$res.= $key.'="'.form_prep($row).'" ';
						}
					}
					$res.='>';
					return $res;
				}
			}
