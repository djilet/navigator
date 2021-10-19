<?php
class ApiResponse extends CommonObject
{
	private $data;
	private $status;
	private $code;

	public function SetData($data)
	{
		$this->data = $data;
	}

	public function SetStatus($status)
	{
		$this->status = $status;
	}

	public function SetCode($code)
	{
		$this->code = $code;
	}

	public function Output()
	{
		header("Content-Type: application/json; charset=utf-8");
		$body = array(
			"Status" => $this->status,
			"Code" => $this->code,
			"ErrorList" => $this->HasErrors() ? $this->GetErrors() : null,
			"MessageList" => $this->HasMessages() ? $this->GetMessages() : null,
			"Data" => $this->data,
		);
		echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}
}