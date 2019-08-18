<?php

namespace Application\Core\Components\Graph;

/**
 * 图片裁剪器
 * @description
 *  $imageClipper = new ImageClipper($fileTmp);
 *  //裁切图片
 *  $imageClipper
 *  ->resizeTo(300)  //宽300 高按宽等比缩放
 *  ->resizeTo('', 300)// 高300，宽按搞等比缩放
 *  ->resizeTo(150, 300)// 按设置的大小，自由裁剪，取图形中断数据，维持结果图不拉伸
 *  ->execute(ROOT_PATH.'logs/d.jpg');
 *  //压缩图片
 *  $imageClipper->qualityTo(0.8)->execute(ROOT_PATH.'logs/d.jpg');
 *  //修复手机拍照的图片旋转
 *  $imageClipper->fixORI()->execute(ROOT_PATH.'logs/d.jpg');
 *
 * @author 850328994@qq.com
 * @mail zeng444@163.com
 *
 * Class ImageClipper
 * @package Application\Core\Components\Graph
 */
class ImageClipper
{

    /**
     * 原始图路径路径
     * @var
     */
    public $srcImage;

    /**
     * 原始图宽
     * @var
     */
    protected $srcImageWidth;

    /**
     * 原始图高
     * @var
     */
    protected $srcImageHeight;

    /**
     * 原始图类型
     * @var
     */
    protected $srcImageMime;

    /**
     * 图片旋转信息
     * @var
     */
    protected $srcImageOrientation;


    /**
     * 目标图大小
     * @var
     */
    protected $distImageWidth;

    /**
     * 目标图高度
     * @var
     */
    protected $distImageHeight;

    /**
     * 允许的文件类型
     * @var array
     */
    protected $allowType = ['image/jpeg', 'image/png'];

    /**
     * 设置图片压缩的尺寸
     * @var int
     */
    protected $quality = '';


    /**
     * 裁剪对象
     * @var null|resource
     */
    private $imageObj;

    /**
     * 是否需要裁剪
     * Author:Robert
     *
     * @var bool
     */
    private $resizeRequired = false;

    /**
     * 自动修复图
     * Author:Robert
     *
     * @var bool 1
     */
    private $fixORIRequired = true;


    /**
     * ImageClipper constructor.
     * @param $srcImage
     * @param array $config
     * @throws \Exception
     */
    public function __construct($srcImage, array $config = [])
    {
        /**
         * 图片类型
         */
        if (isset($config['allowType'])) {
            $this->allowType = $config['allowType'];
        }
        $this->srcImage = $srcImage;
        if (file_exists($srcImage) === false) {
            throw new \Exception("源图不存在无法压缩图片！");
        }
        $imageInfo = self::getImageInfo($this->srcImage);
        if (!$imageInfo) {
            throw new \Exception("获取图片信息失败！");
        }
        $this->srcImageMime = $imageInfo['mime'];
        if (in_array($this->srcImageMime, $this->allowType) === false) {
            throw new \Exception("图片格式不合法！无法压缩图片");
        }

        $this->imageObj = $this->createImage($this->srcImage, $this->srcImageMime);
        if (!$this->imageObj) {
            throw new \Exception("创建图片对象失败！");
        }
        $this->srcImageWidth = $this->distImageWidth = imagesx($this->imageObj);
        $this->srcImageHeight = $this->distImageHeight = imagesy($this->imageObj);
        $this->srcImageOrientation = $this->getOrientation();
    }

    /**
     * 调整大小,自动裁剪能力
     * Author:Robert
     *
     * @param string $width
     * @param string $height
     * @return $this
     * @throws \Exception
     */
    public function resizeTo($width = '', $height = '')
    {
        $width = abs($width);
        $height = abs($height);
        if ($width) {
            $width = $width > $this->getWidth() ? $this->getWidth() : $width;
        }
        if ($height) {
            $height = $height > $this->getHeight() ? $this->getHeight() : $height;
        }
        if (!$width && !$height) {
            throw new \Exception("对不起，resize尺寸设置错误");
        }
        if ($width && !$height) {
            $height = ceil(($width * $this->getHeight()) / $this->getWidth());
        }
        if (!$width && $height) {
            $width = ceil(($this->getWidth() * $height) / $this->getHeight());
        }
        $this->distImageWidth = $width;
        $this->distImageHeight = $height;
        $this->resizeRequired = true;
        return $this;
    }


    /**
     * 设置图片压缩
     * Author:Robert Tsang
     *
     * @param float $quality
     * @return $this
     * @throws \Exception
     */
    public function qualityTo($quality = 0.8)
    {
        $this->quality = $quality;
        return $this;
    }


    /**
     * Author:Robert
     *
     * @param $ratio
     * @return ImageClipper
     * @throws \Exception
     */
    public function scaleTo($ratio)
    {
        return $this->resizeTo(ceil($ratio * $this->distImageWidth), ceil($ratio * $this->distImageHeight));
    }

    /**
     * 获得mime
     * @return mixed
     */
    public function getMime()
    {
        return $this->srcImageMime;
    }


    /**
     * 获得原始图宽度
     * @return mixed
     */
    public function getWidth()
    {
        return $this->srcImageWidth;
    }

    /**
     * 获得原始图高度
     * @return mixed
     */
    public function getHeight()
    {
        return $this->srcImageHeight;
    }

    /**
     * 图片信息
     * @param $path
     * @return array|bool
     */
    public static function getImageInfo($path)
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'mime' => $imageInfo['mime'],
        ];
    }

    /**
     * 是否支持EXIF
     * @param $mime
     * @return bool
     */
    public static function isSupportExif($mime)
    {
        if (in_array($mime, ['image/jpeg', 'image/tiff', 'image/x-canon-cr2', 'image/x-nikon-nef'])) {
            return true;
        }
        return false;
    }

    /**
     * 修复图片
     * @param $orientation
     * @return bool
     */
    protected function fixOrientation($orientation)
    {
        if (!empty($orientation)) {
            switch ($orientation) {
                case 8:
                    $this->imageObj = imagerotate($this->imageObj, 90, 0);
                    break;
                case 3:
                    $this->imageObj = imagerotate($this->imageObj, 180, 0);
                    break;
                case 6:
                    $this->imageObj = imagerotate($this->imageObj, -90, 0);
                    break;
            }
            if (in_array($orientation, [3, 6, 8])) {
                $this->srcImageWidth = imagesx($this->imageObj);
                $this->srcImageHeight = imagesy($this->imageObj);
                $this->srcImageOrientation = 1;
            }
            return true;
        }
        return false;
    }


    /**
     * 获得图片旋转信息
     * @return string
     */
    public function getOrientation()
    {
        if (self::isSupportExif($this->srcImageMime)) {
            $exif = @exif_read_data($this->srcImage);
            if ($exif !== false && isset($exif['Orientation'])) {
                return $exif['Orientation'];
            }
        }
        return false;
    }

    /**
     * 创建图片对象
     * @param $path
     * @param $mime
     * @return null|resource
     */
    protected function createImage($path, $mime)
    {
        $image = null;
        switch ($mime) {
            case 'image/jpeg':
                $image = ImageCreateFromJpeg($path);
                break;
            case 'image/png':
                $image = ImageCreateFromPNG($path);
                break;
        }
        return $image;
    }


    /**
     * 获取长宽比
     * @param $width
     * @param $height
     * @return float
     */
    protected function getAspectRatio($width, $height)
    {
        return round($width / $height, 4);
    }

    /**
     * 创建一个画布
     * @param $width
     * @param $height
     * @return resource
     */
    protected function createCanvas($width, $height)
    {
        if (function_exists("imagecreatetruecolor")) {
            $canvas = imagecreatetruecolor($width, $height);
            if ($this->srcImageMime === 'image/png') {
                $alpha = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $alpha);
            }
            return $canvas;
        } else {
            return imagecreate($width, $height);
        }
    }

    /**
     * 保存图片
     * @param $path
     * @return bool
     */
    protected function save($path)
    {
        if ($this->srcImageMime !== 'image/png') {
            $result = ImageJpeg($this->imageObj, $path);
        } else {
            imagealphablending($this->imageObj, true);
            imagesavealpha($this->imageObj, true);
            $result = ImagePNG($this->imageObj, $path);
        }
        return $result;
    }

    /**
     * Author:Robert Tsang
     *
     * @return bool|resource
     */
    protected function resize()
    {
        $canvasObj = $this->createCanvas($this->distImageWidth, $this->distImageHeight);
        $benchMarkRatio = $this->getAspectRatio($this->distImageWidth, $this->distImageHeight);
        $sourceImageRatio = $this->getAspectRatio($this->srcImageWidth, $this->srcImageHeight);
        if ($benchMarkRatio > $sourceImageRatio) {
            $width = $this->srcImageWidth;
            $height = ($this->srcImageWidth * $this->distImageHeight) / $this->distImageWidth;
            $x = 0;
            $y = intval(($this->srcImageHeight - $height) / 2);
        } else {
            $height = $this->srcImageHeight;
            $width = ($this->srcImageHeight * $this->distImageWidth) / $this->distImageHeight;
            $x = intval(($this->srcImageWidth - $width) / 2);
            $y = 0;
        }
        if (imagecopyresampled($canvasObj, $this->imageObj, 0, 0, $x, $y, $this->distImageWidth, $this->distImageHeight, $width, $height) === false) {
            return false;
        }
        $this->imageObj = $canvasObj;
        unset($canvasObj);
        return true;
    }

    /**
     * 压缩图片
     * Author:Robert Tsang
     *
     * @param $sourceFile
     * @param string $distFile
     * @param string $quality
     * @return bool
     * @throws \Exception
     */
    protected function compress($sourceFile, $distFile = '', $quality = '')
    {
        //判断是否加载了该扩展
        if (!extension_loaded('Imagick')) {
            throw new \Exception("对不起，图片压缩功能需要加载Imagick");
        }
        $quality = $quality ? $quality : $this->quality;
        $distFile = $distFile ?: $sourceFile;
        if ($quality !== '' && ($quality <= 0 || $quality >= 1)) {
            throw new \Exception("对不起，压缩比设置错误");
        }
        try {
            $imagick = new \Imagick();
            $imagick->readImage($sourceFile);
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality($quality);
            if (!$imagick->writeImage($distFile)) {
                return false;
            }
            $imagick->clear();
            $imagick->destroy();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 设置是否秀动修复图片旋转
     * Author:Robert
     *
     * @param bool $bool
     * @return $this
     */
    public function fixORI(bool $bool = true)
    {
        $this->fixORIRequired = $bool;
        return $this;
    }

    /**
     * 整齐处理
     * Author:Robert Tsang
     *
     * @param string $distFile
     * @param bool $fixORI
     * @return bool
     * @throws \Exception
     */
    public function execute($distFile = "", $fixORI = null)
    {

        $distFile = $distFile ? $distFile : $this->srcImage;
        if (is_bool($fixORI)) {
            $this->fixORIRequired = $fixORI;
        }
        if ($this->fixORIRequired === true && $this->srcImageOrientation !== false) {
            $this->fixOrientation($this->srcImageOrientation);
        }

        if ($this->resizeRequired && $this->resize() === false) {
            return false;
        }
        if ($this->save($distFile) === false) { //保存画布
            return false;
        }
        if ($this->quality) {
            if ($this->compress($distFile) === false) { //最终压缩
                return false;
            }
        }
        $this->destroy();
        return true;
    }

    /**
     * 注销
     */
    protected function destroy()
    {
        if ($this->imageObj) {
            ImageDestroy($this->imageObj);
            unset($this->imageObj);
        }
    }
}
