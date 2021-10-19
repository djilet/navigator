<?php

class URLParser
{
	var $host;
	var $fullPath;
	var $shortPath;
	var $fixedPath;
	var $redirectURL;
	var $requestURI;
	var $contentType;
    var $subDomain;
    var $hostPieces;
	var $fileExtension;

    /**
     * replacing or removing a subdomain from the current host
     * @param $subDomain
     * @return string
     */
    public static function getPrefixWithSubDomain($subDomain)
    {
        $urlFilter = new URLParser();
        $currentSubDomain = $urlFilter->GetSubDomain();
        $host = $urlFilter->GetHostName();
        if ($currentSubDomain != $subDomain){
            $hostPieces = $urlFilter->GetHostPieces();
            if ($currentSubDomain && !empty($subDomain)){
                $hostPieces[0] = $subDomain;
            }
            elseif ($currentSubDomain && empty($subDomain)){
                unset($hostPieces[0]);
            }
            elseif (!$currentSubDomain && !empty($subDomain)){
                array_unshift($hostPieces, $subDomain);
            }

            $host = implode('.', $hostPieces);
        }

        return GetCurrentProtocol() . $host;
    }

	function URLParser()
	{
		// Define host
		$this->host = $_SERVER['HTTP_HOST'];
		
		// Split URL to chunks
		$requestURI = $_SERVER['REQUEST_URI'];
		
		// HACK: for Registration path
		$this->requestURI = str_replace('/Registration', '/?Registration', $requestURI);
		
		$this->fullPath = explode("/", $requestURI);
		$lastChunk = explode("?", $this->fullPath[sizeof($this->fullPath) - 1]);
		$this->fullPath[sizeof($this->fullPath) - 1] = $lastChunk[0];
		array_shift($this->fullPath);

		//sub domain
        //todo get subdomain from config
        $this->hostPieces = explode('.', $this->host);
        $this->subDomain = count($this->hostPieces) > 2 ? $this->hostPieces[0] : null;


		// Define Content-Type
		$fileName = explode(".", $lastChunk[0]);
		if(count($fileName) > 1)
			$this->fileExtension = '.'.$fileName[count($fileName) - 1];
		else
			$this->fileExtension = '';

		switch($this->fileExtension)
		{
			case ".txt":
				$this->contentType = "text/plain";
				break;
			case ".xml":
				$this->contentType = "text/xml";
				break;
			default:
				$this->contentType = "text/html";
				break;
		}
	}

	function Emulate()
	{
		$this->fileExtension = HTML_EXTENSION;
		$this->contentType = "text/html";
		$this->fullPath = array();
	}

	function GetFullPathAsString()
	{
		return "/".implode("/", $this->fullPath);
	}

	function GetRequestURI()
    {
        return $this->requestURI;
    }

	function GetShortPathAsArray()
	{
		if ($this->shortPath)
			return $this->shortPath;

		$this->shortPath = $this->fullPath;
		$possibleProjectPath = "/";
		$found = false;
		for ($i = 0; $i < sizeof($this->shortPath); $i++)
		{
			if ($possibleProjectPath == GetDirPrefix())
			{
				$found = true;
				break;
			}
			$possibleProjectPath .= $this->shortPath[$i]."/";
		}

		if (!$found)
		{
			Send301(GetUrlPrefix().INDEX_PAGE.HTML_EXTENSION);
		}

		if ($i > 0 && $i < sizeof($this->shortPath))
		{
			$this->shortPath = array_slice($this->shortPath, $i);
		}

		return $this->shortPath;
	}

	function GetFixedPathAsArray()
	{
		if ($this->fixedPath)
			return $this->fixedPath;

		$this->fixedPath = $this->GetShortPathAsArray();
		// Check path & fix problem with index in path
		if (count($this->fixedPath) == 0)
		{
			$this->fixedPath[0] = INDEX_PAGE;
		}
		else if (count($this->fixedPath) == 1)
		{
			if ($this->fixedPath[0] == "")
			{
				$this->fixedPath[0] = INDEX_PAGE;
			}
			else if (substr($this->fixedPath[0], -strlen($this->fileExtension)) == $this->fileExtension)
			{
				$this->fixedPath[0] = substr($this->fixedPath[0], 0, strlen($this->fixedPath[0]) - strlen($this->fileExtension));
			}
		}
		else
		{
			$lastIndex = count($this->fixedPath) - 1;
			if ($this->fixedPath[$lastIndex] == INDEX_PAGE.HTML_EXTENSION || $this->fixedPath[$lastIndex] == "")
			{
				unset($this->fixedPath[$lastIndex]);
			}
			else if (substr($this->fixedPath[$lastIndex], -strlen($this->fileExtension)) == $this->fileExtension)
			{
				$this->fixedPath[$lastIndex] = substr($this->fixedPath[$lastIndex], 0, strlen($this->fixedPath[$lastIndex]) - strlen($this->fileExtension));
			}
		}

		// URL is passed incorrectly, redirect to correct path
		// Index page condition
		if (count($this->fixedPath) == 1 && $this->fixedPath[0] == INDEX_PAGE)
		{
			if (!(count($this->shortPath) == 1 && ($this->shortPath[0] == '' || $this->shortPath[0] == INDEX_PAGE.HTML_EXTENSION)))
			{
				$this->redirectURL = GetUrlPrefix();
			}
		}
		// Other pages condition
		else if ($this->fixedPath[count($this->fixedPath) - 1] == $this->shortPath[count($this->shortPath) - 1])
		{
			// Redirect only in case final chunk of the path doesn't look like file
			$chunks = explode(".", $this->fixedPath[count($this->fixedPath) - 1]);
			if (!(is_array($chunks) && count($chunks) > 1))
			{
				$this->redirectURL = GetUrlPrefix().implode("/", $this->shortPath)."/";
			}
		}

		return $this->fixedPath;
	}

	function GetRedirectURL()
	{
		return $this->redirectURL;
	}

	function GetHostName()
	{
		return $this->host;
	}

	function GetSubDomain()
    {
        return $this->subDomain;
    }

    function GetHostPieces()
    {
        return $this->hostPieces;
    }

	function IsXML()
	{
		if ($this->contentType == 'text/xml')
			return true;
		else
			return false;
	}

	function IsHTML()
	{
		// . - means request to folder (like /path/to/page/)
		if ($this->contentType == 'text/html' && ($this->fileExtension == HTML_EXTENSION || $this->fileExtension == "."))
			return true;
		else
			return false;
	}
}

?>