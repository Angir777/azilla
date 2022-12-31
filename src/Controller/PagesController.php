<?php

namespace App\Controller;

/* mercure */
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

/* general options */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;

/* mailer */
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/* paginator */
use Knp\Component\Pager\PaginatorInterface;

/* tabels */
use App\Entity\User;
use App\Entity\Posts;
use App\Entity\Settings;
use App\Entity\HashtagSwitch;
use App\Entity\RatingPostSwitch;
use App\Entity\RatingCommentSwitch;
use App\Entity\PostsComments;
use App\Entity\WatchingUsersSwitch;
use App\Entity\BlockedUsersSwitch;
use App\Entity\Groups;
use App\Entity\GroupsSwitch;
use App\Entity\GroupUserWatching;
use App\Entity\ReportUser;
use App\Entity\GroupDeletePostNotification;
use App\Entity\Conversations;
use App\Entity\Messages;
use App\Entity\UserToken;

/* session */
if(!isset($_SESSION)){session_start();} 
$session = new Session(new PhpBridgeSessionStorage());
$session->start();

class PagesController extends AbstractController
{
    /**
     * @Route("/", name="public_stream")
     */
    public function publicStream(Request $request,Security $security,PaginatorInterface $paginator,SessionInterface $session)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
    	
    	// entity manager
		$entityManager = $this->getDoctrine()->getManager();

        // chech if user has ban
        $userBan = $entityManager->getRepository(User::class)->findOneBy(array('email' => $userEmail));
        if($userBan){
            $ban = $userBan->getBan();
            if($ban != "null"){
                // set timezone
                date_default_timezone_set('Europe/Warsaw');
                // check date
                if($ban == 1){
                    return $this->loginBan($ban);
                }else{
                    $data = $ban;
                    $obecna_data = date("Y-m-d");
                    $dni = (strtotime($data) - strtotime($obecna_data)) / (60*60*24);
                    if($dni<=0){
                        $userBan->setBan("null");
                        $entityManager->persist($userBan);
                        $entityManager->flush(); 
                    }else{ 
                        return $this->loginBan($ban);
                    }
                }
            }
        }

        // INFO
        /*
        echo "<pre>";
        echo print_r($_SESSION);
        echo print_r($_POST);
        echo print_r($_FILES);
        echo "</pre>";
        */

        // deletion accounts
        /*
        $users = $entityManager->getRepository(User::class)->findAll();
        foreach($users as $single){
            $current_date = date("Y-m-d");
            $deletion_date = $single->getDateDeletion();
            if($current_date == $deletion_date){
                $entityManager->remove($single);
                $entityManager->flush();
            }
        }
        */

        // check if account activation
        $account_activation = $entityManager->getRepository(User::class)->findOneBy(array('email' => $userEmail,'activated' => 0));
        if($account_activation){
            $session = $request->getSession();
            if($session->has('close_account_activation')){$session->set('close_account_activation', 0);}else{$session->set('close_account_activation', 1);}
        }else{$session->set('close_account_activation', 0);}
        $close_account_activation = $session->get('close_account_activation');

        // get posts
        $postsList = $this->getPostsList('public', 0);
        // paginate the results of the query
        $showList = $paginator->paginate(
            // doctrine Query, not results
            $postsList,
            // define the page parameter
            $request->query->getInt('p', 1),
            // items per page
            10
        );

        // get user settings
        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        if (!$userSettings){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get tags list
        $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findAll();
        // get groups list
        $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findAll();
        // get rating list
        $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findAll();
        // get count comments to post
        $commentsList = $entityManager->getRepository(PostsComments::class)->findAll();
        // get user nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // if active user blocked user profile
        $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $userId));

        // get user join groups
        $groupUserWatching = $entityManager->getRepository(GroupUserWatching::class)->findBy(array('idUser' => $userId));
        $groupsAll = $entityManager->getRepository(Groups::class)->findAll();

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // info alert
        $infoErrorSearch = $this->get('session')->getFlashBag()->get('info_error_search');

        if(!empty($infoErrorSearch)){
            $infoErrorSearch = $infoErrorSearch[0];
            // return theme
            return $this->render('private/public_stream.html.twig', [
                "groupUserWatching" => $groupUserWatching,
                "groupsAll" => $groupsAll,
                "info_error_search" => $infoErrorSearch,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "close_account_activation" => $close_account_activation,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }else{
            // return theme
            return $this->render('private/public_stream.html.twig', [
                "groupUserWatching" => $groupUserWatching,
                "groupsAll" => $groupsAll,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "close_account_activation" => $close_account_activation,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }
    }

    /**
     * @Route("/a/{post_id}-{post_title}", name="single_post", requirements={"post_id"="\d+"})
     *
     * @param $post_id
     * @param $post_title
     *
     * @return Response
     */
    public function singlePost(Request $request, Security $security, $post_id, $post_title)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // POST
        if($request->getMethod() == 'POST'){
            
            // check span
            if(!isset($_COOKIE['spamstopcomment'])){
                setcookie ("spamstopcomment", time()+30, time()+30);
            }else{
                $info_text = true;
                $this->addFlash('info_spamstop', $info_text);
                return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
            }

            // set timezone
            date_default_timezone_set('Europe/Warsaw');
            // if user add new comment to post
            $sendComment = $request->request->get('sendComment');
            // if user add new sub comment to post
            $sendSubComment = $request->request->get('sendSubComment');
            $sendSubSubComment = $request->request->get('sendSubSubComment');
            if(isset($sendComment)){

                // get content
                $formMessage = $this->test_input($request->request->get('formMessage'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($formMessage)){
                    $info_text = true;
                    $this->addFlash('info_error_long_text', $info_text);
                    return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                }else{
                    // sprawdzenie maksymalnej długości dla wiadomości
                    $words = explode(" ", $formMessage);
                    if(count($words) > 280){
                        $info_text = true;
                        $this->addFlash('info_error_long_text', $info_text);
                        return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                    }
                }

                // zapis informacji o poście do bazy danych
                $saveComment = new PostsComments();
                $saveComment->setIdPost($post_id);
                $saveComment->setIdAuthor($userId);
                $saveComment->setDate(date("Y-m-d H:i:s"));
                $saveComment->setIdParent("NULL");
                $saveComment->setIdParent2("NULL");
                $saveComment->setContent($formMessage);
                $saveComment->setStatusNotification1(0);
                $saveComment->setStatusNotification2(0);
                $entityManager->persist($saveComment);
                $entityManager->flush();
            }elseif(isset($sendSubComment)){
                // get explode
                $var = explode("/", $sendSubComment);
                $idParent = $var[0];
                $idParent2 = $var[1];

                // get content
                $formMessage = $this->test_input($request->request->get('formMessage'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($formMessage)){
                    $info_text = true;
                    $this->addFlash('info_error_long_text', $info_text);
                    return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                }else{
                    // sprawdzenie maksymalnej długości dla wiadomości
                    $words = explode(" ", $formMessage);
                    if(count($words) > 280){
                        $info_text = true;
                        $this->addFlash('info_error_long_text', $info_text);
                        return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                    }
                }

                // zapis informacji o poście do bazy danych
                $saveComment = new PostsComments();
                $saveComment->setIdPost($post_id);
                $saveComment->setIdAuthor($userId);
                $saveComment->setDate(date("Y-m-d H:i:s"));
                $saveComment->setIdParent($idParent);
                $saveComment->setIdParent2($idParent2);
                $saveComment->setContent($formMessage);
                $saveComment->setStatusNotification1(0);
                $saveComment->setStatusNotification2(0);
                $entityManager->persist($saveComment);
                $entityManager->flush();
            }elseif(isset($sendSubSubComment)){
                // get explode
                $var = explode("/", $sendSubSubComment);
                $idParent = $var[0];
                $idParent2 = $var[1];
                
                // get content
                $formMessage = $this->test_input($request->request->get('formMessage'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($formMessage)){
                    $info_text = true;
                    $this->addFlash('info_error_long_text', $info_text);
                    return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                }else{
                    // sprawdzenie maksymalnej długości dla wiadomości
                    $words = explode(" ", $formMessage);
                    if(count($words) > 280){
                        $info_text = true;
                        $this->addFlash('info_error_long_text', $info_text);
                        return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
                    }
                }

                // zapis informacji o poście do bazy danych
                $saveComment = new PostsComments();
                $saveComment->setIdPost($post_id);
                $saveComment->setIdAuthor($userId);
                $saveComment->setDate(date("Y-m-d H:i:s"));
                $saveComment->setIdParent($idParent);
                $saveComment->setIdParent2($idParent2);
                $saveComment->setContent($formMessage);
                $saveComment->setStatusNotification1(0);
                $saveComment->setStatusNotification2(0);
                $entityManager->persist($saveComment);
                $entityManager->flush();
            }
            // info message
            $info_text = true;
            $this->addFlash('info_success', $info_text);
            return $this->redirectToRoute('single_post', array('post_id' => $post_id, 'post_title' => $post_title));
        }

        // single post
        $singlePost = $entityManager->getRepository(Posts::class)->findOneBy(['id' => $post_id]);
        if(!$singlePost){
            // info message
            $info_text = 404;
            $this->addFlash('info_error', $info_text);
            return $this->redirectToRoute('error_page');
        }else{

            // get user settings
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
            if (!$userSettings){return $this->loginReset();}

            // get users settings
            $usersSettings = $entityManager->getRepository(Settings::class)->findAll();

            // get users list
            $usersList = $entityManager->getRepository(User::class)->findAll();
            if (!$usersList){return $this->loginReset();}

            // get tags list
            $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findBy(['idPost' => $post_id]);

            // get groups list
            $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findBy(['idPost' => $post_id]);

            // get rating list
            $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findBy(['idPost' => $post_id]);

            // get comments
            $commentsList = $entityManager->getRepository(PostsComments::class)->findBy(array('idPost' => $post_id));
            $commentsListCount = $entityManager->getRepository(PostsComments::class)->count(array('idPost' => $post_id));

            // get number all voted
            $ratingCommentList = $entityManager->getRepository(RatingCommentSwitch::class)->findAll();

            // get user nick
            $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $userId));
            if (!$user){return $this->loginReset();}else{
                $userNick = $user->getNick();
                $globalUserName = $user->getNick();
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getId()));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $user->getName();
                    $userSurname = $user->getSurname();
                    $globalUserName = $userName . ' ' . $userSurname;
                }
            }

            // get users settings
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $userId));

            // if active user blocked user profile
            $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserB' => $this->getUser()->getId()));
            $blockedUsersSwitchList2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $this->getUser()->getId()));

            // get conversations
            $repo = $entityManager->getRepository(Conversations::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.idUserA = :key1')->setParameter('key1', $userId)
                ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
                ->orderBy('q.position', 'DESC')
                ->getQuery();
            $globalConversations = $querry->getResult();
            // get messages
            $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

            // info alert
            $infoSucces = $this->get('session')->getFlashBag()->get('info_success');
            $infoErrorLongText = $this->get('session')->getFlashBag()->get('info_error_long_text');
            $info_spamstop = $this->get('session')->getFlashBag()->get('info_spamstop');

            if(!empty($infoSucces)){
                $info = $infoSucces[0];
                // return theme
                return $this->render('private/single_post.html.twig', [
                    "info_success" => $info,
                    "singlePost" => $singlePost,
                    "userSettings" => $userSettings,
                    "usersSettings" => $usersSettings,
                    "usersList" => $usersList,
                    "tagsList" => $tagsList,
                    "groupsList" => $groupsList,
                    "ratingList" => $ratingList,
                    "commentsList" => $commentsList,
                    "commentsListCount" => $commentsListCount,
                    "ratingCommentList" => $ratingCommentList,
                    "activeUsersSettings" => $activeUsersSettings,
                    "blockedUsersSwitchList" => $blockedUsersSwitchList,
                    "blockedUsersSwitchList2" => $blockedUsersSwitchList2,
                    "userId" => $userId,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif(!empty($infoErrorLongText)){
                $info = $infoErrorLongText[0];
                // return theme
                return $this->render('private/single_post.html.twig', [
                    "info_error_long_text" => $info,
                    "singlePost" => $singlePost,
                    "userSettings" => $userSettings,
                    "usersSettings" => $usersSettings,
                    "usersList" => $usersList,
                    "tagsList" => $tagsList,
                    "groupsList" => $groupsList,
                    "ratingList" => $ratingList,
                    "commentsList" => $commentsList,
                    "commentsListCount" => $commentsListCount,
                    "ratingCommentList" => $ratingCommentList,
                    "activeUsersSettings" => $activeUsersSettings,
                    "blockedUsersSwitchList" => $blockedUsersSwitchList,
                    "blockedUsersSwitchList2" => $blockedUsersSwitchList2,
                    "userId" => $userId,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif(!empty($info_spamstop)){
                $info = $info_spamstop[0];
                // return theme
                return $this->render('private/single_post.html.twig', [
                    "info_spamstop" => $info,
                    "singlePost" => $singlePost,
                    "userSettings" => $userSettings,
                    "usersSettings" => $usersSettings,
                    "usersList" => $usersList,
                    "tagsList" => $tagsList,
                    "groupsList" => $groupsList,
                    "ratingList" => $ratingList,
                    "commentsList" => $commentsList,
                    "commentsListCount" => $commentsListCount,
                    "ratingCommentList" => $ratingCommentList,
                    "activeUsersSettings" => $activeUsersSettings,
                    "blockedUsersSwitchList" => $blockedUsersSwitchList,
                    "blockedUsersSwitchList2" => $blockedUsersSwitchList2,
                    "userId" => $userId,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }else{
                // return theme
                return $this->render('private/single_post.html.twig', [
                    "singlePost" => $singlePost,
                    "userSettings" => $userSettings,
                    "usersSettings" => $usersSettings,
                    "usersList" => $usersList,
                    "tagsList" => $tagsList,
                    "groupsList" => $groupsList,
                    "ratingList" => $ratingList,
                    "commentsList" => $commentsList,
                    "commentsListCount" => $commentsListCount,
                    "ratingCommentList" => $ratingCommentList,
                    "activeUsersSettings" => $activeUsersSettings,
                    "blockedUsersSwitchList" => $blockedUsersSwitchList,
                    "blockedUsersSwitchList2" => $blockedUsersSwitchList2,
                    "userId" => $userId,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }
        }
    }

    /**
     * @Route("/watched", name="watched_stream")
     */
    public function watchedStream(Request $request,Security $security,PaginatorInterface $paginator,SessionInterface $session)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // check if account activation
        $account_activation = $entityManager->getRepository(User::class)->findOneBy(array('email' => $userEmail,'activated' => 0));
        if($account_activation){
            $session = $request->getSession();
            if($session->has('close_account_activation')){$session->set('close_account_activation', 0);}else{$session->set('close_account_activation', 1);}
        }else{$session->set('close_account_activation', 0);}
        $close_account_activation = $session->get('close_account_activation');

        // get posts
        $postsList = $this->getPostsList('watched', 0);
        // paginate the results of the query
        $showList = $paginator->paginate(
            // doctrine Query, not results
            $postsList,
            // define the page parameter
            $request->query->getInt('p', 1),
            // items per page
            10
        );

        // get user settings
        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        if (!$userSettings){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get tags list
        $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findAll();
        // get groups list
        $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findAll();
        // get rating list
        $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findAll();
        // get count comments to post
        $commentsList = $entityManager->getRepository(PostsComments::class)->findAll();
        // get user nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // if active user blocked user profile
        $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $userId));

        // if active user follow user profile
        $watchingUsersSwitchList = $entityManager->getRepository(WatchingUsersSwitch::class)->findBy(array('idUserA' => $userId));

        // get groups
        $getGroups = $entityManager->getRepository(GroupsSwitch::class)->findAll();

        // check if loggin user jpoin in group
        $getGroup = $entityManager->getRepository(GroupUserWatching::class)->findBy(array('idUser' => $userId));

        // get user join groups
        $groupUserWatching = $entityManager->getRepository(GroupUserWatching::class)->findBy(array('idUser' => $userId));
        $groupsAll = $entityManager->getRepository(Groups::class)->findAll();

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // info alert
        $infoErrorSearch = $this->get('session')->getFlashBag()->get('info_error_search');

        if(!empty($infoErrorSearch)){
            $infoErrorSearch = $infoErrorSearch[0];
            // return theme
            return $this->render('private/watched_stream.html.twig', [
                "groupUserWatching" => $groupUserWatching,
                "groupsAll" => $groupsAll,
                "getGroups" => $getGroups,
                "getGroup" => $getGroup,
                "info_error_search" => $infoErrorSearch,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "close_account_activation" => $close_account_activation,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "watchingUsersSwitchList" => $watchingUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }else{
            // return theme
            return $this->render('private/watched_stream.html.twig', [
                "groupUserWatching" => $groupUserWatching,
                "groupsAll" => $groupsAll,
                "getGroups" => $getGroups,
                "getGroup" => $getGroup,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "close_account_activation" => $close_account_activation,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "watchingUsersSwitchList" => $watchingUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }
    }

    /**
     * @Route("/u/{user_nick}", name="user_stream")
     *
     * @param $user_nick
     *
     * @return Response
     */
    public function userStream(Request $request, $user_nick, Security $security, PaginatorInterface $paginator)
    {  
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get global user logged nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $userId));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getId()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }

        // get user setting to profile
        $user = $entityManager->getRepository(User::class)->findOneBy(array('nick' =>  $user_nick));
        if (!$user){return $this->loginReset();}else{
            $idUser = $user->getId();
            
            // get user settings
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idUser));
            if (!$userSettings){return $this->loginReset();}
            // get users settings
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
            // get user posts
            $userPostsCount = $entityManager->getRepository(Posts::class)->count(array('idAuthor' => $idUser));
                $userPostsList = $entityManager->getRepository(Posts::class)->findAll();
            // get user comments
            $userCommentsCount = $entityManager->getRepository(PostsComments::class)->count(array('idAuthor' => $idUser));
                $userCommentsList = $entityManager->getRepository(PostsComments::class)->findBy(array('idAuthor' => $idUser),array('id' => 'DESC'));
            // get users watched
            $userWatched = $entityManager->getRepository(WatchingUsersSwitch::class)->count(array('idUserB' => $idUser));
                $userWatchedList = $entityManager->getRepository(WatchingUsersSwitch::class)->findBy(array('idUserB' => $idUser));
            // get users the follower
            $userFollower = $entityManager->getRepository(WatchingUsersSwitch::class)->count(array('idUserA' => $idUser));
                $userFollowerList = $entityManager->getRepository(WatchingUsersSwitch::class)->findBy(array('idUserA' => $idUser));

            // if active user watched user profile
            $watchingUsersSwitch = $entityManager->getRepository(WatchingUsersSwitch::class)->count(array('idUserA' => $this->getUser()->getId(), 'idUserB' => $idUser));
                $watchingUsersSwitchList = $entityManager->getRepository(WatchingUsersSwitch::class)->findBy(array('idUserA' => $this->getUser()->getId()));
            // if active user blocked user profile
            $blockedUsersSwitch = $entityManager->getRepository(BlockedUsersSwitch::class)->count(array('idUserA' => $this->getUser()->getId(), 'idUserB' => $idUser));
                $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $this->getUser()->getId()));

            // get users list
            $usersList = $entityManager->getRepository(User::class)->findAll();
            if (!$usersList){return $this->loginReset();}
            // get users settings
            $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            // get tags list
            $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findAll();
            // get groups list
            $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findAll();
            // get rating list
            $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findAll();
            // get count comments to post
            $commentsList = $entityManager->getRepository(PostsComments::class)->findAll();

            // get user join groups
            $groupUserWatching = $entityManager->getRepository(GroupUserWatching::class)->findBy(array('idUser' => $idUser));
            $groupsAll = $entityManager->getRepository(Groups::class)->findAll();

            // get conversations
            $repo = $entityManager->getRepository(Conversations::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.idUserA = :key1')->setParameter('key1', $userId)
                ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
                ->orderBy('q.position', 'DESC')
                ->getQuery();
            $globalConversations = $querry->getResult();
            // get messages
            $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

            // get user posts
            $postsList = $this->getPostsList('profil', $idUser);
            // paginate the results of the query
            $showList = $paginator->paginate(
                // doctrine Query, not results
                $postsList,
                // define the page parameter
                $request->query->getInt('p', 1),
                // items per page
                10
            );
        }

        // return theme
        return $this->render('private/user_stream.html.twig', [
            "accountuser" => $user,
            "userSettings" => $userSettings,
            "activeUsersSettings" => $activeUsersSettings,
            "userPostsCount" => $userPostsCount,
                "userPostsList" => $userPostsList,
            "userCommentsCount" => $userCommentsCount,
                "userCommentsList" => $userCommentsList,
            "userWatched" => $userWatched,
                "userWatchedList" => $userWatchedList,
            "userFollower" =>  $userFollower,
                "userFollowerList" => $userFollowerList,
            "watchingUsersSwitch" => $watchingUsersSwitch,
                "watchingUsersSwitchList" => $watchingUsersSwitchList,
            "blockedUsersSwitch" => $blockedUsersSwitch,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
            "showList" => $showList,
            "userNick" => $userNick,
            "globalUserNick" => $userNick,
            "globalUserName" => $globalUserName,
            "globalIdUser" => $userId,
            "usersList" => $usersList,
            "usersSettings" => $usersSettings,
            "tagsList" => $tagsList,
            "groupsList" => $groupsList,
            "ratingList" => $ratingList,
            "commentsList" => $commentsList,
            "groupUserWatching" => $groupUserWatching,
            "groupsAll" => $groupsAll,
            "globalConversations" => $globalConversations,
            "globalMessages" => $globalMessages,
        ]);
    }

    /**
     * @Route("/messages", name="messages")
     */
    public function messages(Request $request,Security $security)
    {  
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
        // get user info
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        if (!$user){return $this->loginReset();}else{
            $userPhone = $user->getPhone();
            $userNick = $user->getNick();
            $userName = $user->getName();
            $userSurname = $user->getSurname();
            $userSex = $user->getSex();

            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getId()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // info alert
        $infoErrorSearch = $this->get('session')->getFlashBag()->get('info_error_search');
        $infoErrorConversation = $this->get('session')->getFlashBag()->get('info_error_conversation');
        $infoErrorBlocked = $this->get('session')->getFlashBag()->get('info_error_blocked');

        if(!empty($infoErrorSearch)){
            $infoErrorSearch = $infoErrorSearch[0];
            // return theme
            return $this->render('private/messages.html.twig', [
                "usersList" => $usersList,
                "info_error_search" => $infoErrorSearch,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }elseif(!empty($infoErrorConversation)){
            $infoErrorConversation = $infoErrorConversation[0];
            // return theme
            return $this->render('private/messages.html.twig', [
                "usersList" => $usersList,
                "info_error_conversation" => $infoErrorConversation,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }elseif(!empty($infoErrorBlocked)){
            $infoErrorBlocked = $infoErrorBlocked[0];
            // return theme
            return $this->render('private/messages.html.twig', [
                "usersList" => $usersList,
                "info_error_blocked" => $infoErrorBlocked,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }else{
            // return theme
            return $this->render('private/messages.html.twig', [
                "usersList" => $usersList,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }
    }

    /**
     * @Route("/messages/{conversation}", name="messages_conversation")
     *
     * @param $conversation
     *
     * @return Response
     */
    public function messagesConversation(Request $request,Security $security,$conversation)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
        // get user info
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        if (!$user){return $this->loginReset();}else{
            $userPhone = $user->getPhone();
            $userNick = $user->getNick();
            $userName = $user->getName();
            $userSurname = $user->getSurname();
            $userSex = $user->getSex();

            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getId()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }

        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
        // get messages
        $messages = $entityManager->getRepository(Messages::class)->findBy(array('conversation' => $conversation),array('id' => 'DESC'));
        // get users id
        $conversations = $entityManager->getRepository(Conversations::class)->findOneBy(array('conversation' => $conversation));
        $cUserA = $conversations->getIdUserA();
        $cUserB = $conversations->getIdUserB();
        $userNameMessage = '';
        if($userId == $cUserA){
            $userIdForMessage = $cUserB;
        }else{
            $userIdForMessage = $cUserA;
        }

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        if(!$globalConversations){
            return $this->redirectToRoute("messages");
        }

        // check if user blocked / is blocked
        $blocked = 0;
        $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $cUserA,'idUserB' => $cUserB));
        if($userBlocked){
            $blocked = 1;
        }else{
            $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $cUserB,'idUserB' => $cUserA));
            if($userBlocked2){
                $blocked = 1;
            }else{
                $blocked = 0;
            }
        }
        if($blocked == 1){
            // info message
            $info_text = true;
            $this->addFlash('info_error_blocked', $info_text);
            return $this->redirectToRoute("messages");
        }

        // reset status notification
        $repo = $entityManager->getRepository(Messages::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUser != :key1')->setParameter('key1', $userId)
            ->andWhere('q.conversation = :key2')->setParameter('key2', $conversation)
            ->andWhere('q.statusNotification = :key3')->setParameter('key3', 0)
            ->orderBy('q.id', 'ASC')
            ->getQuery();
        $querry = $querry->getResult();
        if($querry){
            foreach($querry as &$value){
                $value->setStatusNotification(1);
                $entityManager->persist($value);
                $entityManager->flush();
            }
        }

        // info alert
        $infoErrorSearch = $this->get('session')->getFlashBag()->get('info_error_search');
        $infoErrorConversation = $this->get('session')->getFlashBag()->get('info_error_conversation');
        $infoErrorMessageOne = $this->get('session')->getFlashBag()->get('error_message_1');

        if(!empty($infoErrorSearch)){
            $infoErrorSearch = $infoErrorSearch[0];
            // return theme
            return $this->render('private/conversation.html.twig', [
                "info_error_search" => $infoErrorSearch,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "messages" => $messages,
                "usersList" => $usersList,
                "userIdForMessage" => $userIdForMessage,
                "conversation" => $conversation,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }elseif(!empty($infoErrorConversation)){
            $infoErrorConversation = $infoErrorConversation[0];
            // return theme
            return $this->render('private/conversation.html.twig', [
                "info_error_conversation" => $infoErrorConversation,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "messages" => $messages,
                "usersList" => $usersList,
                "userIdForMessage" => $userIdForMessage,
                "conversation" => $conversation,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }elseif(!empty($infoErrorMessageOne)){
            $infoErrorMessageOne = $infoErrorMessageOne[0];
            // return theme
            return $this->render('private/conversation.html.twig', [
                "error_message_1" => $infoErrorMessageOne,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "messages" => $messages,
                "usersList" => $usersList,
                "userIdForMessage" => $userIdForMessage,
                "conversation" => $conversation,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);  
        }else{
            // return theme
            return $this->render('private/conversation.html.twig', [
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "messages" => $messages,
                "usersList" => $usersList,
                "userIdForMessage" => $userIdForMessage,
                "conversation" => $conversation,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }
    }

    /**
     * @Route("/system/messages", name="system_messages")
     *
     * @return Response
     */
    public function systemMessages(Request $request,Security $security)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        $errors = [];
        $data = [];

        $conversation = $this->test_input($request->request->get('conversation'));
        $message = $request->request->get('message');

        if (empty($conversation)) {
            $errors['conversation'] = 'Wystapił nieoczekiwany błąd podczas wysyłania wiadomości.';
        }

        if (empty($message)) {
            $errors['message'] = 'Nie możesz dodać pustej wiadomości.';
        }

        if (!empty($errors)) {
            $data['success'] = false;
            $data['errors'] = $errors;
        } else {
            $data['success'] = true;
            $data['message'] = $message;
        }

        if($data['success'] == true){
            // check name
            if($message == ''){
              // info message
              $info_text = true;
              $this->addFlash('error_message_1', $info_text);
              return $this->redirectToRoute('messages_conversation', array('conversation' => $conversation));
            }

            // set timezone
            date_default_timezone_set('Europe/Warsaw');

            // create new message
            $saveMessage = new Messages();
            $saveMessage->setConversation($conversation);
            $saveMessage->setIdUser($userId);
            $saveMessage->setMsg($message);
            $saveMessage->setDate(date("Y-m-d H:i:s"));
            $saveMessage->setStatusNotification(0);
            $entityManager->persist($saveMessage);
            $entityManager->flush();

            // update conversations
            $conversations = $entityManager->getRepository(Conversations::class)->findOneBy(['conversation' => $conversation]);
            if($conversations){
                $status = $conversations->getStatus();
                if($status!=1){
                    $conversations->setStatus(1);
                }
                $conversations->setPosition(time());
                $entityManager->persist($conversations);
                $entityManager->flush();
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/system/messages/{conversation}", name="load_messages")
     *
     * @param $conversation
     *
     * @return Response
     */
    public function loadMessages(Request $request,Security $security,$conversation)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        $output = '';

        // save message
        if($request->getMethod() == 'POST'){

            // get messages
            $messages = $entityManager->getRepository(Messages::class)->findBy(array('conversation' => $conversation),array('id' => 'DESC'));

            if($messages){
                foreach($messages as $key => $value){
                    $user_id = $value->getIdUser();
                    $msg = $value->getMsg();
                    $date = $value->getDate();
                    $status_notification = $value->getStatusNotification();

                    if($userId == $user_id){
                        $output .= '<div class="chat outgoing"><div class="details"><p data-bs-toggle="tooltip" data-bs-placement="bottom" title="'.$date.'">'.$msg.'</p></div></div>';
                    }else{

                        // reset notifications
                        if($status_notification == 0){
                            $value->setStatusNotification(1);
                            $entityManager->persist($value);
                            $entityManager->flush();
                        }
                        
                        $output .= '<div class="chat incoming"><div class="details"><p data-bs-toggle="tooltip" data-bs-placement="bottom" title="'.$date.'">'.$msg.'</p></div></div>';
                    }
                    
                }
            }else{
                $output .= '<div id="no-message" class="text">Brak dostępnych wiadomości. Po wysłaniu wiadomości pojawią się tutaj.</div>';
            }

            return new JsonResponse(
                [
                    'messages' => $output,
                ]
            );

        }
    }

    /**
     * @Route("/system/mgs-notification/", name="notification_msg_system")
     *
     * @return Response
     */
    public function notificationMsgSystem(Security $security)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        $output = '';
        $count = 0;
        $array = []; 

        if(isset($_POST["view"])){

            // get conversations
            $repo = $entityManager->getRepository(Conversations::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.idUserA = :key1')->setParameter('key1', $userId)
                ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
                ->orderBy('q.position', 'DESC')
                ->getQuery();
            $globalConversations = $querry->getResult();
            // get messages
            $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

            $countMsg = 0;
            foreach($globalConversations as $key1 => $value1){
                $conversation_conversation = $value1->getConversation();
                foreach($globalMessages as $key2 => $value2){
                    $message_conversation = $value2->getConversation();
                    $message_status_notification = $value2->getStatusNotification();
                    $message_id_user = $value2->getIdUser();
                    if($conversation_conversation == $message_conversation){
                        if($message_status_notification == 0){
                            if($userId == $message_id_user){}else{
                                $countMsg++;
                            }
                        }
                    }
                }
            }

        }

        $data = array(
            'unseen_notification' => $countMsg
        );

        return new JsonResponse(
            [
                'unseen_notification' => $countMsg,
            ]
        );
    }

    /**
     * @Route("/settings", name="settings")
     *
     * @return Response
     */
    public function settings(Request $request, Security $security)
    {  
        // stay if logged in
        if ($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'POST'){
            // if user click delete avatar button
            if ($request->request->get('deleteAvatar')){
                // create user token
                $key = $userEmail . $userId;
                $token = $this->base64Encode($key);
                // delete older files
                $folder = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/avatar';
                $files = glob($folder . '/*');
                foreach($files as $file){
                    if(is_file($file)){
                        unlink($file);
                    }
                }
                // update user avatar in info table in database 'settings'
                $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
                if ($settings){
                    $settings->setAvatar("null");
                    $entityManager->persist($settings);
                    $entityManager->flush();
                }
            }
            // if user click delete background button
            if ($request->request->get('deleteBackground')){
                // create user token
                $key = $userEmail . $userId;
                $token = $this->base64Encode($key);
                // delete older files
                $folder = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/background';
                $files = glob($folder . '/*');
                foreach($files as $file){
                    if(is_file($file)){
                        unlink($file);
                    }
                }
                // update user background in info table in database 'settings'
                $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
                if ($settings){
                    $settings->setBackground("null");
                    $entityManager->persist($settings);
                    $entityManager->flush();
                }
            }
            // send background file
            if (isset($_FILES['background'])){
                if ($_FILES['background']['size'] != 0 && $_FILES['background']['error'] == 0){
                    
                    // create user token
                    $key = $userEmail . $userId;
                    $token = $this->base64Encode($key);
                    
                    // check max ini file size
                    if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > ((int) ini_get('post_max_size') * 1024 * 1024)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // ACCEPTED FILE TYPES & SIZE
                    $accept = ["jpg", "jpeg", "png"]; // ALL LOWER CASE
                    $maxSize = 5000000; // 5 MB

                    // CHECK FILE EXTENSION
                    $upExt = strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
                    if(!in_array($upExt, $accept)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_bad_format', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // CHECK FILE SIZE
                    if($_FILES['background']['size'] > $maxSize){
                        // delete post schema in databases because we have a error
                        $entityManager->remove($userPosts);
                        $entityManager->flush();
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // send file
                    if($_FILES['background']['error'] == '0' && $_FILES['background']['tmp_name'] != ''){
                        // create folder
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token;
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token . '/background';
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        if(!empty($_FILES['background']['name'])){
                            $file = $_FILES['background']['name'];
                            // create file name token
                            $key = 'background' . $userId;
                            $background = $this->base64Encode($key);
                            $file_url = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/background/' . $background . '.' . $upExt;
                            // delete older files
                            $folder = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/background';
                            $files = glob($folder . '/*');
                            foreach($files as $file){
                                if(is_file($file)){
                                    unlink($file);
                                }
                            }
                        }
                        // get orginal file url to send
                        $filename = $_FILES['background']['tmp_name'];
                        // send file to server
                        if(move_uploaded_file($filename, $file_url)){
                            $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
                            if ($settings){
                                $backgroundUrl = $token . '/background/' . $background . '.' . $upExt;
                                $settings->setBackground($backgroundUrl);
                                $entityManager->persist($settings);
                                $entityManager->flush();
                            }else{
                                // info message
                                $info_text = true;
                                $this->addFlash('info_error_file', $info_text);
                                return $this->redirectToRoute("settings");
                            }
                        }else{
                            // delete post schema in databases because we have a error
                            $entityManager->remove($userPosts);
                            $entityManager->flush();
                            // info message
                            $info_text = true;
                            $this->addFlash('info_error_file', $info_text);
                            return $this->redirectToRoute("settings");
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file', $info_text);
                        return $this->redirectToRoute("settings");
                    }
                }else{
                    if ($_FILES['background']['name'] != ""){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }
                }
            }
            // send avatar file
            if (isset($_FILES['avatar'])){
                if ($_FILES['avatar']['size'] != 0 && $_FILES['avatar']['error'] == 0){
                    
                    // create user token
                    $key = $userEmail . $userId;
                    $token = $this->base64Encode($key);
                    
                    // check max ini file size
                    if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > ((int) ini_get('post_max_size') * 1024 * 1024)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file2_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // ACCEPTED FILE TYPES & SIZE
                    $accept = ["jpg", "jpeg", "png"]; // ALL LOWER CASE
                    $maxSize = 5000000; // 5 MB

                    // CHECK FILE EXTENSION
                    $upExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    if(!in_array($upExt, $accept)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file2_bad_format', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // CHECK FILE SIZE
                    if($_FILES['avatar']['size'] > $maxSize){
                        // delete post schema in databases because we have a error
                        $entityManager->remove($userPosts);
                        $entityManager->flush();
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file2_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }

                    // send file
                    if($_FILES['avatar']['error'] == '0' && $_FILES['avatar']['tmp_name'] != ''){
                        // create folder
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token;
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token . '/avatar';
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        if(!empty($_FILES['avatar']['name'])){
                            $file = $_FILES['avatar']['name'];
                            // create file name token
                            $key = 'avatar' . $userId;
                            $avatar = $this->base64Encode($key);
                            $file_url = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/avatar/' . $avatar . '.' . $upExt;
                            // delete older files
                            $folder = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/avatar';
                            $files = glob($folder . '/*');
                            foreach($files as $file){
                                if(is_file($file)){
                                    unlink($file);
                                }
                            }
                        }
                        // get orginal file url to send
                        $filename = $_FILES['avatar']['tmp_name'];
                        // send file to server
                        if(move_uploaded_file($filename, $file_url)){
                            $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
                            if ($settings){
                                $backgroundUrl = $token . '/avatar/' . $avatar . '.' . $upExt;
                                $settings->setAvatar($backgroundUrl);
                                $entityManager->persist($settings);
                                $entityManager->flush();
                            }else{
                                // info message
                                $info_text = true;
                                $this->addFlash('info_error_file2', $info_text);
                                return $this->redirectToRoute("settings");
                            }
                        }else{
                            // delete post schema in databases because we have a error
                            $entityManager->remove($userPosts);
                            $entityManager->flush();
                            // info message
                            $info_text = true;
                            $this->addFlash('info_error_file2', $info_text);
                            return $this->redirectToRoute("settings");
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file2', $info_text);
                        return $this->redirectToRoute("settings");
                    }
                }else{
                    if ($_FILES['avatar']['name'] != ""){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file2_size', $info_text);
                        return $this->redirectToRoute("settings");
                    }
                }
            }
            // update user info in database 'settings'
            $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
            if ($settings){
                $checkEmailNotifications = $this->test_input($request->request->get('emailNotifications'));
                if ($checkEmailNotifications == 'on'){$statusEmailNotifications = 1;}else{$statusEmailNotifications = 0;}
                $settings->setEmailNotifications($statusEmailNotifications);

                $checkDarkTheme = $this->test_input($request->request->get('darkTheme'));
                if ($checkDarkTheme == 'on'){$statusDarkTheme = 1;}else{$statusDarkTheme = 0;}
                $settings->setDarkTheme($statusDarkTheme);

                $checkSpoiler = $this->test_input($request->request->get('spoiler'));
                if ($checkSpoiler == 'on'){$statusSpoiler = 1;}else{$statusSpoiler = 0;}
                $settings->setSpoiler($statusSpoiler);

                $checkNsfw = $this->test_input($request->request->get('nsfw'));
                if ($checkNsfw == 'on'){$statusNsfw = 1;}else{$statusNsfw = 0;}
                $settings->setNsfw($statusNsfw);

                $checkNickShow = $this->test_input($request->request->get('nickShow'));
                if ($checkNickShow == 'on'){$statusNickShow = 1;}else{$statusNickShow = 0;}
                $settings->setNickShow($statusNickShow);

                $availability = $this->test_input($request->request->get('availability')); $settings->setAvailability($availability);
                $accountType = $this->test_input($request->request->get('accountType')); $settings->setAccountType($accountType);
                
                $userDescription = $this->test_input($request->request->get('userDescription')); 
                $words = explode(" ", $userDescription);
                if(count($words) > 280){
                    $info_text = true;
                    $this->addFlash('info_error_long_text', $info_text);
                    return $this->redirectToRoute("settings");
                }else{$settings->setUserDescription($userDescription);}

                $entityManager->persist($settings);
                $entityManager->flush();
            }
            // update user info in database 'user'
            //$phone = $this->test_input($request->request->get('phoneUser'));
            $phone = "000-000-000";
            $sexUser = $this->test_input($request->request->get('sexUser'));
            $surname = $this->test_input($request->request->get('surnameUser'));
            $name = $this->test_input($request->request->get('nameUser'));
            $password = $this->test_input($request->request->get('user_password'));
            $password_reply = $this->test_input($request->request->get('user_password_reply'));
            
            // check name
            if($name == ''){
              // info message
              $info_text = true;
              $this->addFlash('error_name_1', $info_text);
              return $this->redirectToRoute("settings");
            }else{
                if(preg_match('/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{1,20}$/i', $name)){}else{
                    // info message
                    $info_text = true;
                    $this->addFlash('error_name_2', $info_text);
                    return $this->redirectToRoute("settings");
                }
            }
            // check surname
            if($surname == ''){
              // info message
              $info_text = true;
              $this->addFlash('error_surname_1', $info_text);
              return $this->redirectToRoute("settings");
            }else{
                if(preg_match('/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{1,20}$/i', $surname)){}else{
                    // info message
                    $info_text = true;
                    $this->addFlash('error_surname_2', $info_text);
                    return $this->redirectToRoute("settings");
                }
            }
            // check phone
            if($phone == ''){
              // info message
              $info_text = true;
              $this->addFlash('error_phone_1', $info_text);
              return $this->redirectToRoute("settings");
            }else{
                if(preg_match('/^[\d]{3}[\-][\d]{3}[\-][\d]{3}$/i', $phone)){}else{
                    // info message
                    $info_text = true;
                    $this->addFlash('error_phone_2', $info_text);
                    return $this->redirectToRoute("settings");
                }
            }
            
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            if($user){
                $user->setPhone($phone);
                $user->setName($name);
                $user->setSurname($surname);
                $user->setSex($sexUser);
                $entityManager->persist($user);
                $entityManager->flush();
            }

            /* change pass */
            // check password
            if($password == ''){
              // info message
              //$info_text = true;
              //$this->addFlash('error_password_1', $info_text);
              //return $this->redirectToRoute("settings");
            }else{
              if($password_reply == ''){
                // info message
                $info_text = true;
                $this->addFlash('error_password_2', $info_text);
                return $this->redirectToRoute("settings");
              }else{
                if($password != $password_reply)
                {
                  // info message
                  $info_text = true;
                  $this->addFlash('error_password_3', $info_text);
                  return $this->redirectToRoute("settings");
                }else{
                  $check_pass = strlen($password);
                  if($check_pass < 8){
                    // info message
                    $info_text = true;
                    $this->addFlash('error_password_4', $info_text);
                    return $this->redirectToRoute("settings");
                  }else{
                    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
                    if($user){
                        $pass = password_hash($password, PASSWORD_BCRYPT);
                        $user->setPassword($pass);
                        $entityManager->persist($user);
                        $entityManager->flush();
                    }
                  }
                }
              }
            }

            // info message
            $info_text = true;
            $this->addFlash('info_success', $info_text);
            return $this->redirectToRoute("settings");
        }else{
            // info alert
            $infoSucces = $this->get('session')->getFlashBag()->get('info_success');
            $infoErrorLongText = $this->get('session')->getFlashBag()->get('info_error_long_text');
            $infoErrorFileSize = $this->get('session')->getFlashBag()->get('info_error_file_size');
            $infoErrorFileBadFormat = $this->get('session')->getFlashBag()->get('info_error_file_bad_format');
            $infoErrorFile = $this->get('session')->getFlashBag()->get('info_error_file');
            $infoErrorFileSize2 = $this->get('session')->getFlashBag()->get('info_error_file2_size');
            $infoErrorFileBadFormat2 = $this->get('session')->getFlashBag()->get('info_error_file2_bad_format');
            $infoErrorFile2 = $this->get('session')->getFlashBag()->get('info_error_file2');
            $error_name_1 = $this->get('session')->getFlashBag()->get('error_name_1');
            $error_name_2 = $this->get('session')->getFlashBag()->get('error_name_2');
            $error_surname_1 = $this->get('session')->getFlashBag()->get('error_surname_1');
            $error_surname_2 = $this->get('session')->getFlashBag()->get('error_surname_2');
            $error_email_1 = $this->get('session')->getFlashBag()->get('error_email_1');
            $error_email_2 = $this->get('session')->getFlashBag()->get('error_email_2');
            $error_email_3 = $this->get('session')->getFlashBag()->get('error_email_3');
            $error_phone_1 = $this->get('session')->getFlashBag()->get('error_phone_1');
            $error_phone_2 = $this->get('session')->getFlashBag()->get('error_phone_2');
            $error_password_1 = $this->get('session')->getFlashBag()->get('error_password_1');
            $error_password_2 = $this->get('session')->getFlashBag()->get('error_password_2');
            $error_password_3 = $this->get('session')->getFlashBag()->get('error_password_3');
            $error_password_4 = $this->get('session')->getFlashBag()->get('error_password_4');

            $info_send_token = $this->get('session')->getFlashBag()->get('info_send_token');
            $info_error_token = $this->get('session')->getFlashBag()->get('info_error_token');

            // get users list
            $usersList = $entityManager->getRepository(User::class)->findAll();
            if (!$usersList){return $this->loginReset();}
            // get users settings
            $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            // get user settings and email
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $userId));
            if ($userSettings){
                $userAvatar = $userSettings->getAvatar();
                $userBackground = $userSettings->getBackground();
            }
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            if (!$user){return $this->loginReset();}else{
                $userPhone = $user->getPhone();
                $userNick = $user->getNick();
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $userSex = $user->getSex();

                $userNick = $user->getNick();
                $globalUserName = $user->getNick();
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getId()));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $user->getName();
                    $userSurname = $user->getSurname();
                    $globalUserName = $userName . ' ' . $userSurname;
                }
            }

            // get conversations
            $repo = $entityManager->getRepository(Conversations::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.idUserA = :key1')->setParameter('key1', $userId)
                ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
                ->orderBy('q.position', 'DESC')
                ->getQuery();
            $globalConversations = $querry->getResult();
            // get messages
            $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

            if(!empty($infoSucces)){
                $info = $infoSucces[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_success" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorLongText)){
                $info = $infoErrorLongText[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_long_text" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFileSize)){
                $info = $infoErrorFileSize[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file_size" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFileBadFormat)){
                $info = $infoErrorFileBadFormat[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file_bad_format" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFile)){
                $info = $infoErrorFile[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFileSize2)){
                $info = $infoErrorFileSize2[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file2_size" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFileBadFormat2)){
                $info = $infoErrorFileBadFormat2[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file2_bad_format" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif (!empty($infoErrorFile2)){
                $info = $infoErrorFile2[0];
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_file2" => $info,
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]);
            }elseif(!empty($error_name_1)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_name_1" => $error_name_1[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_name_2)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_name_2" => $error_name_2[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_surname_1)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_surname_1" => $error_surname_1[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_surname_2)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_surname_2" => $error_surname_2[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_email_1)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_email_1" => $error_email_1[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_email_2)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_email_2" => $error_email_2[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_email_3)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_email_3" => $error_email_3[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_phone_1)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_phone_1" => $error_phone_1[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_phone_2)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_phone_2" => $error_phone_2[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_password_1)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_password_1" => $error_password_1[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_password_2)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_password_2" => $error_password_2[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_password_3)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_password_3" => $error_password_3[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($error_password_4)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "error_password_4" => $error_password_4[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($info_send_token)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_send_token" => $info_send_token[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }elseif(!empty($info_error_token)){
                // return theme
                return $this->render('private/settings.html.twig', [
                    "info_error_token" => $info_error_token[0],
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }else{
                // return theme
                return $this->render('private/settings.html.twig', [
                    "usersSettings" => $usersSettings,
                    "userSettings" => $userSettings,
                    "userEmail" => $userEmail,
                    "userPhone" => $userPhone,
                    "userAvatar" => $userAvatar,
                    "userBackground" => $userBackground,
                    "userName" => $userName,
                    "userSurname" => $userSurname,
                    "userSex" => $userSex,
                    "userNick" => $userNick,
                    "globalUserNick" => $userNick,
                    "globalUserName" => $globalUserName,
                    "globalIdUser" => $userId,
                    "usersList" => $usersList,
                    "globalConversations" => $globalConversations,
                    "globalMessages" => $globalMessages,
                ]); 
            }
        }
    }

    /**
     * @Route("/g/{group_url}", name="get_group")
     *
     * @param $group_url
     *
     * @return Response
     */
    public function getGroup($group_url,Request $request,Security $security,PaginatorInterface $paginator,SessionInterface $session)
    {  
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get search words
        $words = $group_url;

        // get id group
        $group = $entityManager->getRepository(Groups::class)->findOneBy(array(
            'url' => $words,
        ));
        if ($group){
            $idGroup = $group->getId();
            $nameGroup = $group->getName();
            $urlGroup = $group->getUrl();
            $backgroundGroup = $group->getBackground();
            $creationDateGroup = $group->getCreationDate();
            $descriptionGroup = $group->getDescription();
            $authorGroup = $group->getIdAuthor();

            //get count post in thus group
            $postsWithGroup = $entityManager->getRepository(Posts::class)->count(array('idGroup' => $idGroup));

            // get posts
            $postsList = $this->getPostsList('group', $idGroup);
        }else{
            // info message
            $info_text = true;
            $this->addFlash('info_error_search', $info_text);
            return $this->redirectToRoute("public_stream");
        }
        
        // paginate the results of the query
        $showList = $paginator->paginate(
            // doctrine Query, not results
            $postsList,
            // define the page parameter
            $request->query->getInt('p', 1),
            // items per page
            10
        );

        // get user settings
        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        if (!$userSettings){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get tags list
        $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findAll();
        // get groups list
        $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findAll();
        // get groups list
        $groupsListAll = $entityManager->getRepository(Groups::class)->findAll();
        // get rating list
        $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findAll();
        // get count comments to post
        $commentsList = $entityManager->getRepository(PostsComments::class)->findAll();
        // get user nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // if active user blocked user profile
        $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $userId));

        // count users in group
        $countGroupsSwitch = $entityManager->getRepository(GroupUserWatching::class)->count(array('idGroup' => $idGroup));

        // check if loggin user jpoin in group
        $getGroup = $entityManager->getRepository(GroupUserWatching::class)->findOneBy(array('idGroup' => $idGroup, 'idUser' => $userId));
        if($getGroup){$user_join_group = 1;}else{$user_join_group = 0;}

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // info alert
        $success_create_group = $this->get('session')->getFlashBag()->get('success_create_group');

        if(!empty($success_create_group)){
            // return theme
            return $this->render('private/search_group.html.twig', [
                "success_create_group" => $success_create_group[0],
                "postsWithGroup" => $postsWithGroup,
                "countGroupsSwitch" => $countGroupsSwitch,
                "nameGroup" => $nameGroup,
                "urlGroup" => $urlGroup,
                "backgroundGroup" => $backgroundGroup,
                "creationDateGroup" => $creationDateGroup,
                "descriptionGroup" => $descriptionGroup,
                "authorGroup" => $authorGroup,
                "idGroup" => $idGroup,
                "words_search" => $words,
                "user_join_group" => $user_join_group,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "groupsListAll" => $groupsListAll,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }else{
            // return theme
            return $this->render('private/search_group.html.twig', [
                "postsWithGroup" => $postsWithGroup,
                "countGroupsSwitch" => $countGroupsSwitch,
                "nameGroup" => $nameGroup,
                "urlGroup" => $urlGroup,
                "backgroundGroup" => $backgroundGroup,
                "creationDateGroup" => $creationDateGroup,
                "descriptionGroup" => $descriptionGroup,
                "authorGroup" => $authorGroup,
                "idGroup" => $idGroup,
                "words_search" => $words,
                "user_join_group" => $user_join_group,
                "posts" => $postsList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "activeUsersSettings" => $activeUsersSettings,
                "showList" => $showList,
                "usersList" => $usersList,
                "tagsList" => $tagsList,
                "groupsList" => $groupsList,
                "groupsListAll" => $groupsListAll,
                "ratingList" => $ratingList,
                "commentsList" => $commentsList,
                "blockedUsersSwitchList" => $blockedUsersSwitchList,
                "userNick" => $userNick,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }
    }

    /**
     * @Route("/h/{hashtag_url}", name="get_hashtag")
     *
     * @param $hashtag_url
     *
     * @return Response
     */
    public function getHashtag($hashtag_url,Request $request,Security $security,PaginatorInterface $paginator,SessionInterface $session)
    {  
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get search words
        $words = $hashtag_url;

        // get all hashtag
        $repo = $entityManager->getRepository(HashtagSwitch::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.tagName = :key1')->setParameter('key1', $words)
            ->orWhere('q.tagUrl = :key2')->setParameter('key2', $words)
            ->orderBy('q.id', 'DESC')
            ->getQuery();
        $querry = $querry->getResult();

        if(count($querry) > 0){
            $array = [];
            foreach($querry as $key => $value){
                $idPost = $value->getIdPost();
                if(in_array($idPost, $array)){}else{array_push($array, $idPost);}
            }
            
            // get posts
            $postsList = $this->getPostsList('hashtag', $array);

        }else{
            // info message
            $info_text = true;
            $this->addFlash('info_error_search', $info_text);
            return $this->redirectToRoute("public_stream");
        }

        // paginate the results of the query
        $showList = $paginator->paginate(
            // doctrine Query, not results
            $postsList,
            // define the page parameter
            $request->query->getInt('p', 1),
            // items per page
            10
        );

        // get user settings
        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        if (!$userSettings){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
            $activeUsersSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get tags list
        $tagsList = $entityManager->getRepository(HashtagSwitch::class)->findAll();
        // get groups list
        $groupsList = $entityManager->getRepository(GroupsSwitch::class)->findAll();
        // get rating list
        $ratingList = $entityManager->getRepository(RatingPostSwitch::class)->findAll();
        // get count comments to post
        $commentsList = $entityManager->getRepository(PostsComments::class)->findAll();
        // get user nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // if active user blocked user profile
        $blockedUsersSwitchList = $entityManager->getRepository(BlockedUsersSwitch::class)->findBy(array('idUserA' => $userId));

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // return theme
        return $this->render('private/search_hastag.html.twig', [
            "words_search" => $words,
            "posts" => $postsList,
            "userSettings" => $userSettings,
            "usersSettings" => $usersSettings,
            "activeUsersSettings" => $activeUsersSettings,
            "showList" => $showList,
            "usersList" => $usersList,
            "tagsList" => $tagsList,
            "groupsList" => $groupsList,
            "ratingList" => $ratingList,
            "commentsList" => $commentsList,
            "blockedUsersSwitchList" => $blockedUsersSwitchList,
            "userNick" => $userNick,
            "globalUserNick" => $userNick,
            "globalUserName" => $globalUserName,
            "globalIdUser" => $userId,
            "globalConversations" => $globalConversations,
            "globalMessages" => $globalMessages,
        ]);
    }

    /*
      ERROR PAGES
    */

    /**
     * @Route("/404", name="error_page_404")
     */
    public function errorPage404( Request $request )
    {  
        return $this->redirectToRoute('public_stream');
    }

    /*
      MODUŁ : DODAWANIE POSTU
    */

    /**
     * @Route("/submit", name="submit_post")
     */
    public function submitPost(Request $request, Security $security)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get user settings
        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId()));
        if (!$userSettings){return $this->loginReset();}
        // get users settings
        $usersSettings = $entityManager->getRepository(Settings::class)->findAll();
        // get user nick
        $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
        if (!$user){return $this->loginReset();}else{
            $userNick = $user->getNick();
            $globalUserName = $user->getNick();
            $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
            $userSettingsNS = $userSettings->getNickShow();
            if($userSettingsNS == 1){
                $userName = $user->getName();
                $userSurname = $user->getSurname();
                $globalUserName = $userName . ' ' . $userSurname;
            }
        }
        // get users list
        $usersList = $entityManager->getRepository(User::class)->findAll();
        if (!$usersList){return $this->loginReset();}
        // get group basic
        $repo = $entityManager->getRepository(Groups::class);
        $groups  = $repo->createQueryBuilder('q')
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(14)
            ->getQuery();
        $groups = $groups->getResult();

        // get conversations
        $repo = $entityManager->getRepository(Conversations::class);
        $querry  = $repo->createQueryBuilder('q')
            ->where('q.idUserA = :key1')->setParameter('key1', $userId)
            ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
            ->orderBy('q.position', 'DESC')
            ->getQuery();
        $globalConversations = $querry->getResult();
        // get messages
        $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

        // info alert
        $infoSucces = $this->get('session')->getFlashBag()->get('info_success');
        $infoErrorLongText = $this->get('session')->getFlashBag()->get('info_error_long_text');
        $infoErrorFileSize = $this->get('session')->getFlashBag()->get('info_error_file_size');
        $infoErrorFileBadFormat = $this->get('session')->getFlashBag()->get('info_error_file_bad_format');
        $infoErrorFile = $this->get('session')->getFlashBag()->get('info_error_file');
        $infoErrorNone = $this->get('session')->getFlashBag()->get('info_error_none');
        $infoError = $this->get('session')->getFlashBag()->get('info_error');
        $info_spamstop = $this->get('session')->getFlashBag()->get('info_spamstop');

        // return theme
        if(!empty($infoSucces)){
            $info = $infoSucces[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_success" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoErrorLongText)){
            $info = $infoErrorLongText[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error_long_text" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoErrorFileSize)){
            $info = $infoErrorFileSize[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error_file_size" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoErrorFileBadFormat)){
            $info = $infoErrorFileBadFormat[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error_file_bad_format" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoErrorFile)){
            $info = $infoErrorFile[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error_file" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoErrorNone)){
            $info = $infoErrorNone[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error_none" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($infoError)){
            $info = $infoError[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_error" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }elseif(!empty($info_spamstop)){
            $info = $info_spamstop[0];
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "info_spamstop" => $info,
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }else{
            // return theme
            return $this->render('private/submit_post.html.twig', [
                "usersList" => $usersList,
                "userSettings" => $userSettings,
                "usersSettings" => $usersSettings,
                "globalUserNick" => $userNick,
                "globalUserName" => $globalUserName,
                "globalIdUser" => $userId,
                "groups" => $groups,
                "globalConversations" => $globalConversations,
                "globalMessages" => $globalMessages,
            ]);
        }
    }

    /**
     * @Route("/submit/{type_form}", name="submit_post_form", requirements={"type_form" = "post|media|link"})
     *
     * @param $type_form
     *
     */
    public function submitPostForm(Request $request, Security $security, $type_form)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();$userEmail = $security->getUser()->getUsername();}else{return $this->loginReset();}

        if ($request->getMethod() == 'POST'){

            // check span
            if(!isset($_COOKIE['spamstoppost'])){
                setcookie ("spamstoppost", time()+30, time()+30);
            }else{
                $info_text = true;
                $this->addFlash('info_spamstop', $info_text);
                return $this->redirectToRoute('submit_post');
            }

            // entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // set timezone
            date_default_timezone_set('Europe/Warsaw');

            if ($type_form == 'post'){

                // input - community
                $post_community1 = $request->request->get('post_community1');
                // sprawdź czy jest taka grupa
                $createUrl = str_replace(" ","-",$post_community1);
                $repo = $entityManager->getRepository(Groups::class);
                $groups  = $repo->createQueryBuilder('q')
                    ->where('q.name = :key')->setParameter('key', $post_community1)
                    ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                    ->orderBy('q.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery();
                $groups = $groups->getOneOrNullResult();
                if($groups){$id_group = $groups->getId();}else{$id_group = 0;}

                // input - tytul
                $checkFormPostTitle = $this->test_input($request->request->get('post_title'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($checkFormPostTitle)){
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_none', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // input - tekst
                $checkFormMessage = $this->test_input($request->request->get('formMessage'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($checkFormMessage)){
                    $checkFormMessage = "";
                }else{
                    // sprawdzenie maksymalnej długości dla wiadomości
                    $words = explode(" ", $checkFormMessage);
                    if(count($words) > 280){
                        $info_text = true;
                        $this->addFlash('info_error_long_text', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                }
                 
                // input - url
                $checkFormPostUrl = "";

                // input - dostępność
                $checkFormAvailability = $this->test_input($request->request->get('formAvailability'));

                // input - spoiler
                $post_spoiler = $request->request->get('post_spoiler');
                if($post_spoiler){$post_spoiler = 1;}else{$post_spoiler = 0;}

                // input - nsfw
                $post_nsfw = $request->request->get('post_nsfw');
                if($post_nsfw){$post_nsfw = 1;}else{$post_nsfw = 0;}

                // input - tags
                $checkTagsInput = $this->test_input($request->request->get('tagsInput'));

                // zapis informacji o poście do bazy danych
                $savePost = new Posts();
                $savePost->setIdAuthor($userId);
                $savePost->setTitle($checkFormPostTitle);
                $savePost->setPostUrl($this->friendlyUrl($checkFormPostTitle));
                $savePost->setText($checkFormMessage);
                $savePost->setUrl($checkFormPostUrl);
                $savePost->setUrlVideo("none");
                $savePost->setUrlPhotos("none");
                $savePost->setDateAdded(date("Y-m-d"));
                $savePost->setTimeAdded(date("H:i:s"));
                $savePost->setNumberLikes("0");
                $savePost->setNumberDislikes("0");
                $savePost->setSpoiler($post_spoiler);
                $savePost->setNsfw($post_nsfw);
                $savePost->setIdGroup(0);
                $savePost->setAvailability($checkFormAvailability);
                $entityManager->persist($savePost);
                $entityManager->flush();

                // sprawdź jeśli nie ma uzupełnionego pola
                if(!empty($checkTagsInput)){
                    // create post token
                    $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                    if($userPosts){
                        $id_post = $userPosts->getId();
                        // zapis informacji o tagach do bazy danych
                        $tagsArray = explode(",", $checkTagsInput);
                        $t = 0;
                        foreach($tagsArray as &$value){
                            $tag_name = $value;
                            $tag_url = $this->friendlyUrl($value);
                            // zapis do bazy danych
                            if($t<8){
                                $saveTag = new HashtagSwitch();
                                $saveTag->setTagName($tag_name);
                                $saveTag->setTagUrl($tag_url);
                                $saveTag->setIdPost($id_post);
                                $entityManager->persist($saveTag);
                                $entityManager->flush(); 
                            }
                            $t++;
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                }

                // check and save group
                $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                if($userPosts){
                    $id_post = $userPosts->getId();
                    // sprawdź czy jest taka grupa
                    $createUrl = str_replace(" ","-",$post_community1);
                    $repo = $entityManager->getRepository(Groups::class);
                    $groups  = $repo->createQueryBuilder('q')
                        ->where('q.name = :key')->setParameter('key', $post_community1)
                        ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                        ->orderBy('q.id', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery();
                    $groups = $groups->getOneOrNullResult();
                    if($groups){
                        $id_group = $groups->getId();
                        $groupName = $groups->getName();
                        $groupUrl = $this->friendlyUrl($groupName);
                        // uaktulnienie id grupy dla postu
                        $userPosts->setIdGroup($id_group);
                        $entityManager->persist($userPosts);
                        $entityManager->flush();
                        // zapis grupy do bazy danych
                        $saveGroup = new GroupsSwitch();
                        $saveGroup->setGroupName($groupName);
                        $saveGroup->setGroupUrl($groupUrl);
                        $saveGroup->setIdGroup($id_group);
                        $saveGroup->setIdPost($id_post);
                        $entityManager->persist($saveGroup);
                        $entityManager->flush();
                    }
                }

                // info message success
                $info_text = true;
                $this->addFlash('info_success', $info_text);
                return $this->redirectToRoute("submit_post");

            }elseif ($type_form == 'media'){
                        
                // input - community
                $post_community2 = $request->request->get('post_community2');
                // sprawdź czy jest taka grupa
                $createUrl = str_replace(" ","-",$post_community2);
                $repo = $entityManager->getRepository(Groups::class);
                $groups  = $repo->createQueryBuilder('q')
                    ->where('q.name = :key')->setParameter('key', $post_community2)
                    ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                    ->orderBy('q.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery();
                $groups = $groups->getOneOrNullResult();
                if($groups){$id_group = $groups->getId();}else{$id_group = 0;}

                // input - tytul
                $checkFormPostTitle = $this->test_input($request->request->get('post_title'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($checkFormPostTitle)){
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_none', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // input - tekst
                $checkFormMessage = "";
                 
                // input - url
                $checkFormPostUrl = "";

                // input - dostępność
                $checkFormAvailability = $this->test_input($request->request->get('formAvailability'));

                // input - spoiler
                $post_spoiler = $request->request->get('post_spoiler');
                if($post_spoiler){$post_spoiler = 1;}else{$post_spoiler = 0;}

                // input - nsfw
                $post_nsfw = $request->request->get('post_nsfw');
                if($post_nsfw){$post_nsfw = 1;}else{$post_nsfw = 0;}

                // input - tags
                $checkTagsInput = $this->test_input($request->request->get('tagsInput'));

                // zapis informacji o poście do bazy danych
                $savePost = new Posts();
                $savePost->setIdAuthor($userId);
                $savePost->setTitle($checkFormPostTitle);
                $savePost->setPostUrl($this->friendlyUrl($checkFormPostTitle));
                $savePost->setText($checkFormMessage);
                $savePost->setUrl($checkFormPostUrl);
                $savePost->setUrlVideo("none");
                $savePost->setUrlPhotos("none");
                $savePost->setDateAdded(date("Y-m-d"));
                $savePost->setTimeAdded(date("H:i:s"));
                $savePost->setNumberLikes("0");
                $savePost->setNumberDislikes("0");
                $savePost->setSpoiler($post_spoiler);
                $savePost->setNsfw($post_nsfw);
                $savePost->setIdGroup(0);
                $savePost->setAvailability($checkFormAvailability);
                $entityManager->persist($savePost);
                $entityManager->flush();

                // send file
                // create user token
                $key = $userEmail . $userId;
                $token = $this->base64Encode($key);
                // get id post
                $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                if($userPosts){
                    $id_post = $userPosts->getId();
                }else{
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_file', $info_text);
                    return $this->redirectToRoute("submit_post");
                }
                $key = $id_post . $userId;
                $post = $this->base64Encode($key);

                // check max ini file size
                if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > ((int) ini_get('post_max_size') * 1024 * 1024)){
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_file_size', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // ERROR - NO FILE UPLOADED
                if(!isset($_FILES['formFile'])){
                    // delete post schema in databases because we have a error
                    $entityManager->remove($userPosts);
                    $entityManager->flush();
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_none', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // ACCEPTED FILE TYPES & SIZE
                $accept = ["jpg", "jpeg", "png", "gif", "mp4"]; // ALL LOWER CASE
                $maxSize = 5000000; // 5 MB

                // CHECK FILE EXTENSION
                $upExt = strtolower(pathinfo($_FILES['formFile']['name'], PATHINFO_EXTENSION));
                if(!in_array($upExt, $accept)){
                    // delete post schema in databases because we have a error
                    $entityManager->remove($userPosts);
                    $entityManager->flush();
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_file_bad_format', $info_text);
                    return $this->redirectToRoute("submit_post");
                }else{
                    $mediaPostType = $_FILES['formFile']['type'];
                    $mediaPostType = explode("/",$mediaPostType);
                    $mediaPostType = $mediaPostType[0];
                }
                
                // CHECK FILE SIZE
                if($_FILES['formFile']['size'] > $maxSize){
                    // delete post schema in databases because we have a error
                    $entityManager->remove($userPosts);
                    $entityManager->flush();
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_file_size', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // send file
                if($_FILES['formFile']['error'] == '0' && $_FILES['formFile']['tmp_name'] != ''){
                    // create folder
                    $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token;
                    if(!file_exists( $dir )){
                        $oldmask = umask(0);
                        mkdir($dir, 0777, true);
                        umask($oldmask);
                    }
                    $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token . '/media';
                    if(!file_exists( $dir )){
                        $oldmask = umask(0);
                        mkdir($dir, 0777, true);
                        umask($oldmask);
                    }
                    $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/users/' . $token . '/media/' . $post;
                    if(!file_exists( $dir )){
                        $oldmask = umask(0);
                        mkdir($dir, 0777, true);
                        umask($oldmask);
                    }
                    // create file name token
                    $key = date("YmdH") . $userId . $upExt;
                    $file = $this->base64Encode($key);
                    // create file url
                    $file_url = $_SERVER['DOCUMENT_ROOT'] . '/tmp/users/' . $token . '/media/' . $post . '/' . $file . '.' . $upExt;
                    // get orginal file url to send
                    $filename = $_FILES['formFile']['tmp_name'];
                    // check if file is image or video
                    if( ($mediaPostType == 'image') or ($mediaPostType == 'video') ){}else{
                        // delete post schema in databases because we have a error
                        $entityManager->remove($userPosts);
                        $entityManager->flush();
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_bad_format', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                    // send file to server
                    if(move_uploaded_file($filename, $file_url)){
                        // update user post url photo/video
                        if($userPosts){
                            $fileUrl = $token . '/media/' . $post . '/' . $file . '.' . $upExt;
                            if($mediaPostType == 'image'){
                                $userPosts->setUrlPhotos($fileUrl);
                            }elseif($mediaPostType == 'video'){
                                $userPosts->setUrlVideo($fileUrl);
                            }
                            $entityManager->persist($userPosts);
                            $entityManager->flush();
                        }else{
                            // info message
                            $info_text = true;
                            $this->addFlash('info_error', $info_text);
                            return $this->redirectToRoute("submit_post");
                        }
                    }else{
                        // delete post schema in databases because we have a error
                        $entityManager->remove($userPosts);
                        $entityManager->flush();
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                }else{
                    // delete post schema in databases because we have a error
                    $entityManager->remove($userPosts);
                    $entityManager->flush();
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_file', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // sprawdź jeśli nie ma uzupełnionego pola
                if(!empty($checkTagsInput)){
                    $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                    if($userPosts){
                        $id_post = $userPosts->getId();
                        // zapis informacji o tagach do bazy danych
                        $tagsArray = explode(",", $checkTagsInput);
                        $t = 0;
                        foreach($tagsArray as &$value){
                            $tag_name = $value;
                            $tag_url = $this->friendlyUrl($value);
                            // zapis do bazy danych
                            if($t<8){
                                $saveTag = new HashtagSwitch();
                                $saveTag->setTagName($tag_name);
                                $saveTag->setTagUrl($tag_url);
                                $saveTag->setIdPost($id_post);
                                $entityManager->persist($saveTag);
                                $entityManager->flush(); 
                            }
                            $t++;
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                }

                // check and save group
                $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                if($userPosts){
                    $id_post = $userPosts->getId();
                    // sprawdź czy jest taka grupa
                    $createUrl = str_replace(" ","-",$post_community2);
                    $repo = $entityManager->getRepository(Groups::class);
                    $groups  = $repo->createQueryBuilder('q')
                        ->where('q.name = :key')->setParameter('key', $post_community2)
                        ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                        ->orderBy('q.id', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery();
                    $groups = $groups->getOneOrNullResult();
                    if($groups){
                        $id_group = $groups->getId();
                        $groupName = $groups->getName();
                        $groupUrl = $this->friendlyUrl($groupName);
                        // uaktulnienie id grupy dla postu
                        $userPosts->setIdGroup($id_group);
                        $entityManager->persist($userPosts);
                        $entityManager->flush();
                        // zapis grupy do bazy danych
                        $saveGroup = new GroupsSwitch();
                        $saveGroup->setGroupName($groupName);
                        $saveGroup->setGroupUrl($groupUrl);
                        $saveGroup->setIdGroup($id_group);
                        $saveGroup->setIdPost($id_post);
                        $entityManager->persist($saveGroup);
                        $entityManager->flush();
                    }
                }

                // info message success
                $info_text = true;
                $this->addFlash('info_success', $info_text);
                return $this->redirectToRoute("submit_post");

            }elseif ($type_form == 'link'){
                
                // input - community
                $post_community3 = $request->request->get('post_community3');
                // sprawdź czy jest taka grupa
                $createUrl = str_replace(" ","-",$post_community3);
                $repo = $entityManager->getRepository(Groups::class);
                $groups  = $repo->createQueryBuilder('q')
                    ->where('q.name = :key')->setParameter('key', $post_community3)
                    ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                    ->orderBy('q.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery();
                $groups = $groups->getOneOrNullResult();
                if($groups){$id_group = $groups->getId();}else{$id_group = 0;}

                // input - tytul
                $checkFormPostTitle = $this->test_input($request->request->get('post_title'));
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($checkFormPostTitle)){
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_none', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // input - tekst
                $checkFormMessage = "";
                 
                // input - url
                $checkFormPostUrl = $request->request->get('post_link'); // czy sprawdzić co wpisuje user?
                // sprawdź jeśli nie ma uzupełnionego pola
                if(empty($checkFormPostUrl)){
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_none', $info_text);
                    return $this->redirectToRoute("submit_post");
                }

                // input - dostępność
                $checkFormAvailability = $this->test_input($request->request->get('formAvailability'));

                // input - spoiler
                $post_spoiler = $request->request->get('post_spoiler');
                if($post_spoiler){$post_spoiler = 1;}else{$post_spoiler = 0;}

                // input - nsfw
                $post_nsfw = $request->request->get('post_nsfw');
                if($post_nsfw){$post_nsfw = 1;}else{$post_nsfw = 0;}

                // input - tags
                $checkTagsInput = $this->test_input($request->request->get('tagsInput'));

                // zapis informacji o poście do bazy danych
                $savePost = new Posts();
                $savePost->setIdAuthor($userId);
                $savePost->setTitle($checkFormPostTitle);
                $savePost->setPostUrl($this->friendlyUrl($checkFormPostTitle));
                $savePost->setText($checkFormMessage);
                $savePost->setUrl($checkFormPostUrl);
                $savePost->setUrlVideo("none");
                $savePost->setUrlPhotos("none");
                $savePost->setDateAdded(date("Y-m-d"));
                $savePost->setTimeAdded(date("H:i:s"));
                $savePost->setNumberLikes("0");
                $savePost->setNumberDislikes("0");
                $savePost->setSpoiler($post_spoiler);
                $savePost->setNsfw($post_nsfw);
                $savePost->setIdGroup(0);
                $savePost->setAvailability($checkFormAvailability);
                $entityManager->persist($savePost);
                $entityManager->flush();

                // sprawdź jeśli nie ma uzupełnionego pola
                if(!empty($checkTagsInput)){
                    // create post token
                    $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                    if($userPosts){
                        $id_post = $userPosts->getId();
                        // zapis informacji o tagach do bazy danych
                        $tagsArray = explode(",", $checkTagsInput);
                        $t = 0;
                        foreach($tagsArray as &$value){
                            $tag_name = $value;
                            $tag_url = $this->friendlyUrl($value);
                            // zapis do bazy danych
                            if($t<8){
                                $saveTag = new HashtagSwitch();
                                $saveTag->setTagName($tag_name);
                                $saveTag->setTagUrl($tag_url);
                                $saveTag->setIdPost($id_post);
                                $entityManager->persist($saveTag);
                                $entityManager->flush(); 
                            }
                            $t++;
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error', $info_text);
                        return $this->redirectToRoute("submit_post");
                    }
                }

                // check and save group
                $userPosts = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $this->getUser()->getId()),array('id' => 'DESC'));
                if($userPosts){
                    $id_post = $userPosts->getId();
                    // sprawdź czy jest taka grupa
                    $createUrl = str_replace(" ","-",$post_community3);
                    $repo = $entityManager->getRepository(Groups::class);
                    $groups  = $repo->createQueryBuilder('q')
                        ->where('q.name = :key')->setParameter('key', $post_community3)
                        ->orWhere('q.url = :key')->setParameter('key', $createUrl)
                        ->orderBy('q.id', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery();
                    $groups = $groups->getOneOrNullResult();
                    if($groups){
                        $id_group = $groups->getId();
                        $groupName = $groups->getName();
                        $groupUrl = $this->friendlyUrl($groupName);
                        // uaktulnienie id grupy dla postu
                        $userPosts->setIdGroup($id_group);
                        $entityManager->persist($userPosts);
                        $entityManager->flush();
                        // zapis grupy do bazy danych
                        $saveGroup = new GroupsSwitch();
                        $saveGroup->setGroupName($groupName);
                        $saveGroup->setGroupUrl($groupUrl);
                        $saveGroup->setIdGroup($id_group);
                        $saveGroup->setIdPost($id_post);
                        $entityManager->persist($saveGroup);
                        $entityManager->flush();
                    }
                }

                // info message success
                $info_text = true;
                $this->addFlash('info_success', $info_text);
                return $this->redirectToRoute("submit_post");

            }
        }
        else
        {
            return $this->redirectToRoute('submit_post');
        }
    }

    /*
      MODUŁ : KREATOR AWATARS
    */

    /**
     * @Route("/create-avatar", name="create_avatar")
     */
    public function createAvatar(Request $request, Security $security)
    {
        return new Response();
    }

    /*
      MODUŁ : KREATOR GRUPY
    */

    /**
     * @Route("/create-group", name="create_group")
     */
    public function createGroup(Request $request, Security $security)
    {
        // tymczasowo wyłączone
        return $this->loginReset();

        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // POST
        if($request->getMethod() == 'POST'){
            // check show name
            $nick_show = $this->test_input($request->request->get('nick_show'));
            if ($nick_show == 'on'){$statusNickShow = 1;}else{$statusNickShow = 0;}
            // check name
            $name = $request->request->get('name_group');
            if($name == ''){
              // info message
              $info_text = $name;
              $this->addFlash('error_namegroup_1', $info_text);
              return $this->redirectToRoute("create_group");
            }else{
                $checkName = $entityManager->getRepository(Groups::class)->findOneBy(['url' => strtolower($name)]);
                if ($checkName){
                    // info message
                    $info_text = $name;
                    $this->addFlash('error_namegroup_2', $info_text);
                    return $this->redirectToRoute("create_group");
                }else{
                    if(preg_match('/^[A-Za-z_]{5,20}$/i', $name)){}else{
                        // info message
                        $info_text = true;
                        $this->addFlash('error_namegroup_3', $info_text);
                        return $this->redirectToRoute("create_group");
                    }
                }
            }
            // check description
            $description_group = $this->test_input($request->request->get('description_group'));
            // sprawdź jeśli nie ma uzupełnionego pola
            if(empty($description_group)){}else{
                // sprawdzenie maksymalnej długości dla wiadomości
                $words = explode(" ", $description_group);
                if(count($words) > 100){
                    $info_text = true;
                    $this->addFlash('info_error_long_text', $info_text);
                    return $this->redirectToRoute("create_group");
                }
            }
            // send background file
            $backgroundUrl = 'null';
            if (isset($_FILES['background'])){
                if ($_FILES['background']['size'] != 0 && $_FILES['background']['error'] == 0){
                    
                    // create user token
                    $key = $this->friendlyUrl($name) . $userId;
                    $token = $this->base64Encode($key);
                    
                    // check max ini file size
                    if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > ((int) ini_get('post_max_size') * 1024 * 1024)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("create_group");
                    }

                    // ACCEPTED FILE TYPES & SIZE
                    $accept = ["jpg", "jpeg", "png"]; // ALL LOWER CASE
                    $maxSize = 5000000; // 5 MB

                    // CHECK FILE EXTENSION
                    $upExt = strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
                    if(!in_array($upExt, $accept)){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_bad_format', $info_text);
                        return $this->redirectToRoute("create_group");
                    }

                    // CHECK FILE SIZE
                    if($_FILES['background']['size'] > $maxSize){
                        // delete post schema in databases because we have a error
                        $entityManager->remove($userPosts);
                        $entityManager->flush();
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("create_group");
                    }

                    // send file
                    if($_FILES['background']['error'] == '0' && $_FILES['background']['tmp_name'] != ''){
                        // create folder
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/groups/' . $token;
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        $dir = $_SERVER['DOCUMENT_ROOT']  . '/tmp/groups/' . $token . '/background';
                        if(!file_exists( $dir )){
                            $oldmask = umask(0);
                            mkdir($dir, 0777, true);
                            umask($oldmask);
                        }
                        if(!empty($_FILES['background']['name'])){
                            $file = $_FILES['background']['name'];
                            // create file name token
                            $key = 'background' . $userId;
                            $background = $this->base64Encode($key);
                            $file_url = $_SERVER['DOCUMENT_ROOT'] . '/tmp/groups/' . $token . '/background/' . $background . '.' . $upExt;
                            // delete older files
                            $folder = $_SERVER['DOCUMENT_ROOT'] . '/tmp/groups/' . $token . '/background';
                            $files = glob($folder . '/*');
                            foreach($files as $file){
                                if(is_file($file)){
                                    unlink($file);
                                }
                            }
                        }
                        // get orginal file url to send
                        $filename = $_FILES['background']['tmp_name'];
                        // send file to server
                        if(move_uploaded_file($filename, $file_url)){
                            $settings = $entityManager->getRepository(Settings::class)->findOneBy(['idUser' => $userId]);
                            if ($settings){
                                $backgroundUrl = $token . '/background/' . $background . '.' . $upExt;
                                $settings->setBackground($backgroundUrl);
                                $entityManager->persist($settings);
                                $entityManager->flush();
                            }else{
                                // info message
                                $info_text = true;
                                $this->addFlash('info_error_file', $info_text);
                                return $this->redirectToRoute("create_group");
                            }
                        }else{
                            // delete post schema in databases because we have a error
                            $entityManager->remove($userPosts);
                            $entityManager->flush();
                            // info message
                            $info_text = true;
                            $this->addFlash('info_error_file', $info_text);
                            return $this->redirectToRoute("create_group");
                        }
                    }else{
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file', $info_text);
                        return $this->redirectToRoute("create_group");
                    }
                }else{
                    if ($_FILES['background']['name'] != ""){
                        // info message
                        $info_text = true;
                        $this->addFlash('info_error_file_size', $info_text);
                        return $this->redirectToRoute("create_group");
                    }
                }
            }
            // create new group
            $saveGroup = new Groups();
            $saveGroup->setName($name);
            $saveGroup->setUrl(strtolower($name));
            $saveGroup->setIdAuthor($userId); 
            $saveGroup->setNickShow($statusNickShow); 
            $saveGroup->setCreationDate(date("Y-m-d"));
            $saveGroup->setDescription($description_group);
            $saveGroup->setBackground($backgroundUrl);
            $entityManager->persist($saveGroup);
            $entityManager->flush();
            // get new group
            $getGroup = $entityManager->getRepository(Groups::class)->findOneBy(array('url' => strtolower($name), 'idUser' => $userId));
            // add join user
            $groupId = $getGroup->getId();
            $saveJoin = new GroupUserWatching();
            $saveJoin->setIdGroup($groupId);
            $saveJoin->setIdUser($userId);
            $entityManager->persist($saveJoin);
            $entityManager->flush();
            // info message
            $info_text = true;
            $this->addFlash('success_create_group', $info_text);
            return $this->redirectToRoute('get_group', array('group_url' => strtolower($name)));
        }else{
            // get users settings
            $usersSettings = $entityManager->getRepository(Settings::class)->findAll();

            // get user nick
            $user = $entityManager->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->getId()));
            if (!$user){return $this->loginReset();}else{
                $userNick = $user->getNick();
                $globalUserName = $user->getNick();
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $user->getID()));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $user->getName();
                    $userSurname = $user->getSurname();
                    $globalUserName = $userName . ' ' . $userSurname;
                }
            }

            // get conversations
            $repo = $entityManager->getRepository(Conversations::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.idUserA = :key1')->setParameter('key1', $userId)
                ->orWhere('q.idUserB = :key2')->setParameter('key2', $userId)
                ->orderBy('q.position', 'DESC')
                ->getQuery();
            $globalConversations = $querry->getResult();
            // get messages
            $globalMessages = $entityManager->getRepository(Messages::class)->findBy(array(), array('id' => 'DESC'));

            // info message
            $error_namegroup_1 = $this->get('session')->getFlashBag()->get('error_namegroup_1');
            $error_namegroup_2 = $this->get('session')->getFlashBag()->get('error_namegroup_2');
            $error_namegroup_3 = $this->get('session')->getFlashBag()->get('error_namegroup_3');
            $info_error_long_text = $this->get('session')->getFlashBag()->get('info_error_long_text');
            $info_error_file_size = $this->get('session')->getFlashBag()->get('info_error_file_size');
            $info_error_file_bad_format = $this->get('session')->getFlashBag()->get('info_error_file_bad_format');
            $info_error_file = $this->get('session')->getFlashBag()->get('info_error_file');

            if(!empty($error_namegroup_1)){
                 return $this->render('private/create_group.html.twig', ["error_namegroup_1" => $error_namegroup_1[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($error_namegroup_2)){
                 return $this->render('private/create_group.html.twig', ["error_namegroup_2" => $error_namegroup_2[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($error_namegroup_3)){
                 return $this->render('private/create_group.html.twig', ["error_namegroup_3" => $error_namegroup_3[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($info_error_long_text)){
                 return $this->render('private/create_group.html.twig', ["info_error_long_text" => $info_error_long_text[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($info_error_file_size)){
                 return $this->render('private/create_group.html.twig', ["info_error_file_size" => $info_error_file_size[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($info_error_file_bad_format)){
                 return $this->render('private/create_group.html.twig', ["info_error_file_bad_format" => $info_error_file_bad_format[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }elseif(!empty($info_error_file)){
                 return $this->render('private/create_group.html.twig', ["info_error_file" => $info_error_file[0],"usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }else{
                return $this->render('private/create_group.html.twig', ["usersSettings" => $usersSettings,"userNick" => $userNick,"globalUserNick" => $userNick,"globalUserName" => $globalUserName,"globalIdUser" => $userId,"globalConversations" => $globalConversations,"globalMessages" => $globalMessages,]); 
            }
        }
    }

    /**
     * @Route("/g/{group_url}/edit", name="edit_group")
     *
     * @param $group_url
     *
     * @return Response
     */
    public function editGroup($group_url,Request $request,Security $security,SessionInterface $session)
    {
        return new Response();
    }

    /*
      FUKCJE WEWNĘTRZNE
    */

    /**
     * @Route("/system/search-group/{search_type}", name="search_group", requirements={"search_type" = "post|media|link"})
     *
     * @param $search_type
     *
     * @return Response
     */
    public function searchGroup( Request $request, Security $security,$search_type )
    {  
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $item = "";
            if($search_type=="post"){
                $item = "1";      
            }elseif($search_type=="media"){
                $item = "2";
            }elseif($search_type=="link"){
                $item = "3";
            }

            $output = '';

            $repo = $entityManager->getRepository(Groups::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.name LIKE :key')->setParameter('key', '%'.$_POST["query"].'%')
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            $output .= '<ul>';

            if(count($querry1) > 0){
                foreach($querry1 as $key => $value){
                    $groupId = $value->getId();
                    $groupName = $value->getName();
                    $groupUrl = $value->getUrl();

                    $countGroupsSwitch = $entityManager->getRepository(GroupsSwitch::class)->count(array('idGroup' => $groupId));

                    $output .= '<li>g/'.$groupUrl.' (<span class="item'.$item.'">'.$groupName.'</span>)<br><small>'.$countGroupsSwitch.' członków</small></li>';
                }
            }else{
                $output .= '<li>Brak wyników</li>';
            }

            $output .= '</ul>';
            return new Response($output);
        }else{
            return $this->redirectToRoute('submit_post');
        }
    }

    /**
     * @Route("/system/search/{type_search}", name="search", requirements={"type_search" = "group|user|hashtag|conversation"})
     *
     * @param $type_search
     *
     * @return Response
     */
    public function search($type_search,Request $request,Security $security,PaginatorInterface $paginator,SessionInterface $session)
    {  
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        if ($request->getMethod() == 'POST')
        {
            // entity manager
            $entityManager = $this->getDoctrine()->getManager();

            // get search words
            $words = $request->request->get('search');
            $url = $this->friendlyUrl($words);

            if($words == ""){return $this->redirectToRoute("public_stream");}

            if($type_search == 'group'){

                $repo = $entityManager->getRepository(Groups::class);
                $querry  = $repo->createQueryBuilder('q')
                    ->where('q.name LIKE :key')->setParameter('key', '%'.$words.'%')
                    ->orderBy('q.id', 'ASC')
                    ->getQuery();
                $querry = $querry->getResult();

                if(count($querry) > 0){
                    return $this->redirectToRoute('get_group', array('group_url' => $url));
                }else{
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_search', $info_text);
                    return $this->redirectToRoute("public_stream");
                }

            }elseif($type_search == 'user'){

                $user = $entityManager->getRepository(User::class)->findOneBy(array('nick' => $words));
                if($user){
                    $userNick = strtolower($user->getNick());
                    return $this->redirectToRoute('user_stream', array('user_nick' => $userNick));
                }else{
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_search', $info_text);
                    return $this->redirectToRoute("public_stream");
                }

            }elseif($type_search == 'conversation'){

                $user = $entityManager->getRepository(User::class)->findOneBy(array('nick' => $words));
                if($user){
                    $userIdB = strtolower($user->getId());

                    $conversationA = $entityManager->getRepository(Conversations::class)->findOneBy(array('idUserA' => $userId,'idUserB' => $userIdB));
                    if($conversationA){
                        $conversation = $conversationA->getConversation();
                    }else{
                        $conversationB = $entityManager->getRepository(Conversations::class)->findOneBy(array('idUserA' => $userIdB,'idUserB' => $userId));
                        if($conversationB){
                            $conversation = $conversationB->getConversation();
                        }else{

                            // check if user blocked / is blocked
                            $blocked = 0;
                            $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $userId,'idUserB' => $userIdB));
                            if($userBlocked){
                                $blocked = 1;
                            }else{
                                $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $userIdB,'idUserB' => $userId));
                                if($userBlocked2){
                                    $blocked = 1;
                                }else{
                                    $blocked = 0;
                                }
                            }
                            if($blocked == 1){
                                // info message
                                $info_text = true;
                                $this->addFlash('info_error_blocked', $info_text);
                                return $this->redirectToRoute("messages");
                            }

                            // rand unique id
                            $ran_id = rand(time(), 100000000);
                            // new conversation
                            $newConversation = new Conversations();
                            $newConversation->setIdUserA($userId); 
                            $newConversation->setIdUserB($userIdB); 
                            $newConversation->setConversation($ran_id);
                            $newConversation->setPosition(time());
                            $newConversation->setStatus(0);
                            $entityManager->persist($newConversation);
                            $entityManager->flush();

                            $conversationC = $entityManager->getRepository(Conversations::class)->findOneBy(array('idUserA' => $userId,'idUserB' => $userIdB));
                            if($conversationC){
                                $conversation = $conversationC->getConversation();
                            }else{
                                // info message
                                $info_text = true;
                                $this->addFlash('info_error_conversation', $info_text);
                                return $this->redirectToRoute("messages");
                            }
                        }
                    }

                    return $this->redirectToRoute('messages_conversation', array('conversation' => $conversation));
                }else{
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_search', $info_text);
                    return $this->redirectToRoute("messages");
                }

            }if($type_search == 'hashtag'){

                $repo = $entityManager->getRepository(HashtagSwitch::class);
                $querry  = $repo->createQueryBuilder('q')
                    ->where('q.tagName LIKE :key1')->setParameter('key1', '%'.$words.'%')
                    ->orWhere('q.tagUrl LIKE :key2')->setParameter('key2', '%'.$words.'%')
                    ->orderBy('q.id', 'DESC')
                    ->getQuery();
                $querry = $querry->getResult();

                if(count($querry) > 0){
                    return $this->redirectToRoute('get_hashtag', array('hashtag_url' => $url));
                }else{
                    // info message
                    $info_text = true;
                    $this->addFlash('info_error_search', $info_text);
                    return $this->redirectToRoute("public_stream");
                }

            }
        }else{
            return $this->redirectToRoute('public_stream');
        }
    }

    /**
     * @Route("/system/search-header-group", name="search_header_group")
     *
     * @return Response
     */
    public function searchHeaderGroup( Request $request, Security $security )
    {  
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $output = '';

            $repo = $entityManager->getRepository(Groups::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.name LIKE :key')->setParameter('key', '%'.$_POST["query"].'%')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(10)
                ->getQuery();
            $querry = $querry->getResult();

            $output .= '<ul>';

            if(count($querry) > 0){
                foreach($querry as $key => $value){
                    $groupId = $value->getId();
                    $groupName = $value->getName();
                    $groupUrl = $value->getUrl();

                    $countGroupsSwitch = $entityManager->getRepository(GroupUserWatching::class)->count(array('idGroup' => $groupId));

                    $output .= '<li>g/'.$groupUrl.' (<span class="item4">'.$groupName.'</span>)<br><small>'.$countGroupsSwitch.' członków</small></li>';
                }
            }else{
                $output .= '<li>Brak wyników</li>';
            }

            $output .= '</ul>';
            return new Response($output);
        }else{
            return $this->redirectToRoute('public_stream');
        }
    }

    /**
     * @Route("/system/search-header-user", name="search_header_user")
     *
     * @return Response
     */
    public function searchHeaderUser( Request $request, Security $security )
    {  
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $output = '';

            $repo = $entityManager->getRepository(User::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.nick LIKE :key1')->setParameter('key1', '%'.$_POST["query"].'%')
                ->orWhere('q.name LIKE :key2')->setParameter('key2', '%'.$_POST["query"].'%')
                ->orWhere('q.surname LIKE :key3')->setParameter('key3', '%'.$_POST["query"].'%')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(10)
                ->getQuery();
            $querry = $querry->getResult();

            $output .= '<ul>';

            if(count($querry) > 0){
                foreach($querry as $key => $value){
                    $userNick = $value->getNick();
                    $userUrl = strtolower($userNick);

                    $output .= '<li>u/'.$userUrl.' (<span class="item5">'.$userNick.'</span>)</li>';
                }
            }else{
                $output .= '<li>Brak wyników</li>';
            }

            $output .= '</ul>';
            return new Response($output);
        }else{
            return $this->redirectToRoute('public_stream');
        }
    }

    /**
     * @Route("/system/search-header-tag", name="search_header_tag")
     *
     * @return Response
     */
    public function searchHeaderTag( Request $request, Security $security )
    {  
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $output = '';

            $repo = $entityManager->getRepository(HashtagSwitch::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.tagName LIKE :key1')->setParameter('key1', '%'.$_POST["query"].'%')
                ->orWhere('q.tagUrl LIKE :key2')->setParameter('key2', '%'.$_POST["query"].'%')
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(10)
                ->getQuery();
            $querry = $querry->getResult();

            $array = [];
            foreach($querry as $key => $value){
                $tagName = $value->getTagName();
                if(in_array($tagName, $array)){}else{array_push($array, $tagName);}
            }
            
            $output .= '<ul>';

            if(count($array) > 0){
                foreach($array as $key){
                    $tagName = $key;
                    $tagUrl = $this->friendlyUrl($tagName);

                    $countHashtagSwitch = $entityManager->getRepository(HashtagSwitch::class)->count(array('tagName' => $tagName));

                    $output .= '<li>h/'.$tagUrl.' (<span class="item6">'.$tagName.'</span>)<br><small>'.$countHashtagSwitch.' postów</small></li>';
                }
            }else{
                $output .= '<li>Brak wyników</li>';
            }

            $output .= '</ul>';
            return new Response($output);
        }else{
            return $this->redirectToRoute('public_stream');
        }
    }

    /**
     * @Route("/system/search-header-conversation", name="search_header_conversation")
     *
     * @return Response
     */
    public function searchHeaderConversation( Request $request, Security $security )
    {  
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $output = '';

            $repo = $entityManager->getRepository(User::class);
            $querry  = $repo->createQueryBuilder('q')
                ->where('q.nick LIKE :key1')->setParameter('key1', '%'.$_POST["query"].'%')
                ->orWhere('q.name LIKE :key2')->setParameter('key2', '%'.$_POST["query"].'%')
                ->orWhere('q.surname LIKE :key3')->setParameter('key3', '%'.$_POST["query"].'%')
                ->andWhere('q.id != :key4')->setParameter('key4', $userId)
                ->orderBy('q.id', 'ASC')
                ->setMaxResults(10)
                ->getQuery();
            $querry = $querry->getResult();

            $output .= '<ul>';

            if(count($querry) > 0){
                foreach($querry as $key => $value){
                    $userNick = $value->getNick();
                    $userUrl = strtolower($userNick);

                    $output .= '<li>u/'.$userUrl.' (<span class="item7">'.$userNick.'</span>)</li>';
                }
            }else{
                $output .= '<li>Brak wyników</li>';
            }

            $output .= '</ul>';
            return new Response($output);
        }else{
            return $this->redirectToRoute('public_stream');
        }
    }

    /**
     * @Route("/system/rating/{post_id}/{type}", name="rating_system", requirements={"post_id"="\d+", "type" = "like|unlike|dislikecomment|likecomment"})
     *
     * @param $post_id
     * @param $type
     *
     * @return Response
     */
    public function ratingSystem(Security $security, $post_id, $type)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();
        // get rating type
        if($type == "unlike"){
            $rating_type = 0;
            $typ = 0;
        }elseif ($type == "like"){
            $rating_type = 1;
            $typ = 0;
        }elseif ($type == "dislikecomment"){
            $rating_type = 0;
            $typ = 1;
        }elseif ($type == "likecomment"){
            $rating_type = 1;
            $typ = 1;
        }
        // choose what to do
        if($typ == 0){
            // check if user voted
            $rating = $entityManager->getRepository(RatingPostSwitch::class)->findOneBy(array(
                'idUser' => $userId,
                'idPost' => $post_id,
            ));
            if (!$rating){
                // create new vote
                $saveRating = new RatingPostSwitch();
                $saveRating->setIdUser($userId); 
                $saveRating->setIdPost($post_id); 
                $saveRating->setRating($rating_type);
                $saveRating->setStatusNotification(0);
                $entityManager->persist($saveRating);
                $entityManager->flush();

                // get number all voted
                $allRating = $entityManager->getRepository(RatingPostSwitch::class)->count(array(
                    'idPost' => $post_id,
                    'rating' => $rating_type,
                ));
                if(!$allRating){
                    throw $this->createNotFoundException('PagesController > ratingSystem > Line: 495');
                }else{
                    $var = $allRating;

                    // update number rating in post data
                    $post = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $post_id));

                    if(!$post){
                        throw $this->createNotFoundException('PagesController > ratingSystem > Line: 508');
                    }else{
                        if ($type == "unlike")
                            $post->setNumberDislikes($var);
                        elseif ($type == "like")
                            $post->setNumberLikes($var);

                        // update quantity in database
                        $entityManager->persist($post);
                        $entityManager->flush();  
                    }
                }
            }else{
                $var = "none";
            }
        }elseif($typ == 1){
            // check if user voted
            $rating = $entityManager->getRepository(RatingCommentSwitch::class)->findOneBy(array(
                'idUser' => $userId,
                'idComment' => $post_id,
            ));
            if (!$rating)
            {
                // create new vote
                $saveRating = new RatingCommentSwitch();
                $saveRating->setIdUser($userId); 
                $saveRating->setIdComment($post_id); 
                $saveRating->setRating($rating_type);
                $saveRating->setStatusNotification(0);
                $entityManager->persist($saveRating);
                $entityManager->flush();
                // get number all voted
                $allRatingDislike = $entityManager->getRepository(RatingCommentSwitch::class)->count(array(
                    'idComment' => $post_id,
                    'rating' => 0,
                ));
                $allRatingLike = $entityManager->getRepository(RatingCommentSwitch::class)->count(array(
                    'idComment' => $post_id,
                    'rating' => 1,
                ));
                $var = $allRatingLike - $allRatingDislike;
            }else{
                $var = "none";
            }
        }
        // if "none" then user voted
        return new Response($var);
    }

    /**
     * @Route("/system/follow/{id_user_b}/{type}", name="follow_system", requirements={"id_user_b"="\d+", "type" = "\d+"})
     *
     * @param $id_user_b
     * @param $type
     *
     * @return Response
     */
    public function followSystem(Security $security, $id_user_b, $type)
    {
        // stay if logged in
        if($this->getUser()){
            $id_user_a = $security->getUser()->getId();
        }else{return $this->loginReset();}
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();
        // get rating type
        if($type == 1){
            $typ = 0;
        }elseif ($type == 2){
            $typ = 0;
        }elseif ($type == 3){
            $typ = 1;
        }elseif ($type == 4){
            $typ = 1;
        }
        // choose what to do
        if($typ == 0){
            // check if user voted
            $follow = $entityManager->getRepository(WatchingUsersSwitch::class)->findOneBy(array(
                'idUserA' => $id_user_a,   // user który obserwuje
                'idUserB' => $id_user_b,   // user który jest obserwowany
            ));
            if (!$follow){
                // create new vote
                $saveRating = new WatchingUsersSwitch();
                $saveRating->setIdUserA($id_user_a); 
                $saveRating->setIdUserB($id_user_b);
                $saveRating->setStatusNotification(0);
                $entityManager->persist($saveRating);
                $entityManager->flush();

                // get number all voted
                $allFollow = $entityManager->getRepository(WatchingUsersSwitch::class)->count(array(
                    'idUserA' => $id_user_a,
                    'idUserB' => $id_user_b,
                ));
                if(!$allFollow){
                    throw $this->createNotFoundException('PagesController > followSystem > Line: 1196');
                }
            }else{
                $entityManager->remove($follow);
                $entityManager->flush();
            }

            $allFollow = $entityManager->getRepository(WatchingUsersSwitch::class)->count(array(
                'idUserA' => $id_user_a,
                'idUserB' => $id_user_b,
            ));
            $var = $allFollow;

        }elseif($typ == 1){
            // check if user voted
            $follow = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array(
                'idUserA' => $id_user_a,   // user który blokuje
                'idUserB' => $id_user_b,   // user który jest blokowany
            ));
            if (!$follow){
                // create new vote
                $saveRating = new BlockedUsersSwitch();
                $saveRating->setIdUserA($id_user_a); 
                $saveRating->setIdUserB($id_user_b);
                $saveRating->setStatusNotification(0);
                $entityManager->persist($saveRating);
                $entityManager->flush();

                // get number all voted
                $allFollow = $entityManager->getRepository(BlockedUsersSwitch::class)->count(array(
                    'idUserB' => $id_user_b,
                ));
                if(!$allFollow){
                    throw $this->createNotFoundException('PagesController > followSystem > Line: 1196');
                }
            }else{
                $entityManager->remove($follow);
                $entityManager->flush();
            }

            $var = "none";
        }
        // return
        return new Response($var);
    }

    /**
     * @Route("/system/delete-comment/{comment_id}", name="delete_comment", requirements={"comment_id"="\d+"})
     *
     * @param $comment_id
     *
     * @return Response
     */
    public function deleteComment(Request $request, $comment_id)
    {
        // stay if logged in
        if ($this->getUser()){}else{return $this->loginReset();}
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();
        $singleComment = $entityManager->getRepository(PostsComments::class)->findOneBy(array('id' => $comment_id));
        if(!$singleComment){
            $var = "none";
        }else{
            $singleComment->setContent("Komentarz został usunięty przez użytkownika");
            $entityManager->persist($singleComment);
            $entityManager->flush();
            $var = "ok";
        }
        return new Response($var);
    }

    /**
     * @Route("/system/notification/", name="notification_system")
     *
     * @return Response
     */
    public function notificationSystem(Security $security)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}
        
        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        $output = '';
        $count = 0;
        $array = []; 

        if(isset($_POST["view"])){

            // SYTUACJA 1 - Ktoś odpowiedział w komentarzu zalogowanemu userowi "Użytkownik X odpowiedział na Twój komentarz."
            $repo = $entityManager->getRepository(PostsComments::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.statusNotification1 = :status1')->setParameter('status1', 0)
                ->andWhere('q.statusNotification2 = :status2')->setParameter('status2', 0)
                ->andWhere('q.idParent != :idParent')->setParameter('idParent', 'NULL')
                ->andWhere('q.idParent2 = :idParent2')->setParameter('idParent2', $userId)
                ->andWhere('q.idAuthor != :idAuthor')->setParameter('idAuthor', $userId)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){
                 
                $idComment = $value->getId();
                $idPost = $value->getIdPost();          
                $idAuthor = $value->getIdAuthor();

                // get post title to create url
                $getPostTitle = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $idPost));
                $postUrl = $getPostTitle->getPostUrl(); 

                // get user nick
                $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idAuthor));
                $userNick = $getUserNick->getNick();

                // chceck if add user name and suername to nick
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idAuthor));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $getUserNick->getName();
                    $userSurname = $getUserNick->getSurname();
                    $userNick = $userName . ' ' . $userSurname;
                }

                $count++;
                $output .= '<a id="'.$idComment.'-c-2" class="text-light notification" href="/a/'.$idPost.'-'.$postUrl.'#comment_'.$idComment.'">Użytkownik '.$userNick.' odpowiedział na Twój komentarz.</a>';
            
                array_push($array, $idComment);
            }

            // SYTUACJA 2 - Zalogowanemu autorowi postu ktoś napisał komentarz "Użytkownik X skomentował Twój wpis"
            $repo = $entityManager->getRepository(Posts::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.idAuthor = :id')->setParameter('id', $userId)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){
                                         
                $idPost = $value->getId();

                // get post title to create url
                $getPostTitle = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $idPost));
                $postUrl = $getPostTitle->getPostUrl(); 

                $repo = $entityManager->getRepository(PostsComments::class);
                $querry2  = $repo->createQueryBuilder('q')
                    ->where('q.idPost = :idPost')->setParameter('idPost', $idPost)
                    ->andWhere('q.statusNotification1 = :status1')->setParameter('status1', 0)
                    ->andWhere('q.statusNotification2 = :status2')->setParameter('status2', 0)
                    ->andWhere('q.idAuthor != :idAuthor')->setParameter('idAuthor', $userId)
                    ->orderBy('q.id', 'ASC')
                    ->getQuery();
                $querry2 = $querry2->getResult();

                foreach($querry2 as $key => $value){

                    $idComment = $value->getId();
                    
                    if(in_array($idComment, $array)){
                    }else{
                        $idAuthor = $value->getIdAuthor();
                        $count++;

                        // get user nick
                        $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idAuthor));
                        $userNick = $getUserNick->getNick();

                        // chceck if add user name and suername to nick
                        $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idAuthor));
                        $userSettingsNS = $userSettings->getNickShow();
                        if($userSettingsNS == 1){
                            $userName = $getUserNick->getName();
                            $userSurname = $getUserNick->getSurname();
                            $userNick = $userName . ' ' . $userSurname;
                        }

                        $output .= '<a id="'.$idComment.'-c-1" class="text-light notification" href="/a/'.$idPost.'-'.$postUrl.'#comment_'.$idComment.'">Użytkownik '.$userNick.' skomentował Twój wpis.</a>';
                    }
                }
            }

            // SYTAUCJA 3 - Zalogowanemu autorowi postu ktoś ocenił wpis
            $repo = $entityManager->getRepository(Posts::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.idAuthor = :id')->setParameter('id', $userId)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){
                                         
                $idPost = $value->getId();

                $repo = $entityManager->getRepository(RatingPostSwitch::class);
                $querry2  = $repo->createQueryBuilder('q')
                    ->where('q.idPost = :idPost')->setParameter('idPost', $idPost)
                    ->andWhere('q.idUser != :idUser')->setParameter('idUser', $userId)
                    ->andWhere('q.statusNotification = :status')->setParameter('status', 0)
                    ->orderBy('q.id', 'ASC')
                    ->getQuery();
                $querry2 = $querry2->getResult();

                foreach($querry2 as $key => $value){

                    $idRating = $value->getId();
                    $idPost = $value->getIdPost();
                    $idAuthor = $value->getIdUser();
                    $rating = $value->getRating();

                    // get post title to create url
                    $getPostTitle = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $idPost));
                    $postUrl = $getPostTitle->getPostUrl(); 

                    // get rating type
                    $ratingText = '';
                    if($rating == 0){
                        $ratingText = 'negatywnie';
                    }else{
                        $ratingText = 'pozytywnie';
                    }

                    // get user nick
                    $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idAuthor));
                    $userNick = $getUserNick->getNick();

                    // chceck if add user name and suername to nick
                    $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idAuthor));
                    $userSettingsNS = $userSettings->getNickShow();
                    if($userSettingsNS == 1){
                        $userName = $getUserNick->getName();
                        $userSurname = $getUserNick->getSurname();
                        $userNick = $userName . ' ' . $userSurname;
                    }

                    $count++;
                    $output .= '<a id="'.$idRating.'-r-1" class="text-light notification" href="/a/'.$idPost.'-'.$postUrl.'">Użytkownik '.$userNick.' ocenił '.$ratingText.' Twój wpis.</a>';

                }
            }

            // SYTAUCJA 4 - Zalogowanemu użytkownikowi ktoś ocenił komentarz
            $repo = $entityManager->getRepository(PostsComments::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.idAuthor = :id')->setParameter('id', $userId)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){

                $idComment = $value->getId();
                $idPost = $value->getIdPost();
                
                // get post title to create url
                $getPostTitle = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $idPost));
                if($getPostTitle){
                    $postUrl = $getPostTitle->getPostUrl(); 
                }

                $repo = $entityManager->getRepository(RatingCommentSwitch::class);
                $querry2  = $repo->createQueryBuilder('q')
                    ->where('q.idComment = :idComment')->setParameter('idComment', $idComment)
                    ->andWhere('q.idUser != :idUser')->setParameter('idUser', $userId)
                    ->andWhere('q.statusNotification = :status')->setParameter('status', 0)
                    ->orderBy('q.id', 'ASC')
                    ->getQuery();
                $querry2 = $querry2->getResult();

                foreach($querry2 as $key => $value){

                    $idRating = $value->getId();             
                    $idAuthor = $value->getIdUser();
                    $rating = $value->getRating();

                    // get rating type
                    $ratingText = '';
                    if($rating == 0){
                        $ratingText = 'negatywnie';
                    }else{
                        $ratingText = 'pozytywnie';
                    }

                    // get user nick
                    $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idAuthor));
                    $userNick = $getUserNick->getNick();

                    // chceck if add user name and suername to nick
                    $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idAuthor));
                    $userSettingsNS = $userSettings->getNickShow();
                    if($userSettingsNS == 1){
                        $userName = $getUserNick->getName();
                        $userSurname = $getUserNick->getSurname();
                        $userNick = $userName . ' ' . $userSurname;
                    }

                    $count++;
                    $output .= '<a id="'.$idRating.'-r-2" class="text-light notification" href="/a/'.$idPost.'-'.$postUrl.'#comment_'.$idComment.'"">Użytkownik '.$userNick.' ocenił '.$ratingText.' Twój komentarz.</a>';

                }
            }

            // SYTAUCJA 5 - Użytkownik obserwuje zalogowanego użytkownika
            $repo = $entityManager->getRepository(WatchingUsersSwitch::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.idUserB = :id')->setParameter('id', $userId)
                ->andWhere('q.statusNotification = :status')->setParameter('status', 0)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){
                $idFollow = $value->getId();
                $idUserA = $value->getIdUserA();

                // get user nick
                $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idUserA));
                $userNick = $getUserNick->getNick();
                $urlUserNick = strtolower($userNick);

                // chceck if add user name and suername to nick
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idUserA));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $getUserNick->getName();
                    $userSurname = $getUserNick->getSurname();
                    $userNick = $userName . ' ' . $userSurname;
                }

                $count++;
                $output .= '<a id="'.$idFollow.'-u-1" class="text-light notification" href="/u/'.$urlUserNick.'">Użytkownik '.$userNick.' obserwuje Twój profil.</a>';
            }

            // SYTAUCJA 6 - Właściciel grupy usunoł post zalogowanego użytkownika ze swojej grupy
            $repo = $entityManager->getRepository(GroupDeletePostNotification::class);
            $querry1  = $repo->createQueryBuilder('q')
                ->where('q.idUserB = :id')->setParameter('id', $userId)
                ->andWhere('q.statusNotification = :status')->setParameter('status', 0)
                ->orderBy('q.id', 'ASC')
                ->getQuery();
            $querry1 = $querry1->getResult();

            foreach($querry1 as $key => $value){
                $idDelete = $value->getId();
                $idUserA = $value->getIdUserA();
                $idPost = $value->getIdPost();
                $idGroup = $value->getIdGroup();
                
                // get user nick
                $getUserNick = $entityManager->getRepository(User::class)->findOneBy(array('id' => $idUserA));
                $userNick = $getUserNick->getNick();
                $urlUserNick = strtolower($userNick);

                // chceck if add user name and suername to nick
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $idUserA));
                $userSettingsNS = $userSettings->getNickShow();
                if($userSettingsNS == 1){
                    $userName = $getUserNick->getName();
                    $userSurname = $getUserNick->getSurname();
                    $userNick = $userName . ' ' . $userSurname;
                }

                // get url post
                $getPost = $entityManager->getRepository(Posts::class)->findOneBy(array('id' => $idPost));
                $postUrl = $getPost->getPostUrl();

                // get name group
                $getGroup = $entityManager->getRepository(Groups::class)->findOneBy(array('id' => $idGroup));
                $groupName = $getGroup->getName();

                $count++;
                $output .= '<a id="'.$idDelete.'-d-1" class="text-light notification" href="/a/'.$idPost.'-'.$postUrl.'">Założyciel grupy "'.$groupName.'" usunoł Twój wpis.</a>';

            }

            // ZMIANA STATUSU PO KLIKNIĘCIU
            if($_POST["view"] != '')
            {
                $myVal = $_POST["view"];
                $myVal = explode("-", $myVal);

                $id = $myVal[0];
                $situation = $myVal[1];
                $notificationType = $myVal[2];

                if($situation == 'c'){
                    $comment = $entityManager->getRepository(PostsComments::class)->findOneBy(array('id' => $id));
                    if($notificationType == 1){
                        $comment->setStatusNotification1(1);
                    }elseif($notificationType == 2){
                        $comment->setStatusNotification2(1);
                    }
                    $entityManager->persist($comment);
                    $entityManager->flush();
                }elseif($situation == 'r'){
                    if($notificationType == 1){
                        $getRatingPostSwitch = $entityManager->getRepository(RatingPostSwitch::class)->findOneBy(array('id' => $id));
                        $getRatingPostSwitch->setStatusNotification(1);
                        $entityManager->persist($getRatingPostSwitch);
                        $entityManager->flush();
                    }elseif($notificationType == 2){
                        $getRatingCommentSwitch = $entityManager->getRepository(RatingCommentSwitch::class)->findOneBy(array('id' => $id));
                        $getRatingCommentSwitch->setStatusNotification(1);
                        $entityManager->persist($getRatingCommentSwitch);
                        $entityManager->flush();
                    }
                }elseif($situation == 'u'){
                    if($notificationType == 1){
                        $getRatingCommentSwitch = $entityManager->getRepository(WatchingUsersSwitch::class)->findOneBy(array('id' => $id));
                        $getRatingCommentSwitch->setStatusNotification(1);
                        $entityManager->persist($getRatingCommentSwitch);
                        $entityManager->flush();
                    }
                }elseif($situation == 'd'){
                    if($notificationType == 1){
                        $getRatingCommentSwitch = $entityManager->getRepository(GroupDeletePostNotification::class)->findOneBy(array('id' => $id));
                        $getRatingCommentSwitch->setStatusNotification(1);
                        $entityManager->persist($getRatingCommentSwitch);
                        $entityManager->flush();
                    }
                }
            }
        }

        if($count == 0){
            $count = '';
            $output = '<a class="text-light disabled" href="#">Brak nowych powiadomień</a>';
        }

        $data = array(
            'notification' => $output,
            'unseen_notification' => $count
        );

        return new JsonResponse(
            [
                'notification' => $output,
                'unseen_notification' => $count,
            ]
        );
    }

    /**
     * @Route("/system/report-user", name="report_user")
     *
     * @return Response
     */
    public function reportUser(Security $security)
    {
        // stay if logged in
        if ($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $var = explode("/", $_POST["query"]);
            $author_id = $var[0];
            $post_id = $var[1];

            $report = $entityManager->getRepository(ReportUser::class)->findOneBy(array('idUserA' => $userId,'idUserB' => $author_id,'idPost' => $post_id));
            if(!$report){

                $saveReport = new ReportUser();
                $saveReport->setIdUserA($userId);
                $saveReport->setIdUserB($author_id);
                $saveReport->setIdPost($post_id);
                $entityManager->persist($saveReport);
                $entityManager->flush();

            }

            return new Response();

        }else{
            return $this->loginReset();
        }
    }

    /**
     * @Route("/system/delete-post-group", name="delete_post_group")
     *
     * @return Response
     */
    public function deletePostGroup(Security $security)
    {
        // stay if logged in
        if ($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $var = explode("/", $_POST["query"]);
            $author_id = $var[0];
            $post_id = $var[1];

            $post = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $author_id,'id' => $post_id));
            if($post){
                // pobierz id grupy
                $group_id = $post->getIdGroup();
                // usunięcie postu z grupy, ale ogólnie zostaje
                $post->setIdGroup(0);
                $entityManager->persist($post);
                $entityManager->flush();
                // usunięcie skojarzenia grupy z postem
                $getGroup = $entityManager->getRepository(GroupsSwitch::class)->findOneBy(array('idPost' => $post_id));
                if($getGroup){
                    $entityManager->remove($getGroup);
                    $entityManager->flush();
                }
                // zapis informacji do bazy danych i tym samym wysłanie powiadomienia dla autora tego postu
                $saveInfo = new GroupDeletePostNotification();
                $saveInfo->setIdUserA($userId);
                $saveInfo->setIdUserB($author_id);
                $saveInfo->setIdPost($post_id);
                $saveInfo->setIdGroup($group_id);
                $saveInfo->setStatusNotification(0);
                $entityManager->persist($saveInfo);
                $entityManager->flush();
            }

            return new Response();

        }else{
            return $this->loginReset();
        }
    }

    /**
     * @Route("/system/delete-post-user", name="delete_post_user")
     *
     * @return Response
     */
    public function deletePostUser(Security $security)
    {
        // stay if logged in
        if ($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($_POST["query"])){

            $post_id = $_POST["query"];

            $post = $entityManager->getRepository(Posts::class)->findOneBy(array('idAuthor' => $userId,'id' => $post_id));
            if(!$post){

                // usunięcie postu
                // usunięcie obrazka/wideo
                // usunięcie komentarzy

                // lub wymazanie informacji w bazie odnośnie samego postu i zostawienie takiego trupa jak w przypadku komentarzy

            }

            return new Response();

        }else{
            return $this->loginReset();
        }
    }

    // check data inputs
    private function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        $data = str_replace("&quot;","''",$data);;
        return $data;
    }

    // get post list
    private function getPostsList($postType, $var)
    {
        $entityManager = $this->getDoctrine()->getManager();

        if($postType == 'watched'){         // posty 'obserwowane'

            // Pobranie wszystkich postów publicznych
            $repo = $entityManager->getRepository(Posts::class);
            $result  = $repo->createQueryBuilder('q')
                ->where('q.availability = :availability')->setParameter('availability', 0)
                ->orderBy('q.id', 'desc')
                ->getQuery()
                ->getResult();

            // Przegląd tablicy z obiektem postów
            foreach($result as $key=>$value){
                
                /* 
                    W posiecie [1] to znaczy że jest załączony, że post zawiera np spoiler.
                    W ustawieniach usera jeśli [1] to znaczy ze ma wyświetlać takie posty ze spoilerami.
                */

                // Pobranie ustawień użytkownika o SPOILER i NSFW.
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId())); 
                $userSettingSpoiler = $userSettings->getSpoiler();
                $userSettingNsfw = $userSettings->getNsfw();

                // Ustawienia postu
                $postSpoiler = $value->getSpoiler();
                $postNsfw = $value->getNsfw();

                if( (($postSpoiler == 1) and ($userSettingSpoiler == 0)) or (($postNsfw == 1) and ($userSettingNsfw == 0))){unset($result[$key]);}

                /*
                    Wyświetlanie wpisów usera którą obserwuje user chyba że jest zablokowany to nie wyświetli
                */

                // Pobranie id autora postu
                $postAuthor = $value->getIdAuthor();

                /* 
                    Czy autorem postu jest zalogowany user 
                */
                if($postAuthor != $this->getUser()->getId()){

                    // Sprawdź czy user obserwuje autora postu
                    $userWatching = $entityManager->getRepository(WatchingUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                    if(!$userWatching){

                        /*
                            Wyświetlanie grupy którą obserwuje user
                        */

                        // Pobranie grupy postu
                        $postGroup = $value->getIdGroup();

                        if($postGroup != 0){
                            // Sprawdź czy user pulubił daną grupę
                            $userGroup = $entityManager->getRepository(GroupUserWatching::class)->findOneBy(array('idUser' => $this->getUser()->getId(),'idGroup' => $postGroup));
                            if(!$userGroup){unset($result[$key]);}   
                        }else{unset($result[$key]);}

                    }

                    // Sprawdź czy user zablokował autora postu
                    $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                    if($userBlocked){unset($result[$key]);} 

                    $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $postAuthor,'idUserB' => $this->getUser()->getId()));
                    if($userBlocked2){unset($result[$key]);} 

                }else{unset($result[$key]);}

            }

        }else if($postType == 'public'){    // posty 'publiczne'

            // Pobranie wszystkich postów publicznych
            $repo = $entityManager->getRepository(Posts::class);
            $result  = $repo->createQueryBuilder('q')
                ->where('q.availability = :availability')->setParameter('availability', 0)
                //->orderBy('q.numberLikes - q.numberDislikes', 'desc')
                ->orderBy('q.id', 'desc')
                ->getQuery()
                ->getResult();

            // Przegląd tablicy z obiektem postów
            foreach($result as $key=>$value){
                
                /* 
                    W posiecie [1] to znaczy że jest załączony, że post zawiera np spoiler.
                    W ustawieniach usera jeśli [1] to znaczy ze ma wyświetlać takie posty ze spoilerami.
                */

                // Pobranie ustawień użytkownika o SPOILER i NSFW.
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId())); 
                $userSettingSpoiler = $userSettings->getSpoiler();
                $userSettingNsfw = $userSettings->getNsfw();

                // Ustawienia postu
                $postSpoiler = $value->getSpoiler();
                $postNsfw = $value->getNsfw();

                if( (($postSpoiler == 1) and ($userSettingSpoiler == 0)) or (($postNsfw == 1) and ($userSettingNsfw == 0))){unset($result[$key]);}

                /*
                    Wyświetlanie wpisów usera którą obserwuje user chyba że jest zablokowany to nie wyświetli
                */

                // Pobranie id autora postu
                $postAuthor = $value->getIdAuthor();

                /* 
                    Czy autorem postu jest zalogowany user 
                */
                if($postAuthor != $this->getUser()->getId()){

                    // Sprawdź czy user zablokował autora postu
                    $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                    if($userBlocked){unset($result[$key]);}

                    $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $postAuthor,'idUserB' => $this->getUser()->getId()));
                    if($userBlocked2){unset($result[$key]);}

                }

            }

        }else if($postType == 'profil'){    // posty 'profil'
        
            // Pobranie wszystkich postów publicznych
            $repo = $entityManager->getRepository(Posts::class);
            $result  = $repo->createQueryBuilder('q')
                ->orderBy('q.id', 'desc')
                ->getQuery()
                ->getResult();

            // Przegląd tablicy z obiektem postów
            foreach($result as $key=>$value){
                
                /* 
                    Czy autorem postu jest user w profilu
                */

                // Pobranie id autora postu
                $postAuthor = $value->getIdAuthor();

                if($postAuthor == $var){

                    /* 
                        W posiecie [1] to znaczy że jest załączony, że post zawiera np spoiler.
                        W ustawieniach usera jeśli [1] to znaczy ze ma wyświetlać takie posty ze spoilerami.
                    */

                    // Pobranie ustawień użytkownika o SPOILER i NSFW.
                    $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId())); 
                    $userSettingSpoiler = $userSettings->getSpoiler();
                    $userSettingNsfw = $userSettings->getNsfw();

                    // Ustawienia postu
                    $postSpoiler = $value->getSpoiler();
                    $postNsfw = $value->getNsfw();

                    if( (($postSpoiler == 1) and ($userSettingSpoiler == 0)) or (($postNsfw == 1) and ($userSettingNsfw == 0))){unset($result[$key]);}

                    /*
                        Czy profil przegląda właściciel czy obca osoba
                    */

                    if($postAuthor != $this->getUser()->getId()){

                        // Pobranie wartości dostepności postu
                        $postAvailability = $value->getAvailability();

                        if($postAvailability != 0){unset($result[$key]);}

                    }

                    /*
                        Wyświetlanie wpisów usera chyba że jest zablokowany to nie wyświetli
                    */

                    // Sprawdź czy user zablokował autora postu
                    $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                    if($userBlocked){unset($result[$key]);}  

                    $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $postAuthor,'idUserB' => $this->getUser()->getId()));
                    if($userBlocked2){unset($result[$key]);}

                }else{unset($result[$key]);}

            }

        }else if($postType == 'group'){     // posty w wyszikiwarce 'grupy'

            // Pobranie wszystkich postów publicznych
            $repo = $entityManager->getRepository(Posts::class);
            $result  = $repo->createQueryBuilder('q')
                ->where('q.idGroup = :idGroup')->setParameter('idGroup', $var)
                ->andWhere('q.availability = :availability')->setParameter('availability', 0)
                ->orderBy('q.id', 'desc')
                ->getQuery()
                ->getResult();

            // Przegląd tablicy z obiektem postów
            foreach($result as $key=>$value){

                /* 
                    W posiecie [1] to znaczy że jest załączony, że post zawiera np spoiler.
                    W ustawieniach usera jeśli [1] to znaczy ze ma wyświetlać takie posty ze spoilerami.
                */

                // Pobranie ustawień użytkownika o SPOILER i NSFW.
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId())); 
                $userSettingSpoiler = $userSettings->getSpoiler();
                $userSettingNsfw = $userSettings->getNsfw();

                // Ustawienia postu
                $postSpoiler = $value->getSpoiler();
                $postNsfw = $value->getNsfw();

                if( (($postSpoiler == 1) and ($userSettingSpoiler == 0)) or (($postNsfw == 1) and ($userSettingNsfw == 0))){unset($result[$key]);}

                /*
                    Wyświetlanie grupy którą obserwuje user
                */

                // Pobranie grupy postu
                $postGroup = $value->getIdGroup();

                if($postGroup != 0){
                    
                    /*
                        Wyświetlanie wpisów usera którą obserwuje user chyba że jest zablokowany to nie wyświetli
                    */

                    // Pobranie id autora postu
                    $postAuthor = $value->getIdAuthor();

                    /* 
                        Czy autorem postu jest zalogowany user 
                    */
                    if($postAuthor != $this->getUser()->getId()){

                        // Sprawdź czy user zablokował autora postu
                        $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                        if($userBlocked){unset($result[$key]);} 

                        $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $postAuthor,'idUserB' => $this->getUser()->getId()));
                        if($userBlocked2){unset($result[$key]);} 

                    }
                    
                }
                else
                {
                    unset($result[$key]);
                }

            }

            //echo '<pre>';
            //echo print_r($result);
            //echo '</pre>';

        }else if($postType == 'hashtag'){   // posty w wyszikiwarce 'hashtag'

            // Pobranie wszystkich postów publicznych
            $repo = $entityManager->getRepository(Posts::class);
            $result  = $repo->createQueryBuilder('q')
                ->where('q.id IN (:id)')->setParameter('id', $var)
                ->andWhere('q.availability = :availability')->setParameter('availability', 0)
                ->orderBy('q.id', 'desc')
                ->getQuery()
                ->getResult();

            // Przegląd tablicy z obiektem postów
            foreach($result as $key=>$value){

                /* 
                    W posiecie [1] to znaczy że jest załączony, że post zawiera np spoiler.
                    W ustawieniach usera jeśli [1] to znaczy ze ma wyświetlać takie posty ze spoilerami.
                */

                // Pobranie ustawień użytkownika o SPOILER i NSFW.
                $userSettings = $entityManager->getRepository(Settings::class)->findOneBy(array('idUser' => $this->getUser()->getId())); 
                $userSettingSpoiler = $userSettings->getSpoiler();
                $userSettingNsfw = $userSettings->getNsfw();

                // Ustawienia postu
                $postSpoiler = $value->getSpoiler();
                $postNsfw = $value->getNsfw();

                if( (($postSpoiler == 1) and ($userSettingSpoiler == 0)) or (($postNsfw == 1) and ($userSettingNsfw == 0))){unset($result[$key]);}

                /*
                    Wyświetlanie wpisów usera którą obserwuje user chyba że jest zablokowany to nie wyświetli
                */

                // Pobranie id autora postu
                $postAuthor = $value->getIdAuthor();

                /* 
                    Czy autorem postu jest zalogowany user 
                */
                if($postAuthor != $this->getUser()->getId()){

                    // Sprawdź czy user zablokował autora postu
                    $userBlocked = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $this->getUser()->getId(),'idUserB' => $postAuthor));
                    if($userBlocked){unset($result[$key]);} 

                    $userBlocked2 = $entityManager->getRepository(BlockedUsersSwitch::class)->findOneBy(array('idUserA' => $postAuthor,'idUserB' => $this->getUser()->getId()));
                    if($userBlocked2){unset($result[$key]);} 

                }

            }

        }

        return $result;
    }

    /**
     * @Route("/system/join-the-group/{group}", name="join_the_group", requirements={"group"="\d+"})
     *
     * @param $group
     *
     * @return Response
     */
    public function joinTheGroup(Request $request,Security $security,$group)
    {
        // stay if logged in
        if($this->getUser()){$userId = $security->getUser()->getId();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // check if user follow this group
        $getGroup = $entityManager->getRepository(GroupUserWatching::class)->findOneBy(array('idGroup' => $group, 'idUser' => $userId));
        if($getGroup){
            $entityManager->remove($getGroup);
            $entityManager->flush();

            $var = 1;
        }else{
            $saveJoin = new GroupUserWatching();
            $saveJoin->setIdGroup($group);
            $saveJoin->setIdUser($userId);
            $entityManager->persist($saveJoin);
            $entityManager->flush();

            $var = 0;
        }

        return new Response($var);
    }

    /**
     * @Route("/send-activation-link", name="send_activation_link")
     *
     * @return Response
     */
    public function sendActivationLink(Request $request,Security $security,\Swift_Mailer $mailer)
    {
        // stay if logged in
        if($this->getUser()){$userEmail = $security->getUser()->getUsername();}else{return $this->loginReset();}

        // entity manager
        $entityManager = $this->getDoctrine()->getManager();

        // get mail from token
        $user_token = $entityManager->getRepository(UserToken::class)->findOneBy(array('email' => $userEmail,'active' => 0));
        if($user_token){
            
            $token = $user_token->getToken();

            // send email
            $message = (new \Swift_Message('Twoje konto zostało utworzone!'))
                ->setFrom('no-reply@azilla.pl')
                ->setTo($userEmail)
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

            // info message
            $info_text = true;
            $this->addFlash('info_send_token', $info_text);
            return $this->redirectToRoute("settings");

        }else{

            // info message
            $info_text = true;
            $this->addFlash('info_error_token', $info_text);
            return $this->redirectToRoute("settings");

        }
    }

    private function base64Encode($input){return strtr(base64_encode($input), '+/=', '._-');}

    private function base64Decode($input){return base64_decode(strtr($input, '._-', '+/='));}

    private function hashFunction($input){$token = password_hash($input, PASSWORD_BCRYPT);return strtr($token, '.=/', '---');}

    // format yrl do przyjaznych linków
    private function removePolishCharacters($variable){
        $aReplacePL = array('ą' => 'a', 'ę' => 'e', 'ś' => 's', 'ć' => 'c', 'ó' => 'o', 'ń' => 'n', 'ż' => 'z', 'ź' => 'z', 'ł' => 'l', 'Ą' => 'A', 'Ę' => 'E', 'Ś' => 'S', 'Ć' => 'C', 'Ó' => 'O', 'Ń' => 'N', 'Ż' => 'Z', 'Ź' => 'Z', 'Ł' => 'L');
        return str_replace(array_keys($aReplacePL), array_values($aReplacePL), $variable);
    }
    private function friendlyUrl($variable){
        $variable = $this->removePolishCharacters($variable);
        $variable = strtolower($variable);
        $variable = str_replace(' ', '-', $variable);
        $variable = preg_replace('/[^0-9a-z\-]+/', '', $variable);
        $variable = preg_replace('/[\-]+/', '-', $variable);
        $variable = trim($variable, '-');
        return $variable;  
    }

    // logout user
    private function loginReset(){
        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();
        return $this->redirectToRoute('security_login');
    }
    private function loginBan($ban){
        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();
        if($ban == 1){
            $info_text = 'Twoje konto zostało wyłączone na stałe!';
        }else{
            $info_text = 'Twoje konto zostało wyłączone do '.$ban;
        }
        $this->addFlash('error_ban_1', $info_text);
        return $this->redirectToRoute("security_login");
    }
}