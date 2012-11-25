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
    	$this->assertEquals("Save the page", SaveThePage::compress("Save \n the    \t \r page"));
    }

    public function testBookmarlet()
    {
        $b = SaveThePage::bookmarklet("../savethepage/bookmarklet.js");
        $this->assertNotEmpty($b);
        echo "$b\n";
    }
    
    public function testCleanTitle()
    {
        $this->assertEquals("Tamwiki Main Tamwiki Home Page", SaveThePage::cleantitle("TamWiki » Main — TamWiki Home Page") );
    }

    public function testFetchPage()
    {
        $content = "This is some content";
        file_put_contents("/tmp/testfile.1234", $content);
        $this->assertEquals($content, SaveThePage::fetchpage("file:///tmp/testfile.1234"));
    }

    public function testConvertEncoding ()
    {
        $html = "Some ¶ “text that contains highbyte chars”";
        $this->assertEquals("Some &para; &ldquo;text that contains highbyte chars&rdquo;",SaveThePage::convertencoding($html));
    }
}
