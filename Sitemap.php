<?php

namespace idfly\sitemap;

class Sitemap // extends \yii\base\Module
{

    public $items = [];

    public $splitCount = 10000;
    public $splitSize = 1048576;

    const HEADER = <<<HEADER
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
\txmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
\txmlns:news="http://www.google.com/schemas/sitemap-news/0.9">

HEADER;

    const FOOTER = <<<FOOTER
</urlset>

FOOTER;

    public $fputs = null;
    public $fclose = null;
    public $fopen = null;

    public $path = '';
    public $url = '';

    public function __construct($path, $url)
    {
        $this->fputs = function($file, $string) {
           return fputs($file, $string);
        };

        $this->fclose = function($file) {
           return fclose($file);
        };

        $this->fopen = function($path, $mode) {
           return fopen($path, $mode);
        };

        $this->path = $path;
        $this->url = $url;
    }

    public function write()
    {
        $result = '';
        $count = 0;
        $index = 0;
        list($file, $size) = $this->_openFile($index);

        foreach($this->items as $item) {
            if(is_array($item['list'])) {
                $values = $item['list'];
            } else {
                $values = $item['list']();
            }

            $prepare = null;
            if(isset($item['prepare'])) {
                $prepare = $item['prepare'];
            }

            foreach($values as $value) {
                $data = $value;

                if($prepare !== null) {
                    $data = $prepare($value);
                }

                $string = $this->_render($data);

                $split = (
                    $count + 1 > $this->splitCount ||
                    $size + strlen($string) + strlen(Sitemap::FOOTER) >=
                        $this->splitSize
                );

                if($split) {
                    $this->_closeFile($file);
                    $count = 0;
                    $index += 1 ;
                    list($file, $size) = $this->_openFile($index);
                }

                $this->_write($file, $string);
                $count += 1;
            }
        }

        $this->_closeFile($file);
        $this->_writeCommonSitemaps($index);
    }

    private function _writeCommonSitemaps($count)
    {
        list($file, $_) = $this->_openFile(null);
        for($index = 0; $index <= $count; $index += 1) {
            $url = $this->url . '/sitemap_' . $index . '.xml';
            $data = $this->_render(['loc' => $url]);
            $this->_write($file, $data);
        }

        $this->_closeFile($file);
    }

    private function _closeFile($file) {
        $this->_write($file, Sitemap::FOOTER);
        $fclose = $this->fclose;
        $fclose($file);
    }

    private function _openFile($index)
    {
        $filePath = $this->_getPath($index);
        $fopen = $this->fopen;
        $file = $fopen($filePath, 'w');
        $size = $this->_write($file, Sitemap::HEADER);
        return [$file, $size];
    }

    private function _write($file, $data)
    {
        $fputs = $this->fputs;
        $writtenCount = $fputs($file, $data);
        if($writtenCount === false) {
            throw new \Exception("Failed to write file $filePath");
        }

        return $writtenCount;
    }

    private function _getPath($index)
    {
        $path = $this->path;

        $path .= '/sitemap';
        if($index !== null) {
            $path .= '_' . $index;
        }

        $path .= '.xml';

        return $path;
    }

    private function _render($data)
    {
        $result = "<url>\n";
        $result .= $this->_renderData($data, "\t");
        $result .= "</url>\n";
        return $result;
    }

    private function _renderData($data, $prefix = '')
    {
        $result = '';
        foreach($data as $key => $value) {

            // indexed array case
            if(is_array($value) && array_keys($value)[0] === 0) {
                foreach($value as $subValue) {
                    $result .= $this->_renderData([$key => $subValue], $prefix);
                }

                continue;
            }

            $result .= $prefix . '<' . htmlspecialchars($key) . '>';

            // associative array case
            if(is_array($value)) {
                $result .= "\n";
                $result .= $this->_renderData($value, $prefix . "\t");
                $result .= $prefix;
            } else {
                $result .= htmlspecialchars($value);
            }


            $result .= '</' . htmlspecialchars($key) . ">\n";
        }

        return $result;
    }

}