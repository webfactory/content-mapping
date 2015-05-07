<?php

namespace Webfactory\ContentMapping\Solr;

use Webfactory\ContentMapping\Mappable as BaseMappable;

/**
 * Objekt, das in ein Apache_Solr_Document abgebildet werden kann.
 */
interface Mappable extends BaseMappable
{
    const SOLR_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * Mappt das Objekt in das $targetDocument.
     *
     * @param \Apache_Solr_Document $targetDocument
     * @return void
     */
    public function mapToSolrDocument(\Apache_Solr_Document $targetDocument);
}
