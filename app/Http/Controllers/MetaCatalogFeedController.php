<?php

namespace App\Http\Controllers;

use App\Marketing\Destinations\Meta\MetaCatalogFeed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Public Meta catalog feed URL, registered outside the `web` middleware
 * group (bootstrap/app.php) so Meta's fetcher gets no session and isn't
 * recorded as a visitor device / PageView.
 */
class MetaCatalogFeedController extends Controller
{
    public function __invoke(Request $request, MetaCatalogFeed $feed): Response
    {
        // Meta fetches on a schedule, but the URL is public — a short cache
        // keeps repeat hits from rebuilding the whole catalog each time.
        // Keyed by host because every link in the XML is built from the
        // request's host: a request with a forged Host header must only
        // ever poison its own entry, never the one Meta reads.
        $xml = Cache::remember(
            'marketing:meta-catalog-feed:'.$request->getHost(),
            now()->addMinutes(15),
            fn () => $feed->render(),
        );

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
