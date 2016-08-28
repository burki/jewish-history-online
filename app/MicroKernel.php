<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

// see https://github.com/ikoene/symfony-micro
class MicroKernel extends Kernel
{
    use MicroKernelTrait;

    /*
     * {@inheritDoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Symfony\Bundle\TwigBundle\TwigBundle(),

            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),

            // $slug = $this->get('cocur_slugify')->slugify('Hello World!');
            // see https://github.com/cocur/slugify#user-content-symfony2
            new Cocur\Slugify\Bridge\Symfony\CocurSlugifyBundle(),

            new Symfony\Bundle\MonologBundle\MonologBundle(), // required by JMS\TranslationBundle\JMSTranslationBundle

            // translate routes
            new JMS\I18nRoutingBundle\JMSI18nRoutingBundle(),
            // not required, but recommended for better extraction
            new JMS\TranslationBundle\JMSTranslationBundle(),

            // asset management
            // see http://symfony.com/doc/current/cookbook/assetic/asset_management.html
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),

            // menu
            // see http://symfony.com/doc/current/bundles/KnpMenuBundle/index.html
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),

            // converturls filter
            new Liip\UrlAutoConverterBundle\LiipUrlAutoConverterBundle(),

            // own code
            new AppBundle\AppBundle(),

            // solr
            // new FS\SolrBundle\FSSolrBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
        }

        return $bundles;
    }

    // see https://github.com/symfony/symfony-standard/blob/master/app/AppKernel.php
    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    /*
     * {@inheritDoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
        $loader->load(__DIR__ . '/config/services.yml');
    }

    /*
     * {@inheritDoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $routes->mount('/_wdt', $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml'));
            $routes->mount(
                '/_profiler',
                $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')
            );

            // TODO (doesn't work yet) if we want to check error pages in dev
            // see http://symfony.com/doc/current/cookbook/controller/error_pages.html
            // e.g. http://host/base/_error/404
            /* $routes->mount(
                '/_error',
                $routes->import('@TwigBundle/Resources/config/routing/errors.xml')
            ); */
        }
        /*
        // Loading annotated routes doesn't seem to work with route translation?!
        $routes->mount('/', $routes->import('@AppBundle/Controller', 'annotation'));
        */

        $routes->add('/preview', 'AppBundle:Default:index', 'home');
        $routes->add('/about', 'AppBundle:About:about', 'about');
        $routes->add('/about/staff', 'AppBundle:About:staff', 'about-staff');
        $routes->add('/about/editors', 'AppBundle:About:editors', 'about-editors');
        $routes->add('/about/board', 'AppBundle:About:board', 'about-board');
        $routes->add('/about/sponsors', 'AppBundle:About:sponsors', 'about-sponsors');
        $routes->add('/contact', 'AppBundle:About:contact', 'contact');
        $routes->add('/terms', 'AppBundle:About:terms', 'terms');

        $routes->add('/topic', 'AppBundle:Topic:index', 'topic-index');

        $topicBackgroundRoute = new \Symfony\Component\Routing\Route(
            '/topic/{slug}',
            [ '_controller' => 'AppBundle:Topic:background' ],
            [ 'slug' => '[^\.]+' ]
        );
        $routes->addRoute($topicBackgroundRoute, 'topic-background');
        $routes->add('/topic/{slug}.pdf', 'AppBundle:Topic:background', 'topic-background-pdf');

        $articleRoute = new \Symfony\Component\Routing\Route(
            '/article/{slug}',
            [ '_controller' => 'AppBundle:Article:article' ],
            [ 'slug' => '[^\.]+' ]
        );
        $routes->addRoute($articleRoute, 'article');
        $routes->add('/article/{slug}.pdf', 'AppBundle:Article:article', 'article-pdf');

        $routes->add('/source/{uid}', 'AppBundle:Source:sourceViewer', 'source');

        $routes->add('/map', 'AppBundle:Place:map', 'place-map');
        $routes->add('/map/popup-content/{ids}', 'AppBundle:Place:mapPopupContent', 'place-map-popup-content');

        $routes->add('/chronology', 'AppBundle:Date:chronology', 'date-chronology');

        $routes->add('/person', 'AppBundle:Person:index', 'person-index');
        $routes->add('/person/{id}', 'AppBundle:Person:detail', 'person');
        $routes->add('/person/gnd/beacon', 'AppBundle:Person:gndBeacon', 'person-gnd-beacon');
        $routes->add('/person/gnd/{gnd}', 'AppBundle:Person:detail', 'person-by-gnd');

        $routes->add('/place', 'AppBundle:Place:index', 'place-index');
        $routes->add('/place/{id}', 'AppBundle:Place:detail', 'place');
        $routes->add('/place/tgn/{tgn}', 'AppBundle:Place:detail', 'place-by-tgn');

        $routes->add('/organization', 'AppBundle:Organization:index', 'organization-index');
        $routes->add('/organization/{id}', 'AppBundle:Organization:detail', 'organization');
        $routes->add('/organization/gnd/beacon', 'AppBundle:Organization:gndBeacon', 'organization-gnd-beacon');
        $routes->add('/organization/gnd/{gnd}', 'AppBundle:Organization:detail', 'organization-by-gnd');

        $routes->add('/glossary', 'AppBundle:Glossary:index', 'glossary-index');
        $routes->add('/glossary/{slug}', 'AppBundle:Glossary:detail', 'glossary');

        // allow / in route .+
        $routes->addRoute(
                          new \Symfony\Component\Routing\Route('/source/imginfo/{path}',
                                                               [ '_controller' => 'AppBundle:Source:imgInfo' ],
                                                               [ 'path' => '.*' ]),
                          'imginfo');
        $routes->addRoute(
                          new \Symfony\Component\Routing\Route('/source/tei2html/{path}',
                                                               [ '_controller' => 'AppBundle:Source:tei2html' ],
                                                               [ 'path' => '.*' ]),
                          'tei2html');
    }
}
