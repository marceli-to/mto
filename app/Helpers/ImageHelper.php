<?php

namespace App\Helpers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageHelper
{
  static function get($image = NULL, $size = 'sm')
  {
   
    // Set default image uri
    $src = '/media/' . $image . '/' . $size;

    // Overwrite image uri if file already exists
    if (File::exists(storage_path('app/public/media/images/processed/' . $size . '/') . $image))
    {
      $src = '/storage/media/images/processed/' . $size . '/' . $image;
    }

    return $src;
  }

  static function preview($image = NULL)
  {
    // Set default image uri
    $src = '/media/preview/' . $image;

    // Overwrite image uri if file already exists
    if (File::exists(storage_path('app/public/media/images/processed/preview/') . $image))
    {
      $src = '/storage/media/images/processed/preview/' . $image;
    }

    return $src;
  }

  static function related($image = NULL)
  {
    // Set default image uri
    $src = '/media/related/' . $image;

    // Overwrite image uri if file already exists
    if (File::exists(storage_path('app/public/media/images/processed/related/') . $image))
    {
      $src = '/storage/media/images/processed/related/' . $image;
    }

    return $src;
  }
}