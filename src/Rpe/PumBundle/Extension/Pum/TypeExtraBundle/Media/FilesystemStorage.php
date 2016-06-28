<?php
namespace Rpe\PumBundle\Extension\Pum\TypeExtraBundle\Media;

use Pum\Bundle\TypeExtraBundle\Media\FilesystemStorage as BaseFilesystemStorage;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Pum\Bundle\TypeExtraBundle\Exception\MediaNotFoundException;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Pum\Core\Extension\Util\Namer;
use Pum\Bundle\TypeExtraBundle\Media\StorageInterface;

/**
 * @method string store(\SplFileInfo $file)
 * @method string guessMime(\SplFileInfo $file)
 * @method string generateFileName(\SplFileInfo $file)
 * @method array  getTrustedExtensions()
 *
 */
class FilesystemStorage extends BaseFilesystemStorage
{
    /**
     * store a file
     *
     * @access public
     * @param SplFileInfo   $file   File object
     *
     * @return string stored file name
     */
    public function store(\SplFileInfo $file)
    {
        $fileName = $this->generateFileName($file);
        $copy     = $this->getUploadFolder().$fileName;
        $folder   = dirname($copy);

        if (!is_dir($folder)) {
            if (false === @mkdir($folder, 0777, true)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $folder));
            }
        }
        if (false === @copy($file, $copy)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $folder));
        }

        return $fileName;
    }

    /**
     * return mime string
     *
     * @access public
     * @param SplFileInfo   $file   File object
     *
     * @return string
     */
    public function guessMime(\SplFileInfo $file)
    {
        if (method_exists($file, 'getClientOriginalExtension')) {
            $clientExtension = $file->getClientOriginalExtension();
        } else {
            $clientExtension = $file->getExtension();
        }

        if (in_array($clientExtension, array_keys($trustedExtensions = $this->getTrustedExtensions()))) {
            return $trustedExtensions[$clientExtension];
        }

        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($file);
    }

    /**
     * return mime string
     *
     * @access private
     * @param SplFileInfo   $file   File object
     *
     * @return string
     */
    private function generateFileName(\SplFileInfo $file)
    {
        $extension = $file->guessExtension();
        if (method_exists($file, 'getClientOriginalExtension')) {
            $clientExtension = $file->getClientOriginalExtension();
        } else {
            $clientExtension = $file->getExtension();
        }

        if (!$extension || null === $extension) {
            $extension = 'bin';
        } else if ($extension == 'jpeg') {
             $extension = 'jpg';
        } else if (in_array($clientExtension, array_keys($this->getTrustedExtensions()))) {
            $extension = $clientExtension;
        }

        $dateFolder = '';
        if ($this->dateFolder) {
            $dateFolder = date("Y/m/d/");
        }

        $i = 0;
        do {
            $fileName = md5(uniqid().time().$i).'.'.$extension;

            $preFolder = '';
            if ($this->dateFolder) {
                $preFolder = substr($fileName, 0, 1).'/';
            }

            $i++;
        } while ($this->exists($this->getUploadFolder().$dateFolder.$preFolder.$fileName) && $i < 10000);

        return $dateFolder.$preFolder.$fileName;
    }

    /**
     * return array of extentions
     *
     * @access private
     * @return array
     */
    public function getTrustedExtensions()
    {
        return array(
            "xls"      => "application/vnd.ms-excel",
            "xlsx"     => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "xlt"      => "application/vnd.ms-excel",
            "xltx"     => "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
            "doc"      => "application/msword",
            "docx"     => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "dot"      => "application/msword",
            "dotx"     => "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
            "pps"      => "application/vnd.ms-powerpoint",
            "ppsx"     => "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
            "ppt"      => "application/vnd.ms-powerpoint",
            "pptx"     => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "pot"      => "application/vnd.ms-powerpoint",
            "potx"     => "application/vnd.openxmlformats-officedocument.presentationml.template",
            "mp3"      => "audio/mp3",
            "mp4"      => "video/mp4",
            "mov"      => "video/quicktime",
            "svg"      => "image/svg+xml",
            "pdf"      => "application/pdf",
            "pages"    => "application/vnd.apple.pages",
            "key"      => "application/vnd.apple.keynote",
            "numbers"  => "application/vnd.apple.numbers",
            "psd"      => "image/vnd.adobe.photoshop",
            "txt"      => "text/plain",
            "rtf"      => "application/rtf",
            "md"       => "text/x-markdown",
            "markdown" => "text/x-markdown",
            "flv"      => "video/x-flv",
            "wmv"      => "video/x-ms-wmv",
            "csv"      => "text/csv",
            "xml"      => "application/xml",
            "json"     => "application/json",
            "odt"      => "application/vnd.oasis.opendocument.text",
            "sxw"      => "application/vnd.sun.xml.writer",
            "ods"      => "application/vnd.oasis.opendocument.spreadsheet",
            "sxc"      => "application/vnd.sun.xml.calc",
            "odp"      => "application/vnd.oasis.opendocument.presentation",
            "odg"      => "application/vnd.oasis.opendocument.graphics",
            "sxi"      => "application/vnd.sun.xml.impress"
        );
    }
}
