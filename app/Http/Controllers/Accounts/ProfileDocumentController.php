<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\MasterProfileDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Profile documents (NID, trade license, …) live on the private disk and
 * are only served through this panel-guarded route.
 */
class ProfileDocumentController extends Controller
{
    public function __invoke(MasterProfileDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, $document->title . '.' . pathinfo($document->path, PATHINFO_EXTENSION));
    }
}
