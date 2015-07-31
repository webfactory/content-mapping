content-mapping
===============

[![Build Status](https://travis-ci.org/webfactory/content-mapping.svg?branch=master)](https://travis-ci.org/webfactory/content-mapping)
[![Coverage Status](https://coveralls.io/repos/webfactory/content-mapping/badge.svg?branch=master&service=github)](https://coveralls.io/github/webfactory/content-mapping?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webfactory/content-mapping/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/webfactory/content-mapping/?branch=master)

content-mapping is a mini framework for mapping content from a source to a destination system. E.g. from Propel objects
to Solr, from Doctrine entities to ElasticSearch or from one XML structure to another. It features interfaces to guide
you on your mapping way and ships with some abstract implementation helpers or magic implementations.

In easy situations, you may want to consider other libraries instead. E.g. if in a Symfony2 project you want to index
Doctrine entities in a Solr server, you may want to consider the great [floriansemm/solr-bundle](/floriansemm/SolrBundle).
But that and other libraries have shortcomings in more complex situation, e.g. when you want to index attributes of
related entities. That's why we present you content-mapping with a more general approach.


Installation
------------

Add the content-mapping dependency by running the command (see http://getcomposer.org/):

    php composer.phar require webfactory/content-mapping

and run

    php composer.phar install

Concept
-------

![Class diagram](doc/class-diagram.png)

The content-mapping process is based on four parts: the `Synchronizer`, a ``SourceAdapter``, a ``Mapper`` and a
``DestinationAdapter``. The entry point is ``Synchronizer->synchronize()``: there, the Synchronizer gets an Iterator
from the ``SourceAdapter->getObjectsOrderedById()`` as well as an Iterator from the
``DestinationAdapter->getObjectsOrderedById()``, and compares the objects in each one. During the comparison, it deletes
outdated objects (``DestinationAdapter->delete()``), stores a new objects (``DestinationAdapter->createObject()``) and
updates existing objects in the destination system (``Mapper->map()``).
 
``DestinationAdapter->updated()`` and ``DestinationAdapter->commit()`` are only hooks for external change tracking, to
say an object has been updated or both Iterators have been processed, i.e. changes can be persisted.


Usage
-----

To make use of the ``Synchronizer``, you need implementations for the ``SourceAdaper``, ``Mapper`` and
``DestinationAdapter``.

You may find the example implementations shipped with the library useful:

**SourceAdapters:**

* The ``GenericDoctrineSourceAdapter`` can be configured with a Doctrine repository and a method name to
  ``getObjectsOrderedById()``
* With some assumptions, the abstract ``PropelSourceAdapter`` only needs an implementation of ``createPeer()`` and
  ``createResultSet($peer, \Criteria $criteria)`` to query a Propel peer to ``getObjectsOrderedById()``. Such are
  provided in a generic way via the ``GenericPropelSourceAdapter``.

**Mapper:**

* The ``PropelToSolrMapper`` uses some accessor magic features to provide convenience methods for maping and converting
  fields from a Propel object to a Solr document.

**DestinationAdapter:**

* The ``SolrDestinationAdapter`` is a complete DestinationAdapter for Solr based on a Solr PHP client
  (reprovinci/solr-php-client or compatible fork of [the original solr-php-client](https://github.com/PTCInc/solr-php-client)). 

If you need to write an own implementation of any interface, please feel free to open a pull request. We'll be happy to
share it! 

Implementations ready? Inject them into the ``Synchronizer``'s constructor, call ``Synchronizer->synchronize()`` and
lay back!


Credits, Copyright and License
------------------------------

This project was started at webfactory GmbH, Bonn.

- <http://www.webfactory.de>
- <http://twitter.com/webfactory>

Copyright 2015 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).
