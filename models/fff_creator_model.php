<?php
class fff_creator_model extends fff_base {

   function __construct()
    {
          //carga el modelo que comprueba los permisos a los usuarios mirando su sesion
       		$tablename = $this->uri->segment();
       		$config['table'] = '';
			$config['form_insert'] = 'Welcome/index';
        parent::__construct($config);
			
    }
    
    function index()
	{
		echo "fas";
	}
}