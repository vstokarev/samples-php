<?php

/**
 * Class RPCServer
 * @author Viacheslav Tokarev
 */
class RPCServer
{
	protected $apiVersion;

	public function __construct( $apiVersion )
	{
		$this->apiVersion = $apiVersion;
	}

	/**
	 * @param JSONRPCRequest $request
	 * @param JSONRPCResponse $response
	 *
	 * @throws Exception
	 */
	public function runMethod( $request, $response )
	{
		$processor = $this->_loadAPIProcessor();
		if ( $processor === false )
		{
			$response->setError( $request->id, -32000,
				'Processor for the specified API version was not found' );
			return;
		}

		$class = new \ReflectionClass( $processor );
		if ( !$class->hasMethod( $request->method ) )
		{
			$response->setError( $request->id, JSONRPCError::METHOD_NOT_FOUND, 'Method was not found' );
			return;
		}

		$method = $class->getMethod( $request->method );
		if ( !$method->isPublic() )
		{
			$response->setError( $request->id, -32001, 'Method is not public' );
			return;
		}

		if ( $method->getNumberOfRequiredParameters() > sizeof( $request->params ) )
		{
			$response->setError( $request->id, JSONRPCError::INVALID_PARAMS,
				'Invalid number of parameters' );
			return;
		}

		$pass = [];
		if ( $method->getNumberOfRequiredParameters() > 0 )
		{
			foreach( $method->getParameters() as $param )
			{
				if ( isset( $request->params[$param->getName()] ) )
					$pass[] = $request->params[$param->getName()];
				elseif( $param->isDefaultValueAvailable() )
					$pass[] = $param->getDefaultValue();
				else
				{
					$response->setError( $request->id, JSONRPCError::INVALID_PARAMS,
						'Required param ' . $param->getName() . ' was not found in request' );
					return;
				}
			}
		}

		try
		{
			$result = $method->invokeArgs( $processor, $pass );
		}
		catch( \Exception $e )
		{
			$response->setError( $request->id, JSONRPCError::INTERNAL_ERROR,
				$e->getMessage() );
			return;
		}

		$response->id = $request->id;
		$response->result = $result;
	}

	/**
	 * @return bool|object
	 */
	public function _loadAPIProcessor()
	{
		if ( !file_exists( Yii::getPathOfAlias( 'application.controllers.rpc.Methods' ) . $this->apiVersion . '.php' ) )
			return false;

		Yii::import( 'application.controllers.rpc.Methods' . $this->apiVersion );

		$className = 'Methods' . $this->apiVersion;

		return new $className();
	}
}