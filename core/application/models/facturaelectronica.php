<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Ion Auth Model
*
* Version: 2.5.2
*
* Author:  Ben Edmunds
* 		   ben.edmunds@gmail.com
*	  	   @benedmunds
*
* Added Awesomeness: Phil Sturgeon
*
* Location: http://github.com/benedmunds/CodeIgniter-Ion-Auth
*
* Created:  10.01.2009
*
* Last Change: 3.22.13
*
* Changelog:
* * 3-22-13 - Additional entropy added - 52aa456eef8b60ad6754b31fbdcc77bb
*
* Description:  Modified auth system based on redux_auth with extensive customization.  This is basically what Redux Auth 2 should be.
* Original Author name has been kept but that does not mean that the method has not been modified.
*
* Requirements: PHP5 or above
*
*/

class Facturaelectronica extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('cookie');
		$this->load->helper('date');
	}



	public function ruta_libredte(){
		$base_path = __DIR__;
		$base_path = str_replace("\\", "/", $base_path);
		$path = $base_path . "/../libraries/inc.php";		
		return $path;
	}

	public function genera_config(){
		$config = [
		    'firma' => [
		        'file' => $this->ruta_certificado(),
		        'pass' => $this->busca_parametro_fe('cert_password'),
		    ],
		];

		return $config;
	}


	public function ruta_certificado(){
		$base_path = __DIR__;
		$base_path = str_replace("\\", "/", $base_path);
		$path = $base_path . "/../../facturacion_electronica/certificado/certificado.p12";		
		return $path;
	}

	 public function busca_parametro_fe($parametro){
		$this->db->select('valor ')
		  ->from('param_fe')
		  ->where('nombre',$parametro);
		$query = $this->db->get();
		$parametro = $query->row();	
		return $parametro->valor;
	 }	


	 public function set_parametro_fe($parametro,$valor){
		  $this->db->where('nombre',$parametro);
		  $this->db->update('param_fe',array('valor' => $valor));
		return 1;
	 }		 


	 public function put_trackid($idfactura,$trackid){
		  $this->db->where('idfactura',$idfactura);
		  $this->db->update('folios_caf',array('trackid' => $trackid));
		return 1;
	 }		 


	  public function put_trackid_libro($idlibro,$trackid){
		  $this->db->where('id',$idlibro);
		  $this->db->update('log_libros',array('trackid' => $trackid));
		return 1;
	 }	


	 public function contribuyentes_autorizados($start = null,$limit = null){

	 	$tabla_contribuyentes = $this->busca_parametro_fe('tabla_contribuyentes');

	 	$countAll = $this->db->count_all_results($tabla_contribuyentes);

		$data = $this->db->select('rut, dv, concat(rut,"-",dv) as rut_contribuyente, razon_social, nro_resolucion, date_format(fec_resolucion,"%d/%m/%Y") as fec_resolucion, mail, url',false)
		  ->from($tabla_contribuyentes)
		  ->order_by('razon_social');

		$data = is_null($start) || is_null($limit) ? $data : $data->limit($limit,$start);
		$query = $this->db->get();
		return array('total' => $countAll, 'data' => $query->result());

	 }

	 public function log_libros($start = null,$limit = null,$estado = null){

	 	$countAll = $this->db->count_all_results('log_libros');
		$data = $this->db->select('id, mes, anno, tipo_libro, archivo, date_format(fecha_solicita,"%d/%m/%Y %H:%i:%s") as fecha_solicita, date_format(fecha_procesa,"%d/%m/%Y %H:%i:%s") as fecha_creacion, estado',false)
		  ->from('log_libros')
		  ->order_by('anno','desc')
		  ->order_by('mes','desc');

		$data = is_null($start) || is_null($limit) ? $data : $data->limit($limit,$start);
		$data = is_null($estado) ? $data : $data->where('estado',$estado);		
		$query = $this->db->get();
		return array('total' => $countAll, 'data' => $query->result());

	 }

	public function get_empresa(){
		$this->db->select('rut, dv, razon_social, giro, cod_actividad, dir_origen, comuna_origen, fec_resolucion, nro_resolucion, logo ')
		  ->from('empresa')
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	 }


	public function datos_dte_periodo($mes,$anno){
		$this->db->select("f.folio, f.path_dte, f.archivo_dte, f.dte, f.pdf, f.pdf_cedible, f.trackid, c.tipo_caf, tc.nombre as tipo_doc, fc.fecha_factura, concat(left(cl.rut,length(cl.rut)-1),'-',upper(right(cl.rut,1))) as rut, cl.nombres, fc.neto, fc.iva, fc.totalfactura ",false)
		  ->from('folios_caf f')
		  ->join('caf c','f.idcaf = c.id')
		  ->join('tipo_caf tc','c.tipo_caf = tc.id')
		  ->join('factura_clientes fc','f.idfactura = fc.id')
		  ->join('clientes cl','fc.id_cliente = cl.id')
		  ->where('left(fc.fecha_factura,7)',$anno."-".$mes)
		  ->where('c.tipo_caf <> 52')
		  //->where('left(f.updated_at,7)',$anno."-".$mes) //AUN TENEMOS FACTURAS QUE NO SE EMITEN POR EL SISTEMA
		  ->where('f.estado','O');
		$query = $this->db->get();
		return $query->result();
	}



	public function datos_dte_proveedores_periodo($mes,$anno){
		$this->db->select('d.id, d.idproveedor, d.dte, d.envios_recibos, d.recepcion_dte, d.resultado_dte ')
		  ->from('dte_proveedores d')
		  ->where('left(d.fecha_documento,7)',$anno."-".$mes);
		$query = $this->db->get();
		return $query->result();
	}


	public function valida_existe_libro($mes,$anno,$tipo){
		$this->db->select('id, mes, anno, tipo_libro ')
		  ->from('log_libros')
		  ->where('mes',$mes)
		  ->where('anno',$anno)
		  ->where('tipo_libro',$tipo);
		$query = $this->db->get();
		return count($query->result()) > 0 ? true : false;
	}


	public function genera_libro($id_libro,$tipo,$archivo,$xml_libro){
		$array_update = array(
					'estado' => 'G',
					'fecha_procesa' => date("Y-m-d H:i:s"),
					'archivo' => $archivo,
					'xml_libro' => $xml_libro
					);

	    $this->db->where('id', $id_libro);
		$this->db->update('log_libros',$array_update); 




		//$this->db->insert('log_libros',$array_insert); 
		return true;
	}

	public function put_log_libros($mes,$anno,$tipo,$archivo){

			$array_insert = array(
						'mes' => $mes,
						'anno' => $anno,
						'tipo_libro' => $tipo,
						'trackid' => 0,
						'fecha_solicita' => date("Y-m-d H:i:s"),
						'archivo' => $archivo
						);

		$this->db->insert('log_libros',$array_insert); 
		return $this->db->insert_id();
	}


	public function get_empresa_factura($id_factura){

		$tabla_contribuyentes = $this->busca_parametro_fe('tabla_contribuyentes');

		$this->db->select('c.nombres as nombre_cliente, c.rut as rut_cliente, c.direccion, m.nombre as nombre_comuna, s.nombre as nombre_ciudad, c.fono, e.nombre as giro, ifnull(ca.mail,c.e_mail) as e_mail, c.tipodocref',false)
		  ->from('factura_clientes acc')
		  ->join('clientes c','acc.id_cliente = c.id','left')
		  ->join('cod_activ_econ e','c.id_giro = e.id','left')
		  ->join('comuna m','c.id_comuna = m.id','left')		  
		  ->join('ciudad s','c.id_ciudad = s.id','left')	
		  ->join($tabla_contribuyentes . ' ca','c.rut = concat(ca.rut,ca.dv)','left')
		  ->where('acc.id',$id_factura)
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	 }	 


	public function get_detalle_factura($id_factura){
		$this->db->select('p.nombre, f.precio, f.cantidad, f.descuento , f.totalproducto')
		  ->from('detalle_factura_cliente f')
		  ->join('productos p','f.id_producto = p.id')
		  ->where('f.id_factura',$id_factura);
		$query = $this->db->get();
		return $query->result();
	 }

	public function get_detalle_factura_glosa($id_factura){
		$this->db->select('f.glosa, f.neto, f.iva, f.total ')
		  ->from('detalle_factura_glosa f')
		  ->where('f.id_factura',$id_factura);
		$query = $this->db->get();
		return $query->result();
	 }	 


	public function get_factura_no_enviada(){
		$this->db->select('c.idfactura')
		  ->from('folios_caf c ')
		  ->join('factura_clientes fc','c.idfactura = fc.id')
		  ->where('c.trackid','0')
		  ->where('c.idfactura <> 0')
		  ->where('c.estado','O');
		$query = $this->db->get();
		return $query->result();
	 }	

	public function get_content_caf_folio($folio,$tipo_documento){
		$this->db->select('c.archivo, c.caf_content ')
		  ->from('caf c')
		  ->join('folios_caf f','f.idcaf = c.id')
		  ->where('f.folio',$folio)
		  ->where('c.tipo_caf',$tipo_documento)
		  ->limit(1);
		  $query = $this->db->get();
		  $caf = $query->row();					  
		  return $caf;
	 }	 

	public function datos_dte($idfactura){

		$this->db->select('f.id, f.folio, f.path_dte, f.archivo_dte, f.archivo_dte_cliente, f.dte, f.dte_cliente, f.pdf, f.pdf_cedible, f.trackid, c.tipo_caf, tc.nombre as tipo_doc, cae.nombre as giro, cp.nombre as cond_pago, v.nombre as vendedor ')
		  ->from('folios_caf f')
		  ->join('caf c','f.idcaf = c.id')
		  ->join('tipo_caf tc','c.tipo_caf = tc.id')
		  ->join('factura_clientes fc','f.idfactura = fc.id','left')
		  ->join('clientes cl','fc.id_cliente = cl.id','left')
		  ->join('cod_activ_econ cae','cl.id_giro = cae.id','left')
		  ->join('cond_pago cp','fc.id_cond_venta = cp.id','left')
		  ->join('vendedores v','fc.id_vendedor = v.id','left')

		  ->where('f.idfactura',$idfactura)
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	}	


	public function get_libro_by_id($idlibro){
		$this->db->select('id, mes, anno, tipo_libro, archivo, date_format(fecha_solicita,"%d/%m/%Y %H:%i:%s") as fecha_solicita, date_format(fecha_procesa,"%d/%m/%Y %H:%i:%s") as fecha_creacion, estado, trackid, xml_libro, created_at',false)
		  ->from('log_libros')
		  ->where('id',$idlibro);
		$query = $this->db->get();
		return $query->row();
	}

	public function datos_dte_by_trackid($trackid){
		$this->db->select('f.id, f.folio, f.path_dte, f.archivo_dte, f.dte, f.pdf, f.pdf_cedible, f.trackid, c.tipo_caf, tc.nombre as tipo_doc, cae.nombre as giro, cp.nombre as cond_pago, v.nombre as vendedor    ')
		  ->from('folios_caf f')
		  ->join('caf c','f.idcaf = c.id')
		  ->join('tipo_caf tc','c.tipo_caf = tc.id')
		  ->join('factura_clientes fc','f.idfactura = fc.id','left')
		  ->join('clientes cl','fc.id_cliente = cl.id','left')
		  ->join('cod_activ_econ cae','cl.id_giro = cae.id','left')	
		  ->join('cond_pago cp','fc.id_cond_venta = cp.id','left')	
		  ->join('vendedores v','fc.id_vendedor = v.id','left')  
		  ->where('f.trackid',$trackid)
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	}	


	public function datos_dte_provee($iddte){
		$this->db->select('d.id, p.nombres as proveedor, p.e_mail, d.path_dte, d.arch_rec_dte, d.arch_res_dte, d.arch_env_rec, date_format(d.fecha_documento,"%d/%m/%Y") as fecha_documento , date_format(d.created_at,"%d/%m/%Y") as fecha_creacion ',false)
		  ->from('dte_proveedores d')
		  ->join('proveedores p','d.idproveedor = p.id')
		  ->where('d.id',$iddte)
		  ->order_by('d.id','desc');
		$query = $this->db->get();
		return $query->row();
	}


	 public function exportFePDF($idfactura,$tipo_consulta,$cedible = null){

	 	include $this->ruta_libredte();
	 	if($tipo_consulta == 'id'){
	 		$factura = $this->datos_dte($idfactura);
	 	}else if($tipo_consulta == 'trackid'){
	 		$factura = $this->datos_dte_by_trackid($idfactura);
	 	}
	 	$nombre_pdf = is_null($cedible) ? $factura->pdf : $factura->pdf_cedible;
	 	//file_exists 
	 	$crea_archivo = true;
	 	if($nombre_pdf != ''){
			$base_path = __DIR__;
			$base_path = str_replace("\\", "/", $base_path);
			$file = $base_path . "/../../facturacion_electronica/pdf/".$factura->path_dte.$nombre_pdf;		 		
	 		if(file_exists($file)){
	 			$crea_archivo = false;
	 		}
	 	}

	 	$empresa = $this->get_empresa();
	 	
	 	if($crea_archivo){
			// sin límite de tiempo para generar documentos
			set_time_limit(0);
		 	// archivo XML de EnvioDTE que se generará
		 	$archivo = "./facturacion_electronica/dte/".$factura->path_dte.$factura->archivo_dte;
		 	if(file_exists($archivo)){
		 		$content_xml = file_get_contents($archivo);
		 	}else{
		 		$content_xml = $factura->dte;
		 	}

		 	// Cargar EnvioDTE y extraer arreglo con datos de carátula y DTEs
		 	$EnvioDte = new \sasco\LibreDTE\Sii\EnvioDte();
		 	$EnvioDte->loadXML($content_xml);
			$Caratula = $EnvioDte->getCaratula();
			$Documentos = $EnvioDte->getDocumentos();	 	

			if(!file_exists('./facturacion_electronica/pdf/'.$factura->path_dte)){
				mkdir('./facturacion_electronica/pdf/'.$factura->path_dte,0777,true);
			}		

			$base_path = __DIR__;
			$base_path = str_replace("\\", "/", $base_path);
			$path_pdf = $base_path . "/../../facturacion_electronica/pdf/".$factura->path_dte;				

			foreach ($Documentos as $DTE) {
			    if (!$DTE->getDatos())
			        die('No se pudieron obtener los datos del DTE');
			    $pdf = new \sasco\LibreDTE\Sii\PDF\Dte(false); // =false hoja carta, =true papel contínuo (false por defecto si no se pasa)
			    $pdf->setFooterText();
			    $pdf->setLogo('./facturacion_electronica/images/logo_empresa.png'); // debe ser PNG!
			    
			    if($factura->giro != ""){
			    	$pdf->setGiroCliente($factura->giro); 
			    }
			    

			    $pdf->setCondPago($factura->cond_pago); 
			    $pdf->setVendedor($factura->vendedor); 

			    $pdf->setGiroEmisor($empresa->giro); 


			    $pdf->setResolucion(['FchResol'=>$Caratula['FchResol'], 'NroResol'=>$Caratula['NroResol']]);
			    if(!is_null($cedible)){
			    	$pdf->setCedible(true);
			    }
			    $pdf->agregar($DTE->getDatos(), $DTE->getTED());
			    //$pdf->Output('facturacion_electronica/pdf/'.$factura->path_dte.'dte_'.$Caratula['RutEmisor'].'_'.$DTE->getID().'.pdf', 'FI');
			    $archivo = 'dte_'.$Caratula['RutEmisor'].'_'.$DTE->getID();
			    $nombre_archivo = is_null($cedible) ? $archivo.".pdf" : $archivo."_CED.pdf";
			    //$tipo_generacion = is_null($cedible) ? 'FI' : 'F';
			    $tipo_generacion = 'FI';
			    $pdf->Output($path_pdf.$nombre_archivo, $tipo_generacion);
			    $nombre_campo = is_null($cedible) ? 'pdf' : 'pdf_cedible';

			    $this->db->where('idfactura', $idfactura);
				$this->db->update('folios_caf',array($nombre_campo => $nombre_archivo)); 		    

			}

		}else{

			/*$base_path = __DIR__;
			$base_path = str_replace("\\", "/", $base_path);
			
			$file = $base_path . "/../../facturacion_electronica/pdf/".$factura->path_dte.$nombre_pdf;				*/
			//echo $file; exit;
			//$file = '.facturacion_electronica/pdf/'.$factura->path_dte.$factura->pdf;
			$filename = $nombre_pdf; /* Note: Always use .pdf at the end. */

			header('Content-type: application/pdf');
			header('Content-Disposition: inline; filename="' . $filename . '"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($file));
			header('Accept-Ranges: bytes');

			@readfile($file);


		}
	}
	 




	public function carga_contribuyentes($path_base,$archivo){

		$this->db->trans_start();
		$this->db->query('truncate contribuyentes_autorizados'); 

		$base_path = __DIR__;
		$base_path = str_replace("\\", "/", $base_path);
		
		$file = $base_path . "/../../facturacion_electronica/base_contribuyentes/".$path_base.$archivo;				



		$this->db->query('LOAD DATA LOW_PRIORITY LOCAL INFILE "' . $file . '" REPLACE INTO TABLE contribuyentes_autorizados FIELDS TERMINATED BY ";" LINES TERMINATED BY "\n" IGNORE 1 LINES (rut,razon_social,nro_resolucion,fec_resolucion,mail,url);'); 

		$tabla_contribuyentes = $this->busca_parametro_fe('tabla_contribuyentes');
		$tabla_inserta = $tabla_contribuyentes == 'contribuyentes_autorizados_1' ? 'contribuyentes_autorizados_2' : 'contribuyentes_autorizados_1';

		$this->db->query("insert into " . $tabla_inserta . " (rut,dv,razon_social,nro_resolucion,fec_resolucion,mail,url)
						select SUBSTRING_INDEX(rut, '-', 1) as rut, SUBSTRING_INDEX(rut, '-', -1) as dv, razon_social, nro_resolucion, concat(SUBSTRING(fec_resolucion,7,4),'-',SUBSTRING(fec_resolucion,4,2),'-',SUBSTRING(fec_resolucion,1,2)) as fec_resolucion, mail, url  from contribuyentes_autorizados");

		$array_insert = array(
						'nombre_archivo' => $archivo,
						'ruta' => $path_base,
						);

		$this->db->insert('log_cargas_bases_contribuyentes',$array_insert); 


		$this->set_parametro_fe('tabla_contribuyentes',$tabla_inserta);

		$this->db->query('truncate '. $tabla_contribuyentes);

		$this->db->trans_complete(); 		

	 }	 


	 public function registro_email($data){

		$this->db->select('id')
		  ->from('email_fe');
		$query = $this->db->get();
		$email = $query->row();	 		

        	if(count($email) > 0){ //actualizar
        		$this->db->where('id',1);
        		$this->db->update('email_fe',$data);
        	}else{ //insertar
        		$data['created_at'] = date("Y-m-d H:i:s");
				$this->db->insert('email_fe',$data);
        	}	 	
        return true;
	 }

	public function get_email(){
		$this->db->select('email_contacto, pass_contacto, tserver_contacto, port_contacto, host_contacto, email_intercambio, pass_intercambio, tserver_intercambio, port_intercambio, host_intercambio ')
		  ->from('email_fe')
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	 }



	public function envio_mail_dte($idfactura){


			$factura = $this->datos_dte($idfactura);
			$track_id = $factura->trackid;
			$path = $factura->path_dte;
			$nombre_dte = $factura->archivo_dte_cliente != '' ? $factura->archivo_dte_cliente : $factura->archivo_dte;

			$empresa = $this->get_empresa();
			$datos_empresa_factura = $this->get_empresa_factura($idfactura);

			$messageBody  = 'Envío de DTE<br><br>';
	        $messageBody .= '<b>Datos Emisor:</b><br>';
	        $messageBody .= $empresa->razon_social.'<br>';
	        $messageBody .= 'RUT:'.$empresa->rut.'-'.$empresa->dv .'<br><br>';

	        $messageBody .= '<b>Datos Receptor:</b><br>';
	        $messageBody .= $datos_empresa_factura->nombre_cliente.'<br>';
	        $messageBody .= 'RUT:'.substr($datos_empresa_factura->rut_cliente,0,strlen($datos_empresa_factura->rut_cliente) - 1)."-".substr($datos_empresa_factura->rut_cliente,-1) .'<br><br>';			        

	        //$messageBody .= '<a href="'. base_url() .'facturas/exportFePDF_mail/'.$track_id.'" >Ver Factura</a><br><br>';

	        $messageBody .= 'Este correo adjunta Documentos Tributarios Electrónicos (DTE) para el receptor electrónico indicado. Por favor responda con un acuse de recibo (RespuestaDTE) conforme al modelo de intercambio de Factura Electrónica del SII.<br><br>';
	        $messageBody .= 'Facturación Electrónica Infosys SPA.';


	        $email_data = $this->facturaelectronica->get_email();
		    if(count($email_data) > 0 && !is_null($datos_empresa_factura->e_mail)){ //MAIL SE ENVÍA SÓLO EN CASO QUE TENGAMOS REGISTRADOS EMAIL DE ORIGEN Y DESTINO
		    	$this->load->library('email');
				$config['protocol']    = $email_data->tserver_intercambio;
				$config['smtp_host']    = $email_data->host_intercambio;
				$config['smtp_port']    = $email_data->port_intercambio;
				$config['smtp_timeout'] = '7';
				$config['smtp_user']    = $email_data->email_intercambio;
				$config['smtp_pass']    = $email_data->pass_intercambio;
				$config['charset']    = 'utf-8';
				$config['newline']    = "\r\n";
				$config['mailtype'] = 'html'; // or html
				$config['validation'] = TRUE; // bool whether to validate email or not      			


		        $this->email->initialize($config);		  		
				
			    $this->email->from($email_data->email_intercambio, 'Factura Electrónica '. NOMBRE_EMPRESA);
			    $this->email->to($datos_empresa_factura->e_mail);

			    #$this->email->bcc(array('rodrigo.gonzalez@info-sys.cl','cesar.moraga@info-sys.cl','sergio.arriagada@info-sys.cl','rene.gonzalez@info-sys.cl')); 
			    $this->email->subject('Envio de DTE ' .$track_id . '_'.$empresa->rut.'-'.$empresa->dv."_".substr($datos_empresa_factura->rut_cliente,0,strlen($datos_empresa_factura->rut_cliente) - 1)."-".substr($datos_empresa_factura->rut_cliente,-1));
			    $this->email->message($messageBody);
			    $ruta =  $factura->archivo_dte_cliente != '' ? 'dte_cliente' : 'dte';
			    $this->email->attach('./facturacion_electronica/' . $ruta .'/'.$path.$nombre_dte);

			    try {
			      $this->email->send();
			      //var_dump($this->email->print_debugger());
			      	        //exit;
			    } catch (Exception $e) {
			      echo $e->getMessage() . '<br />';
			      echo $e->getCode() . '<br />';
			      echo $e->getFile() . '<br />';
			      echo $e->getTraceAsString() . '<br />';
			      echo "no";

			    }
			    return true;

			}else{

				return false;
			}

	}


	public function get_factura($id_factura){

		$this->db->select('fc.tipo_documento, fc.num_factura, fc.fecha_factura, fc.sub_total, fc.descuento, fc.neto, fc.iva, fc.totalfactura, fc.forma, fc.id_factura')
		  ->from('factura_clientes fc')
		  ->where('fc.id',$id_factura)
		  ->limit(1);
		$query = $this->db->get();
		return $query->row();
	 }	 



	public function crea_archivo_dte($xml,$idfactura,$tipo_caf,$tipo_dte){

				$datos_factura = $this->get_factura($idfactura);
				$datos_empresa_factura = $this->get_empresa_factura($idfactura);
				$rutCliente = substr($datos_empresa_factura->rut_cliente,0,strlen($datos_empresa_factura->rut_cliente) - 1)."-".substr($datos_empresa_factura->rut_cliente,-1);

			    $xml_dte = $tipo_dte == 'sii' ? $xml : str_replace("60803000-K",$rutCliente,$xml);

				$file_name = $tipo_dte == 'sii' ? "SII_" : "CLI_";
				$nombre_dte = $datos_factura->num_factura."_". $tipo_caf ."_".$idfactura."_".$file_name.date("His").".xml"; // nombre archivo
				$ruta = $tipo_dte == 'sii' ? 'dte' : 'dte_cliente';
				$path = date('Ym').'/'; // ruta guardado
				if(!file_exists('./facturacion_electronica/' . $ruta . '/'.$path)){
					mkdir('./facturacion_electronica/' . $ruta . '/'.$path,0777,true);
				}				
				$f_archivo = fopen('./facturacion_electronica/' . $ruta .'/'.$path.$nombre_dte,'w');
				fwrite($f_archivo,$xml_dte);
				fclose($f_archivo);

				return array('xml_dte' => $xml_dte,
							 'nombre_dte' => $nombre_dte,
							 'path' => $path);

	 }	 



	/*public function get_contribuyentes(){

		
		$this->db->trans_start();
		header('Content-type: text/plain; charset=ISO-8859-1');

		$config = $this->genera_config();
		include $this->ruta_libredte();

		// solicitar datos
		$datos = \sasco\LibreDTE\Sii::getContribuyentes(
		    new \sasco\LibreDTE\FirmaElectronica($config['firma']),
		    \sasco\LibreDTE\Sii::PRODUCCION
		);

		
		$this->db->query('truncate contribuyentes_autorizados');


		$tabla_contribuyentes = $this->busca_parametro_fe('tabla_contribuyentes');
		$tabla_inserta = $tabla_contribuyentes == 'contribuyentes_autorizados_1' ? 'contribuyentes_autorizados_2' : 'contribuyentes_autorizados_1';
		
		$array_batch = array();
		foreach ($datos as $dato) {




			$this->db->insert('contribuyentes_autorizados',			
							array(
								'rut' => $dato[0],
								'razon_social' => $dato[1],
								'nro_resolucion' => $dato[2],
								'fec_resolucion' => $dato[3],
								'mail' => $dato[4],
								'url' => $dato[5]
							)); 


		}

		$this->db->query("insert into " . $tabla_inserta . " (rut,dv,razon_social,nro_resolucion,fec_resolucion,mail,url)
						select SUBSTRING_INDEX(rut, '-', 1) as rut, SUBSTRING_INDEX(rut, '-', -1) as dv, razon_social, nro_resolucion, concat(SUBSTRING(fec_resolucion,7,4),'-',SUBSTRING(fec_resolucion,4,2),'-',SUBSTRING(fec_resolucion,1,2)) as fec_resolucion, mail, url  from contribuyentes_autorizados");

		//$this->db->insert_batch($tabla_inserta,$array_batch);

		$array_insert = array(
						'nombre_archivo' => null,
						'ruta' => null,
						);

		$this->db->insert('log_cargas_bases_contribuyentes',$array_insert); 


		$this->set_parametro_fe('tabla_contribuyentes',$tabla_inserta);

		$this->db->query('truncate '. $tabla_contribuyentes);

		$this->db->trans_complete(); 		

	}	*/



public function get_contribuyentes(){

		
		$this->db->trans_start();
		header('Content-type: text/plain; charset=ISO-8859-1');

		$config = $this->genera_config();
		include $this->ruta_libredte();

		// solicitar datos
		$datos = \sasco\LibreDTE\Sii::getContribuyentes(
		    new \sasco\LibreDTE\FirmaElectronica($config['firma']),
		    \sasco\LibreDTE\Sii::PRODUCCION
		);
		//print_r($datos); exit;
		
		$tabla_contribuyentes = $this->busca_parametro_fe('tabla_contribuyentes');
		$tabla_inserta = $tabla_contribuyentes == 'contribuyentes_autorizados_1' ? 'contribuyentes_autorizados_2' : 'contribuyentes_autorizados_1';


		foreach ($datos as $dato) {

			$array_rut = explode("-",$dato[0]);
			$array_insert = array(
								'rut' => $array_rut[0],
								'dv' => $array_rut[1],
								'razon_social' => $dato[1],
								'nro_resolucion' => $dato[2],
								'fec_resolucion' => formato_fecha($dato[3],'d-m-Y','Y-m-d'),
								'mail' => $dato[4],
								'url' => $dato[5]
							);

			$this->db->insert($tabla_inserta,$array_insert); 


		}


		$array_insert = array(
						'nombre_archivo' => null,
						'ruta' => null,
						);

		$this->db->insert('log_cargas_bases_contribuyentes',$array_insert); 



		$this->db->select('count(*) as cantidad')
			  ->from($tabla_inserta);
		$query = $this->db->get();
		if(isset($query->row()->cantidad)){
			if($query->row()->cantidad > 0){
				$this->set_parametro_fe('tabla_contribuyentes',$tabla_inserta);
				$this->db->query('truncate '. $tabla_contribuyentes);				
			}

		}


		$this->db->trans_complete(); 		

	}

}
