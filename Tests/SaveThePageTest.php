<?php
require("../savethepage/SaveThePage.php");

class SaveThePageTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // your code here
    }

    public function tearDown()
    {
        // your code here
    }

    public function testCompress()
    {
        $this->assertTrue(method_exists("SaveThePage", "compress"));
        $cases = array(
            array("content"  => "Save \n the     \t page \r some \r\n more",
                  "expected" => 'Save the page some more'),
            array("content"  => "function ()
                                 {
                                    var a,
                                        b,
                                        d = 'something';
                                    u = f + '&url=' + en(l.href);
                                 };",
                  "expected" => "function(){var a,b,d='something';u=f+'&url='+en(l.href);};"));
        foreach ($cases as $case) {
            $this->assertEquals($case['expected'], SaveThePage::compress($case['content']));    
        }

    	
    }

    public function testBookmarlet()
    {
        $this->assertTrue(method_exists("SaveThePage", "bookmarklet"));
        $pagename = "/tmp/".__CLASS__.".".__METHOD__.".testfile";
        $content = <<<EOF
function ()
{
    var a,
        b,
        d=document;
    u = f + '&url=' + en(l.href);
};
EOF;
        $expected = "<a href=\"javascript:function(){var a,b,d=document;u=f+'&url='+en(l.href);};\">Bookmarklet</a>";
        file_put_contents($pagename, $content);
        $b = SaveThePage::bookmarklet($pagename);
        unlink($pagename);
        $this->assertEquals($expected,$b);
    }
    
    public function testCleanTitle()
    {
        $this->assertTrue(method_exists("SaveThePage", "cleantitle"));
        $this->assertEquals("Tamwiki Main Tamwiki Home Page", SaveThePage::cleantitle("TamWiki » Main — TamWiki Home Page") );
    }

    public function testFetchPage()
    {
        $this->assertTrue(method_exists("SaveThePage", "fetchpage"));
        $this->assertNotEmpty(SaveThePage::fetchpage("http://www.example.com/"));
        $this->assertFalse(SaveThePage::fetchpage("http://asdflaksdfjasdfasfdasdf.com"));
        $this->assertFalse(SaveThePage::fetchpage("file:///etc/password"));
    }

    public function testConvertEncoding ()
    {
        $this->assertTrue(method_exists("SaveThePage", "convertencoding"));
        $html = "Some ¶ “text that contains highbyte chars”";
        $this->assertEquals("Some &para; &ldquo;text that contains highbyte chars&rdquo;",SaveThePage::convertencoding($html));
    }

    public function testFilter ()
    {
        $this->assertTrue(method_exists("SaveThePage", "filter"));
        $cases = array(
            array('content'   => 'Just some text',
                  'expected'  => array(0=>'Just some text',1=>'',2=>0),
                  'filter'    => '/bin/cat',
                  'options'   => null),
            array('content'   => 'just some text',
                  'expected'  => array(0=>'',1=>"sh: asdfxxxxxxxx: not found\n",2=>127),
                  'filter'    => 'asdfxxxxxxxx',
                  'options'   => null)
            );
        foreach ($cases as $case) {
            $results = SaveThePage::filter($case['content'], $case['filter'], $case['options']);
            $this->assertTrue(is_array($results));
            $this->assertEquals(6,count($results));
            //var_dump($results);
            //var_dump($case['expected'][0]);
            //$exp = ($case['expected'][0]);
            $this->assertEquals(($case['expected'][0]), $results[0]);
            $this->assertEquals(($case['expected'][1]), $results[1]);
            $this->assertEquals(($case['expected'][2]), $results[2]);
        }
    }

    public function testGetBaseUrl ()
    {
        $this->assertTrue(method_exists("SaveThePage", "getbaseurl"));
        $cases = array(
            array('content'  => 'http://www.example.com/with/some/path',
                  'expected' => 'http://www.example.com:80/'),
            array('content'  => '/some/page.html',
                  'expected' => 'http://www.example.com:80/')
                      );
        foreach ($cases as $case) {
            if (!array_key_exists('HTTP_HOST', $_SERVER)) {
                $_SERVER['HTTP_HOST'] = 'www.example.com';
            }
            $result = SaveThePage::getbaseurl($case['content']);
            $this->assertTrue(is_string($result));
            $this->assertEquals($case['expected'], $result);
        }
    }
}
