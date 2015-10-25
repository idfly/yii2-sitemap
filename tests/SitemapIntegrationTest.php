<?php

namespace idfly\sitemap;

require_once dirname(__FILE__) . '/../Sitemap.php';

class SitemapIntegrationTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->items = ['VALUE1', 'VALUE2'];
        $this->path = dirname(__FILE__) . '/data';
        $this->object = new Sitemap($this->path, '');
        $this->object->splitCount = 3;
        $this->cleanUp();
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    public function cleanUp()
    {
        if(file_exists($this->path . '/sitemap.xml')) {
            unlink($this->path . '/sitemap.xml');
        }

        if(file_exists($this->path . '/sitemap_0.xml')) {
            unlink($this->path . '/sitemap_0.xml');
        }

        if(file_exists($this->path . '/sitemap_1.xml')) {
            unlink($this->path . '/sitemap_1.xml');
        }
    }

    public function testWriteWritesFile()
    {
        $this->object->items = [
            [
                'list' => function() {
                    return ['/url-1', '/url-2', '/url-3'];
                },
                'prepare' => function($value) {
                    return ['loc' => $value];
                }
            ],
            [
                'list' => [
                    ['loc' => '/url-4'],
                    ['loc' => '/url-5', 'lastmod' => 'date'],
                ],
            ]
        ];

        $this->object->write();

        $expected = <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
\txmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
\txmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<url>
\t<loc>/sitemap_0.xml</loc>
</url>
<url>
\t<loc>/sitemap_1.xml</loc>
</url>
</urlset>

SITEMAP;

        $actual = file_get_contents($this->path . '/sitemap.xml');
        $this->assertEquals($expected, $actual);

        $expected = <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
\txmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
\txmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<url>
\t<loc>/url-1</loc>
</url>
<url>
\t<loc>/url-2</loc>
</url>
<url>
\t<loc>/url-3</loc>
</url>
</urlset>

SITEMAP;

        $actual = file_get_contents($this->path . '/sitemap_0.xml');
        $this->assertEquals($expected, $actual);

        $expected = <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
\txmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
\txmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<url>
\t<loc>/url-4</loc>
</url>
<url>
\t<loc>/url-5</loc>
\t<lastmod>date</lastmod>
</url>
</urlset>

SITEMAP;

        $actual = file_get_contents($this->path . '/sitemap_1.xml');
        $this->assertEquals($expected, $actual);
    }
}