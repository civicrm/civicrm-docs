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

  /**
   * Displays a page for one book which shows the various languages and
   * versions available for the book
   *
   * @route("/{slug}/", requirements={"slug": "(?!_)(?!admin/)[^/]+"},
   * name="book_home")
   */
  public function BookAction($slug) {
    /** @var \AppBundle\Model\Library */
    $library = $this->get('library');
    $book = $library->getBookBySlug($slug);
    if (!$book) {
      throw $this->createNotFoundException(
          "We can't find a '$slug' book");
    }
    return $this->render('AppBundle:Read:book.html.twig', array(
      'book' => $book));
  }

  /**
   * @route("/{slug}/{code}/", requirements={"slug": "(?!_)(?!admin/)[^/]+"})
   */
  public function LanguageAction($slug, $code) {
    return $this->redirectToRoute('book_home', array('slug' => $slug));
  }

}
