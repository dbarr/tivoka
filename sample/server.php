<?php
include('../include.php');	//STEP 1

//define the remote procedures in an array
$server = array(							//STEP 2
	'demo.sayHello' => function($request)
	{
		$request->returnResult('Hello World!');
	},
	'demo.substract' => function($request)
	{
		$tmp = array_keys($request->params);
		if(	!is_array($request->params) ||
			!count($request->params) == 2 || 
			!is_numeric($request->params[array_pop($tmp)]) ||
			!is_numeric($request->params[array_pop($tmp)]) )
		{
			$request->returnError(-32602);return False;
		}
		$request->returnResult($request->params[0] - $request->params[1]);
		return TRUE;
	}
);

//convert the array and process the request
$server = new Tivoka_ServerServer(new Tivoka_ServerArrayHost($server));		//STEP 3
$server->process();

?>