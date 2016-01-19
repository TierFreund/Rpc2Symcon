<?
abstract class uRpcBase extends IPSModule{
	private $_io=null;
	private $_boIsOnline=null;
	
	public function Create(){
        parent::Create();
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyInteger("Port", 0);
        $this->RegisterPropertyInteger("Timeout", 5);
        $this->RegisterPropertyString("ConnectionType", "socks");
		$this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
 	}
    public function ApplyChanges(){
        parent::ApplyChanges();
        $this->_io=null;
		$suffix=str_ireplace(array('module','_'),'',get_class($this));
        $this->RegisterProfileBooleanEx("RPC.OnlineState", "Information", "", "", Array(
                                             Array(false, "offline",  "", -1),
                                             Array(true, "online",  "", -1)
        ));
        $this->RegisterVariableBoolean("OnlineStateVAR", "Online Status","RPC.OnlineState");
	}
	public function Test(){
		return $this->GetOnlineState();
	}	
	protected function Call($service,$action,$arguments,$filter=null){
		if(!$con=$this->GetServiceConnData($service)) 
			throw new Exception ("Invalid Service Name '$service' :: $action");
		return $this->IO()->Call($url=$con[2],$service=$con[1],$action,$arguments,$filter,$ReturnValue=null,$Port=$con[0]);	
	}	
	protected abstract function GetServiceConnData($name);  // Override This in Own Modules
	protected function GetOnlineState(){
		if(!is_null($this->_boIsOnline))return $this->_boIsOnline;
		if(!$host=$this->ReadPropertyString("Host"))throw new Exception ("No Hostname");
		if($test=@parse_url($host)['host'])$host=$test;
		$this->_boIsOnline=Sys_Ping($host, 2000);
    	$this->SetValueBoolean('OnlineStateVAR',$this->_boIsOnline);
   		return $this->_boIsOnline; 
	}
    protected function SetValueInteger($Ident, $Value){
		$ID = $this->GetIDForIdent($Ident);
        if (GetValueInteger($ID) <> $Value){
            SetValueInteger($ID, intval($Value));
            return true;
        }
        return false;
    }
    protected function SetValueFloat($Ident, $Value){
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueFloat($ID) <> $Value){
            SetValueFloat($ID, intval($Value));
            return true;
        }
        return false;
    }
    protected function SetValueString($Ident, $Value){
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueString($ID) <> $Value){
            SetValueString($ID, strval($Value));
            return true;
        }
        return false;
    }
    protected function SetValueBoolean($Ident, $Value){
        $ID = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($ID) <> $Value){
            SetValueBoolean($ID, boolval($Value));
            return true;
        }
        return false;
    }
    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name))
            IPS_CreateVariableProfile($Name, 0);
        else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){$MinValue = 0;$MaxValue = 0;} 
		else {$MinValue = $Associations[0][0];$MaxValue = $Associations[sizeof($Associations)-1][0];}
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        foreach($Associations as $Association)
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
	}
    protected function RegisterProfileString($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name))
            IPS_CreateVariableProfile($Name, 3);
        else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 3)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }
    protected function RegisterProfileStringEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){ $MinValue = 0; $MaxValue = 0;} 
		else {$MinValue = $Associations[0][0]; $MaxValue = $Associations[sizeof($Associations)-1][0];}
        $this->RegisterProfileString($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        foreach($Associations as $Association)
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
    }
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize){
		if (!IPS_VariableProfileExists($Name))
      		IPS_CreateVariableProfile($Name, 1);
      	else {
      		$profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1)
            	throw new Exception("Variable profile type does not match for profile " . $Name);
      	}
      	IPS_SetVariableProfileIcon($Name, $Icon);
      	IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
      	IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
		}
    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){ $MinValue = 0; $MaxValue = 0;} 
		else { $MinValue = $Associations[0][0]; $MaxValue = $Associations[sizeof($Associations)-1][0];}
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        foreach($Associations as $Association)
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
    }
    protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize){
		if (!IPS_VariableProfileExists($Name))
      		IPS_CreateVariableProfile($Name, 2);
      	else{
      		$profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 2)
            	throw new Exception("Variable profile type does not match for profile " . $Name);
      	}
      	IPS_SetVariableProfileIcon($Name, $Icon);
      	IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
      	IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	}
    protected function RegisterTimer($Name, $Interval, $Script) {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id === false)$id = 0;
        if ($id > 0){
            if (!IPS_EventExists($id))
                throw new Exception("Ident with name " . $Name . " is used for wrong object type", E_USER_WARNING);
            if (IPS_GetEvent($id)['EventType'] <> 1){
                IPS_DeleteEvent($id);
                $id = 0;
            }
        }
        if ($id == 0){
            $id = IPS_CreateEvent(1);
            IPS_SetParent($id, $this->InstanceID);
            IPS_SetIdent($id, $Name);
        }
        IPS_SetName($id, $Name);
        IPS_SetHidden($id, true);
        IPS_SetEventScript($id, $Script);
        if ($Interval > 0){
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $Interval);
            IPS_SetEventActive($id, true);
        } else{
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
            IPS_SetEventActive($id, false);
        }
    }
    protected function UnregisterTimer($Name) {
        $id = @IPS_GetObjectIDByIdent($Name, $this->InstanceID);
        if ($id > 0){
            if (!IPS_EventExists($id))
                throw new Exception('Timer not present', E_USER_NOTICE);
            IPS_DeleteEvent($id);
        }
    }
	private function IO(){
		if(!$this->_io){
			if(!$this->GetOnlineState())return null;
			if(!$url=$this->ReadPropertyString("Host"))throw new Exception ("No Hostname");
			if(!$port=$this->ReadPropertyString("Port"))throw new Exception ("Invalid Port");
	        if (!$type=$this->ReadPropertyString("ConnectionType"))$type='socks';
			switch ($type) {
				case 'socks': 
					require_once(__DIR__ . '/uRpcIoSocks.class.php');
					$this->_io=new uRpcIoSocks($url,$port); break;
				case 'curl' : 
					require_once(__DIR__ . '/uRpcIoCurl.class.php');
					$this->_io=new uRpcIoCurl($url,$port); break;
				case 'soap' : 
					require_once(__DIR__ . '/uRpcIoSoap.class.php');
					$this->_io=new uRpcIoSoap($url,$port); break;			
				default : throw new Exception ("No or Invalid ConnectionType '$type' ! Allowed : socks|curl|soap");
			}
			$this->_io->SetAuth($this->ReadPropertyString("Username"),$this->ReadPropertyString("Password"));
		}
		return $this->_io;
	}
	private function Patch_autoload_inc($ident, $_c=''){
		$result=false;
		$c=empty($_c)?@file_get_contents(IPS_GetKernelDir().'scripts\\__autoload.php'):$_c;
		$c=str_ireplace(array('<?php','<?','?>'),'',$c);
		if(!preg_match("/##Rpc4Patch##/",$c,$m)){
			$f=array('/*##Rpc4Patch##*/');
			$f[]='function Rpc4_Patch_generated($ident, $_c=""){';
			$f[]=' $Patch=function($ident, &$c){';
			$f[]='  static $a=array("Instance_ID"=>"Instance_ID=0","Channel"=>"Channel=\"Master\"","Speed"=>"Speed=1");';
			$f[]='  $result=false;';
			$f[]='  if(!preg_match_all("/{$ident}_\w+\((.+)\)/",$c,$m))return $result;';
			$f[]='  for($j=0;$j<count($m[0]);$j++){';
			$f[]='   list($line,$found)=array(trim($m[0][$j]),trim($m[1][$j]));';
			$f[]='   $found=explode(\',\',$found);';
			$f[]='   foreach($found as &$f){$f=substr(trim($f),1);if(!empty($a[$f]))$f=$a[$f];}';
			$f[]='   $found=\'$\'.implode(\', $\',$found);';
			$f[]='   $newline=substr($line,0,strpos($line,\'(\')+1).$found.\')\';';
			$f[]='   if(strcmp($line,$newline)!=0){$c=str_replace($line,$newline,$c);$result=true;}';
			$f[]='  }';
			$f[]='  if($result)$c.="\nfunction {$ident}_PatchApplyed(){return true;}\n";';
			$f[]='  return $result;';
			$f[]=' };';
			$f[]=' $c=$_c?$_c:@file_get_contents(IPS_GetKernelDir()."\scripts\__generated.inc.php");';
			$f[]=' $result=false;';
			$f[]=' if(!$c)return false;';
			$f[]=' if(is_array($ident)){foreach($ident as $i)if($Patch($i,$c))$result=true;}';
			$f[]=' else $result=$Patch($ident,$c);';
			$f[]=' if(!empty($_c))return $c;';
			$f[]=' return $result?file_put_contents(IPS_GetKernelDir()."\scripts\__generated.inc.php",$c):null;';
			$f[]='}';
			$c=implode("\r\n",$f)."\n".$c;
			$result=true;
		}
		if(!preg_match("/##Rpc4RunPatch##/",$c,$m)){
			$c.="/*##Rpc4RunPatch##*/ if(!empty(\$patch))Rpc4_Patch_generated(\$patch);";
			$result=true;
		}
		if(!preg_match('/##'.$ident.'##/',$c,$m)){
			$p=strpos($c,'/*##Rpc4RunPatch');
			$c=substr($c,0,$p)."\n/*##$ident##*/ if(!function_exists('{$ident}_PatchApplyed'))\$patch[]='$ident';\n".substr($c,$p);
			$result=true;
		}
		
		$c="<?php\n$c\n?>\n";
		if(!empty($_c))return $c;
		return $result?file_put_contents(IPS_GetKernelDir().'scripts\\__autoload.php',$c):null;
	}	
}
?>