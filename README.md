Data importer for Symfony 2
===========================

The Data Importer defines the interface between a back-end storage and the outer world. 
Its purpose is to either actively fetch (as a HTTP client) or retrieve (as a web service) data from an external 
data source. These actions are handled each by a separate controller not implementing any data translation logic. 
A service called module processes the data and routes it to the storage unit.

Current Status
--------------

* base implementation is done
* example service module in place
* base configuration for both controllers provided

Dependencies
------------

The Jackalope sources are only necessary to run the example importer. If you do not want to use it, it is not 
necessary to install them.
Note that the Jackalope sources do have their own dependencies. 

* Symfony 2 (https://github.com/symfony/symfony)
* Jackelop (http://jackalope.github.com/jackalope/
* JackalopeBundle (https://github.com/jackalope/JackalopeBundle)

Installation
---------------

1. Install the jackalope/JackalopeBundle as described at http://github.com/jackalope/JackalopeBundle

2. Include in your project

        $ git submodule add git://github.com/lapistano/SF2-FeedImportBundle src/Bundle/FeedImportBundle
        $ git submodule update --recursive --init

2. Add bundle to your application kernel
  
        // app/AppKernel.php
        public function registerBundles()
        {
            return array(
                // ...
                new Bundle\FeedImportBundle\FeedImportBundle(),
                // ...
            );
        }

3. Add the bundle to your application config:

     // app/config/config.yml
     import.controller:
     class: Application\ImportBundle\Controller\ImportController
     arguments:
         - whitelist:
           faz: [127.0.0.1] 
         debug: %kernel.debug%
         - @request
         - @response
         - faz: @?import.importer.faz.books
         - @?logger
         
     
     // import services
     import.importer.faz:
     class: Application\ImportBundle\Importer\Faz\ImporterFaz
     arguments:
         - http:
             method: POST
             server_vars:
                 content_type: text\/xml; charset=utf-8
             xml:
                 schema_file: ~
           article:
             publisher: Frankfurter Allgemeine Zeitung GmbH
           jackalope:
             root_path: /feeds/faz
             article_path: article
             meta_path: metaData
         - @jackalope
         - @?logger

Running tests
-------------
