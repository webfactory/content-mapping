<?php

namespace Webfactory\ContentMapping\Solr;

use Webfactory\ContentMapping\Mappable as BaseMappable;

/* Ein ContentMappable, das in ein Apache_Solr_Document abgebildet werden kann. */
interface Mappable extends BaseMappable {

    const SOLR_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    public function mapToSolrDocument(\Apache_Solr_Document $document);

}
