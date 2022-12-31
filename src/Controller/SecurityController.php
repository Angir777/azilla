<?php

namespace App\Controller;

/* general options */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;

/* mailer */
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/* tabels */
use App\Entity\User;
use App\Entity\Settings;
use App\Entity\UserToken;
use App\Entity\UserPassReset;

/* session */
if(!isset($_SESSION)){session_start();} 
$session = new Session(new PhpBridgeSessionStorage());
$session->start();

class SecurityController extends AbstractController
{
    /*
      FUKCJE STRON I PODSTRON
    */

    /**
     * @Route("/public", name="security_login")
     *
     * @return Response
     */
    public function securityLogin(AuthenticationUtils $authenticationUtils,Request $request):Response{
  		// back if logged in
      	if($this->getUser()){return $this->redirectToRoute('public_stream');}else{}
      	// app security
	    $error_authentication = $authenticationUtils->getLastAuthenticationError();
	    $lastUsername = $authenticationUtils->getLastUsername();
	    // info message
	    $success_activation = $this->get('session')->getFlashBag()->get('success_activation');
	    $error_activation = $this->get('session')->getFlashBag()->get('error_activation');
	    $success_account_created = $this->get('session')->getFlashBag()->get('success_account_created');
	    $error_ban_1 = $this->get('session')->getFlashBag()->get('error_ban_1');
	    // return theme
	    if(!empty($success_activation)){
	        return $this->render('public/login.html.twig', ["last_username" => $lastUsername, "error_authentication" => $error_authentication,"success_activation" => $success_activation[0]]);
      	}elseif(!empty($error_activation)){
	        return $this->render('public/login.html.twig', ["last_username" => $lastUsername, "error_authentication" => $error_authentication,"error_activation" => $error_activation[0]]);
	    }elseif(!empty($success_account_created)){
	        return $this->render('public/login.html.twig', ["last_username" => $lastUsername, "error_authentication" => $error_authentication,"success_account_created" => $success_account_created[0]]);
	    }elseif(!empty($error_ban_1)){
	        return $this->render('public/login.html.twig', ["last_username" => $lastUsername, "error_authentication" => $error_authentication,"error_ban_1" => $error_ban_1[0]]);
	    }else{
	        return $this->render('public/login.html.twig', ["last_username" => $lastUsername, "error_authentication" => $error_authentication,]);
	    }
    }

    /**
     * @Route("/public/register", name="security_register")
     *
     * @return Response
     */
    public function securityRegister(Request $request,\Swift_Mailer $mailer,SessionInterface $session):Response{
  		// back if logged in
      	if($this->getUser()){return $this->redirectToRoute('start');}else{}
      	// run
      	if($request->getMethod() == 'POST'){
	        // entity manager
	        $entityManager = $this->getDoctrine()->getManager();
	        // get inputs
	        $nick = $request->request->get('user_nick');$session->set('r_nick', $nick);
	        $name = $request->request->get('user_name');$session->set('r_name', $name);
	        $surname = $request->request->get('user_surname');$session->set('r_surname', $surname);
	        $sex = $request->request->get('user_sex');$session->set('r_sex', $sex);
	        $email = $request->request->get('user_email');$session->set('r_email', $email);
	        //$phone = $request->request->get('user_phone');$session->set('r_phone', $phone);
	        $phone = "000-000-000";
	        $password = $request->request->get('user_password');
	        $password_reply = $request->request->get('user_password_reply');
	        $age = $request->request->get('age');
	        $regulations = $request->request->get('regulations');
	        // check nick
	        if($nick == ''){
	          // info message
	          $info_text = $nick;
	          $this->addFlash('error_nick_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
	          $checkNick = $entityManager->getRepository(User::class)->findOneBy(['nick' => $nick]);
	          if ($checkNick){
	            // info message
	            $info_text = $nick;
	            $this->addFlash('error_nick_2', $info_text);
	            return $this->redirectToRoute("security_register");
	          }else{
				if(preg_match('/^[A-Za-z\d_]{5,20}$/i', $nick)){}else{
					// info message
		            $info_text = true;
		            $this->addFlash('error_nick_3', $info_text);
		            return $this->redirectToRoute("security_register");
				}
	          }
	        }
	        // check name
	        if($name == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_name_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
				if(preg_match('/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{1,20}$/i', $name)){}else{
					// info message
		            $info_text = true;
		            $this->addFlash('error_name_2', $info_text);
		            return $this->redirectToRoute("security_register");
				}
	        }
	        // check surname
	        if($surname == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_surname_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
				if(preg_match('/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{1,20}$/i', $surname)){}else{
					// info message
		            $info_text = true;
		            $this->addFlash('error_surname_2', $info_text);
		            return $this->redirectToRoute("security_register");
				}
	        }
	        // check sex
	        if($sex == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_sex_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
	          if($sex == 'man'){$sex = 0;}elseif($sex == 'woman'){$sex = 1;}
	        }
	        // check email
	        if($email == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_email_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
	          $check_email = '/^[a-zA-Z0-9.\-_]+@[a-zA-Z0-9\-.]+\.[a-zA-Z]/';
	          if(preg_match($check_email, $email)){
	            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
	            if($user){
	              // info message
	              $info_text = true;
	              $this->addFlash('error_email_3', $info_text);
	              return $this->redirectToRoute("security_register");
	            }
	          }else{
	            // info message
	            $info_text = true;
	            $this->addFlash('error_email_2', $info_text);
	            return $this->redirectToRoute("security_register");
	          }
	        }
	        // check phone
	        if($phone == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_phone_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
	        	if(preg_match('/^[\d]{3}[\-][\d]{3}[\-][\d]{3}$/i', $phone)){}else{
					// info message
		            $info_text = true;
		            $this->addFlash('error_phone_2', $info_text);
		            return $this->redirectToRoute("security_register");
				}
	        }
	        // check password
	        if($password == ''){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_password_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }else{
	          if($password_reply == ''){
	            // info message
	            $info_text = true;
	            $this->addFlash('error_password_2', $info_text);
	            return $this->redirectToRoute("security_register");
	          }else{
	            if($password != $password_reply)
	            {
	              // info message
	              $info_text = true;
	              $this->addFlash('error_password_3', $info_text);
	              return $this->redirectToRoute("security_register");
	            }else{
	              $check_pass = strlen($password);
	              if($check_pass < 8){
	                // info message
	                $info_text = true;
	                $this->addFlash('error_password_4', $info_text);
	                return $this->redirectToRoute("security_register");
	              }
	            }
	          }
	        }
	        // check regulations
	        if($regulations != 'on'){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_regulations_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        }
	        // check age
	        if($age != 'on'){
	          // info message
	          $info_text = true;
	          $this->addFlash('error_age_1', $info_text);
	          return $this->redirectToRoute("security_register");
	        } 
	        // rand unique id
	        $ran_id = rand(time(), 100000000);
	        // create new user profile
	        $saveUser = new User();
	        $saveUser->setNick($nick);
	        $saveUser->setUniqueId($ran_id);
	        $saveUser->setName($name);
	        $saveUser->setSurname($surname);
	        $saveUser->setSex($sex);
	        $saveUser->setEmail($email); 
	        $saveUser->setPhone($phone);
	        $pass = password_hash($password, PASSWORD_BCRYPT);
	        $saveUser->setPassword($pass);
	        $saveUser->setDateJoining(date("Y-m-d"));
	        $saveUser->setActivated(0);
	        $saveUser->setBan("null");
	        $saveUser->setVerification(0);
	        $entityManager->persist($saveUser);
	        $entityManager->flush();
	        // create token
	        $token = $this->hashFunction($email);
	        // create db with token
	        $saveToken = new UserToken();
	        $saveToken->setEmail($email);
	        $saveToken->setToken($token);
	        $saveToken->setData(date("Y-m-d"));
	        $saveToken->setActive(0);
	        $entityManager->persist($saveToken);
	        $entityManager->flush();
	        // send email
	        $message = (new \Swift_Message('Twoje konto zostało utworzone!'))
	            ->setFrom('no-reply@azilla.pl')
	            ->setTo($email)
	            ->setBody(
	                $this->renderView(
	                    'emails/new-account.html.twig',
	                    [
	                      'token' => $token,
	                    ]
	                ),
	                'text/html'
	            )
	        ;
	        $mailer->send($message);
	        // create user settings
	        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
	        if($user){
	        	// get user id
	          	$idUser = $user->getId();
	          	// create new settings
				$saveSettings = new Settings();
				$saveSettings->setIdUser($idUser);
				$saveSettings->setEmailNotifications(0);
				$saveSettings->setDarkTheme(0);
				$saveSettings->setSpoiler(0);
				$saveSettings->setNsfw(0);
				$saveSettings->setNickShow(0);
				$saveSettings->setAvailability(0);
				$saveSettings->setAccountType(0);
				$saveSettings->setAvatar("null");
				$saveSettings->setBackground("null");
				$entityManager->persist($saveSettings);
				$entityManager->flush();
	        }
	        // session destroy
	        $session->clear();
	        // info message
	        $info_text = true;
	        $this->addFlash('success_account_created', $info_text);
	        return $this->redirectToRoute("security_login");
	    }else{
        	// get session inputs
	        $r_nick = $session->get('r_nick');
	        $r_name = $session->get('r_name');
	        $r_surname = $session->get('r_surname');
	        $r_sex = $session->get('r_sex');
	        $r_email = $session->get('r_email');
	        $r_phone = $session->get('r_phone');
	        // info message
	        $error_nick_1 = $this->get('session')->getFlashBag()->get('error_nick_1');
	        $error_nick_2 = $this->get('session')->getFlashBag()->get('error_nick_2');
	        $error_nick_3 = $this->get('session')->getFlashBag()->get('error_nick_3');
	        $error_name_1 = $this->get('session')->getFlashBag()->get('error_name_1');
	        $error_name_2 = $this->get('session')->getFlashBag()->get('error_name_2');
	        $error_surname_1 = $this->get('session')->getFlashBag()->get('error_surname_1');
	        $error_surname_2 = $this->get('session')->getFlashBag()->get('error_surname_2');
	        $error_sex_1 = $this->get('session')->getFlashBag()->get('error_sex_1');
	        $error_email_1 = $this->get('session')->getFlashBag()->get('error_email_1');
	        $error_email_2 = $this->get('session')->getFlashBag()->get('error_email_2');
	        $error_email_3 = $this->get('session')->getFlashBag()->get('error_email_3');
	        $error_phone_1 = $this->get('session')->getFlashBag()->get('error_phone_1');
	        $error_phone_2 = $this->get('session')->getFlashBag()->get('error_phone_2');
	        $error_password_1 = $this->get('session')->getFlashBag()->get('error_password_1');
	        $error_password_2 = $this->get('session')->getFlashBag()->get('error_password_2');
	        $error_password_3 = $this->get('session')->getFlashBag()->get('error_password_3');
	        $error_password_4 = $this->get('session')->getFlashBag()->get('error_password_4');
	        $error_regulations_1 = $this->get('session')->getFlashBag()->get('error_regulations_1');
	        $error_age_1 = $this->get('session')->getFlashBag()->get('error_age_1');
	        // return theme
	        if(!empty($error_nick_1)){
	            return $this->render('public/register.html.twig', ["error_nick_1" => $error_nick_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_nick_2)){
	            return $this->render('public/register.html.twig', ["error_nick_2" => $error_nick_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_nick_3)){
	            return $this->render('public/register.html.twig', ["error_nick_3" => $error_nick_3[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_name_1)){
	            return $this->render('public/register.html.twig', ["error_name_1" => $error_name_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_name_2)){
	            return $this->render('public/register.html.twig', ["error_name_2" => $error_name_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_surname_1)){
	            return $this->render('public/register.html.twig', ["error_surname_1" => $error_surname_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_surname_2)){
	            return $this->render('public/register.html.twig', ["error_surname_2" => $error_surname_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_sex_1)){
	            return $this->render('public/register.html.twig', ["error_sex_1" => $error_sex_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_email_1)){
	            return $this->render('public/register.html.twig', ["error_email_1" => $error_email_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_email_2)){
	            return $this->render('public/register.html.twig', ["error_email_2" => $error_email_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_email_3)){
	            return $this->render('public/register.html.twig', ["error_email_3" => $error_email_3[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_phone_1)){
	            return $this->render('public/register.html.twig', ["error_phone_1" => $error_phone_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_phone_2)){
	            return $this->render('public/register.html.twig', ["error_phone_2" => $error_phone_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_password_1)){
	            return $this->render('public/register.html.twig', ["error_password_1" => $error_password_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_password_2)){
	            return $this->render('public/register.html.twig', ["error_password_2" => $error_password_2[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_password_3)){
	            return $this->render('public/register.html.twig', ["error_password_3" => $error_password_3[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_password_4)){
	            return $this->render('public/register.html.twig', ["error_password_4" => $error_password_4[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_regulations_1)){
	            return $this->render('public/register.html.twig', ["error_regulations_1" => $error_regulations_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }elseif(!empty($error_age_1)){
	            return $this->render('public/register.html.twig', ["error_age_1" => $error_age_1[0],"r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }else{
	          return $this->render('public/register.html.twig', ["r_nick" => $r_nick,"r_name" => $r_name,"r_surname" => $r_surname,"r_sex" => $r_sex,"r_email" => $r_email,"r_phone" => $r_phone]);
	        }
	    }
	}

    /**
     * @Route("/public/regulations", name="security_regulations")
     *
     * @return Response
     */
    public function securityRegulations(){if($this->getUser()){return $this->redirectToRoute('start');}else{return $this->render('public/regulations.html.twig', []);}}

    /**
     * @Route("/public/privacy", name="security_privacy")
     *
     * @return Response
     */
    public function securityPrivacy(){if($this->getUser()){return $this->redirectToRoute('start');}else{return $this->render('public/privacy.html.twig', []);}}

    /**
     * @Route("/public/about-us", name="security_about")
     *
     * @return Response
     */
    public function securityAbout(){if($this->getUser()){return $this->redirectToRoute('start');}else{return $this->render('public/about_us.html.twig', []);}}

    /**
     * @Route("/logout", name="security_logout")
     *
     * @return Response
     */
    public function securityLogout(){}

    /**
     * @Route("/public/password-reset", name="security_password_reset")
     *
     * @return Response
     */
    public function securityPasswordReset(Request $request,\Swift_Mailer $mailer){
  		// back if logged in
  		if($this->getUser()){return $this->redirectToRoute('start');}else{}
      	// entity manager
      	$entityManager = $this->getDoctrine()->getManager();
      	// run
      	if($request->getMethod() == 'POST'){
        	// get email and basic check
        	$email = $this->checkInput($request->request->get('new_email'));
	        // check email
	        if($email == ''){
	      		// info message
	      		$info_text = true;
	      		$this->addFlash('error_email_1', $info_text);
	      		return $this->redirectToRoute("security_password_reset");
	        }else{
	          $check_email = '/^[a-zA-Z0-9.\-_]+@[a-zA-Z0-9\-.]+\.[a-zA-Z]/';
	          if(preg_match($check_email, $email)){
	            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
	            if($user){
	              	// get id user
	        		$userId = $user->getId();
					// check when the password was changed
					$passData = $entityManager->getRepository(UserPassReset::class)->findOneBy(['email' =>  $email]);
					if($passData){
						$last_modification_date = $passData->getData();
						$actual_date = time();

						$hour_of_break = $last_modification_date + 3600; // +1h

						if($actual_date > $hour_of_break){
					  		$entityManager->remove($passData);
					  		$entityManager->flush();
						}else{
					  		// info message
					  		$info_text = true;
					  		$this->addFlash('error_email_4', $info_text);
					  		return $this->redirectToRoute("security_password_reset");
						}
					}else{
						$saveInfo = new UserPassReset();
						$saveInfo->setEmail($email);
						$saveInfo->setData(time());
						$entityManager->persist($saveInfo);
						$entityManager->flush();
					}

					// create new password
					$new_password = $this->randomPassword();
					$pass = password_hash($new_password, PASSWORD_BCRYPT);
					$user->setPassword($pass);
					$entityManager->persist($user);
					$entityManager->flush();
					// send email with new password
					$message = (new \Swift_Message('Oto Twoje nowe hasło!'))
				  		->setFrom('no-reply@azilla.pl')
					  	->setTo($email)
					  	->setBody(
						    $this->renderView(
					      		'emails/password_reset.html.twig',
						      	[
						        	'new_password' => $new_password,
						      	]
					    	),
						    'text/html'
						)
					;
					$mailer->send($message);
					// return theme
					// info message
					$info_text = true;
					$this->addFlash('success_email_reset', $info_text);
					return $this->redirectToRoute("security_password_reset");
	            }else{
	            	// info message
			  		$info_text = true;
			  		$this->addFlash('error_email_3', $info_text);
			  		return $this->redirectToRoute("security_password_reset");
	            }
	          }else{
	            // info message
	            $info_text = true;
	            $this->addFlash('error_email_2', $info_text);
	            return $this->redirectToRoute("security_password_reset");
	          }
	        }
      	}else{
      		// info message
	        $error_email_1 = $this->get('session')->getFlashBag()->get('error_email_1');
	        $error_email_2 = $this->get('session')->getFlashBag()->get('error_email_2');
	        $error_email_3 = $this->get('session')->getFlashBag()->get('error_email_3');
	        $error_email_4 = $this->get('session')->getFlashBag()->get('error_email_4');
	        $success_email_reset = $this->get('session')->getFlashBag()->get('success_email_reset');
	        // return theme
	        if(!empty($error_email_1)){
	            return $this->render('public/password_reset.html.twig', ["error_email_1" => $error_email_1[0]]);
	        }elseif(!empty($error_email_2)){
	            return $this->render('public/password_reset.html.twig', ["error_email_2" => $error_email_2[0]]);
	        }elseif (!empty($error_email_3)){
	            return $this->render('public/password_reset.html.twig', ["error_email_3" => $error_email_3[0]]);
	        }elseif (!empty($error_email_4)){
	            return $this->render('public/password_reset.html.twig', ["error_email_4" => $error_email_4[0]]);
	        }elseif (!empty($success_email_reset)){
	            return $this->render('public/password_reset.html.twig', ["success_email_reset" => $success_email_reset[0]]);
	        }else{
          		return $this->render('public/password_reset.html.twig', []);
	        }
      	}
    }

    /*
      FUKCJE WEWNĘTRZNE
    */

    /**
     * @Route("/public/activation", name="security_activation_page")
     *
     * @return Response
     */
    public function securityActivationPage(){
    	return $this->redirectToRoute("security_login");
    }

    /**
     * @Route("/public/activation/{token}", name="security_activation")
     *
     * @param $token
     *
     * @return Response
     */
    public function securityActivation($token){
      	// back if logged in
      	if($this->getUser()){
      		$this->get('security.token_storage')->setToken(null);
        	$this->get('session')->invalidate();
      	}
      	// entity manager
      	$entityManager = $this->getDoctrine()->getManager();
      	// get mail from token
      	$token = $entityManager->getRepository(UserToken::class)->findOneBy(array('token' => $token,'active' => 0));
      	if($token){
        	// token deactivation
	        $token->setActive(1);
	        $entityManager->persist($token);
	        $entityManager->flush();
	        // get email user
	        $emailUser = $token->getEmail();
	        // get user db
	        $user = $entityManager->getRepository(User::class)->findOneBy(array('email' => $emailUser,'activated' => 0));
	        if($user){
	          	// activation a account
	          	$user->setActivated(1);
	          	$entityManager->persist($user);
	          	$entityManager->flush();
          		// info message
	          	$info_text = true;
	          	$this->addFlash('success_activation', $info_text);
	          	return $this->redirectToRoute("security_login");
        	}else{
	          	// info message
	          	$info_text = true;
	          	$this->addFlash('error_activation', $info_text);
	          	return $this->redirectToRoute("security_login");
	        }
      	}else{
        	// info message
        	$info_text = true;
        	$this->addFlash('error_activation', $info_text);
        	return $this->redirectToRoute("security_login");
      	}
    }

    private function randomPassword(){
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for($i = 0; $i < 8; $i++){$n = rand(0, $alphaLength);$pass[] = $alphabet[$n];}
        return implode($pass);
    }

    private function base64Encode($input){return strtr(base64_encode($input), '+/=', '._-');}

    private function base64Decode($input){return base64_decode(strtr($input, '._-', '+/='));}

    private function hashFunction($input){$token = password_hash($input, PASSWORD_BCRYPT);return strtr($token, '.=/', '---');}

    private function checkInput($input){$input = trim($input);$input = stripslashes($input);$input = htmlspecialchars($input);return $input;}
}