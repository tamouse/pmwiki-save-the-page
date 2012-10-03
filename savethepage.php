<?php if (!defined('PmWiki')) exit();// Time-stamp: <2012-10-03 09:38:43 tamara>
/** savethepage.php
 *
 * Copyright (C) 2012 by Tamara Temple
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * \author    Tamara Temple <tamara@tamaratemple.com>
 * \since     2012-04-15
 * \copyright 2012 by Tamara Temple
 * \license   GPLv3
 * \version   0.1
 *
 * SaveThePage is a PmWiki Cookbook Recipe that will enable a user to
 * save the contents of a web page in her wiki (similar to how
 * evernote works). The page will be converted from the HTML into
 * PmWiki Markup along the way.
 *
 */

$RecipeInfo['SaveThePage']['Version'] = '2012-06-25';

define('STPDIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'savethepage'.DIRECTORY_SEPARATOR);

// Bundle the recipe
require(STPDIR.'bundlepages.php');

require_once(STPDIR."simple_html_dom.php");

Markup('savethepage','inline','/\\(:savethepage:\\)/e',
       "Keep(STP_CreateBookmarklet(\$pagename))");
$HandleActions['savethepage']='STP_SaveThePage';

/**
 * Recipe variables (can be set before including the recipe
 * to replace the default values.
 */

SDV($STP_PagePrefix,'');
SDV($STP_PageSuffix,'');
SDV($STP_NewPageNamePrefix,'NewSavedPage');
SDV($STP_PageFmt,"
(:linebreaks:)
Summary:\$summary
Tags:\$tags
Source:\$stp_url
Title: \$title
(:title {*$:Title}:)
Saved:\$time
(:nolinebreaks:)

(:nolinkwikiwords:)
\$text
(:linkwikiwords:)

");

/**
 * Create the bookmarklet that enables saving a page
 *
 * The bookmarklet is a little bit of javascript tucked inside
 * a link that the user can drag to their bookmark bar. When
 * clicked on, the bookmarklet with commence to save the current
 * web page to the wiki.
 *
 * @returns result of FmtPageName with bookmarklet added
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $pagename
 **/
function STP_CreateBookmarklet ($pagename)
{
  $bookmarklet_code = STP_Compress(file_get_contents(STPDIR.'bookmarklet.js'));
  $bookmarklet="<a href=\"javascript:$bookmarklet_code\" title=\"Save The Page bookmarklet\">Save The Page</a>";
  return FmtPageName("Save the page bookmarklet: $bookmarklet - drag to bookmarks bar!", $pagename);
} // END function STP_CreateBookmarklet

/**
 * compress extra white space out of a string
 *
 * This is used to make the bookmarklet javascript fit on one line in
 * the actual bookmarklet.
 *
 * @returns string compressed string
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $s - uncompressed string
 *
 * TODO: strip comments as well
 **/
function STP_Compress ($s)
{
  return preg_replace('/[[:space:]]+/','%20',$s);
} // END function STP_Compress

if ($action=='savethepage') {
  $action='edit';
  $STP_OldEditHandler=$HandleActions[$action];
  $HandleActions[$action] = 'STP_SaveThePage';
}

/**
 * Reformat the web page in PmWiki formatting and open
 * the page in an edit box.
 *
 * @returns void - action taken is to edit the page
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $pagename
 **/
function STP_SaveThePage ($pagename)
{
  global $STP_OldEditHandler, $STP_PagePrefix, $STP_PageSuffix, $Now, $STP_PageFmt;
  Lock(2);
  // information is supplied in the $_POST variable from the bookmarklet (we hope)
  $stp_url=(isset($_REQUEST['url'])?$_REQUEST['url']:'');
  if (empty($stp_url)) {
    Abort("Url not specified: stp_url=$stp_url");
  }
  $html = STP_FetchPage($stp_url);
  if (false === $html) {
    Abort("Could not retrieve $stp_url");
  }
  
  $base = STP_GetBaseUrl($stp_url);
  if (false === $base) {
    Abort("Could not determine base url from $stp_url");
  }
  
  $wikitext = STP_ConvertHTML($html,$base);
  if (empty($wikitext)) Abort("html2wiki did not return any text");

  $wikitext = STP_ConvertEncoding($wikitext);
  if (false === $wikitext) {
    Abort("Could not convert contents of $stp_url");
  }

  $dom = new simple_html_dom();
  $dom->load(STP_ConvertEncoding($html));

  $STP_Var=Array();
  $STP_Var['$stp_url'] = $stp_url;
  $STP_Var['$title'] = trim(STP_ExtractTitle($dom));
  $STP_Var['$summary'] = STP_ExtractDescription($dom);
  $STP_Var['$tags'] = STP_ExtractKeywords($dom);
  $STP_Var['$time'] = date('r');
  $STP_Var['$text'] = $wikitext;
  $text = str_replace(array_keys($STP_Var),array_values($STP_Var),
			 $STP_PageFmt);

  $text =
    $STP_PagePrefix .
    $text .
    $STP_PageSuffix;
  $action='edit';
  $_POST['text']=$text;
  
  $newpage = STP_CreatePageName($pagename,STP_CleanTitle($STP_Var['$title']));
  $STP_OldEditHandler($newpage);
} // END function STP_SaveThePage

/**
 * Retrieve the web page at $url using curl
 *
 * @returns string - contents of web page
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $url
 **/
function STP_FetchPage ($url)
{
  $ch = curl_init();
  $c_options = array(
		     CURLOPT_URL=>$url,
		     CURLOPT_USERAGENT=>"Mozilla/5.0",
		     CURLOPT_HEADER=>false,
		     CURLOPT_FOLLOWLOCATION=>true,
		     CURLOPT_AUTOREFERER=>true,
		     CURLOPT_MAXREDIRS=>10,
		     CURLOPT_CONNECTTIMEOUT=>30,
		     CURLOPT_TIMEOUT=>120,
		     CURLOPT_RETURNTRANSFER=>true,
		     );
  curl_setopt_array($ch,$c_options);
  $contents = curl_exec($ch);
  curl_close($ch);
  return $contents;
} // END function STP_FetchPage

/**
 * Convert the page's encoding into HTML-ENTITIES
 *
 * @returns string - $html converted to HTML-ENTITIES
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $html
 **/
function STP_ConvertEncoding ($html)
{
  if (empty($html)) {
    return FALSE;
  }

  $e=mb_detect_encoding($html);
  if (FALSE === $e) {
    $e = 'ISO-8859-1';
  }
  return mb_convert_encoding($html,'HTML-ENTITIES',$e);
} // END function STP_ConvertEncoding


/**
 * Convert HTML to PmWiki using external program html2wiki
 *
 * Perl Modules required:
 * https://metacpan.org/module/html2wiki
 * https://metacpan.org/module/HTML::WikiConverter
 * https://metacpan.org/module/HTML::WikiConverter::Dialects
 * https://metacpan.org/module/HTML::WikiConverter::PmWiki
 *
 * @returns string - converted text
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $html
 **/
function STP_ConvertHTML ($html,$baseurl)
{
  // do nothing if no input
  if (empty($html)) return $html;

  // kludge for running on mac and using fink utilities
  putenv("PATH=/sw/bin:".getenv("PATH"));
  
  // verify that the tidy program exists
  $tidy = trim(shell_exec(escapeshellcmd("which tidy")));
  if (empty($tidy)) Abort('Could not find tidy program');

  // verify that html2wiki program exists
  $html2wiki = trim(shell_exec(escapeshellcmd("which html2wiki")));
  if (empty($html2wiki)) Abort('Could not find program html2wiki');

  /**
   * Fix up some known issues
   *
   * the allrecipes.com site has a css bug in one of their style
   * statements: "*width:"
   *
   */

  $html = preg_replace("/\*width:.*?;/",'',$html);

  // run $html through converter
  $tempfile=tempnam(sys_get_temp_dir(), 'STP');
  file_put_contents($tempfile,$html);
  if (!file_exists($tempfile)) {
    Abort("$tempfile does not exist!!!");
  }

  $cmd="tidy $tempfile 2>/dev/null | $html2wiki --dialect=PmWiki --base-uri '$baseurl'  2>&1";
  $wikitext = shell_exec($cmd);
  unlink($tempfile);
  return $wikitext;
} // END function STP_ConvertHTML

/**
 * Determine the page's base url
 *
 * @returns string base url
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $url
 **/
function STP_GetBaseUrl ($url)
{
  if (empty($url)) return $url;
  
  $url_components = parse_url($url);
  
  if (!isset($url_components['scheme'])) $url_components['scheme'] = 'http';
  if (!isset($url_components['host'])) $url_components['host'] = $_SERVER['HTTP_HOST'];
  if (!isset($url_components['port'])) $url_components['port'] = "80";
  $baseurl = sprintf("%s://%s:%s/",
		     $url_components['scheme'],
		     $url_components['host'],
		     $url_components['port']);
  return $baseurl;

} // END function STP_GetBaseUrl

/**
 * Create the new page name for this clipping based on the title
 * gleened from the html
 *
 * @returns string new page name
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $pagename
 * @param string $title
 **/
function STP_CreatePageName ($pagename,$title)
{
  global $STP_NewPageNamePrefix;
  if (empty($title)) {
    $title=$STP_NewPageNamePrefix.date("YmdHis");
  }
  $newpagename = MakePageName($pagename,$title.date("YmdHis"));
  return $newpagename;
} // END function STP_CreatePageName

/**
 * Extract the title from the page's html text
 *
 * @returns string title text
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $dom - simple html dom object
 **/
function STP_ExtractTitle ($dom)
{
  $eltitle = $dom->find('title',0);
  if (empty($eltitle)) {
    // no title in document
    return '';
  }
  $title = $eltitle->innertext;
  return $title;
} // END function STP_ExtractTitle

/**
 * Clean up the title so it will work as a wiki page name
 *
 * @returns string cleaned title
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $title
 **/
function STP_CleanTitle ($title)
{
  $clean = mb_convert_encoding(trim($title),'ASCII','HTML-ENTITIES');
  $clean = preg_replace("/[^[:alnum:] ]+/",'',$clean);
  $clean = ucwords(strtolower($clean));
  return $clean;

} // END function STP_CleanTitle

/**
 * Retrieve the description field from the page's meta
 *
 * @returns string - contents of description meta
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $dom
 **/
function STP_ExtractDescription ($dom)
{
  $eldesc=$dom->find("meta[name=description]",0);
  if (empty($eldesc)) {
    return 'a short description of the page';
  }
  return $eldesc->content;
} // END function STP_ExtractDescription

/**
 * Retrieve the keywords field from the page's meta
 *
 * @returns string - list of keywords
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $dom
 **/
function STP_ExtractKeywords ($dom)
{
  $elkwds=$dom->find("meta[name=keywords]",0);
  if (empty($elkwds)) {
    return 'saved page';
  }
  return $elkwds->content;
} // END function STP_ExtractKeywords