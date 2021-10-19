<?php

interface TemplateListInterface{
	/**
	 * Adding item in list
	 * In $fields can insert any data as [key=>value]
	 * @param $id
	 * @param $name
	 * @param array $list
	 * @param array $fields ([key=>value])
	 * @return mixed
	 */
	public function addItemInTemplateList($id, $name, array &$list, array $fields = array());

	/**
	 * Should return a list with the keys Name and ID
	 * @example [0=>[Id=>'', Name=>'', SomeField=>'']
	 * @return array
	 */
	public function getListForTemplate(); //:array
}