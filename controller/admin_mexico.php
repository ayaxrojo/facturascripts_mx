<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez - neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of admin_mexico
 *
 * @author ayaxrojo
 */

class admin_mexico extends fs_controller
{
   
   protected $EpathMX;
   protected $UsHr;
   
   public $Install_Path;   
   public $Rsl_Mx_Str = '{info:{rsl:0, cp:"0"}};';
   public $ColMx;
   public $UsHrMX = '';
      
   const DS = DIRECTORY_SEPARATOR;
   const R = "\r\n";
   const SL = "\\";
   
   public function install_MX(){
              
       $path = realpath(dirname(__FILE__));
       $this->Install_Path = realpath(dirname($path, 1)) . self::DS;
       
       unset($path);
       
       $FsConf = $this->Install_Path . 'config.php';
       $FsUnI =  $this->Install_Path . 'plugins' . self::DS . 'admin_mexico' . self::DS . 'extras' . self::DS . 'uninstall.xml';
              
       if(!copy($FsConf, $FsConf. '.bak')){
           return (boolean) false;
       }
       
       if(is_writable($FsConf)){
           
           $strConf = file_get_contents($FsConf);           
           
           if(strpos($strConf, "define('FS_INSTALL_PATH',") === false){
              $setDef = self::R . '/// Modificación admin_mexico' . self::R . "define('FS_INSTALL_PATH', '" . $this->Install_Path . "');"; 
              if(!file_put_contents($FsConf, $setDef, FILE_APPEND | LOCK_EX)){
					return (boolean) false;
			  }                  
           }
           
           unset($strConf);
           
           if(file_exists($FsUnI)){
             unlink($FsUnI);
           }
           
           $fp = fopen($FsUnI, 'w+');
           fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n");
           fwrite($fp, '<files>' . self::R);
           fclose($fp);
           
           require_once($this->Install_Path . 'base'. self::DS .'fs_db2.php');
           $db = new fs_db2();
           
           if( $db->connect() ){
              $tables = $db->list_tables();              
              file_put_contents($FsUnI,  '<databases>'. self::R, FILE_APPEND | LOCK_EX);
              $qry = "SELECT TABLE_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . FS_DB_NAME . "' AND COLUMN_NAME = 'codpostal' AND TABLE_NAME='empresa';";
               
              if($rs = $db->select($qry)){
                    foreach($rs as $tb){
                        $e = '<tb nam="'. $tb["name"] . '" />' . self::R;
                        file_put_contents($FsUnI, $e, FILE_APPEND | LOCK_EX);
						$db->exec("ALTER TABLE " . $tb["name"] . " ADD colonia TEXT NULL AFTER codpostal;", false);
                    }
              }             
              
              file_put_contents($FsUnI,  '</databases>' . self::R . '</files>', FILE_APPEND | LOCK_EX);
               
           }else{
		     return (boolean) false;
		   }          
           return (boolean) false;
       }else{
           return (boolean) false;
       }
   }
   
   protected function huso_hor_mx(){
   
       $DThus = array(
                        "UTC/GMT -06:00" => array("Distrito Federal", "Aguascalientes", "Campeche", "Chiapas", "Coahuila de Zaragoza", "Colima", "Durango", "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "México", "Michoacán de Ocampo", "Morelos", "Nuevo León", "Oaxaca", "Puebla", "Querétaro Arteaga", "San Luis Potosí", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"),
                        "UTC/GMT -07:00" => array("Baja California Sur", "Chihuahua", "Nayarit", "Sinaloa", "Sonora"),
                        "UTC/GMT -08:00" => array("Baja California")
                    );
       $html_str = "";
       
       foreach($DThus as $hor => $val){            
            foreach($val as $hr){			    
				$xscl = '';
                
				if(preg_match("/$this->UsHr/i", $hr)){
                    $xscl = 'selected';
                }
                
                $html_str .= '<option value="'. $hor .'"' . $xscl . ' >' . $hr . '  ' . $hor .'</option>';
            }              
       };
	        
       return (string) $html_str;
	   
   }  
   
   public function traducciones()
   {
      $clist = array();
      
      $include = array(          
          'albaran','albaranes', 'cifnif','irpf'
      );
      
      $tradMX = array(
          'recibo', 'recibos', 'R.F.C.', 'I.S.R.' 
      );
      
      $x=0;
      
      foreach($GLOBALS['config2'] as $i => $value)
      {
         if( in_array($i, $include) ){
            $clist[] = array('tradMX' => $tradMX[$x], 'nombre' => $i, 'valor' => $value);
            $x++;
         }
      }
      
      return (array) $clist;
   }
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Mexico', 'admin', TRUE, TRUE, TRUE);
   }
   
   protected function get_path_extras_mx(){
       
       $PT = '/^(.+)(' . self::SL . self::DS . 'admin_mexico' . self::SL . self::DS . 'controller)$/m';
       $RP = self::DS . 'admin_mexico' . self::DS . 'extras'. self::DS;
       $DN = dirname(__FILE__);
       
       if(preg_match($PT,  $DN)){
            $this->EpathMX = preg_replace($PT, "$1".$RP, $DN);
            unset($PT);
            unset($RP);
            unset($DN);
       }
       
   }

   protected function get_cp_mx( $cp ){
       $file_mx =  $this->EpathMX . 'simple_html_dom.php';
       if(is_readable($file_mx)){
              require_once ($file_mx);
              unset($file_mx);
              $cp_Mx_Url = 'https://api-codigos-postales.herokuapp.com/v2/codigo_postal/' . $cp;
              $html = file_get_html($cp_Mx_Url);
              if($html){
                $ret = $html->plaintext;
                unset($html);
                return (string) $ret;
              }else{
                return (string) '';  
              }
      }      
      return (string) '';
   }
            
   protected function private_core(){
          
      $this->share_extensions();
      $this->get_path_extras_mx();
      $CodMx =  $this->empresa->codpostal;	  
     
	 if(isset($_POST['cpMX']) && !empty($_POST['cpMX'])){          
		$CpMx = $this->get_cp_mx( $_POST['cpMX'] );		  		  
     }elseif(!empty($CodMx)){	 
		$CpMx = $this->get_cp_mx( $CodMx );		 
	 }
      
	 if(!empty($CpMx)){
		 $json = json_decode($CpMx);
		 if($json){
		    if(!empty($json->estado) && !empty($json->municipio)){ 
				$this->empresa->provincia = $json->estado;
				$this->empresa->ciudad = $json->municipio;
				$this->colonia = $json->colonias;
				$this->empresa->codpostal = $json->codigo_postal;
				$this->UsHr =  $json->estado;
				$this->Rsl_Mx_Str = '{info: {rsl:1, cp:"' . $json->codigo_postal . '"}};';
				$this->UsHrMX = $this->huso_hor_mx();
			}else{
				$this->Rsl_Mx_Str = '{info:{rsl:0, cp:"0"}};';
				$this->empresa->codpostal = '';
			}
		 }else{
			$this->Rsl_Mx_Str = '{info:{rsl:0, cp:"0"}};';
			$this->empresa->codpostal = '';
		 }
     }else{
         $this->Rsl_Mx_Str = '{info:{rsl:0, cp:"0"}};';
		 $this->empresa->codpostal = '';
     } 
	  
	 if(isset($_POST['Frm_install'])){
	     $rslt = $this->install_MX();	  
	 }
	  
     if( isset($_GET['opcion']) )
      {
         if($_GET['opcion'] == 'moneda')
         {
            $this->empresa->coddivisa = 'PSO';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
         }
         else if($_GET['opcion'] == 'pais')
         {
            $this->empresa->codpais = 'MX';
            if( $this->empresa->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
         }
      }
   }
   
   private function share_extensions()
   {
      
   }
}
