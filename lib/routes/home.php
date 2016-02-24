<?php 
	require_once __DIR__."/../includes/usersClass.php";
	require_once __DIR__."/../includes/dbClass.php";
	require_once __DIR__."/../includes/utilClass.php";
	require_once __DIR__."/../common.php";
	$app->get('/home',function() use($app){
		$now = time();
		$i=0;
		$users = new Users(DB::getInstance('root','','fantacalcio','localhost'));
		$sequence = array();
		foreach ($users->getUsersByScore() as $user)
			array_push($sequence, array('username'=>$user->getUsername(),'points'=>$user->getScore()));
		$twigParameters = getTwigParameters('Home',$app['siteName'],'home',$app['userMoney'],array('sequence'=>$sequence));
		$cmn = new Utils(DB::getInstance('root','','fantacalcio','localhost'));
		$cmn->initNavbar($app);
		return $app['twig']->render('index.twig',$twigParameters);
	});

	$app->get('/',function() use($app){
		return $app->redirect('//'.$app['request']->getHttpHost().'/home');
	});

?>