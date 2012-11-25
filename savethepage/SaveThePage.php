<?php
/**
 * Encapsulate the methods for saving a web page as a wiki page
 **/

/**
* SaveThePage
*
* @uses     
*
* @package  PmWiki
* @author   Tamara Temple <tamara@tamaratemple.com>
* @license  GPL3
* @link     http://pmwiki.org/wiki/Cookbook/SaveThePage
*/
class SaveThePage
{
    function __construct()
    {

    }



    /**
     * bookmarklet
     * 
     * @param string $bookmarkletfn Bookmaklet code file.
     * @param string $format        Format to embed bookmarklet code into.
     *
     * @access public
     *
     * @return string compressed bookmarklet code.
     */
    function bookmarklet($bookmarkletfn, $format='<a href="javascript:$bookmarklet">Bookmaklet</a>')
    {
        $replacements['$bookmarklet'] = self::compress(file_get_contents($bookmarkletfn));
        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

        /**
     * cleantitle
     * 
     * @param string $title The page title from the web page.
     *
     * @access public
     *
     * @return string A clean page title.
     */
    function cleantitle ($title)
    {
        $clean = mb_convert_encoding(trim($title), 'ASCII', 'HTML-ENTITIES');
        $clean = preg_replace("/[^[:alnum:] ]+/", '', $clean);
        $clean = preg_replace("/[[:space:]]+/", ' ', $clean);
        $clean = ucwords(strtolower($clean));
        return $clean;
    }

    /**
     * compress
     * 
     * @param string $s Input string to compress.
     *
     * @access public
     *
     * @return string Input string with spans of white space converted to %20 codes.
     */
    function compress($s)
    {
        return preg_replace('/[[:space:]]+/', ' ', $s);
    }

    /**
     * fetchpage
     * 
     * @param string $url Uniform Resource Locator of page to fetch.
     *
     * @access public
     *
     * @return string Fetched page content.
     */
    function fetchpage ($url)
    {
      $ch = curl_init();
      $c_options = array(
        CURLOPT_URL              => $url,
        CURLOPT_USERAGENT        => "Mozilla/5.0",
        CURLOPT_HEADER           => false,
        CURLOPT_FOLLOWLOCATION   => true,
        CURLOPT_AUTOREFERER      => true,
        CURLOPT_MAXREDIRS        => 10,
        CURLOPT_CONNECTTIMEOUT   => 30,
        CURLOPT_TIMEOUT          => 120,
        CURLOPT_RETURNTRANSFER   => true,
        );
      curl_setopt_array($ch,$c_options);
      $contents = curl_exec($ch);
      curl_close($ch);
      return $contents;
    }

    /**
     * convertencoding
     * 
     * @param string $html Contents of page to convert to HTML-ENTITIES.
     *
     * @access public
     *
     * @return string Converted HTML page.
     */
    function convertencoding ($html)
    {
      if (empty($html)) {
        return FALSE;
      }

      $e=mb_detect_encoding($html);
      if (FALSE === $e) {
        $e = 'ISO-8859-1';
      }
      return mb_convert_encoding($html,'HTML-ENTITIES',$e);
    }

}