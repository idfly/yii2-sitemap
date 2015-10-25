<?php

namespace idfly\sitemap;

require_once dirname(__FILE__) . '/../Sitemap.php';

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    private $fopen = [];
    private $fputs = [];
    private $fclose = [];
    private $index = 0;

    public function setUp()
    {
        $this->items = ['VALUE1', 'VALUE2'];
        $this->object = new Sitemap('', '');
        $self = $this;

        $this->object->fopen = function($path, $mode) use ($self) {
            $self->fopen[] = [$path, $mode];
            $result = 'FILE_' . $self->index;
            $self->index += 1;
            return $result;
        };

        $this->object->fputs = function($file, $string) use ($self) {
            if(!isset($self->fputs[$file])) {
                $self->fputs[$file] = [];
            }

            $self->fputs[$file][] = [$file, $string];
        };

        $this->object->fclose = function($file) use ($self) {
            $self->fclose[] = [$file];
        };

        $this->object->items = [[
            'list' => function() use ($self) {
                return $self->items;
            },
            'prepare' => function($value) {
                return ['loc' => $value];
            },
        ]];
    }

    public function testWriteCallsOpenFile()
    {
        $this->object->write();
        $this->assertEquals(['/sitemap_0.xml', 'w'], $this->fopen[0]);
    }

    public function testWriteCallsOpenFileWithPath()
    {
        $this->object->path = 'PATH';
        $this->object->write();
        $this->assertEquals(['PATH/sitemap_0.xml', 'w'], $this->fopen[0]);
    }

    public function testWriteClosesFile()
    {
        $this->object->write();
        $this->assertEquals(['FILE_0'], $this->fclose[0]);
    }

    public function testWriteWritesHeader()
    {
        $this->object->write();
        $actual = $this->fputs['FILE_0'][0];
        $this->assertEquals(['FILE_0', Sitemap::HEADER], $actual);
    }

    public function testWriteWritesFooter()
    {
        $this->object->write();
        $actual = $this->fputs['FILE_0'][sizeof($this->fputs['FILE_0']) - 1];
        $this->assertEquals(Sitemap::FOOTER, $actual[1]);
    }

    public function testWritePutsResultOfPrepare()
    {
        $this->object->write();
        $expected = "<url>\n\t<loc>VALUE1</loc>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][1][1]);
    }

    public function testWritePutsResultOfPrepareForEachItem()
    {
        $this->items = ['ITEM1', 'ITEM2'];
        $this->object->write();
        $expected = "<url>\n\t<loc>ITEM2</loc>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][2][1]);
    }

    public function testWriteCallsPrepareWithItem()
    {
        $this->items = ['TEST_ITEM'];
        $actual = '';
        $this->object->items[0]['prepare'] = function($value) use(&$actual) {
            $actual = $value;
            return ['key' => 'value'];
        };
        $this->object->write();
        $this->assertEquals('TEST_ITEM', $actual);
    }

    public function testWriteReturnsRawItemIfNoPrepareDefined()
    {
        $this->items = [['loc' => 'TEST']];
        $this->object->items[0]['prepare'] = null;
        $this->object->write();
        $actual = $this->fputs['FILE_0'][1][1];
        $this->assertEquals("<url>\n\t<loc>TEST</loc>\n</url>\n", $actual);
    }

    public function testWriteCanUseRawItems()
    {
        $this->object->items[0]['list'] = ['VALUE'];
        $this->object->write();
        $actual = $this->fputs['FILE_0'][1][1];
        $this->assertEquals("<url>\n\t<loc>VALUE</loc>\n</url>\n", $actual);
    }

    public function testWritePutsResultOfPrepareWithTwoKeys()
    {
        $this->object->items[0]['prepare'] = function() {
            return ['loc' => 'LOC', 'lastmod' => 'LASTMOD'];
        };
        $this->object->write();
        $expected = "<url>\n\t<loc>LOC</loc>\n\t<lastmod>LASTMOD" .
            "</lastmod>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][1][1]);
    }

    public function testWritePutsResultOfPrepareWithAssociativeArray()
    {
        $this->object->items[0]['prepare'] = function() {
            return ['loc' => 'LOC', 'news:news' => ['news:publication_date' =>
                'DATE']];
        };
        $this->object->write();
        $expected = "<url>\n\t<loc>LOC</loc>\n\t<news:news>\n\t\t" .
            "<news:publication_date>DATE</news:publication_date>\n\t" .
            "</news:news>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][1][1]);
    }

    public function testWritePutsResultOfPrepareWithArray()
    {
        $this->object->items[0]['prepare'] = function() {
            return ['loc' => 'LOC', 'images' => ['IMAGE_1', 'IMAGE_2']];
        };
        $this->object->write();
        $expected = "<url>\n\t<loc>LOC</loc>\n\t<images>IMAGE_1" .
            "</images>\n\t<images>IMAGE_2</images>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][1][1]);
    }

    public function testWritePutsResultOfPrepareWithDeepArray()
    {
        $this->object->items[0]['prepare'] = function() {
            return ['loc' => 'LOC', 'image' => [['url' => 'IMAGE_1'],
                ['url' => 'IMAGE_2']]];
        };
        $this->object->write();
        $expected = "<url>\n\t<loc>LOC</loc>\n\t<image>\n\t\t" .
            "<url>IMAGE_1</url>\n\t</image>\n\t<image>\n\t\t" .
            "<url>IMAGE_2</url>\n\t</image>\n</url>\n";
        $this->assertEquals($expected, $this->fputs['FILE_0'][1][1]);
    }

    public function testWriteOpensTwoFilesByCountRestriction()
    {
        $this->object->splitCount = 1;
        $this->object->write();
        $this->assertEquals('/sitemap_1.xml', $this->fopen[1][0]);
    }

    public function testWriteClosesTwoFilesByCountRestriction()
    {
        $this->object->splitCount = 1;
        $this->object->write();
        $actual = [$this->fclose[0][0], $this->fclose[1][0]];
        $this->assertEquals(['FILE_0', 'FILE_1'], $actual);
    }

    public function testWritePutsHeaderToTwoFilesByCountRestriction()
    {
        $this->object->splitCount = 1;
        $this->object->write();
        $actual = [$this->fputs['FILE_0'][0][1], $this->fputs['FILE_1'][0][1]];
        $this->assertEquals([Sitemap::HEADER, Sitemap::HEADER], $actual);
    }

    public function testWriteOpensTwoFilesBySizeRestriction()
    {
        $this->object->splitSize = 1;
        $this->object->write();
        $this->assertEquals('/sitemap_1.xml', $this->fopen[1][0]);
    }

    public function testWriteClosesTwoFilesBySizeRestriction()
    {
        $this->object->splitSize = 1;
        $this->object->write();
        $actual = [$this->fclose[0][0], $this->fclose[1][0]];
        $this->assertEquals(['FILE_0', 'FILE_1'], $actual);
    }

    public function testWritePutsFooterToTwoFilesBySizeRestriction()
    {
        $this->object->splitSize = 1;
        $this->object->write();
        $actual = [
            $this->fputs['FILE_0'][sizeof($this->fputs['FILE_0']) - 1][1],
            $this->fputs['FILE_1'][sizeof($this->fputs['FILE_1']) - 1][1]
        ];
        $this->assertEquals([Sitemap::FOOTER, Sitemap::FOOTER], $actual);
    }

    public function testWriteWritesCommonSitemap()
    {
        $this->object->write();
        $this->assertEquals('/sitemap.xml', $this->fopen[1][0]);
    }

    public function testWriteWritesCommonSitemapContents()
    {
        $this->object->write();
        $actual = $this->fputs['FILE_1'][1][1];
        $expected = "<url>\n\t<loc>/sitemap_0.xml</loc>\n</url>\n";
        $this->assertEquals($expected, $actual);
    }

    public function testWriteWritesCommonSitemapContentsWithTwoFiles()
    {
        $this->object->splitCount = 1;
        $this->object->write();
        $actual = [$this->fputs['FILE_2'][1][1], $this->fputs['FILE_2'][2][1]];
        $expected = [
            "<url>\n\t<loc>/sitemap_0.xml</loc>\n</url>\n",
            "<url>\n\t<loc>/sitemap_1.xml</loc>\n</url>\n"
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testWriteWritesEachItem()
    {
        $this->object->items = [
            $this->object->items[0],
            $this->object->items[0]
        ];
        $this->object->write(); // 4 items should be written to first file
        $this->assertEquals(6, sizeof($this->fputs['FILE_0']));
    }

}