<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MediaService;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $service)
    {
        $this->mediaService = $service;
    }
    
    public function upload(Request $request)
    {
        $media = $this->mediaService->upload($request);
        return response()->json($media, 200);
    }

    public function uploadDocument(Request $request)
    {
        $media = $this->mediaService->uploadDocument($request);
        return response()->json($media, 200);
    }
    
    public function thumbnail($image = NULL)
    {
        return $this->mediaService->thumbnail($image);
    }

    public function preview($image = NULL)
    {
        return $this->mediaService->preview($image);
    }

    public function related($image = NULL)
    {
        return $this->mediaService->related($image);
    }

    public function source($image = NULL)
    {
        return $this->mediaService->source($image);
    }

    public function resize($image, $size = 'sm')
    {
        return $this->mediaService->resize($image, $size);
    }
}
