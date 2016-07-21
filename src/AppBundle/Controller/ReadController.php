<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Intl\ResourceBundle\RegionBundle;

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
        $k = $this->get('kernel');
        // This seems like a kind of hacky way to find the locales, but not sure what a better way would be.
        $locales = json_decode(file_get_contents($k->getRootDir().'/../vendor/symfony/symfony/src/Symfony/Component/Intl/Resources/data/locales/en.json'), true);
        
        $finder = new Finder();
        $yaml = new Parser();
        
        $books = $this->get('book.loader')->find();        

        foreach ($books as $key => $book) {
                foreach($book['langs'] as $lang){
                    if(isset($lang['stable'])){
                        $stableBooks[$key] = $book;
                    break;
                }
            }
        }
        return $this->render('AppBundle:Read:home.html.twig', array('books'=>$stableBooks, 'locales' => $locales['Names']));
    }

    // $k = $this->get('kernel');
    // if(substr( $path, -1)=='/'){
    //     return new Response(file_get_contents($k->getCacheDir()."/{$path}index.html"));
    // }else{
    //     return new Response(file_get_contents($k->getCacheDir()."/$path"));
    // }
    // //return $this->render('AppBundle:Read:read.html.twig');
}
