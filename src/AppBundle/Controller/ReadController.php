<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ReadController extends Controller {

  /**
   * @Route("/")
   */
  public function HomeAction() {
    /** @var \AppBundle\Model\Library $library */
    $library = $this->get('library');

    return $this->render(
        'AppBundle:Read:home.html.twig', array(
          'core_books' => $library->getBooksByCategory('Core'),
          'extensions_books' => $library->getBooksByCategory('Extensions'),
        )
    );
  }

}
