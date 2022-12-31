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

/* session */
if(!isset($_SESSION)){session_start();} 
$session = new Session(new PhpBridgeSessionStorage());
$session->start();

class ErrorController extends AbstractController
{
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof NotFoundHttpException) {
            $response = $this->resourceNotFoundResponse(json_encode($exception->getMessage()));
        }

        if (isset($response)) {
            $event->setResponse($response);
        }
    }
}