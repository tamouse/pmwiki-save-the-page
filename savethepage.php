<?php if (!defined('PmWiki')) exit("Must be run under PmWiki");
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

$RecipeInfo['SaveThePage']['Version'] = '2012-11-23.1b';

define('STPDIR', dirname(__FILE__).DIRECTORY_SEPARATOR.'savethepage'.DIRECTORY_SEPARATOR);

// Bundle the recipe
require_once(STPDIR.'bundlepages.php');

// Get the functions
require_once(STPDIR.'SaveThePage.php');

// Get the dom manipulator
require_once(STPDIR.'simple_html_dom.php');

Markup('savethepage','inline','/\\(:savethepage:\\)/e',
       "Keep(STP_CreateBookmarklet(\$pagename))");
$HandleActions['savethepage']='STP_SaveThePage';

/**
 * Recipe variables (can be set before including the recipe
 * to replace the default values.
 */

SDV($STP_PagePrefix,'');
SDV($STP_PageSuffix,'');
SDV($STP_NewPageNamePrefix,'');
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
SDV($STP_BookmarkletFmt,'Save The Page bookmarklet: <a href="javascript:$bookmarklet">Save The Page</a> - drag to bookmarks bar!');


if ($action=='savethepage') {
  $action='edit';
  $STP_OldEditHandler=$HandleActions[$action];
  $HandleActions[$action] = 'STP_SaveThePage';
}



/**
 * Create the bookmarklet that enables saving a page
 *
 * The bookmarklet is a little bit of javascript tucked inside
 * a link that the user can drag to their bookmark bar. When
 * clicked on, the bookmarklet with commence to save the current
 * web page to the wiki.
 *
 * @return result of FmtPageName with bookmarklet added
 * @author Tamara Temple <tamara@tamaratemple.com>
 * @param string $pagename
 **/
function STP_CreateBookmarklet ($pagename)
{
  global $MessagesFmt;
  $MessagesFmt[]='<p class="wikimsg">Inside '.__FUNCTION__.'</p>';
  global $STP_BookmarkletFmt;
  $bookmarklet = SaveThePage::bookmarklet(STPDIR.'bookmarklet.js', $STP_BookmarkletFmt);
  $result = FmtPageName($bookmarklet, $pagename);
  return $result;
 } // END function STP_CreateBookmarklet



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
  global $MessagesFmt, $STP_OldEditHandler, $STP_PagePrefix, $STP_PageSuffix, $Now, $STP_PageFmt;
  Lock(2);
  // information is supplied in the $_GET variable from the bookmarklet (we hope)
  if (array_key_exists('url', $_GET) && (!empty($_GET['url']))) {
    $stp_url = $_GET['url'];
  } else {
    Abort("Url not specified");
  }

  $html = SaveThePage::fetchpage($stp_url);
  if (false === $html) {
    Abort("Could not retrieve $stp_url");
  }
  
  file_put_contents("/tmp/stp_".strftime("%F-%T").".out", $html);
  $base = SaveThePage::getbaseurl($stp_url);
  if (false === $base) {
    Abort("Could not determine base url from $stp_url");
  }

  $filterresults = SaveThePage::filter($html,"html2wiki --dialect=PmWiki --base-uri=$base --escape-entities");
  if ($filterresults['return'] != 0) {
    $MessagesFmt[] = "<p class=\"wikimsg\">Errors from html2wiki:</p><pre>${filterresults['errors']}</pre>";
  }
  if (empty($filterresults['output'])) {
    $wikitext = $html;
  } else {
    $wikitext = $filterresults['output'];
  }

  $dom = new simple_html_dom();
  $dom->load($html);

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
  
  $newpage = STP_CreatePageName($pagename,SaveThePage::cleantitle($STP_Var['$title']));
  $STP_OldEditHandler($newpage);
} // END function STP_SaveThePage

/**
 * Create the new page name for this clipping based on the title
 * gleened from the html
 *
 * @return string new page name
 * @param string $pagename
 * @param string $title
 **/
function STP_CreatePageName ($pagename,$title)
{
  global $STP_NewPageNamePrefix;
  $newpagename = MakePageName($pagename,$STP_NewPageNamePrefix.$title.date("YmdHis"));
  return $newpagename;
} // END function STP_CreatePageName

/**
 * Extract the title from the page's html text
 *
 * @return string title text
 * @param object $dom - simple html dom object
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
 * Retrieve the description field from the page's meta
 *
 * @return string - contents of description meta
 * @param object $dom
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
 * @return string - list of keywords
 * @param object $dom
 **/
function STP_ExtractKeywords ($dom)
{
  $elkwds=$dom->find("meta[name=keywords]",0);
  if (empty($elkwds)) {
    return 'saved page';
  }
  return $elkwds->content;
} // END function STP_ExtractKeywords
