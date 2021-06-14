<?php
declare(strict_types=1);

namespace App\Http\File;

use Minimal\Facades\App;
use Minimal\Facades\Config;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 文件上传
 */
class Upload
{
    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 获取文件
        $file = $req->files('file');
        if (empty($file)) {
            throw new Exception('很抱歉、请提供文件！');
        }

        // 格式判断
        $ext = explode('.', $file['name']);
        $ext = array_pop($ext);
        if (!in_array($ext, Config::get('app.upload.image.ext', []))) {
            throw new Exception('很抱歉、格式不允许！');
        }

        // 大小判断
        if ($file['size'] > Config::get('app.upload.image.size', 0)) {
            throw new Exception('很抱歉、文件太大了！');
        }

        // 处理路径
        $folder = date('Y/m/d');
        $filename = md5($file['name'] . microtime()) . '.' . $ext;
        $path = App::appPath(Config::get('app.upload.image.path', '../public/upload/'));
        $path .= DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        // 上传文件
        if (false === move_uploaded_file($file['tmp_name'], $path . DIRECTORY_SEPARATOR . $filename)) {
            throw new Exception('很抱歉、文件不合法！', 500, [$file, $path, $filename]);
        }

        // 返回结果
        return [
            'url'       =>  '/upload/' . $folder . DIRECTORY_SEPARATOR . $filename,
        ];
    }
}