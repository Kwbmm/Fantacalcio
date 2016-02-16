<?php
	/**
	 * Class containing utilities functions
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	*/

	require_once 'dbClass.php';
	class Utils	{
		private $db = null;

		/**
		 * Default constructor
		 * @param PDO::Database $db Optional parameter to initialize class with custom database settings.
		 */
		function __construct($db=null) {
			if(!isset($db))
				$db = DB::getInstance();
			$this->db = $db;
		}

		/**
		 * Closes the session and destroys cookies
		 * 
		 * @return void
		 */
		public function closeSession(){
			if(isset($_COOKIE['token'])){ //Destroy the token
				$cookie_array = explode(":",$_COOKIE['token']);
				$selector = $cookie_array[0];
				$s = $this->db->prepare("SELECT * FROM auth_token WHERE selector=:sel FOR UPDATE");
				$s->bindValue(':sel',$selector,PDO::PARAM_STR);
				$s->execute();
				$results = $s->fetchAll(PDO::FETCH_ASSOC);

				$s = $this->db->prepare("DELETE FROM auth_token WHERE selector=:sel");
				$s->bindValue(':sel',$selector,PDO::PARAM_STR);
				$s->execute();

				setcookie('token','',time()-3600);
			}
			session_unset();
			if(session_id() != "" || isset($_COOKIE[session_name()]))
				setcookie(session_name(), '', time()-3600, '/');
			session_destroy();
		}

		/**
		 * Generates a random string. Exploits mt_rand to generate pseudo-random strings
		 * @param  integer $length   Lenght of the desired string, defaults to 12
		 * @param  boolean $specials If string should contain special characters, defaults to true
		 * @return string            The generated string
		 */
		public function getRandomString($length = 12,$specials=true) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			if($specials)
				$characters.='|!£$%&/()=?^-_<>';
			$string = '';

			for($i = 0; $i < $length; $i++)
				$string .= $characters[mt_rand(0, strlen($characters) - 1)];

			return $string;
		}

		/**
		 * Initialized the menu bar that will be displayed through twig renderer.
		 * Note that the returned object must be assigned to a variable inside the $app object:
		 *
		 * 	$app['mainMenu'] = $util->initMenu($app);
		 * 	$app['knp_menu.menus'] = array('main'=>'mainMenu');
		 * 	
		 * @param  Silex\Application $app The silex application
		 * @return KnpMenu      The initialized KnpMenu
		 */
		public function initMenu($app){
		    $menu = $app['knp_menu.factory']->createItem('MainMenu');
		    $menu->addChild('Home',array('uri' => '/home'));
		    $menu->addChild('Profilo',array());
			    $menu['Profilo']->addChild('Rosa',array('uri'=>'/roster'));
			    $menu['Profilo']->addChild('Formazione',array('uri'=>'/formation'));
		    $menu->addChild('Mercato',array());
				$menu['Mercato']->addChild('Cerca',array('uri'=>'/buy'));
				$menu['Mercato']->addChild('Carrello',array('uri'=>'/checkout'));	
		    $menu->addChild('Voti',array('uri' => '/marks' ));
		    $menu->addChild('Registrati',array('uri' =>'/register'));
		    $menu->addChild('Login',array('uri'=>'/login'));
		    $menu->addChild('Regolamento',array('uri'=>'/rules'));
		    $menu->addChild('Logout',array('uri'=>'/logout'));

		    //Hide the pages if logged in 
		    if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
		    	$menu['Login']->setDisplay(false);
		    	$menu['Registrati']->setDisplay(false);
		    }
		    //Hide the pages if logged out
		    if(!isset($_SESSION['user'])){
		    	$menu['Profilo']->setDisplay(false);
		    	$menu['Mercato']->setDisplay(false);
		    	$menu['Voti']->setDisplay(false);
		    	$menu['Logout']->setDisplay(false);
		    }
		    return $menu;
		}

	}
?>