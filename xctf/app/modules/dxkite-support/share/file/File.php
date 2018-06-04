<?php
namespace dxkite\support\file;
use Exception;

class File implements \JsonSerializable
{
    private $name;
    private $path;
    private $size;
    private $type;
    private $error;
    private $mimeType;

    private $delete=null;
    private $upload=false;
    public static $errorCode=[
        1=>'UPLOAD_ERR_OK',
        2=>'UPLOAD_ERR_INI_SIZE',
        3=>'UPLOAD_ERR_FORM_SIZE',
        4=>'UPLOAD_ERR_PARTIAL',
        5=>'UPLOAD_ERR_NO_FILE',
        6=>'UPLOAD_ERR_NO_TMP_DIR',
        7=>'UPLOAD_ERR_CANT_WRITE'
    ];

    public function __construct(string $path)
    {
        $this->path=$path;
        if (storage()->exist($path)) {
            $this->name=pathinfo($path, PATHINFO_BASENAME);
            $this->type=strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $this->size=filesize($path);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSize()
    {
        return $this->size;
    }
        
    public function getPath()
    {
        return $this->path;
    }
    public function getError()
    {
        return $this->error;
    }

    public function setError(int $error)
    {
        $this->error=$error;
        return $this;
    }
    
    public function setName(string $name)
    {
        $this->name=$name;
        return $this;
    }

    public function setType(string $type)
    {
        $this->type=$type;
        return $this;
    }

    public function setSize(int $size)
    {
        $this->size=$size;
        return $this;
    }
    
    public function getMimeType() {
        return $this->mimeType;
    }
    
    public function setPath(string $path)
    {
        $this->path=$path;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'path'=>$this->path,
            'name'=>$this->name,
            'type'=>$this->type,
            'size'=>$this->size,
            'error'=>$this->error,
        ];
    }

    public function move(string $path)
    {
        storage()->mkdirs(dirname($path));
        if ($this->upload) {
            move_uploaded_file($this->path, $path);
        } else {
            storage()->move($this->path, $path);
        }
        return storage()->exist($path);
    }

    public static function createFromArray(array $param)
    {
        $file=new File($param['path']);
        $file->name=$param['name'];
        $file->type=strtolower(pathinfo($param['name'], PATHINFO_EXTENSION));
        $file->size=$param['size'];
        return $file;
    }

    public static function createFromPost(string $name)
    {
        if ($_FILES[$name]['error'] != 0) {
            throw new Exception(__('%s upload error %s', $name, static::$errorCode[$_FILES[$name]['error']]));
        }
        if (!isset($_FILES[$name]) || !is_uploaded_file($_FILES[$name]['tmp_name'])) {
            throw new Exception(__('%s is not a uploaded file', $name));
        }
        $param=$_FILES[$name];
        $file=new File($param['tmp_name']);
        $file->name=$param['name'];
        $file->type=strtolower(pathinfo($param['name'], PATHINFO_EXTENSION));
        $file->mimeType= $param['type'];
        $file->size=$param['size'];
        $file->upload=true;
        $file->delete=storage()->abspath($param['tmp_name']);
        return $file;
    }

    public static function createFromBase64(string $name, string $base64)
    {
        $path=tempnam(sys_get_temp_dir(), 'dx_');
        $content=base64_decode($base64);
        file_put_contents($path, $content);
        $file=new File($path);
        $file->name=$name;
        $file->type=strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $file->size=strlen($content);
        $file->delete=storage()->abspath($path);
        return $file;
    }

    public static function createFromSelfBase64(string $type, string $base64)
    {
        $path=tempnam(sys_get_temp_dir(), 'dx_');
        $content=base64_decode($base64);
        file_put_contents($path, $content);
        $file=new File($path);
        $file->name=md5($content);
        $file->type=$type;
        $file->size=strlen($content);
        $file->delete=storage()->abspath($path);
        return $file;
    }

    public function __destruct()
    {
        if ($this->delete && file_exists($this->delete)) {
            debug()->trace('delete upload tmpfile > '.$this->delete);
            unlink($this->delete);
        }
    }
}
