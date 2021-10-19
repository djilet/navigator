<?php

class SocialAuth extends LocalObject {
	
	private $module;

	/**
	 * SocialAuth constructor.
	 *
	 * @param $module
	 */
	public function __construct($module)
	{
		parent::LocalObject();
		$this->module = $module;
	}

	public function save(LocalObject $request)
	{
		$deviceID = $request->GetProperty("AuthDeviceID");
		$socialType = $request->GetProperty("type");
		$socialID = $request->GetProperty("id");
		$surname = $request->GetProperty("surname");
		$firstname = $request->GetProperty("firstname");
		$email = $request->GetProperty("email");
		$phone = $request->GetProperty("phone");
		$city = $request->GetProperty("city");
		
		if (empty($socialID)) {
			$this->AddError('auth-social-id-empty', $this->module);
			return false;
		}
		
		$typeList = get_enum_values('social_auth', 'SocialType');
		if (!in_array($socialType, $typeList)) {
			$this->AddError('auth-social-unknown', $this->module, array(
				'socialType' => $socialType,
				'KnowSocial' => implode(', ', $typeList)
			));
			return false;
		}
		
		$stmt = GetStatement();
		$query = "INSERT INTO `social_auth` ( `DeviceID`, `SocialType`, `SocialID`, `SocialSurname`, 
						`SocialFirstName`, `SocialEmail`, `SocialPhone`, `SocialCity`) 
				VALUES (
					".Connection::GetSQLString($deviceID).",
					".Connection::GetSQLString($socialType).",
					".Connection::GetSQLString($socialID).",
					".Connection::GetSQLString($surname).",
					".Connection::GetSQLString($firstname).",
					".Connection::GetSQLString($email).",
					".Connection::GetSQLString($phone).",
					".Connection::GetSQLString($city)."
				)
				ON DUPLICATE KEY UPDATE
					`SocialID` = VALUES(`SocialID`),
					`SocialSurname` = VALUES(`SocialSurname`),
					`SocialFirstName` = VALUES(`SocialFirstName`),
					`SocialEmail` = VALUES(`SocialEmail`),
					`SocialPhone` = VALUES(`SocialPhone`),
					`SocialCity` = VALUES(`SocialCity`)";

		file_put_contents(__DIR__.'/social.log', var_export($query, true));

		return $stmt->Execute($query);
	}
}