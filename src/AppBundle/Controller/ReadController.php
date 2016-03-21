<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Yaml\Parser;

class ReadController extends Controller
{
    /**
    * @Route("/{path}")
    */
    public function RedirectAction($path)
    {
        //If path is the recognised name of a book, then redirect to the stable path of the book, else redirect to the home page.
        $k = $this->get('kernel');
        $fs = $this->get('filesystem');
        $bookConfigFile = $k->getRootDir()."/config/books/{$path}.yml";
        if (!$fs->exists($bookConfigFile)) {
            return $this->redirect("/");
        }
        return $this->redirect("{$path}/en/stable");
    }
    // $k = $this->get('kernel');
    // if(substr( $path, -1)=='/'){
    //     return new Response(file_get_contents($k->getCacheDir()."/{$path}index.html"));
    // }else{
    //     return new Response(file_get_contents($k->getCacheDir()."/$path"));
    // }
    // //return $this->render('AppBundle:Read:read.html.twig');
    
    /**
    * @Route("/")
    */
    public function HomeAction()
    {
        return $this->render('AppBundle:Read:home.html.twig');
    }
    // $k = $this->get('kernel');
    // if(substr( $path, -1)=='/'){
    //     return new Response(file_get_contents($k->getCacheDir()."/{$path}index.html"));
    // }else{
    //     return new Response(file_get_contents($k->getCacheDir()."/$path"));
    // }
    // //return $this->render('AppBundle:Read:read.html.twig');
}
