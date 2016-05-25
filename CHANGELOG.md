CHANGELOG for 3.1
=================

* New interface: \Webfactory\ContentMapping\ProgressListenerInterface
  If your DestinationAdapter implementation also implements this interface, it will be notified for every
  step the synchronization progressed, that is, after every object that has been created, updated or deleted.
  
* New interface: \Webfactory\ContentMapping\UpdateableObjectProviderInterface
  If your DestinationAdapter implementation also implements this interface, it can create new object instances
  for update operations. Do this if your objects returned from getObjectsOrderedById() cannot be updated.

CHANGELOG for 3.0
=================

* The abstract templates and ready-to-use generic implementations of SourceAdapters and DestinationAdapters in the
  [webfactory/content-mapping-*](https://github.com/search?q=webfactory%2Fcontent-mapping) packages. The Mapper usually
  is very specific for your project, so you probably want to implement it in your application.
* The PropelToSolrMapper has been completely removed, as it depended on an outdated library.
* The SolrDestinationAdapter has been removed, as it depended on the same outdated library. Please see
  [webfactory/content-mapping-destinationadapter-solarium](https://github.com/webfactory/content-mapping-destinationadapter-solarium)
  might be an alternative for you.


CHANGELOG for 2.0
=================

Rewritten from scratch; earlier versions are neither documented nor supported. For usage, please see [README](README.md).
