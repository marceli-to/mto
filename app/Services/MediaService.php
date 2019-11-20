<?php

namespace App\Services;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class MediaService
{
    /**
     * Path for source files
     */
    protected $path_source;

    /**
     * Path for uploads
     */
    protected $path_uploads;

    /**
     * Path for preview images
     */
    protected $path_preview;

    /**
     * Path for related images
     */
    protected $path_related;

    /**
     * Path for small images
     */
    protected $path_xsmall;

    /**
     * Path for small images
     */
    protected $path_small;

    /**
     * Path for large images
     */
    protected $path_large;

    /**
     * Path for extra large images
     */
    protected $path_xlarge;

    /**
     * Path for thumbnails
     */
    protected $path_thumbs;

    /**
     * Size for square thumbnails
     */
    protected $size_thumbs = 200;

    /**
     * Size for square small images
     */    
    protected $size_sm = 600;

    /**
     * Maximum width for extra small landscape images
     */    
    protected $max_width_xs = 160;    

    /**
     * Maximum height for extra small portrait images
     */    
    protected $max_height_xs = 120;

    /**
     * Maximum width for small landscape images
     */    
    protected $max_width_sm = 900;    

    /**
     * Maximum height for small portrait images
     */    
    protected $max_height_sm = 500;

    /**
     * Maximum width for large landscape images
     */    
    protected $max_width_lg = 2000;    

    /**
     * Maximum height for large portrait images
     */    
    protected $max_height_lg = 1200;

    /**
     * Maximum width for source images
     */    
    protected $max_width_source = 3000;    

    /**
     * Maximum height for source images
     */    
    protected $max_height_source = 3000;

    /**
     * Image prefix
     */
    protected $prefix = 'wbg.ch';
    
    public function __construct()
    {
        $this->path_source      = storage_path('app/public/media/images/source/');
        $this->path_processed   = storage_path('app/public/media/images/processed/');
        $this->path_downloads   = storage_path('app/public/media/downloads');
        $this->path_uploads     = storage_path('app/public/tmp/uploads');
        $this->path_lg          = $this->path_processed . 'lg/';
        $this->path_sm          = $this->path_processed . 'sm/';
        $this->path_xs          = $this->path_processed . 'xs/';
        $this->path_thumbs      = $this->path_processed . 'thumbs/';
        $this->path_preview     = $this->path_processed . 'preview/';
        $this->path_related     = $this->path_processed . 'related/';
        $this->_mkdir();
    }

    /**
     * Upload the specified resource.
     *
     * @return array
     */

    public function upload(Request $request)
    {
        $file = $request->file('file');
        $name = $this->_sanitizeFilename(trim($file->getClientOriginalName()));
        $name = uniqid() . '_' . $name;
        $file->move($this->path_source, $name);

        // Get file extension to store in media model
        $filetype = \File::extension($this->path_source . $name);

        // Create thumbnail for preview
        $this->thumbnail($name);

        // Resize file and save as source
        $this->resize($name, 'source');

        return ['name' => $name, 'filetype' => $filetype];
    }

    /**
     * Upload the specified resource.
     *
     * @return array
     */

    public function uploadDocument(Request $request)
    {
        $file = $request->file('file');
        $name = $this->_sanitizeFilename(trim($file->getClientOriginalName()));
        $name = uniqid() . '_' . $name;
        $file->move($this->path_downloads, $name);

        // Get file extension to store in media model
        $filetype = \File::extension($this->path_downloads . $name);

        return ['name' => $name, 'filetype' => $filetype];
    }

    /**
     * Generate a thumbnail image.
     * 
     * @param  str $image
     * @return \Illuminate\Http\Response
     */

    public function thumbnail($image = NULL)
    {
        if (!File::exists($this->path_thumbs . $image))
        {
            $image = \Image::make($this->path_source . $image)->fit($this->size_thumbs);
            $image->save($this->path_thumbs . $image->basename);
            return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
        }
        else
        {
            $filename = $image;
            $img = \Image::cache(function($image) use ($filename) {
                return $image->make($this->path_thumbs . $filename);
            }, 300, false);
            return \Response::make($img, 200, ['Content-Type' => 'image/jpeg']);
        }
    }

    /**
     * Resize an image.
     * 
     * @param  str $image
     * @param  str $size
     * @return \Illuminate\Http\Response
     */

    public function resize($image, $size = 'sm')
    {
        if ($image != NULL)
        {
            // Generate small images
            if ($size == 'xs')
            {
                if (!File::exists($this->path_xs . $image))
                {
                    // Create image instance
                    $image = \Image::make($this->path_source . $image);
                    
                    // Resize image
                    $image->resize(null, $this->max_height_xs, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    $image->save($this->path_xs . $image->basename);
                    return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
                }
                else
                {   
                    $filename = $image;
                    $img = \Image::cache(function($image) use ($filename) {
                        return $image->make($this->path_xs . $filename);
                    }, 300, false);
                    
                    return \Response::make($img, 200, ['Content-Type' => 'image/jpeg']);
                }
            }

            // Generate small images
            if ($size == 'sm')
            {
                if (!File::exists($this->path_sm . $image))
                {
                    // Create image instance
                    $image = \Image::make($this->path_source . $image);

                    // Get width and height
                    $width  = $image->getWidth();
                    $height = $image->getHeight();
                    
                    // Resize landscape image
                    if ($width > $height && $width >= $this->max_width_sm)
                    {
                        $image->resize($this->max_width_sm, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }
                    else if ($height >= $this->max_height_sm)
                    {
                        $image->resize(null, $this->max_height_sm, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }

                    $image->save($this->path_sm . $image->basename);
                    return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
                }
                else
                {   
                    $filename = $image;
                    $img = \Image::cache(function($image) use ($filename) {
                        return $image->make($this->path_sm . $filename);
                    }, 300, false);
                    
                    return \Response::make($img, 200, ['Content-Type' => 'image/jpeg']);
                }
            }

            // Generate large images
            if ($size == 'lg')
            {
                if (!File::exists($this->path_lg . $image))
                {
                    // Create image instance
                    $image = \Image::make($this->path_source . $image);

                    // Get width and height
                    $width  = $image->getWidth();
                    $height = $image->getHeight();
                    
                    // Resize landscape image
                    if ($width > $height && $width >= $this->max_width_lg)
                    {
                        $image->resize($this->max_width_lg, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }
                    else if ($height >= $this->max_height_lg)
                    {
                        $image->resize(null, $this->max_height_lg, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }

                    $image->save($this->path_lg . $image->basename);
                    return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
                }
                else
                {   
                    $filename = $image;
                    $img = \Image::cache(function($image) use ($filename) {
                        return $image->make($this->path_lg . $filename);
                    }, 300, false);
                    
                    return \Response::make($img, 200, ['Content-Type' => 'image/jpeg']);
                }
            }

            // Resize source images
            if ($size == 'source')
            {
                // Create image instance
                $image = \Image::make($this->path_source . $image);

                // Get width and height
                $width  = $image->getWidth();
                $height = $image->getHeight();
                
                // Resize landscape image
                if ($width > $height && $width >= $this->max_width_source)
                {
                    $image->resize($this->max_width_source, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
                else if ($height >= $this->max_height_source)
                {
                    $image->resize(null, $this->max_height_source, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }

                $image->save($this->path_source . $image->basename, 100);
                return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
            }
        }
    }

    public function crop($data)
    {
        // Create intervention image
        if (isset($data['image']))
        {
            $image = \Image::make($this->path_source . $data['image']);

            // Create new filename by removing existing unique id first
            $name = substr($image->filename, 14, strlen($image->filename));

            // Add cropped prefix & unique id
            $name = uniqid() . '_' . $name . '.' . $image->extension;
    
            // Crop (w, h, x, y)
            if (!empty($data['coords']))
            {
                $c = $data['coords'];
                $image->crop(floor($c['w']), floor($c['h']), floor($c['x']), floor($c['y']));
            }

            // Save the image
            $image->save($this->path_source . $name, 100);

            return ['name' => $name];
        }
    }

    public function preview($image)
    {
        if ($image != NULL)
        {
            // Create image instance
            $image = \Image::make($this->path_source . $image);

            // Get width and height
            $width  = $image->getWidth();
            $height = $image->getHeight();
            
            // Resize landscape image
            if ($height >= 694)
            {
                $image->resize(null, 694, function ($constraint) {
                    $constraint->aspectRatio();
                })->crop('456', '614');
            }

            $image->save($this->path_preview . $image->basename);
            return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
        }
    }

    public function related($image)
    {
        if ($image != NULL)
        {
            // Create image instance
            $image = \Image::make($this->path_source . $image);

            // Get width and height
            $width  = $image->getWidth();
            $height = $image->getHeight();

            // Resize & Crop
            if ($width > 430 && $height > 280)
            {
                $image->resize(430, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->crop('430', '280');
            }

            $image->save($this->path_related . $image->basename);
            return \Response::make($image, 200, ['Content-Type' => 'image/jpeg']);
        }
    }

    /**
     * Return the source of an image.
     * 
     * @param  str $image
     * @return \Illuminate\Http\Response
     */

    public function source($image = NULL)
    {
        if (File::exists($this->path_source . $image))
        {
            $filename = $image;
            $img = \Image::cache(function($image) use ($filename) {
                return $image->make($this->path_source . $filename);
            }, 300, false);
            return \Response::make($img, 200, ['Content-Type' => 'image/jpeg']);
        }
    }

    /**
     * Delete a file from the storage, including all subfolders
     * 
     * @param str $filename
     */

    public function delete($filename)
    {
        $directories = Storage::allDirectories('public');
        foreach($directories as $d)
        {
            // Delete file from all folders
            Storage::delete($d . '/'. $filename);
        }
    }

    /**
     * Sanitize a string
     *
     * @param str $string
     * @param boolean  $force_lowercase - Force the string to lowercase?
     * @param boolean  $anal - If set to *true*, will remove all non-alphanumeric characters.
     */

    private function _sanitizeFilename($string, $force_lowercase = true, $anal = true)
    {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;", "â€”", "â€“", ",", "<", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9._\-]/", "", $clean) : $clean ;
        return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
    }

    /**
     * Create directories
     * 
     */

    private function _mkdir()
    {
        if (!File::isDirectory($this->path_uploads))
        {
            File::makeDirectory($this->path_uploads, 0775, true, true);
        }

        if (!File::isDirectory($this->path_downloads))
        {
            File::makeDirectory($this->path_downloads, 0775, true, true);
        }
        
        if (!File::isDirectory($this->path_source))
        {
            File::makeDirectory($this->path_source, 0775, true, true);
        }
        
        if (!File::isDirectory($this->path_processed))
        {
            File::makeDirectory($this->path_processed, 0775, true, true);
        }

        if (!File::isDirectory($this->path_thumbs))
        {
            File::makeDirectory($this->path_thumbs, 0775, true, true);
        }

        if (!File::isDirectory($this->path_preview))
        {
            File::makeDirectory($this->path_preview, 0775, true, true);
        }

        if (!File::isDirectory($this->path_related))
        {
            File::makeDirectory($this->path_related, 0775, true, true);
        }

        if (!File::isDirectory($this->path_xs))
        {
            File::makeDirectory($this->path_xs, 0775, true, true);
        }

        if (!File::isDirectory($this->path_sm))
        {
            File::makeDirectory($this->path_sm, 0775, true, true);
        }

        if (!File::isDirectory($this->path_lg))
        {
            File::makeDirectory($this->path_lg, 0775, true, true);
        }
    }
}