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
        $body = $request->getContent();        
        $event = $request->headers->get('X-GitHub-Event');
        $payload = json_decode($body);

        $books = $this->get('book.loader')->find();        

        $processor = $this->get('github.hook.processor');
        $processor->process($event, $payload);
        if(!$processor->published){
            return new Response('Something went wrong during publishing.', 200); // @TODO Add more appropriate error code
        }
        $messages = $processor->getMessages();
        $subject = $processor->getSubject();
        $recipients = $processor->getRecipients();
        
        $mail = \Swift_Message::newInstance()
        ->setSubject("[CiviCRM docs] $subject")
        ->setFrom('docs@civicrm.org')
        ->setTo($recipients)
        ->setBody(
            $this->renderView(
                'Emails/notify.html.twig', array(
                    'branch' => $processor->publisher->branch,
                    'book' => $processor->publisher->book,
                    'lang' => $processor->publisher->lang,
                    'messages' => $processor->publisher->getMessages()
                )
            ),
            'text/html'
        );
        $this->get('mailer')->send($mail);
        return new Response($subject, 200);
        

    }
    
    
}
