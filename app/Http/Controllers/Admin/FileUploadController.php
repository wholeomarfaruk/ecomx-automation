<?php

namespace App\Http\Controllers\Admin;

use App\Enums\File\Type;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\FileItem;

class FileUploadController extends Controller
{
public function storeAdmin(Request $request)
{
    // FilePond sends the chunked file as 'filepond'
    if (!$request->hasFile('filepond')) {
        return response($request->all(), 400);
    }

    $upload = $request->file('filepond');

    // Get FilePond chunk info
    $chunkIndex = (int) $request->input('filepond', 0); // current chunk index
    $totalChunks = (int) $request->input('filepond-total-chunks', 1); // total chunks
    $fileName = $request->input('filepond-file-name', $upload->getClientOriginalName());

    // Temporary folder for chunks
    $tempDir = storage_path('app/public/uploads/tmp/' . md5($fileName));
    if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

    // Store current chunk
    $chunkPath = $tempDir . '/' . $chunkIndex;
    $upload->move($tempDir, $chunkIndex);

    // Check if all chunks uploaded
    $chunks = glob($tempDir . '/*');
    if (count($chunks) < $totalChunks) {
        // Return 200 for intermediate chunk upload
        return response()->json(['status' => 'chunk uploaded']);
    }

    // All chunks uploaded → merge into final file
    $finalName = uniqid() . '-' . $fileName;
    $finalPath = 'uploads/' . $finalName;
    $finalFullPath = storage_path('app/public/' . $finalPath);

    // Open final file for writing
    $out = fopen($finalFullPath, 'wb');

    // Merge all chunks
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunk = fopen($tempDir . '/' . $i, 'rb');
        stream_copy_to_stream($chunk, $out);
        fclose($chunk);
    }

    fclose($out);

    // Remove temp folder
    array_map('unlink', glob("$tempDir/*"));
    rmdir($tempDir);

   
    // Save DB records
    $file = File::create([
        'name' => $fileName,
        'type' => Type::fromExtension($upload->getClientOriginalExtension()),
        'extension' => $upload->getClientOriginalExtension(),
    ]);


    FileItem::create([
        'file_id' => $file->id,
        'type' => 'original',
        'size' => filesize($finalFullPath),
        'path' => $finalPath,
    ]);

    activity('uploads')
        ->causedBy(auth()->user())
        ->performedOn($file)
        ->withProperties([
            'name'      => $fileName,
            'type'      => $file->type,
            'extension' => $file->extension,
            'size'      => filesize($finalFullPath),
        ])
        ->event('created')
        ->log("File \"{$fileName}\" was uploaded");

    return response()->json($file->id);
}

    public function revertAdmin(Request $request)
    {
        $fileId = $request->getContent();

        $file = File::find($fileId);

        if ($file) {

            $item = FileItem::where('file_id', $file->id)->first();

            if ($item) {
                Storage::disk('public')->delete($item->path);
                $item->delete();
            }

            $file->delete();
        }

        return response('', 200);
    }
}
