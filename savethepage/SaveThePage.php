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
    function bookmarklet($bookmarkletfn, $format='<a href="javascript:$bookmarklet">Bookmarklet</a>')
    {
        $replacements['$bookmarklet'] = self::compress(file_get_contents($bookmarkletfn));
        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    /**
     * cleantitle
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
        $s = preg_replace('/(\r|\n|\r\n)/', '', $s);
        $s = preg_replace('/[[:space:]]+/', ' ', $s);
        $s = preg_replace('/ = /','=', $s);
        $s = preg_replace('/, /', ',', $s);
        $s = preg_replace('/ \+ /', '+', $s);
        $s = preg_replace('/ *{ */', '{', $s);
        $s = preg_replace('/ \(/', '(', $s);
        $s = preg_replace('/\) /', ')', $s);
        $s = preg_replace('/; /', ';', $s);
        return $s;
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
      $e=mb_detect_encoding($html);
      if (FALSE === $e) {
        $e = 'ISO-8859-1';
      }
      return mb_convert_encoding($html,'HTML-ENTITIES',$e);
    }

    /**
     * fetchpage
     * 
     * @param string $url Uniform Resource Locator of page to fetch.
     *
     * @access public
     *
     * @return mixed Fetched page content as string, or false on failure.
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
        CURLOPT_CONNECTTIMEOUT   => 5,
        CURLOPT_TIMEOUT          => 10,
        CURLOPT_RETURNTRANSFER   => true,
        CURLOPT_PROTOCOLS        => CURLPROTO_HTTP |
                                    CURLPROTO_HTTPS |
                                    CURLPROTO_FTP |
                                    CURLPROTO_FTPS,
        CURLOPT_REDIR_PROTOCOLS  => CURLPROTO_HTTP |
                                    CURLPROTO_HTTPS |
                                    CURLPROTO_FTP |
                                    CURLPROTO_FTPS,
        );
      curl_setopt_array($ch,$c_options);
      $contents = curl_exec($ch);
      curl_close($ch);
      return $contents;
    }

    /**
     * filter
     * 
     * @param string $text String to run through filter
     * @param string $filter Filter command
     * @param mixed $options Options to append to $filter to run command
     *          (Note: may be string or array, array will be imploded into a string separated by spaces)
     * 
     * @access public
     *
     * @return array [output, errors, return]
     **/
    function filter ($text,$filter,$options=null)
    {
        if (is_array($options)) {
            $options = implode(" ", $options);
        }
        $dspec = array(
            0 => array('pipe','r'), // STDIN
            1 => array('pipe','w'), // STDOUT
            2 => array('pipe','w'), // STDERR
            );

        $process = proc_open("$filter $options", $dspec, $pipes);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // 2 => readable handle connected to child stderr

            fwrite($pipes[0], $text);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
        } else {
            throw new Exception("Could not create process!", 1);
        }
        return array(0 => $output,        'output' => $output,
                     1 => $errors,        'errors' => $errors,
                     2 => $return_value,  'return' => $return_value
                    );
    }
    /**
     * Determine the page's base url
     *
     * @param string $url
     * 
     * @access public
     *  
     * @return string base url
     **/
    function getbaseurl ($url)
    {
      if (empty($url)) return $url;
      
      $url_components = parse_url($url);
      
      if (!array_key_exists('scheme',$url_components)) $url_components['scheme'] = 'http';
      if (!array_key_exists('host',$url_components)) $url_components['host'] = $_SERVER['HTTP_HOST'];
      if (!array_key_exists('port',$url_components)) $url_components['port'] = "80";
      $baseurl = sprintf("%s://%s:%s/",
                 $url_components['scheme'],
                 $url_components['host'],
                 $url_components['port']);
      return $baseurl;

    } // END function STP_GetBaseUrl

}