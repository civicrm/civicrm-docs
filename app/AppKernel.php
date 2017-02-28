<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel {

  public function registerBundles() {
    $bundles = [
      new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
      new Symfony\Bundle\SecurityBundle\SecurityBundle(),
      new Symfony\Bundle\TwigBundle\TwigBundle(),
      new Symfony\Bundle\MonologBundle\MonologBundle(),
      new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
      new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
      new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
      new AppBundle\AppBundle(),
    ];

    if (in_array($this->getEnvironment(), ['dev', 'test'], TRUE)) {
      $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
      $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
      $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
      $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
    }

    return $bundles;
  }

  public function getRootDir() {
    return __DIR__;
  }

  public function getCacheDir() {
    return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
  }

  public function getLogDir() {
    return dirname(__DIR__) . '/var/logs';
  }

  public function registerContainerConfiguration(LoaderInterface $loader) {
    $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
  }

  protected function buildContainer() {
    $container = parent::buildContainer();
    if ($container->hasParameter('mkdocs_path')) {
      $_ENV['PATH'] = $container->getParameter('mkdocs_path')
          . PATH_SEPARATOR . getenv('PATH');
      putenv("PATH=" . $_ENV['PATH']);
    }
    if (!$container->hasParameter('publisher_repos_dir')) {
      // This isn't really a good place to put it because it gets deleted
      // whenever you clear the cache.
      $container->setParameter('publisher_repos_dir', $container->getParameter('kernel.cache_dir') . '/repos'
      );
    }
    return $container;
  }

}
