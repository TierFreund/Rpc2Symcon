<?
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 |  Class           :rpc2Sony extends uRpcBase                                    |
 |  Version         :2.2                                                          |
 |  BuildDate       :Tue 19.01.2016 01:46:24                                      |
 |  Publisher       :(c)2016 Xaver Bauer                                          |
 |  Contact         :xaver65@gmail.com                                            |
 |  Desc            :PHP Classes to Control MULTI CHANNEL AV RECEIVER             |
 |  port            :8080                                                         |
 |  base            :http://192.168.112.61:8080                                   |
 |  scpdurl         :/description.xml                                             |
 |  modelName       :STR-DN1050                                                   |
 |  deviceType      :urn:schemas-upnp-org:device:MediaRenderer:1                  |
 |  friendlyName    :sony                                                         |
 |  manufacturer    :Sony Corporation                                             |
 |  manufacturerURL :http://www.sony.net/                                         |
 |  modelNumber     :JB3.2                                                        |
 |  modelURL        :                                                             |
 |  UDN             :uuid:5f9ec1b3-ed59-1900-4530-d8d43cd2af47                    |
 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/

if (!DEFINED('RPC2SONY_STATE_STOP')) {
  DEFINE('RPC2SONY_STATE_STOP',0);
  DEFINE('RPC2SONY_STATE_PREV',1);
  DEFINE('RPC2SONY_STATE_PLAY',2);
  DEFINE('RPC2SONY_STATE_PAUSE',3);
  DEFINE('RPC2SONY_STATE_NEXT',4);
  DEFINE('RPC2SONY_STATE_TRANS',5);
  DEFINE('RPC2SONY_STATE_ERROR',6);
}
require_once( __DIR__ . '/../uRpcBase.class.php');
require_once( __DIR__ . '/../uRpcIo.class.php');
class rpc2Sony extends uRpcBase {
  protected $_boRepeat=false;
  protected $_boShuffle=false;
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetServiceConnData                                                  |
   |  Erwartet:                                                                     |
   |    Name  ( string )                                                            |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Array ( array  )                                                            |
   +--------------------------------------------------------------------------------*/
  protected function GetServiceConnData($name){
    switch($name){
      case  'RenderingControl' : return [8080,"urn:schemas-upnp-org:service:RenderingControl:1","/RenderingControl/ctrl","/RenderingControl/evt","/RenderingControl/desc.xml"];
      case 'ConnectionManager' : return [8080,"urn:schemas-upnp-org:service:ConnectionManager:1","/ConnectionManager/ctrl","/ConnectionManager/evt","/ConnectionManager/desc.xml"];
      case       'AVTransport' : return [8080,"urn:schemas-upnp-org:service:AVTransport:1","/AVTransport/ctrl","/AVTransport/evt","/AVTransport/desc.xml"];
      case              'IRCC' : return [8080,"urn:schemas-sony-com:service:IRCC:1","/upnp/control/IRCC","","/IRCCSCPD.xml"];
      case          'X_Tandem' : return [8080,"urn:schemas-sony-com:service:X_Tandem:1","/upnp/control/TANDEM","","/TANDEMSCPD.xml"];
    }
    return null;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Create                                                              |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Create(){
    parent::Create();
    IPS_SetProperty ($this->InstanceID, 'Port',8080 );
    IPS_SetProperty ($this->InstanceID, 'ConnectionType','curl');
    IPS_SetProperty ($this->InstanceID, 'Timeout',2);
    $this->RegisterPropertyInteger('IntervallRefresh', 60);
    $this->RegisterTimer('Refresh_All', 0, 'rpc2Sony_Update($_IPS[\'TARGET\']);');
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ApplyChanges                                                        |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function ApplyChanges(){
    parent::ApplyChanges();
    $this->RegisterProfileBooleanEx('rpc2Sony.OnOff','Information','','',Array(Array(false,'Aus','',-1),Array(true,'Ein','',-1)));
    $this->RegisterVariableBoolean('Mute','Mute','rpc2Sony.OnOff');
    $this->RegisterVariableInteger('Volume','Volume','~Intensity.100');
    $this->RegisterProfileIntegerEx('rpc2Sony.State','Status','','',array(Array(0,'Stop','', -1),Array(1,'Prev','', -1),Array(2,'Play','', -1),Array(3,'Pause','', -1),Array(4,'Next','', -1)));
    $this->RegisterVariableInteger('State','State','rpc2Sony.State');
    $this->RegisterVariableBoolean('Repeat','Repeat','rpc2Sony.OnOff');
    $this->RegisterVariableBoolean('Shuffle','Shuffle','rpc2Sony.OnOff');
    foreach(array('Mute','Volume','State','Repeat','Shuffle') as $e)$this->EnableAction($e);
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Test                                                                |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    TestResult ( boolean ) [ true|false ]                                       |
   +--------------------------------------------------------------------------------*/
  public function Test(){
    if (!parent::Test()) return false;
    return $this->Update(true);
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Update                                                              |
   |  Erwartet:                                                                     |
   |    All ( boolean ) [ true|false ] ( Vorgabe = false )                          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Update(boolean $All){
    if($this->GetMute()==null)$this->SetValueBoolean('Mute',false);
    if($this->GetVolume()==null)$this->SetValueInteger('Volume',0);
    if($this->GetState()==null)$this->SetValueInteger('State',RPC2SONY_STATE_STOP);
    if($this->GetRepeat()==null)$this->SetValueBoolean('Repeat',false);
    if($this->GetShuffle()==null)$this->SetValueBoolean('Shuffle',false);
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: RequestAction                                                       |
   |  Erwartet:                                                                     |
   |    Ident ( string )                                                            |
   |    Value ( mixed  )                                                            |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function RequestAction($Ident, $Value){
    switch($Ident) {
      case 'Mute'    : $this->SetMute($Value); break;
      case 'Volume'  : $this->SetVolume($Value); break;
      case 'State'   : $this->SetState($Value); break;
      case 'Repeat'  : $this->SetRepeat($Value); break;
      case 'Shuffle' : $this->SetShuffle($Value); break;
      default        : throw new Exception("Invalid Ident: $Ident");
    }
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentConnectionIDs                                             |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    ConnectionIDs ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentConnectionIDs(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('ConnectionIDs');
    return self::Call('ConnectionManager','GetCurrentConnectionIDs',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentConnectionInfo                                            |
   |  Erwartet:                                                                     |
   |    ConnectionID          ( i4     )                                            |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    RcsID                 ( i4     )                                            |
   |    AVTransportID         ( i4     )                                            |
   |    ProtocolInfo          ( string )                                            |
   |    PeerConnectionManager ( string )                                            |
   |    PeerConnectionID      ( i4     )                                            |
   |    Direction             ( string ) [ Input|Output ]                           |
   |    Status                ( string ) [ OK|ContentFormatMismatch|InsufficientBandwidth|UnreliableChannel|Unknown ]|
   +--------------------------------------------------------------------------------*/
  public function GetCurrentConnectionInfo(integer $ConnectionID){
    if (!$this->GetOnlineState()) return null;
    $args=array('ConnectionID'=>$ConnectionID);
    $filter=array('RcsID','AVTransportID','ProtocolInfo','PeerConnectionManager','PeerConnectionID','Direction','Status');
    return self::Call('ConnectionManager','GetCurrentConnectionInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentTransportActions                                          |
   |  Erwartet:                                                                     |
   |    Instance ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                             |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Actions  ( string )                                                         |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentTransportActions(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('Actions');
    return self::Call('AVTransport','GetCurrentTransportActions',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetDeviceCapabilities                                               |
   |  Erwartet:                                                                     |
   |    Instance        ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                      |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    PlayMedia       ( string )                                                  |
   |    RecMedia        ( string )                                                  |
   |    RecQualityModes ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function GetDeviceCapabilities(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('PlayMedia','RecMedia','RecQualityModes');
    return self::Call('AVTransport','GetDeviceCapabilities',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetMediaInfo                                                        |
   |  Erwartet:                                                                     |
   |    Instance           ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                   |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    NrTracks           ( ui4    ) [ 0 bis 1 ]                                   |
   |    MediaDuration      ( string )                                               |
   |    CurrentURI         ( string )                                               |
   |    CurrentURIMetaData ( string )                                               |
   |    NextURI            ( string )                                               |
   |    NextURIMetaData    ( string )                                               |
   |    PlayMedium         ( string ) [ NETWORK ] ( Vorgabe = 'NETWORK' )           |
   |    RecordMedium       ( string ) [ NOT_IMPLEMENTED ] ( Vorgabe = 'NOT_IMPLEMENTED' )|
   |    WriteStatus        ( string ) [ NOT_IMPLEMENTED ] ( Vorgabe = 'NOT_IMPLEMENTED' )|
   +--------------------------------------------------------------------------------*/
  public function GetMediaInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('NrTracks','MediaDuration','CurrentURI','CurrentURIMetaData','NextURI','NextURIMetaData','PlayMedium','RecordMedium','WriteStatus');
    return self::Call('AVTransport','GetMediaInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetMute                                                             |
   |  Erwartet:                                                                     |
   |    Instance    ( ui4     ) [ 0 bis 9 ] ( Vorgabe = 0 )                         |
   |    Channel     ( string  ) [ Master ] ( Vorgabe = 'Master' )                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentMute ( boolean ) [ true|false ]                                      |
   +--------------------------------------------------------------------------------*/
  public function GetMute(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Channel))$Channel='Master';
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentMute');
    $CurrentMute=self::Call('RenderingControl','GetMute',$args,$filter);;
    $this->SetValueBoolean('Mute',$CurrentMute);
    return $CurrentMute;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetPositionInfo                                                     |
   |  Erwartet:                                                                     |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Track         ( ui4    ) [ 0 bis 1 ]                                        |
   |    TrackDuration ( string )                                                    |
   |    TrackMetaData ( string )                                                    |
   |    TrackURI      ( string )                                                    |
   |    RelTime       ( string )                                                    |
   |    AbsTime       ( string )                                                    |
   |    RelCount      ( i4     )                                                    |
   |    AbsCount      ( i4     )                                                    |
   +--------------------------------------------------------------------------------*/
  public function GetPositionInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('Track','TrackDuration','TrackMetaData','TrackURI','RelTime','AbsTime','RelCount','AbsCount');
    return self::Call('AVTransport','GetPositionInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetProtocolInfo                                                     |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Source ( string )                                                           |
   |    Sink   ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function GetProtocolInfo(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Source','Sink');
    return self::Call('ConnectionManager','GetProtocolInfo',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetRepeat                                                           |
   |  Erwartet:                                                                     |
   |    InstanceID ( ui4     ) ( Vorgabe = 0 )                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Repeat     ( boolean ) [ true|false ]                                       |
   +--------------------------------------------------------------------------------*/
  protected function GetRepeat($InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    return $this->_boRepeat;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetShuffle                                                          |
   |  Erwartet:                                                                     |
   |    InstanceID ( ui4     ) ( Vorgabe = 0 )                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Shuffle    ( boolean ) [ true|false ]                                       |
   +--------------------------------------------------------------------------------*/
  protected function GetShuffle($InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    return $this->_boShuffle;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetState                                                            |
   |  Erwartet:                                                                     |
   |    Instance     ( ui4 ) ( Vorgabe = 0 )                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentState ( ui2 ) [ RPC2SONY_STATE_STOP|RPC2SONY_STATE_PLAY|RPC2SONY_STATE_PAUSE|RPC2SONY_STATE_TRANS|RPC2SONY_STATE_ERROR ]|
   +--------------------------------------------------------------------------------*/
  public function GetState($Instance=0){
    $states=array('STOPPED'=>RPC2SONY_STATE_STOP,'PLAYING'=>RPC2SONY_STATE_PLAY,'PAUSED_PLAYBACK'=>RPC2SONY_STATE_PAUSE,'TRANSITIONING'=>RPC2SONY_STATE_TRANS,'NO_MEDIA_PRESENT'=>RPC2SONY_STATE_ERROR);
    $v=self::GetTransportInfo($Instance);
    return ($v&&($s=$v['CurrentTransportState'])&&isset($a[$s]))?$a[$s]:RPC2SONY_STATE_ERROR;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetTransportInfo                                                    |
   |  Erwartet:                                                                     |
   |    Instance               ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )               |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    CurrentTransportState  ( string ) [ STOPPED|PLAYING|PAUSED_PLAYBACK|TRANSITIONING|NO_MEDIA_PRESENT ]|
   |    CurrentTransportStatus ( string ) [ OK|ERROR_OCCURRED ]                     |
   |    CurrentSpeed           ( ui4    ) [ 1 ] ( Vorgabe = 1 )                     |
   +--------------------------------------------------------------------------------*/
  public function GetTransportInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentTransportState','CurrentTransportStatus','CurrentSpeed');
    return self::Call('AVTransport','GetTransportInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetTransportSettings                                                |
   |  Erwartet:                                                                     |
   |    Instance       ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                       |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    PlayMode       ( string ) [ NORMAL|RANDOM|REPEAT_ONE|REPEAT_ALL ]           |
   |    RecQualityMode ( string ) [ NOT_IMPLEMENTED ] ( Vorgabe = 'NOT_IMPLEMENTED' )|
   +--------------------------------------------------------------------------------*/
  public function GetTransportSettings(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('PlayMode','RecQualityMode');
    return self::Call('AVTransport','GetTransportSettings',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetVolume                                                           |
   |  Erwartet:                                                                     |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |    Channel       ( string ) [ Master ] ( Vorgabe = 'Master' )                  |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentVolume ( ui2    ) [ 0 bis 100 ]                                      |
   +--------------------------------------------------------------------------------*/
  public function GetVolume(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Channel))$Channel='Master';
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentVolume');
    $CurrentVolume=self::Call('RenderingControl','GetVolume',$args,$filter);;
    $this->SetValueInteger('Volume',$CurrentVolume);
    return $CurrentVolume;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ListPresets                                                         |
   |  Erwartet:                                                                     |
   |    Instance              ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentPresetNameList ( string )                                            |
   +--------------------------------------------------------------------------------*/
  public function ListPresets(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentPresetNameList');
    return self::Call('RenderingControl','ListPresets',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Next                                                                |
   |  Erwartet:                                                                     |
   |    Instance ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                                |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Next(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Next',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Pause                                                               |
   |  Erwartet:                                                                     |
   |    Instance ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                                |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Pause(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Pause',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Play                                                                |
   |  Erwartet:                                                                     |
   |    Instance ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                                |
   |    Speed    ( ui4 ) [ 1 ] ( Vorgabe = 1 )                                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Play(integer $Instance,integer $Speed){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Speed))$Speed=1;
    $args=array('InstanceID'=>$Instance,'Speed'=>$Speed);
    return self::Call('AVTransport','Play',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Previous                                                            |
   |  Erwartet:                                                                     |
   |    Instance ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                                |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Previous(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Previous',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Seek                                                                |
   |  Erwartet:                                                                     |
   |    Target   ( string )                                                         |
   |    Instance ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                             |
   |    Unit     ( string ) [ TRACK_NR|REL_TIME ] ( Vorgabe = 'TRACK_NR' )          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Seek(string $Target,integer $Instance,string $Unit){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Unit))$Unit='TRACK_NR';
    $args=array('InstanceID'=>$Instance,'Unit'=>$Unit,'Target'=>$Target);
    return self::Call('AVTransport','Seek',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SelectPreset                                                        |
   |  Erwartet:                                                                     |
   |    Instance   ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                           |
   |    PresetName ( string ) [ FactoryDefaults ] ( Vorgabe = 'FactoryDefaults' )   |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SelectPreset(integer $Instance,string $PresetName){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($PresetName))$PresetName='FactoryDefaults';
    $args=array('InstanceID'=>$Instance,'PresetName'=>$PresetName);
    return self::Call('RenderingControl','SelectPreset',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetAVTransportURI                                                   |
   |  Erwartet:                                                                     |
   |    CurrentURI         ( string )                                               |
   |    CurrentURIMetaData ( string )                                               |
   |    Instance           ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                   |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetAVTransportURI(string $CurrentURI,string $CurrentURIMetaData,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'CurrentURI'=>$CurrentURI,'CurrentURIMetaData'=>$CurrentURIMetaData);
    return self::Call('AVTransport','SetAVTransportURI',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetMute                                                             |
   |  Erwartet:                                                                     |
   |    DesiredMute ( boolean ) [ true|false ]                                      |
   |    Instance    ( ui4     ) [ 0 bis 9 ] ( Vorgabe = 0 )                         |
   |    Channel     ( string  ) [ Master ] ( Vorgabe = 'Master' )                   |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetMute(boolean $DesiredMute,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Channel))$Channel='Master';
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredMute'=>$DesiredMute);
    $this->SetValueBoolean('Mute',$DesiredMute);
    return self::Call('RenderingControl','SetMute',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetNextAVTransportURI                                               |
   |  Erwartet:                                                                     |
   |    NextURI         ( string )                                                  |
   |    NextURIMetaData ( string )                                                  |
   |    Instance        ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetNextAVTransportURI(string $NextURI,string $NextURIMetaData,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'NextURI'=>$NextURI,'NextURIMetaData'=>$NextURIMetaData);
    return self::Call('AVTransport','SetNextAVTransportURI',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetPlayMode                                                         |
   |  Erwartet:                                                                     |
   |    NewPlayMode ( string ) [ NORMAL|RANDOM|REPEAT_ONE|REPEAT_ALL ]              |
   |    Instance    ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetPlayMode(string $NewPlayMode,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'NewPlayMode'=>$NewPlayMode);
    return self::Call('AVTransport','SetPlayMode',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetRepeat                                                           |
   |  Erwartet:                                                                     |
   |    Repeat     ( boolean ) [ true|false ]                                       |
   |    InstanceID ( ui4     ) ( Vorgabe = 0 )                                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  protected function SetRepeat($boRepeat, $InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    $this->SetPlayModes($this->_PlayModes[$this->_boRepeat=$boRepeat][$this->_boShuffle][$this->_boAll]);
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetShuffle                                                          |
   |  Erwartet:                                                                     |
   |    Shuffle    ( boolean ) [ true|false ]                                       |
   |    InstanceID ( ui4     ) ( Vorgabe = 0 )                                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  protected function SetShuffle($boShuffle, $InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    $this->SetPlayMode($this->_PlayModes[$this->_boRepeat][$this->_boShuffle=$boShuffle][$this->_boAll]);
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetState                                                            |
   |  Erwartet:                                                                     |
   |    NewState     ( ui2 ) [ RPC2SONY_STATE_STOP|RPC2SONY_STATE_PLAY|RPC2SONY_STATE_PAUSE|RPC2SONY_STATE_NEXT|RPC2SONY_STATE_PREV ]|
   |    InstanceID   ( ui4 ) ( Vorgabe = 0 )                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentState ( ui2 ) [ RPC2SONY_STATE_STOP|RPC2SONY_STATE_PLAY|RPC2SONY_STATE_PAUSE|RPC2SONY_STATE_TRANS|RPC2SONY_STATE_ERROR ]|
   +--------------------------------------------------------------------------------*/
  public function SetState($NewState, $InstanceID=0){
    switch($NewState){
      case RPC2SONY_STATE_STOP : $s=$this->Stop($InstanceID)?RPC2SONY_STATE_STOP:RPC2SONY_STATE_ERROR; break;
      case RPC2SONY_STATE_PREV : $s=$this->Previous($InstanceID)?RPC2SONY_STATE_PLAY:RPC2SONY_STATE_STOP;break;
      case RPC2SONY_STATE_PLAY : $s=$this->Play($InstanceID)?RPC2SONY_STATE_PLAY:RPC2SONY_STATE_STOP; break;
      case RPC2SONY_STATE_PAUSE: $s=$this->Pause($InstanceID)?RPC2SONY_STATE_PAUSE:RPC2SONY_STATE_STOP;break;
      case RPC2SONY_STATE_NEXT : $s=$this->Next($InstanceID)?RPC2SONY_STATE_PLAY:RPC2SONY_STATE_STOP; break;
      default : return RPC2SONY_STATE_ERROR;
    }
    return $s;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetVolume                                                           |
   |  Erwartet:                                                                     |
   |    DesiredVolume ( ui2    ) [ 0 bis 100 ]                                      |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |    Channel       ( string ) [ Master ] ( Vorgabe = 'Master' )                  |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetVolume(integer $DesiredVolume,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($Channel))$Channel='Master';
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredVolume'=>$DesiredVolume);
    $this->SetValueInteger('Volume',$DesiredVolume);
    return self::Call('RenderingControl','SetVolume',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Stop                                                                |
   |  Erwartet:                                                                     |
   |    Instance ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                                |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function Stop(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Stop',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: UpdatePlayMode                                                      |
   |  Erwartet:                                                                     |
   |    InstanceID ( ui4 ) ( Vorgabe = 0 )                                          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  protected function UpdatePlayMode($InstanceID=0){
    static $modes=array(
        'NORMAL'=>array(false,false,false),
        'RANDOM'=>array(false,false,false),
        'REPEAT_ONE'=>array(true,false,false),
        'REPEAT_ALL'=>array(true,false,true),
    );
    if(empty($this->_PlayModes))
      foreach($modes as $k=>$a)$this->_PlayModes[$a[0]][$a[1]][$a[2]]=$k;
    if(!$t=$this->GetTransportSettings($InstanceID))return false;
    list($this->_boRepeat,$this->_boShuffle,$this->_boAll)=$modes[$t['PlayMode']];
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_ExecuteOperation                                                  |
   |  Erwartet:                                                                     |
   |    AVTInstanceID   ( ui4    )                                                  |
   |    ActionDirective ( string )                                                  |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result          ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function X_ExecuteOperation(integer $AVTInstanceID,string $ActionDirective){
    if (!$this->GetOnlineState()) return null;
    $args=array('AVTInstanceID'=>$AVTInstanceID,'ActionDirective'=>$ActionDirective);
    $filter=array('Result');
    return self::Call('AVTransport','X_ExecuteOperation',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_GetOperationList                                                  |
   |  Erwartet:                                                                     |
   |    AVTInstanceID ( ui4    )                                                    |
   |                                                                                |
   |  Liefert:                                                                      |
   |    OperationList ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function X_GetOperationList(integer $AVTInstanceID){
    if (!$this->GetOnlineState()) return null;
    $args=array('AVTInstanceID'=>$AVTInstanceID);
    $filter=array('OperationList');
    return self::Call('AVTransport','X_GetOperationList',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_GetStatus                                                         |
   |  Erwartet:                                                                     |
   |    CategoryCode       ( string )                                               |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    CurrentStatus      ( string ) [ 0|801|804|805|806 ]                         |
   |    CurrentCommandInfo ( string )                                               |
   +--------------------------------------------------------------------------------*/
  public function X_GetStatus(string $CategoryCode){
    if (!$this->GetOnlineState()) return null;
    $args=array('CategoryCode'=>$CategoryCode);
    $filter=array('CurrentStatus','CurrentCommandInfo');
    return self::Call('IRCC','X_GetStatus',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_SendIRCC                                                          |
   |  Erwartet:                                                                     |
   |    IRCCCode ( string )                                                         |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_SendIRCC(string $IRCCCode){
    if (!$this->GetOnlineState()) return null;
    $args=array('IRCCCode'=>$IRCCCode);
    return self::Call('IRCC','X_SendIRCC',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_Tandem                                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_Tandem(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('X_Tandem','X_Tandem',null,null);;
  }
}
?>