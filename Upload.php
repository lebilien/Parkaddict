<?php 
/**
* @author driaaux cedric <cedric@driaux.com>
* 
* 
*
* @method save
* @method bucketdel
*/

class Model_Upload{

	
	protected $_key = '';
	protected $_secretkey = '';
	protected $_s3;
	protected $_perm;
	protected $_destpath='';

	private static $instance;
	
	/*
	*
	*
	remplir le fichier app.ini avec les informations suivante
	[s3]
		s3.key=key
		s3.secretkey=secretkey
		s3.bucket=nomdubucket
		s3.upload=copietmp/
		
		
	[url]
		url.image=http://nombuckets3.amazonaws.com
	
	[taille_all]
		
		taille.grand=990*464
		taille.moyen=480*225
		taille.petit=200*120

	[taille_avatar]
		taille.avatar=50*50
	*
	*
	*
	*
	*/
	
	
	
	function __construct(){
    
			try{
				require_once('Zend/Service/Amazon/S3.php');
			
				$s3=new Zend_Config_Ini( APPLICATION_PATH.'/configs/app.ini',"s3",true);
			
				$this->_s3 = new Zend_Service_Amazon_S3($s3->s3->key, $s3->s3->secretkey);
				$this->_perm=array(
					Zend_Service_Amazon_S3::S3_ACL_HEADER =>
					Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ
					);
					
			}catch(Exception $e){
		
				 throw new exception("s3nok: ".$e->getMessage());
			}
		

    }
	
	
	/*
	* save  image in the amazons3 cloud 
	* @param file $filepath  ex : $_files[][tmp_name]
	*@param file $name ex: $_files [][name]
	*@param string $bucket ex: bucket/sous_repertoire
	*@param string $taille ex: gestion des images
	* @return array
	*/

	
	function save($filepath,$name,$bucket,$taille="all"){
		
		
	try{	
		$liste_erreur=array();
		$filename_sauvegarde="";
	
	
		require_once LIBRARY_PATH . '/phpthumb/ThumbLib.inc.php';
		$gestionfiltre=new Parkaddict_Gestionfiltre();
		$name_image=$gestionfiltre->nameimage();
		$listetaille=new Zend_Config_Ini(APPLICATION_PATH.'/configs/app.ini',"taille_".$taille,true);
		$s3=new Zend_Config_Ini( APPLICATION_PATH.'/configs/app.ini',"s3",true);
		
		$tempFile = $filepath;
		$ext=explode(".",$name);
		$targetFile =  str_replace('//','/',$s3->s3->destpath) .$name_image.".".$ext[1] ;
		move_uploaded_file($tempFile,$targetFile);
		
		foreach($listetaille as $key => $info){
		
			foreach($info as $keyinterne =>$val){
			
			
				
				
			 
			
			$dim=explode("*",$val);
			
			$thumb = PhpThumbFactory::create($targetFile);
			$thumb->adaptiveResize($dim[0],$dim[1]);
			$filename = basename($name_image, '.'.strtolower($thumb->getFormat())).'_'.str_replace("*","_",(string)$val).'.'.strtolower($thumb->getFormat());
			$filename_sauvegarde=basename($name_image, '.'.strtolower($thumb->getFormat())).'.'.strtolower($thumb->getFormat());
			$destPath = rtrim($s3->s3->destpath, '/') . '/' . $filename;
		
		        if (!file_exists($destPath)) {
				
		         $thumb->save($destPath);
		        }
			$ret=$this->_s3->putFileStream($destPath,$s3->s3->bucket."/".$bucket.'/'.$filename,$this->_perm);
		
			/* gestion unlink **/
		
			unlink($destPath);
			
		
		
			}
		}
		
		$url=new Zend_Config_Ini(APPLICATION_PATH.'/configs/app.ini',"url",true);
		
		
		/* gestion unlink **/
		
		unlink($targetFile);
		
		
		return array("url"=>$url->url->image,"bucket"=>$bucket,"filename"=>$name_image,"extension"=>$ext[1]);
		
		}
		catch(Exception $e){
			throw new exception("s3noupload : ".$e->getMessage());
		}
	}
	
	
	
	
	
	function bucketdel($bucket){
	
		
			$succ=$this->_s3->removeBucket($bucket);
			
			if(!$succ){
				throw new exception("s3nokdelbucket ");
			}
	
	}
	
	


}

?>