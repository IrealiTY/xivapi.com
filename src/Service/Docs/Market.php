<?php

namespace App\Service\Docs;

class Market extends DocBuilder implements DocInterface
{
    public function build()
    {
        return $this
            ->h1('Market *(beta)*')
            ->text('Get market price information from FFXIV for any server, at any time.')
            
            // beta notes
            ->h4('*beta* note:')
            ->note('This feature is in BETA as it is a very unknown territory for developers, SE have not provided
                an open API and could break/change things at any time. I highly recommend just building ideas and
                prototypes for now while we gather confidence in the Companion API.')
            ->gap(2)
            
            ->text('Work In-Progress.')
            
            
            ->get();
    }
}
