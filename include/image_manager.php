<?php
es_include("logger.php");

class ImageManager
{
    const MIME_TYPES = ['image/png', 'image/x-png', 'image/gif', 'image/jpeg', 'image/pjpeg'];
    protected static $tinify;

    public static function SaveImage(LocalObject &$item, $saveDir, $savedImage = "", $paramName = "", $mimeTypes = self::MIME_TYPES)
    {
        $fileSys = new FileSys();

        if ($savedImage)
            $original = $savedImage;
        else
            $original = true;

        $newTypeImage = $fileSys->Upload($paramName . "Image", $saveDir, $original, $mimeTypes);
        if ($newTypeImage)
        {
            $item->SetProperty($paramName . "Image", $newTypeImage["FileName"]);

            // Remove old image if it has different name
            if ($savedImage && $savedImage != $newTypeImage["FileName"])
                @unlink($saveDir.$savedImage);
        }
        else
        {
            if ($savedImage)
                $item->SetProperty($paramName . "Image", $savedImage);
            else
                $item->SetProperty($paramName . "Image", null);
        }

        $item->_properties[$paramName."ImageConfig"]["Width"] = 0;
        $item->_properties[$paramName."ImageConfig"]["Height"] = 0;

        if ($item->GetProperty($paramName . 'Image'))
        {
            if ($info = @getimagesize($saveDir.$item->GetProperty($paramName . 'Image')))
            {
                $item->_properties[$paramName."ImageConfig"]["Width"] = $info[0];
                $item->_properties[$paramName."ImageConfig"]["Height"] = $info[1];
            }
        }

        $item->AppendErrorsFromObject($fileSys);

        return !$fileSys->HasErrors();
    }

    public static function RemoveImage($imagePath)
    {
        if (file_exists($imagePath) and is_file($imagePath)) {
            return @unlink($imagePath);
        }

        return false;
    }

    public static function saveGallery(){
        //TODO create
    }

    public static function getImageUrl($dir, $name, $size, $resizeType = 8, $additionDir = ''){
        $url = GetUrlPrefix();
        $folder = WEBSITE_FOLDER;
        $url .= "images/{$folder}-{$dir}-{$size}_{$resizeType}/{$additionDir}{$name}";
        return $url;
    }

    public static function compress(string $from, string $to)
    {
        try{
            if (is_null(self::$tinify)){
                \Tinify\setKey(GetFromConfig('ApiKey', 'tinify'));
            }

            $source = \Tinify\fromFile($from);
            return $source->toFile($to);
        }
        catch (\Exception $e){
            $logger = new \Logger(PROJECT_DIR . 'var/log/tinify.log');
            $logger->error($e->getMessage());
        }

        return false;
    }
}