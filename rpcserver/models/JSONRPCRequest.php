<?php

/**
 * Class JSONRPCDocument
 * @author Viacheslav Tokarev
 *
 * @property string $jsonrpc
 * @property int $id
 * @property string $method
 * @property array $params
 */
class JSONRPCRequest extends CModel
{
	/** @var string */
	public $jsonrpc;

	/** @var int */
	public $id;

	/** @var string */
	public $method;

	/** @var array */
	public $params;

	// Validation rules
	public function rules()
	{
		return [
			[ 'jsonrpc,method', 'required' ],
			[ 'jsonrpc', 'compare', 'compareValue' => '2.0' ],
			[ 'id,params', 'safe' ]
		];
	}

	/**
	 * @param string $jsonString
	 * @return boolean
	 */
	public function loadFromJSON( $jsonString )
	{
		$requestData = @json_decode( $jsonString, true );
		if ( is_null( $requestData ) )
			return false;

		if ( sizeof( $requestData ) > 0 )
			$this->attributes = $requestData;

		return true;
	}

	// Required method
	public function attributeNames()
	{
		return [ 'jsonrpc', 'id', 'method', 'params' ];
	}
}