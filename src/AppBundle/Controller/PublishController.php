<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;


//@TODO Make the errors that will occur if the slugs don't pass the regexp more obvious to the user

class PublishController extends Controller
{
    /**
    * @Route("/admin/publish")
    */
    public function PublishInstructionsAction()
    {
        return $this->render('AppBundle:Publish:publish.html.twig');
    }
    /**
    * @Route("/admin/publish/{book}/{lang}/{branch}", requirements={
    *   "lang":"[[:alpha:]]{2}",
    *   "book":"[[:alpha:]\-]+",
    *   "branch":"[[:alnum:]\-\.]+",
    * })
    */
    public function PublishAction(Request $request, $lang, $book, $branch)
    {
        $publisher = $this->get('publisher');
        $publisher->publish($book, $lang, $branch);
        $content['messages'] = $publisher->getMessages();
        return $this->render('AppBundle:Publish:publish.html.twig', $content);
    }
    
    
    /**
    * @Route("/admin/listen")
    */
    public function ListenAction(Request $request)
    {
        $secret = $this->getParameter('secret');
        if(!$request->headers->has('x-hub-signature')){
            throw new \Exception("Missing 'X-Hub-Signature' header.");
        }
        $signature = $request->headers->get('X-Hub-Signature');
        list($algo, $hash) = explode('=', $signature, 2);
        $body = $request->getContent();        
        $payloadHash = hash_hmac($algo, $body, $secret);
        if ($hash !== $payloadHash) {
            throw new \Exception("Bad secret.");
        }
        $event = $request->headers->get('X-GitHub-Event');
        $payload = json_decode($body);
        $yaml = new Parser();
        $finder = new Finder();
        foreach ($finder->in($this->get('kernel')->getRootDir().'/config/books')->name("*.yml") as $file) {
            $books[basename($file, '.yml')] = $yaml->parse(file_get_contents("$file"));
        }
        
        $processor = $this->get('github.hook.processor');
        $processor->process($event, $payload, $books);
        if(!$processor->published){
            return new Response('OK', 200);
        }
        $messages = $processor->getMessages();
        $details = $processor->getDetails();
        $subject = $processor->getSubject();
        ;
        
        $mail = \Swift_Message::newInstance()
        ->setSubject("[CiviCRM docs] $subject")
        ->setFrom('docs@civicrm.org')
        ->setTo('michaelmcandrew@thirdsectordesign.org')
        ->setBody($this->renderView( 'Emails/notify.html.twig', array('details' => $details, 'messages' => $messages) ), 'text/html');
        //->setBody('hello');
        $this->get('mailer')->send($mail);
        return new Response('OK', 200);
        

    }
    
    
}
