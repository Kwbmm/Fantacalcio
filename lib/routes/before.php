<?php 
  require_once 'remember_me.php';
  require_once 'user_money.php';
  require_once 'closing_time.php';
  require_once 'fetch_marks.php';
  require_once 'compute_results.php';

  $app->before(function() use($app){
    if($app['maintenance']){
      return $app->abort(453,$app['siteName']." è attualmente in manutenzione. Riprova più tardi!");
    }
    rememberMe($app);
    userMoney($app);
    closingTime($app);
    if(fetchMarks($app))
      computeResults($app); //Compute the results for each user only if marks are out
  });

?>