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
   * @route("/{slug}/", name="book", requirements={"slug": "(?!_)(?!admin/)[^/]+"})
   */
  public function BookAction($slug) {
    /** @var \AppBundle\Model\Library */
    $library = $this->get('library');
    /** @var \AppBundle\Model\Book */
    $book = $library->getBookBySlug($slug);
    if (!$book) {
      throw $this->createNotFoundException(
          "We can't find a '$slug' book");
    }
    $language = $book->getDefaultLanguage();
    $version = $language->getDefaultVersion();
    return $this->redirect("/{$slug}/{$language->code}/{$version->path}");
  }

  /**
   * Displays a page for one book which shows the various languages and
   * versions available for the book
   *
   * @route("/{slug}/editions", requirements={"slug": "(?!_)(?!admin/)[^/]+"},
   * name="book_editions")
   */
  public function EditionsAction($slug) {
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
    /** @var \AppBundle\Model\Library */
    $path = "{$slug}/{$code}";
    $library = $this->get('library');
    $id = $library->getObjectsByIdentifier($path);
    $version = $id['language']->getDefaultVersion()->path;
    return $this->redirect("/{$path}/{$version}");
  }
}
