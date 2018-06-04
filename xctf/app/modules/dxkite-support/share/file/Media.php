<?php
namespace dxkite\support\file;

use dxkite\support\proxy\ProxyObject;
use suda\core\Query;

class Media
{
    public static function getFromPost(string $name)
    {
        return File::createFromPost($name);
    }

    /**
     * 保存文件
     *
     * @param File $file
     * @param integer $status
     * @param integer $visibility
     * @param string $password
     * @return void
     */
    public static function save(File $file, int $status=UploadFile::STATE_PUBLISH, int $visibility=UploadFile::FILE_PUBLIC, string $password=null)
    {
        $uploader=new UploadFile(get_user_id(), $file);
        $uploader->setStatus($status);
        if (is_null($password)) {
            $uploader->setVisibility($visibility);
        } else {
            $uploader->setVisibility($visibility, $password);
        }
        if ($uploader->save()) {
            return $uploader;
        }
        return false;
    }
    

    /**
     * 保存文件
     *
     * @param File $file
     * @param integer $status
     * @return UploadFile
     */
    public static function saveFile(File $file, int $status=UploadFile::STATE_PUBLISH)
    {
        return self::save($file, $status);
    }

    /**
     * 保存文件，并设置密码
     *
     * @param File $file
     * @param string $password
     * @param integer $status
     * @return UploadFile
     */
    public static function saveFileProtected(File $file, string $password, int $status=UploadFile::STATE_PUBLISH)
    {
        return self::save($file, $status, UploadFile::FILE_PASSWORD, $password);
    }

    /**
     * 保存私有文件
     *
     * @param File $file
     * @param integer $status
     * @return UploadFile
     */
    public static function saveFilePrivate(File $file, int $status=UploadFile::STATE_PUBLISH)
    {
        return self::save($file, $status, UploadFile::FILE_PASSWORD, $password);
    }

    /**
     * 保存文件，只有登陆后可以查看
     *
     * @param File $file
     * @param integer $status
     * @return UploadFile
     */
    public static function saveFileOnline(File $file, int $status=UploadFile::STATE_PUBLISH)
    {
        return self::save($file, $status, UploadFile::FILE_PROTECTED, $password);
    }

    /**
     * 获取文件
     *
     * @open false
     * @param integer $id
     * @param string $password
     * @return File
     */
    public static function getFile(int $id, string $password=null)
    {
        $uploader=UploadFile::newInstanceById($id);
        if ($uploader) {
            if ($uploader->isPublic()) {
                return $uploader->getFile();
            } elseif ($uploader->getStatus() === UploadFile::FILE_SIGN) {
                if (!$this->getContext()->getVisitor()->isGuest()) {
                    return $uploader->getFile();
                }
            } elseif ($uploader->getStatus() === UploadFile::FILE_PROTECTED) {
                if (!$this->getContext()->getVisitor()->isGuest()) {
                    $id=$this->getContext()->getVisitor()->getId();
                    if ($uploader->isOwner($id)) {
                        return $uploader->getFile();
                    }
                }
            } elseif ($uploader->getStatus() === UploadFile::FILE_PASSWORD) {
                if ($password && $uploader->checkPassword($password)) {
                    return $uploader->getFile();
                }
            }
        }
        return false;
    }
    
    public static function getFileUrl(int $id, bool $full=false)
    {
        if ($full) {
            $uploader=UploadFile::newInstanceById($id);
            return $uploader->getUrl();
        }
        return u('support:upload', ['id'=>$id]);
    }

    public static function delete(int $id)
    {
        $upload=table('fileInfo')->getByPrimaryKey($id);
        if ($upload) {
            try {
                Query::begin();
                $uploadData=table('fileData')->getByPrimaryKey($upload['data']);
                Query::update(table('fileData')->getTableName(), 'ref = ref - 1', ['id' =>$upload['data']]);
                table('fileInfo')->deleteByPrimaryKey($id);
                if ($uploadData['ref'] - 1 ==0) {
                    table('fileData')->deleteByPrimaryKey($upload['data']);
                    if ($upload['visibility'] == UploadFile::FILE_PUBLIC) {
                        $path=UploadFile::PUBLIC_PATH.'/'.$uploadData['path'];
                    } else {
                        $path=UploadFile::PROTECTED_PATH.'/'.$uploadData['path'];
                    }
                    storage()->delete($path);
                }
                Query::commit();
            } catch (\Exception $e) {
                Query::rollBack();
                return false;
            }
        }
        return true;
    }
}
