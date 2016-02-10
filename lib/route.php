<?php 
  require_once '../vendor/phpQuery/phpQuery-onefile.php';  

  require_once 'routes/before.php'; //Before functions
  require_once 'routes/home.php'; //Home routes
  require_once 'routes/register.php'; //Register routes
  require_once 'routes/login.php'; //Login routes
  require_once 'routes/buy.php'; //Buy routes
  require_once 'routes/checkout.php'; //Checkout routes
  require_once 'routes/formation.php'; //Formation routes
  require_once 'routes/roster.php'; //Roster routes
  require_once 'routes/marks.php'; //Marks routes

/*
**   ========================================================================
**  |                                 LOGOUT                                 |
**   ========================================================================
*/
  $app->get('/logout',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
    closeSession($app);
    $extra = array('success' => 'Logout effettuato!');
    $twigParameters = getTwigParameters('Logout',$app['siteName'],'logout',$app['userMoney'],$extra);
    return $app['twig']->render('index.twig',$twigParameters);
  });
/*
**   ========================================================================
**  |                                 RULES                                  |
**   ========================================================================
*/
  $app->get('/rules',function () use($app){
    $twigParameters = getTwigParameters('Regolamento',$app['siteName'],'rules',$app['userMoney'],array('startMoney'=>$app['startMoney']));
    return $app['twig']->render('index.twig',$twigParameters);    
  });