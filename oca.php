<?php
/**
* $Id$
*
* Copyright (c) 2015, Juancho Rossi.  All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* OCA Express y OCA Express Pak son propiedad de Organización Coordinadora Argentina (OCA)
*/

/**
* OCA PHP API Class
*
* @link https://github.com/juanchorossi/OCA-PHP-API
* @version 0.1.1
*/

class Oca
{
	const VERSION				= '0.1.1';
	protected $webservice_url	= 'webservice.oca.com.ar';
	
	// ========================================================================
	
	public function __construct($Cuit = '', $Operativa = '')
	{
		$this->Cuit 		= trim($Cuit);
		$this->Operativa 	= trim($Operativa);
	}
	
	// =========================================================================
	
	/**
	 * Sets the useragent for PHP to use
	 * 
	 * @return string
	 */
	public function setUserAgent()
	{
		return 'OCA-PHP-API ' . self::VERSION . ' - github.com/juanchorossi/OCA-PHP-API';
	}

	// =========================================================================

	/**
	 * Tarifar un Envío Corporativo
	 *
	 * @param string $PesoTotal
	 * @param string $VolumenTotal
	 * @param string $CodigoPostalOrigen
	 * @param string $CodigoPostalDestino
	 * @param string $CantidadPaquetes
	 * @param string $ValorDeclarado
	 *
	 * Resultado: (XML) conteniendo el tipo de tarifador y el precio del envío.
	 */
	public function tarifarEnvioCorporativo($PesoTotal, $VolumenTotal, $CodigoPostalOrigen, $CodigoPostalDestino, $CantidadPaquetes, $ValorDeclarado)
	{
		$_query_string = array(	'PesoTotal'				=> $PesoTotal,
								'VolumenTotal'			=> $VolumenTotal,
								'CodigoPostalOrigen'	=> $CodigoPostalOrigen,
								'CodigoPostalDestino'	=> $CodigoPostalDestino,
								'CantidadPaquetes'		=> $CantidadPaquetes,
								'ValorDeclarado'		=> $ValorDeclarado,
								'Cuit'					=> $this->Cuit,
								'Operativa'				=> $this->Operativa);

		$ch = curl_init();
		
		curl_setopt_array($ch,	array(	CURLOPT_RETURNTRANSFER	=> TRUE,
										CURLOPT_HEADER			=> FALSE,
										CURLOPT_USERAGENT		=> $this->setUserAgent(),
										CURLOPT_CONNECTTIMEOUT	=> 5,
										CURLOPT_POST			=> TRUE,
										CURLOPT_POSTFIELDS		=> http_build_query($_query_string),
										CURLOPT_URL				=> "{$this->webservice_url}/epak_tracking/Oep_TrackEPak.asmx/Tarifar_Envio_Corporativo",
										CURLOPT_FOLLOWLOCATION	=> TRUE));

		$dom = new DOMDocument();
		@$dom->loadXML(curl_exec($ch));
		$xpath = new DOMXpath($dom);

		$e_corp = array();
		foreach (@$xpath->query("//NewDataSet/Table") as $envio_corporativo)
		{
			$e_corp[] = array(	'Tarifador'		=> $envio_corporativo->getElementsByTagName('Tarifador')->item(0)->nodeValue,
								'Precio'		=> $envio_corporativo->getElementsByTagName('Precio')->item(0)->nodeValue,
								'Ambito'		=> $envio_corporativo->getElementsByTagName('Ambito')->item(0)->nodeValue,
								'PlazoEntrega'	=> $envio_corporativo->getElementsByTagName('PlazoEntrega')->item(0)->nodeValue,
								'Adicional'		=> $envio_corporativo->getElementsByTagName('Adicional')->item(0)->nodeValue,
								'Total'			=> $envio_corporativo->getElementsByTagName('Total')->item(0)->nodeValue,
							);
		}
		
		return $e_corp;
	}

	// =========================================================================
	
	/**
	 * Devuelve todos los Centros de Imposición existentes cercanos al CP
	 * 
	 * @param integer $CP Código Postal
	 * @return type 
	 */
	public function getCentrosImposicionPorCP($CP = NULL)
	{
		if ( ! $CP) return;
		
		$ch = curl_init();
		
		curl_setopt_array($ch,	array(	CURLOPT_RETURNTRANSFER	=> TRUE,
										CURLOPT_HEADER			=> FALSE,
										CURLOPT_USERAGENT		=> $this->setUserAgent(),
										CURLOPT_CONNECTTIMEOUT	=> 5,
										CURLOPT_POST			=> TRUE,
										CURLOPT_POSTFIELDS		=> 'CodigoPostal='.(int)$CP,
										CURLOPT_URL				=> "{$this->webservice_url}/oep_tracking/Oep_Track.asmx/GetCentrosImposicionPorCP",
										CURLOPT_FOLLOWLOCATION	=> TRUE));

		$dom = new DOMDocument();
		@$dom->loadXML(curl_exec($ch));
		$xpath = new DOMXpath($dom);
	
		$c_imp = array();
		foreach (@$xpath->query("//NewDataSet/Table") as $ci)
		{
			$c_imp[] = array(	'idCentroImposicion'	=> $ci->getElementsByTagName('idCentroImposicion')->item(0)->nodeValue,
								'IdSucursalOCA'			=> $ci->getElementsByTagName('IdSucursalOCA')->item(0)->nodeValue,
								'Sigla'					=> $ci->getElementsByTagName('Sigla')->item(0)->nodeValue,
								'Descripcion'			=> $ci->getElementsByTagName('Descripcion')->item(0)->nodeValue,
								'Calle'					=> $ci->getElementsByTagName('Calle')->item(0)->nodeValue,
								'Numero'				=> $ci->getElementsByTagName('Numero')->item(0)->nodeValue,
								'Torre'					=> $ci->getElementsByTagName('Torre')->item(0)->nodeValue,
								'Piso'					=> $ci->getElementsByTagName('Piso')->item(0)->nodeValue,
								'Depto'					=> $ci->getElementsByTagName('Depto')->item(0)->nodeValue,
								'Localidad'				=> $ci->getElementsByTagName('Localidad')->item(0)->nodeValue,
								'IdProvincia'			=> $ci->getElementsByTagName('IdProvincia')->item(0)->nodeValue,
								'idCodigoPostal'		=> $ci->getElementsByTagName('idCodigoPostal')->item(0)->nodeValue,
								'Telefono'				=> $ci->getElementsByTagName('Telefono')->item(0)->nodeValue,
								'eMail'					=> $ci->getElementsByTagName('eMail')->item(0)->nodeValue,
								'Provincia'				=> $ci->getElementsByTagName('Provincia')->item(0)->nodeValue,
								'CodigoPostal'			=> $ci->getElementsByTagName('CodigoPostal')->item(0)->nodeValue
							);
		}
		
		return $c_imp;
	}
	
	// =========================================================================
	
	/**
	 * Devuelve todos los Centros de Imposición existentes
	 * 
	 * @return array $c_imp
	 */
	public function getCentrosImposicion()
	{
		$ch = curl_init();
		
		curl_setopt_array($ch,	array(	CURLOPT_RETURNTRANSFER	=> TRUE,
										CURLOPT_HEADER			=> FALSE,
										CURLOPT_CONNECTTIMEOUT	=> 5,
										CURLOPT_USERAGENT		=> $this->setUserAgent(),
										CURLOPT_URL				=> "{$this->webservice_url}/oep_tracking/Oep_Track.asmx/GetCentrosImposicion",
										CURLOPT_FOLLOWLOCATION	=> TRUE));

		$dom = new DOMDocument();
		@$dom->loadXML(curl_exec($ch));
		$xpath = new DOMXpath($dom);
	
		$c_imp = array();
		foreach (@$xpath->query("//NewDataSet/Table") as $ci)
		{
			$c_imp[] = array(	'idCentroImposicion'	=> $ci->getElementsByTagName('idCentroImposicion')->item(0)->nodeValue,
								'Sigla'					=> $ci->getElementsByTagName('Sigla')->item(0)->nodeValue,
								'Descripcion'			=> $ci->getElementsByTagName('Descripcion')->item(0)->nodeValue,
								'Calle'					=> $ci->getElementsByTagName('Calle')->item(0)->nodeValue,
								'Numero'				=> $ci->getElementsByTagName('Numero')->item(0)->nodeValue,
								'Piso'					=> $ci->getElementsByTagName('Piso')->item(0)->nodeValue,
								'Localidad'				=> $ci->getElementsByTagName('Localidad')->item(0)->nodeValue,
							);
		}
		
		return $c_imp;
	}

	// =========================================================================

	/**
	 * Obtener lista de Provincias 
	 * Resultado: array $e_prov
	 */
	public function getProvincias()
	{
		$ch = curl_init();
		curl_setopt_array($ch,	array(	CURLOPT_RETURNTRANSFER	=> TRUE,
										CURLOPT_HEADER			=> FALSE,
										CURLOPT_CONNECTTIMEOUT	=> 5,
										CURLOPT_USERAGENT		=> $this->setUserAgent(),
										CURLOPT_URL				=> "{$this->webservice_url}/oep_tracking/Oep_Track.asmx/GetProvincias",
										CURLOPT_FOLLOWLOCATION	=> TRUE));
		$dom = new DOMDocument();
		$dom->loadXml(curl_exec($ch));
		$xpath = new DOMXPath($dom);
		
		$e_prov = array();
		foreach (@$xpath->query("//Provincias/Provincia") as $provincia) {
			$e_prov[] = array( 
				'id' => $provincia->getElementsByTagName('IdProvincia')->item(0)->nodeValue,
				'provincia' => $provincia->getElementsByTagName('Descripcion')->item(0)->nodeValue, 
			);
		}
		
		return $e_prov;
	}

	// =========================================================================

	/**
	 * Lista de localidades de una provincia
	 * @param string $idProvincia
	 */
	public function getLocalidadesByProvincia($idProvincia)
	{
		$_query_string = array(	'idProvincia' => $idProvincia );
		
		$ch = curl_init();
		curl_setopt_array($ch,	array(	CURLOPT_RETURNTRANSFER	=> TRUE,
										CURLOPT_HEADER			=> FALSE,
										CURLOPT_CONNECTTIMEOUT	=> 5,
										CURLOPT_POSTFIELDS		=> http_build_query($_query_string),
										CURLOPT_USERAGENT		=> $this->setUserAgent(),
										CURLOPT_URL				=> "{$this->webservice_url}/oep_tracking/Oep_Track.asmx/GetLocalidadesByProvincia",
										CURLOPT_FOLLOWLOCATION	=> TRUE));
		$dom = new DOMDocument();
		$dom->loadXml(curl_exec($ch));
		$xpath = new DOMXPath($dom);
		
		$e_loc = array();
		foreach (@$xpath->query("//Localidades/Provincia") as $provincia) {
			$e_loc[] = array( 'localidad'=> $provincia->getElementsByTagName('Nombre')->item(0)->nodeValue );
		}
		return $e_loc;
	}

	// =========================================================================
}
