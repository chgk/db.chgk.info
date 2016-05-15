<?php

/**
 * https://github.com/chgk/db.chgk.info/issues/11
 */
$result = db_query("SELECT nid, title FROM node WHERE type = 'chgk_issue' ");

while ($obj = db_fetch_object ($result)) {
	$textId = $obj->title;
	if (preg_match('/\./',$textId)) {
		continue;
	}
	preg_match('/^(.*)-(\d+)$/', $textId, $matches);
	$node = node_load($obj->nid);
	$node->title = $matches[1].'.1-'.$matches[2];
	node_save($node);
}
