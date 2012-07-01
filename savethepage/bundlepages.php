<?php if (!defined('PmWiki')) exit(); // Time-stamp: <2012-04-15 10:18:37 tamara>
// From http://www.pmwiki.org/wiki/Cookbook/ModuleGuidelines
// Add a custom wikipage storage location for bundles pages.
global $WikiLibDirs;
$PageStorePath = dirname(__FILE__)."/wikilib.d/\$FullName";
$where = count($WikiLibDirs);
if ($where>1) $where--;
array_splice($WikiLibDirs, $where, 0,
  array(new PageStore($PageStorePath)));
