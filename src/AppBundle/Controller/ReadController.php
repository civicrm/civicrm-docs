<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ReadController extends Controller {

  /**
   * @Route("/")
   */
  public function HomeAction() {
    return $this->render(
        'AppBundle:Read:home.html.twig',
        array('library' => $this->get('library'))
    );
  }

}
