<?php
  use Symfony\Component\HttpFoundation\Response;

  $app->error(function (\Exception $e, $code) use($app) {
    $page = basename($_SERVER['REQUEST_URI']);
    $extra = array('error' => $e->getMessage());
    $extra['error'] .= " (".$e->getCode().")";
    $twigParameters = getTwigParameters(ucfirst($page),$app['siteName'],$page,$app['userMoney'],$extra);
    switch ($code) {
      //Custom error codes can start from 452 (https://it.wikipedia.org/wiki/Codici_di_stato_HTTP#4xx_Client_Error)
      /*
        452 -> Generic error
        460 - 464 -> Errors on Login and Register pages
        465 - 469 -> Error on Buy and Checkout pages
        470 - 474 -> Error on Roster page
        475 - 479 -> Error on Modulo, Formation and confirmForm pages
      */
      case 404:
        return $app['twig']->render('404.twig',array('pageName'=>'Errore','siteName'=>$app['siteName']));
      case 452: //Generic result === false
        return $app['twig']->render('index.twig',$twigParameters);
      
      case 460: //User/psw empty (Login/register)
        return $app['twig']->render('index.twig',$twigParameters);
      case 461: //User already in use (Login/register)
        return $app['twig']->render('index.twig',$twigParameters);
      case 462: //User/psw wrong (Login/register)
        return $app['twig']->render('index.twig',$twigParameters);
      
      case 465: //Buy failed, not enough money
        return $app['twig']->render('index.twig',$twigParameters);
      case 466: //Amounts of POR/DIF/CEN/ATT exceeded
        return $app['twig']->render('index.twig',$twigParameters);
      case 467: //Checkbox keys are not matching
        return $app['twig']->render('index.twig',$twigParameters);

      case 470: //SP Deletion from user_roster failed
        return $app['twig']->render('index.twig',$twigParameters);
      case 471: //Update of user money failed
        return $app['twig']->render('index.twig',$twigParameters);  
      case 472: //Empty request
        return $app['twig']->render('index.twig',$twigParameters);
      case 475: //@confirmForm $role not matching
        return $app['twig']->render('index.twig',$twigParameters);
      case 476: //@confirmForm $SPID not matching
        return $app['twig']->render('index.twig',$twigParameters);
      default:
        return $app['twig']->render('index.twig',$twigParameters);
        //Should never happen
    }
  });
?>