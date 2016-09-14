<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class centrocosto extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		
		$this->load->database();
	}

	public function save(){
		$resp = array();

		$data = json_decode($this->input->post('data'));
		$id = strtoupper($data->nombre); 

		$data = array(
	      	'codigo' => $data->codigo,
	        'nombre' => strtoupper($data->nombre)
	    );

		$this->db->insert('centros_costo', $data); 

        $resp['success'] = true;

         $this->Bitacora->logger("I", 'centros_costo', $id);


        echo json_encode($resp);

	}

	public function update(){
		$resp = array();

		$data = json_decode($this->input->post('data'));
		$id = $data->id;
		$data = array(
	        'codigo' => $data->codigo,
	        'nombre' => strtoupper($data->nombre)
	    );
		$this->db->where('id', $id);
		
		$this->db->update('centros_costo', $data); 

        $resp['success'] = true;

         $this->Bitacora->logger("M", 'centros_costo', $id);


        echo json_encode($resp);

	}

	public function getAll(){
		
		$resp = array();
        $start = $this->input->post('start');
        $limit = $this->input->post('limit');
        $nombres = $this->input->post('nombre');

		$countAll = $this->db->count_all_results("centros_costo");

		if($nombres){
			$query = $this->db->query("SELECT id, codigo, nombre WHERE nombre like '%".$nombres."%' limit ".$start.", ".$limit);
		}else{
			
			$query = $this->db->query("SELECT id, codigo, nombre FROM centros_costo");
			
		}

		$data = array();
		foreach ($query->result() as $row)
		{
			$data[] = $row;
		}
        $resp['success'] = true;
        $resp['total'] = $countAll;
        $resp['data'] = $data;

        echo json_encode($resp);
	}

}
