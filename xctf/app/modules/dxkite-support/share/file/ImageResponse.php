<?php
namespace dxkite\support\file;

class ImageResponse extends \suda\core\Response
{
    public function onRequest(\suda\core\Request $request)
    {
        $fileId=request()->get()->id(0);
        if ($fileId) {
            if (isset(request()->get()->pwd)) {
                $file=proxy('media')->getFile($fileId, $request->get()->pwd);
            } else {
                $file=proxy('media')->getFile($fileId);
            }
            if ($file && storage()->exist($file->getPath())) {
                if (!in_array($file->getType(), ['png','gif','jpeg','jpg','bmp'])) {
                    return false;
                }
                
                $imageCreate=$file->getType()=='jpg'?'imagecreatefromjpeg':'imagecreatefrom'.$file->getType();
                $source=$imageCreate($file->getPath());
                list($_width, $_height) = getimagesize($file->getPath());

                if (!$source) {
                    return false;
                }
      
                if ($size=request()->get('size')) {
                    if ($size<=0) {
                        $size=100;
                    }
                    $width = $_width * $size /100;
                    $height = $_height * $size /100;
                } elseif ($width=request()->get('width')) {
                    if ($height=request()->get('height')) {
                    } else {
                        $height=$width;
                    }
                }
                if (($width == $_width && $height == $_height) && request()->get('quality') == 100) {
                    $this->showResource($file);
                    return true;
                }
                $thumb= imagecreatetruecolor($width, $height);
                if (!imagecopyresized($thumb, $source, 0, 0, 0, 0, $width, $height, $_width, $_height)) {
                    $this->showResource($file);
                    return true;
                }
                if ($quality=request()->get('quality')) {
                    if ($quality>=0 && $quality<=100) {
                        $this->type('jpg');
                        imagejpeg($thumb, null, $quality);
                    } else {
                        $this->type('png');
                        imagepng($thumb);
                    }
                } else {
                    $this->type('png');
                    imagepng($thumb);
                }
                return true;
            } else {
                hook()->execIf('system:404');
            }
        } else {
            $this->type('png');
            if ($width=request()->get('width')) {
                if ($height=request()->get('height')) {
                    $image= imagecreate($width, $height);
                } else {
                    $image= imagecreate($width, $width);
                }
            } else {
                $image= imagecreate(100, 100);
            }
            $r=request()->get('r', 0);
            $g=request()->get('g', 0);
            $b=request()->get('b', 0);
            $a=request()->get('a', 0);
            if (
                ($r >=0 && $r<=255) &&
                ($g >=0 && $g<=255) &&
                ($b >=0 && $a<=255) &&
                ($a >=0 && $a<=127)
            ) {
                $default = imagecolorallocatealpha($image, $r, $g, $b, $a);
            } else {
                $default = imagecolorallocatealpha($image, 0x0e, 0x0e, 0x0e, 0);
            }
            imagefill($image, 0, 0, $default);
            imagepng($image);
        }
    }


    public function showResource($file)
    {
        $this->type($file->getType());
        self::setHeader('Content-Length:'.$file->getSize());
        self::setHeader('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        self::setHeader('Pragma: public'); // HTTP/1.0
        echo  file_get_contents($file->getPath());
    }
}
