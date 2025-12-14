<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'filepond' => 'required|file|mimes:jpg,jpeg|max:10240'
        ]);

        $file = $request->file('filepond');
        $filename = Str::uuid() . '.jpg';

        $file->storeAs('public/temp', $filename);

        return response()->json([
            'filename' => $filename
        ]);
    }

    public function revert(Request $request)
    {
        $filename = $request->getContent();

        if ($filename) {
            Storage::delete('public/temp/' . $filename);
        }

        return response()->json(['success' => true]);
    }
}
