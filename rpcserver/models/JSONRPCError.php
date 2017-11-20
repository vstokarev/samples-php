<?php

/**
 * Class JSONRPCError
 * @author Viacheslav Tokarev
 *
 * @property int $code
 * @property string $message
 * @property mixed $data
 */
class JSONRPCError extends CModel
{
	/** @var int */
	public $code;

	/** @var string */
	public $message;

	/** @var mixed */
	public $data;

	const PARSE_ERROR       = -32700;
	const INVALID_REQUEST   = -32600;
	const METHOD_NOT_FOUND  = -32601;
	const INVALID_PARAMS    = -32602;
	const INTERNAL_ERROR    = -32603;

	public function rules()
	{
		return [
			[ 'code,message,data', 'safe' ]
		];
	}

	// Required method
	public function attributeNames()
	{
		return [ 'code', 'message', 'data' ];
	}
}