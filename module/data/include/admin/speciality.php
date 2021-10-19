<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("filesys.php"); 
es_include("localobject.php");

class DataSpeciality extends LocalObject
{
	var $module;
	
	function DataSpeciality($module, $data = array())
	{
		parent::LocalObject($data);

		$this->module = $module;
	}

	function LoadByID($id)
	{
		$query = "SELECT s.*, s.Score2016 as Score
					FROM `data_speciality` AS s						
					WHERE s.SpecialityID=".Connection::GetSQLString($id);
		$this->LoadFromSQL($query);

		if ($this->GetProperty("SpecialityID"))
		{
			$stmt = GetStatement();
			$query = "SELECT e.SubjectID, e.Score FROM `data_ege` AS e WHERE e.SpecialityID=".Connection::GetSQLString($id);
			$this->SetProperty("Ege", json_encode($stmt->FetchList($query)));
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function Save()
	{
	    $result1 = $this->Validate();
		if (!$result1)
		{
		    return false;
		}
		
		$ege = json_decode($this->GetProperty("Ege"), true);
        if ($this->IsPropertySet('AdditionalList')){
            foreach ($this->GetProperty('AdditionalList') as $key => $value){
                $this->SetProperty('Additional' . ($key + 1), $value['Value']);
            }
        }
		$additionalCount = 0;
		if(strlen(trim($this->GetProperty("Additional1"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional2"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional3"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional4"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional5"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional6"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional7"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional8"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional9"))) > 0) $additionalCount++;
		if(strlen(trim($this->GetProperty("Additional10"))) > 0) $additionalCount++;
		$score = intval($this->GetProperty("Score"));
		if($score == '') $score = 0;
		$avgScore = intval($score / ($additionalCount + count($ege)));

		$stmt = GetStatement();

		if ($this->GetIntProperty("SpecialityID") > 0)
		{
			$query = "UPDATE `data_speciality` SET
						UniversityID=".$this->GetPropertyForSQL("UniversityID").", 
						DirectionID=".$this->GetPropertyForSQL("DirectionID").", 
						Title=".$this->GetPropertyForSQL("Title").", 
						StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
						Recruitment=".$this->GetPropertyForSQL("Recruitment").", 
						Score2016=".$this->GetPropertyForSQL("Score").", 
						AvgScore2016=".Connection::GetSQLString($avgScore).",
						Additional1=".$this->GetPropertyForSQL("Additional1").", 
						Additional2=".$this->GetPropertyForSQL("Additional2").", 
						Additional3=".$this->GetPropertyForSQL("Additional3").", 
						Additional4=".$this->GetPropertyForSQL("Additional4").", 
						Additional5=".$this->GetPropertyForSQL("Additional5").", 
						Additional6=".$this->GetPropertyForSQL("Additional6").", 
						Additional7=".$this->GetPropertyForSQL("Additional7").", 
						Additional8=".$this->GetPropertyForSQL("Additional8").", 
						Additional9=".$this->GetPropertyForSQL("Additional9").", 
						Additional10=".$this->GetPropertyForSQL("Additional10").", 
						Students=".$this->GetPropertyForSQL("Students").", 
						Military=".$this->GetPropertyForSQL("Military").", 
						Delay=".$this->GetPropertyForSQL("Delay").", 
						Hostel=".$this->GetPropertyForSQL("Hostel").", 
						Employment=".$this->GetPropertyForSQL("Employment").", 
						Salary=".$this->GetPropertyForSQL("Salary").", 
						Link=".$this->GetPropertyForSQL("Link").",
						Content=".$this->GetPropertyForSQL("Content")."
				WHERE SpecialityID=".$this->GetIntProperty("SpecialityID");
			
			$deleteQuery = "DELETE FROM `data_ege` WHERE SpecialityID=".$this->GetIntProperty("SpecialityID");
			$stmt->Execute($deleteQuery);
		}
		else
		{
			$query = "INSERT INTO `data_speciality` SET
						UniversityID=".$this->GetPropertyForSQL("UniversityID").", 
						DirectionID=".$this->GetPropertyForSQL("DirectionID").", 
						Title=".$this->GetPropertyForSQL("Title").", 
						StaticPath=".$this->GetPropertyForSQL("StaticPath").", 
						Recruitment=".$this->GetPropertyForSQL("Recruitment").", 
						Score2016=".$this->GetPropertyForSQL("Score").",
						AvgScore2016=".Connection::GetSQLString($avgScore).",
						Additional1=".$this->GetPropertyForSQL("Additional1").", 
						Additional2=".$this->GetPropertyForSQL("Additional2").", 
						Additional3=".$this->GetPropertyForSQL("Additional3").", 
						Additional4=".$this->GetPropertyForSQL("Additional4").", 
						Additional5=".$this->GetPropertyForSQL("Additional5").", 
						Additional6=".$this->GetPropertyForSQL("Additional6").", 
						Additional7=".$this->GetPropertyForSQL("Additional7").", 
						Additional8=".$this->GetPropertyForSQL("Additional8").", 
						Additional9=".$this->GetPropertyForSQL("Additional9").", 
						Additional10=".$this->GetPropertyForSQL("Additional10").",
						Students=".$this->GetPropertyForSQL("Students").", 
						Military=".$this->GetPropertyForSQL("Military").", 
						Delay=".$this->GetPropertyForSQL("Delay").", 
						Hostel=".$this->GetPropertyForSQL("Hostel").", 
						Employment=".$this->GetPropertyForSQL("Employment").", 
						Salary=".$this->GetPropertyForSQL("Salary").", 
						Link=".$this->GetPropertyForSQL("Link").",
						Content=".$this->GetPropertyForSQL("Content");
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("SpecialityID") > 0)
				$this->SetProperty("SpecialityID", $stmt->GetLastInsertID());
			
			for($i=0; $i<count($ege); $i++)
			{
				$query = "INSERT INTO `data_ege` SET
					SpecialityID=".$this->GetPropertyForSQL("SpecialityID").",
					SubjectID=".intval($ege[$i]["SubjectID"]).",
					Score=".intval($ege[$i]["Score"]).",
					SortOrder=".intval($i+1);
				$stmt->Execute($query);
			}

            $specialityStudy = new SpecialityStudy;
            $specialityStudy->saveForSpeciality($this->GetIntProperty("SpecialityID"), $this->GetProperty('StudyList'));

			return true;
		}
		else
		{
			$this->AddError("sql-error");
			return false;
		}
	}
	
	function Validate()
	{
		if(!$this->ValidateNotEmpty("Title"))
			$this->AddError("speciality-title-empty", $this->module);

        if ($this->GetProperty("Recruitment") != "Y")
            $this->SetProperty("Recruitment", "N");
			
		return !$this->HasErrors();
	}

    public function prepareForTemplate(){
	    if ($this->IsPropertySet('AdditionalList')){
	        foreach ($this->GetProperty('AdditionalList') as $key => $value){
	            $this->SetProperty("Additional{$key}", $value['Value']);
            }
        }
	    else{
            $additionList = [];
            for ($i = 0; $i <= 10; $i++){
                $addition = $this->GetProperty("Additional{$i}");
                if (!empty($addition)){
                    $additionList[] = [
                        'Name' => "Additional{$i}",
                        'Value' => $addition,
                    ];
                }
            }

            $this->SetProperty('AdditionalList', $additionList);
        }
    }
}

