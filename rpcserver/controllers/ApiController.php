<?php

/**
 * Class ApiController
 * @author Viacheslav Tokarev
 */
class ApiController extends CController
{
	/** @var JSONRPCRequest */
	protected $request;

	/** @var JSONRPCResponse */
	protected $response;

	public function init()
	{
		parent::init();

		$this->request  = new JSONRPCRequest();
		$this->response = new JSONRPCResponse();

		// Set custom error handler to make sure we always return JSON
		Yii::app()->errorHandler->errorAction = 'api/error';

		// Setup logging (for debugging purposes)
		//Yii::app()->onEndRequest = [ $this, '_logRequest' ];
	}

	public function actionIndex()
	{
		$version = isset( $_GET['v'] ) ? $_GET['v'] : 0;

		if ( !$this->request->loadFromJSON( file_get_contents( 'php://input' ) ) )
		{
			$this->response->setError( null, JSONRPCError::PARSE_ERROR, 'Unable to parse the request' );
			$this->_printResult();
		}

		if ( !$this->request->validate() )
		{
			$this->response->setError( null, JSONRPCError::INVALID_REQUEST, 'Invalid request' );
			$this->_printResult();
		}

		$rpcServer = new RPCServer( $version );
		$rpcServer->runMethod( $this->request, $this->response );

		$this->_printResult();
	}

	public function actionError()
	{
		$this->response->setError( $this->request->id, JSONRPCError::INTERNAL_ERROR,
			Yii::app()->errorHandler->error['message'] );

		$this->_printResult();
	}

	protected function _logRequest()
	{
		$logFile = fopen( Yii::getPathOfAlias( 'application' ) . DIRECTORY_SEPARATOR . 'runtime'
		                  . DIRECTORY_SEPARATOR . 'apilog8.log', 'a' );
		fwrite( $logFile, ( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unkwn ip' ) . ' - '
		                  . file_get_contents( 'php://input' ) . "\n\n" );
		fclose( $logFile );
	}

	protected function _printResult()
	{
		//header( 'Content-type:application/json; charset=utf-8' );
		//header( 'Access-Control-Allow-Origin: *' );

		echo $this->response;

		Yii::app()->end();
	}
}