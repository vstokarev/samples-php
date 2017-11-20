<?php

/**
 * Class JSONRPCResponse
 * @author Viacheslav Tokarev
 *
 * @property string $jsonrpc
 * @property mixed $result
 * @property int $id
 * @property JSONRPCError $error
 */
class JSONRPCResponse extends CModel
{
	/** @var string */
	public $jsonrpc = '2.0';

	/** @var mixed */
	public $result;

	/** @var int */
	public $id;

	/** @var mixed */
	public $error;

	/**
	 * @param int|string|null $id
	 * @param int $code
	 * @param string $message
	 * @param null|mixed $data
	 */
	public function setError( $id, $code, $message, $data=null )
	{
		$error = new JSONRPCError();
		$error->attributes = [ 'id' => $id, 'code' => $code, 'message' => $message, 'data' => $data ];

		$this->error = $error;
	}

	// Required method
	public function attributeNames()
	{
		return [ 'jsonrpc', 'result', 'id', 'error' ];
	}

	// Magic method to convert objects to string
	public function __toString()
	{
		$result = $this->attributes;
		if ( $this->error instanceof JSONRPCError )
		{
			$result['error'] = $this->error->attributes;
			if ( $result['error']['data'] == null )
				unset( $result['error']['data'] );

			unset( $result['result'] );
		}

		return json_encode( $result, JSON_UNESCAPED_UNICODE );
	}
}