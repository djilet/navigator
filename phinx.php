<?php

$file = dirname(__FILE__) . "/website/configure.xml";

$parser = xml_parser_create("UTF-8");
xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
xml_parse_into_struct($parser, implode("", file($file)), $values, $tags);
xml_parser_free($parser);

$folder = $values[1]['attributes']['Folder'];
$config =  dirname(__FILE__) . '/website/' . $folder . '/configure.ini';

if ($db = parse_ini_file($config, true)){
	$db = $db['mysql'];
}

return [
	'environments' =>
		[
			'default_migration_table' => 'phinxlog',
			'default_database' => 'development',
			'development' => [
				'adapter' => 'mysql',
				'host' => $db['Host'],
				'name' => $db['Database'],
				'user' => $db['User'],
				'pass' => $db['Password'],
				'port' => '3306',
				'charset' => 'utf8',
			]
		],

	'paths' =>
		[
			'migrations' => dirname(__FILE__) . '/_db/migrations',
			'seeds' => dirname(__FILE__) . '/_db/seeds',
		],

	'version_order' => 'creation',
];