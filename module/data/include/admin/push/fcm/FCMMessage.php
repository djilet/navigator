<?php

/**
 * Date:    24.07.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */
class FCMMessage
{
	private $title;
	private $body;
	private $data;
	private $token = '/topics/all';

	/**
	 * @return mixed
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param mixed $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param mixed $body
	 */
	public function setBody($body)
	{
		$this->body = $body;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	public function setURL($url)
	{
		if (empty($url)) {
			unset($this->data['target']);
			unset($this->data['url']);
			return;
		}
		
		if (!preg_match('/^http/ui', $url)) {
			$url = 'http://'.$url;
		}
		
		$this->data['target'] = 'url';
		$this->data['url'] = $url;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = !empty($token) ? $token : '/topics/all';
	}
	
}