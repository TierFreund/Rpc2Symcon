<?
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 |  Class           :rpc2Samsung extends uRpcBase                                 |
 |  Version         :2.2                                                          |
 |  BuildDate       :Tue 19.01.2016 01:42:03                                      |
 |  Publisher       :(c)2016 Xaver Bauer                                          |
 |  Contact         :xaver65@gmail.com                                            |
 |  Desc            :PHP Classes to Control Samsung TV DMR                        |
 |  port            :7676                                                         |
 |  base            :http://192.168.112.60:7676                                   |
 |  scpdurl         :/smp_16_                                                     |
 |  modelName       :UE55F6400                                                    |
 |  deviceType      :urn:schemas-upnp-org:device:MediaRenderer:1                  |
 |  friendlyName    :[TV] Samsung                                                 |
 |  manufacturer    :Samsung Electronics                                          |
 |  manufacturerURL :http://www.samsung.com/sec                                   |
 |  modelNumber     :AllShare1.0                                                  |
 |  modelURL        :http://www.samsung.com/sec                                   |
 |  UDN             :uuid:0ee6b280-00fa-1000-b849-0c891041f72d                    |
 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/

if (!DEFINED('RPC2SAMSUNG_STATE_STOP')) {
  DEFINE('RPC2SAMSUNG_STATE_STOP',0);
  DEFINE('RPC2SAMSUNG_STATE_PREV',1);
  DEFINE('RPC2SAMSUNG_STATE_PLAY',2);
  DEFINE('RPC2SAMSUNG_STATE_PAUSE',3);
  DEFINE('RPC2SAMSUNG_STATE_NEXT',4);
  DEFINE('RPC2SAMSUNG_STATE_TRANS',5);
  DEFINE('RPC2SAMSUNG_STATE_ERROR',6);
}
require_once( __DIR__ . '/../uRpcBase.class.php');
require_once( __DIR__ . '/../uRpcIo.class.php');
class rpc2Samsung extends uRpcBase {
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
      case   'RenderingControl' : return [7676,"urn:schemas-upnp-org:service:RenderingControl:1","/smp_18_","/smp_19_","/smp_17_"];
      case  'ConnectionManager' : return [7676,"urn:schemas-upnp-org:service:ConnectionManager:1","/smp_21_","/smp_22_","/smp_20_"];
      case        'AVTransport' : return [7676,"urn:schemas-upnp-org:service:AVTransport:1","/smp_24_","/smp_25_","/smp_23_"];
      case               'dial' : return [7676,"urn:dial-multiscreen-org:service:dial:1","/smp_28_","/smp_29_","/smp_27_"];
      case 'MultiScreenService' : return [7676,"urn:samsung.com:service:MultiScreenService:1","/smp_8_","/smp_9_","/smp_7_"];
      case       'MainTVAgent2' : return [7676,"urn:samsung.com:service:MainTVAgent2:1","/smp_4_","/smp_5_","/smp_3_"];
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
    IPS_SetProperty ($this->InstanceID, 'Port',7676 );
    IPS_SetProperty ($this->InstanceID, 'ConnectionType','curl');
    IPS_SetProperty ($this->InstanceID, 'Timeout',2);
    $this->RegisterPropertyInteger('IntervallRefresh', 60);
    $this->RegisterTimer('Refresh_All', 0, 'rpc2Samsung_Update($_IPS[\'TARGET\']);');
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ApplyChanges                                                        |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function ApplyChanges(){
    parent::ApplyChanges();
    $this->RegisterProfileBooleanEx('rpc2Samsung.OnOff','Information','','',Array(Array(false,'Aus','',-1),Array(true,'Ein','',-1)));
    $this->RegisterVariableBoolean('Mute','Mute','rpc2Samsung.OnOff');
    $this->RegisterVariableInteger('Volume','Volume','~Intensity.100');
    $this->RegisterVariableInteger('Brightness','Brightness','~Intensity.100');
    $this->RegisterVariableInteger('Contrast','Contrast','~Intensity.100');
    $this->RegisterVariableInteger('Sharpness','Sharpness','~Intensity.100');
    $this->RegisterProfileIntegerEx('rpc2Samsung.State','Status','','',array(Array(0,'Stop','', -1),Array(1,'Prev','', -1),Array(2,'Play','', -1),Array(3,'Pause','', -1),Array(4,'Next','', -1)));
    $this->RegisterVariableInteger('State','State','rpc2Samsung.State');
    $this->RegisterVariableBoolean('Repeat','Repeat','rpc2Samsung.OnOff');
    $this->RegisterVariableBoolean('Shuffle','Shuffle','rpc2Samsung.OnOff');
    foreach(array('Mute','Volume','Brightness','Contrast','Sharpness','State','Repeat','Shuffle') as $e)$this->EnableAction($e);
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
    if($this->GetBrightness()==null)$this->SetValueInteger('Brightness',0);
    if($this->GetContrast()==null)$this->SetValueInteger('Contrast',0);
    if($this->GetSharpness()==null)$this->SetValueInteger('Sharpness',0);
    if($this->GetState()==null)$this->SetValueInteger('State',RPC2SAMSUNG_STATE_STOP);
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
      case 'Brightness': $this->SetBrightness($Value); break;
      case 'Contrast': $this->SetContrast($Value); break;
      case 'Sharpness': $this->SetSharpness($Value); break;
      case 'State'   : $this->SetState($Value); break;
      case 'Repeat'  : $this->SetRepeat($Value); break;
      case 'Shuffle' : $this->SetShuffle($Value); break;
      default        : throw new Exception("Invalid Ident: $Ident");
    }
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: AddSchedule                                                         |
   |  Erwartet:                                                                     |
   |    ReservationType       ( string ) [ Manual|Program ]                         |
   |    RemindInfo            ( string )                                            |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                ( string )                                            |
   |    ConflictRemindInfo    ( string )                                            |
   |    ConflictRemindInfoURL ( string )                                            |
   +--------------------------------------------------------------------------------*/
  public function AddSchedule(string $ReservationType,string $RemindInfo){
    if (!$this->GetOnlineState()) return null;
    $args=array('ReservationType'=>$ReservationType,'RemindInfo'=>$RemindInfo);
    $filter=array('Result','ConflictRemindInfo','ConflictRemindInfoURL');
    return self::Call('MainTVAgent2','AddSchedule',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ChangeSchedule                                                      |
   |  Erwartet:                                                                     |
   |    ReservationType       ( string ) [ Manual|Program ]                         |
   |    RemindInfo            ( string )                                            |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                ( string )                                            |
   |    ConflictRemindInfo    ( string )                                            |
   |    ConflictRemindInfoURL ( string )                                            |
   +--------------------------------------------------------------------------------*/
  public function ChangeSchedule(string $ReservationType,string $RemindInfo){
    if (!$this->GetOnlineState()) return null;
    $args=array('ReservationType'=>$ReservationType,'RemindInfo'=>$RemindInfo);
    $filter=array('Result','ConflictRemindInfo','ConflictRemindInfoURL');
    return self::Call('MainTVAgent2','ChangeSchedule',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: CheckPIN                                                            |
   |  Erwartet:                                                                     |
   |    PIN    ( string )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function CheckPIN(string $PIN){
    if (!$this->GetOnlineState()) return null;
    $args=array('PIN'=>$PIN);
    $filter=array('Result');
    return self::Call('MainTVAgent2','CheckPIN',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ConnectionComplete                                                  |
   |  Erwartet:                                                                     |
   |    ConnectionID ( i4 )                                                         |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function ConnectionComplete(integer $ConnectionID){
    if (!$this->GetOnlineState()) return null;
    $args=array('ConnectionID'=>$ConnectionID);
    return self::Call('ConnectionManager','ConnectionComplete',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: DeleteChannelList                                                   |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |    ChannelList ( string )                                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function DeleteChannelList(integer $AntennaMode,string $ChannelList){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode,'ChannelList'=>$ChannelList);
    $filter=array('Result');
    return self::Call('MainTVAgent2','DeleteChannelList',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: DeleteChannelListPIN                                                |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |    ChannelList ( string )                                                      |
   |    PIN         ( string )                                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function DeleteChannelListPIN(integer $AntennaMode,string $ChannelList,string $PIN){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode,'ChannelList'=>$ChannelList,'PIN'=>$PIN);
    $filter=array('Result');
    return self::Call('MainTVAgent2','DeleteChannelListPIN',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: DeleteRecordedItem                                                  |
   |  Erwartet:                                                                     |
   |    UID    ( string )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function DeleteRecordedItem(string $UID){
    if (!$this->GetOnlineState()) return null;
    $args=array('UID'=>$UID);
    $filter=array('Result');
    return self::Call('MainTVAgent2','DeleteRecordedItem',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: DeleteSchedule                                                      |
   |  Erwartet:                                                                     |
   |    UID    ( string )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function DeleteSchedule(string $UID){
    if (!$this->GetOnlineState()) return null;
    $args=array('UID'=>$UID);
    $filter=array('Result');
    return self::Call('MainTVAgent2','DeleteSchedule',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: DestoryGroupOwner                                                   |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function DestoryGroupOwner(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result');
    return self::Call('MainTVAgent2','DestoryGroupOwner',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: EditChannelNumber                                                   |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |    Source      ( string )                                                      |
   |    Destination ( string )                                                      |
   |    ForcedFlag  ( string ) [ Normal|Forced|ADOff ]                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function EditChannelNumber(integer $AntennaMode,string $Source,string $Destination,string $ForcedFlag){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode,'Source'=>$Source,'Destination'=>$Destination,'ForcedFlag'=>$ForcedFlag);
    $filter=array('Result');
    return self::Call('MainTVAgent2','EditChannelNumber',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: EditSourceName                                                      |
   |  Erwartet:                                                                     |
   |    SourceType     ( string )                                                   |
   |    SourceNameType ( string )                                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result         ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function EditSourceName(string $SourceType,string $SourceNameType){
    if (!$this->GetOnlineState()) return null;
    $args=array('SourceType'=>$SourceType,'SourceNameType'=>$SourceNameType);
    $filter=array('Result');
    return self::Call('MainTVAgent2','EditSourceName',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: EnforceAKE                                                          |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function EnforceAKE(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result');
    return self::Call('MainTVAgent2','EnforceAKE',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetACRCurrentChannelName                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    ChannelName ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetACRCurrentChannelName(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','ChannelName');
    return self::Call('MainTVAgent2','GetACRCurrentChannelName',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetACRCurrentProgramName                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    ProgramName ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetACRCurrentProgramName(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','ProgramName');
    return self::Call('MainTVAgent2','GetACRCurrentProgramName',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetACRMessage                                                       |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result  ( string )                                                          |
   |    Message ( string )                                                          |
   +--------------------------------------------------------------------------------*/
  public function GetACRMessage(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','Message');
    return self::Call('MainTVAgent2','GetACRMessage',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetAPInformation                                                    |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result        ( string )                                                    |
   |    APInformation ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function GetAPInformation(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','APInformation');
    return self::Call('MainTVAgent2','GetAPInformation',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetAVOffStatus                                                      |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    AVOffStatus ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetAVOffStatus(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','AVOffStatus');
    return self::Call('MainTVAgent2','GetAVOffStatus',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetAllProgramInformationURL                                         |
   |  Erwartet:                                                                     |
   |    AntennaMode              ( ui4    )                                         |
   |    Channel                  ( string ) ( Vorgabe = 'Master' )                  |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                   ( string )                                         |
   |    AllProgramInformationURL ( string )                                         |
   +--------------------------------------------------------------------------------*/
  public function GetAllProgramInformationURL(integer $AntennaMode,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'Channel'=>$Channel);
    $filter=array('Result','AllProgramInformationURL');
    return self::Call('MainTVAgent2','GetAllProgramInformationURL',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetAvailableActions                                                 |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result           ( string )                                                 |
   |    AvailableActions ( string )                                                 |
   +--------------------------------------------------------------------------------*/
  public function GetAvailableActions(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','AvailableActions');
    return self::Call('MainTVAgent2','GetAvailableActions',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetBannerInformation                                                |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result            ( string )                                                |
   |    BannerInformation ( string )                                                |
   +--------------------------------------------------------------------------------*/
  public function GetBannerInformation(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','BannerInformation');
    return self::Call('MainTVAgent2','GetBannerInformation',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetBrightness                                                       |
   |  Erwartet:                                                                     |
   |    Instance          ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                       |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentBrightness ( ui2 ) [ 0 bis 100 ]                                     |
   +--------------------------------------------------------------------------------*/
  public function GetBrightness(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentBrightness');
    $CurrentBrightness=self::Call('RenderingControl','GetBrightness',$args,$filter);;
    $this->SetValueInteger('Brightness',$CurrentBrightness);
    return $CurrentBrightness;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetChannelListURL                                                   |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result             ( string )                                               |
   |    ChannelListVersion ( ui4    )                                               |
   |    SupportChannelList ( string )                                               |
   |    ChannelListURL     ( string )                                               |
   |    ChannelListType    ( string )                                               |
   |    SatelliteID        ( ui4    )                                               |
   +--------------------------------------------------------------------------------*/
  public function GetChannelListURL(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','ChannelListVersion','SupportChannelList','ChannelListURL','ChannelListType','SatelliteID');
    return self::Call('MainTVAgent2','GetChannelListURL',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetChannelLockInformation                                           |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |    Channel     ( string ) ( Vorgabe = 'Master' )                               |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    Lock        ( string )                                                      |
   |    StartTime   ( ui4    )                                                      |
   |    EndTime     ( ui4    )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetChannelLockInformation(integer $AntennaMode,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('Channel'=>$Channel,'AntennaMode'=>$AntennaMode);
    $filter=array('Result','Lock','StartTime','EndTime');
    return self::Call('MainTVAgent2','GetChannelLockInformation',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetContrast                                                         |
   |  Erwartet:                                                                     |
   |    Instance        ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                         |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentContrast ( ui2 ) [ 0 bis 100 ]                                       |
   +--------------------------------------------------------------------------------*/
  public function GetContrast(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentContrast');
    $CurrentContrast=self::Call('RenderingControl','GetContrast',$args,$filter);;
    $this->SetValueInteger('Contrast',$CurrentContrast);
    return $CurrentContrast;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentBrowserMode                                               |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    BrowserMode ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentBrowserMode(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','BrowserMode');
    return self::Call('MainTVAgent2','GetCurrentBrowserMode',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentBrowserURL                                                |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result     ( string )                                                       |
   |    BrowserURL ( string )                                                       |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentBrowserURL(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','BrowserURL');
    return self::Call('MainTVAgent2','GetCurrentBrowserURL',null,$filter);;
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
   |  Funktion: GetCurrentExternalSource                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                ( string )                                            |
   |    CurrentExternalSource ( string )                                            |
   |    ID                    ( ui4    )                                            |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentExternalSource(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','CurrentExternalSource','ID');
    return self::Call('MainTVAgent2','GetCurrentExternalSource',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentHTSSpeakerLayout                                          |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result        ( string )                                                    |
   |    SpeakerLayout ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentHTSSpeakerLayout(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','SpeakerLayout');
    return self::Call('MainTVAgent2','GetCurrentHTSSpeakerLayout',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentMainTVChannel                                             |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result         ( string )                                                   |
   |    CurrentChannel ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentMainTVChannel(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','CurrentChannel');
    return self::Call('MainTVAgent2','GetCurrentMainTVChannel',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentProgramInformationURL                                     |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result             ( string )                                               |
   |    CurrentProgInfoURL ( string )                                               |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentProgramInformationURL(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','CurrentProgInfoURL');
    return self::Call('MainTVAgent2','GetCurrentProgramInformationURL',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetCurrentTime                                                      |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result      ( string )                                                      |
   |    CurrentTime ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function GetCurrentTime(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','CurrentTime');
    return self::Call('MainTVAgent2','GetCurrentTime',null,$filter);;
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
   |  Funktion: GetDTVInformation                                                   |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result         ( string )                                                   |
   |    DTVInformation ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function GetDTVInformation(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','DTVInformation');
    return self::Call('MainTVAgent2','GetDTVInformation',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetDetailChannelInformation                                         |
   |  Erwartet:                                                                     |
   |    AntennaMode              ( ui4    )                                         |
   |    Channel                  ( string ) ( Vorgabe = 'Master' )                  |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                   ( string )                                         |
   |    DetailChannelInformation ( string )                                         |
   +--------------------------------------------------------------------------------*/
  public function GetDetailChannelInformation(integer $AntennaMode,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('Channel'=>$Channel,'AntennaMode'=>$AntennaMode);
    $filter=array('Result','DetailChannelInformation');
    return self::Call('MainTVAgent2','GetDetailChannelInformation',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetDetailProgramInformation                                         |
   |  Erwartet:                                                                     |
   |    AntennaMode              ( ui4    )                                         |
   |    StartTime                ( string )                                         |
   |    Channel                  ( string ) ( Vorgabe = 'Master' )                  |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result                   ( string )                                         |
   |    DetailProgramInformation ( string )                                         |
   +--------------------------------------------------------------------------------*/
  public function GetDetailProgramInformation(integer $AntennaMode,string $StartTime,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'Channel'=>$Channel,'StartTime'=>$StartTime);
    $filter=array('Result','DetailProgramInformation');
    return self::Call('MainTVAgent2','GetDetailProgramInformation',$args,$filter);;
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
   |  Funktion: GetFilteredProgarmURL                                               |
   |  Erwartet:                                                                     |
   |    Keyword            ( string )                                               |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result             ( string )                                               |
   |    FilteredProgramURL ( string )                                               |
   +--------------------------------------------------------------------------------*/
  public function GetFilteredProgarmURL(string $Keyword){
    if (!$this->GetOnlineState()) return null;
    $args=array('Keyword'=>$Keyword);
    $filter=array('Result','FilteredProgramURL');
    return self::Call('MainTVAgent2','GetFilteredProgarmURL',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetHTSAllSpeakerDistance                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result             ( string )                                               |
   |    MaxDistance        ( ui4    )                                               |
   |    AllSpeakerDistance ( string )                                               |
   +--------------------------------------------------------------------------------*/
  public function GetHTSAllSpeakerDistance(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','MaxDistance','AllSpeakerDistance');
    return self::Call('MainTVAgent2','GetHTSAllSpeakerDistance',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetHTSAllSpeakerLevel                                               |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result          ( string )                                                  |
   |    MaxLevel        ( ui4    )                                                  |
   |    AllSpeakerLevel ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function GetHTSAllSpeakerLevel(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','MaxLevel','AllSpeakerLevel');
    return self::Call('MainTVAgent2','GetHTSAllSpeakerLevel',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetHTSSoundEffect                                                   |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result          ( string )                                                  |
   |    SoundEffect     ( string )                                                  |
   |    SoundEffectList ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function GetHTSSoundEffect(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','SoundEffect','SoundEffectList');
    return self::Call('MainTVAgent2','GetHTSSoundEffect',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetHTSSpeakerConfig                                                 |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result         ( string )                                                   |
   |    SpeakerChannel ( ui4    )                                                   |
   |    SpeakerLFE     ( ui4    )                                                   |
   +--------------------------------------------------------------------------------*/
  public function GetHTSSpeakerConfig(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','SpeakerChannel','SpeakerLFE');
    return self::Call('MainTVAgent2','GetHTSSpeakerConfig',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetMBRDeviceList                                                    |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result        ( string )                                                    |
   |    MBRDeviceList ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function GetMBRDeviceList(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','MBRDeviceList');
    return self::Call('MainTVAgent2','GetMBRDeviceList',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetMBRDongleStatus                                                  |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result          ( string )                                                  |
   |    MBRDongleStatus ( string ) [ Enable|Disable ]                               |
   +--------------------------------------------------------------------------------*/
  public function GetMBRDongleStatus(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','MBRDongleStatus');
    return self::Call('MainTVAgent2','GetMBRDongleStatus',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetMediaInfo                                                        |
   |  Erwartet:                                                                     |
   |    Instance           ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                   |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    NrTracks           ( ui4    ) [ 0 bis 4294967295 ]                          |
   |    MediaDuration      ( string )                                               |
   |    CurrentURI         ( string )                                               |
   |    CurrentURIMetaData ( string )                                               |
   |    NextURI            ( string )                                               |
   |    NextURIMetaData    ( string )                                               |
   |    PlayMedium         ( string ) [ NONE|NETWORK ]                              |
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
   |    Track         ( ui4    ) [ 0 bis 4294967295 ]                               |
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
   |  Funktion: GetRecordChannel                                                    |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result         ( string )                                                   |
   |    RecordChannel  ( string )                                                   |
   |    RecordChannel2 ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function GetRecordChannel(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','RecordChannel','RecordChannel2');
    return self::Call('MainTVAgent2','GetRecordChannel',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetRegionalVariantList                                              |
   |  Erwartet:                                                                     |
   |    AntennaMode         ( ui4    )                                              |
   |    Channel             ( string ) ( Vorgabe = 'Master' )                       |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result              ( string )                                              |
   |    RegionalVariantList ( string )                                              |
   +--------------------------------------------------------------------------------*/
  public function GetRegionalVariantList(integer $AntennaMode,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'Channel'=>$Channel);
    $filter=array('Result','RegionalVariantList');
    return self::Call('MainTVAgent2','GetRegionalVariantList',$args,$filter);;
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
   |  Funktion: GetScheduleListURL                                                  |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result          ( string )                                                  |
   |    ScheduleListURL ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function GetScheduleListURL(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','ScheduleListURL');
    return self::Call('MainTVAgent2','GetScheduleListURL',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetSharpness                                                        |
   |  Erwartet:                                                                     |
   |    Instance         ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentSharpness ( ui2 ) [ 0 bis 100 ]                                      |
   +--------------------------------------------------------------------------------*/
  public function GetSharpness(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentSharpness');
    $CurrentSharpness=self::Call('RenderingControl','GetSharpness',$args,$filter);;
    $this->SetValueInteger('Sharpness',$CurrentSharpness);
    return $CurrentSharpness;
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
   |  Funktion: GetSourceList                                                       |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result     ( string )                                                       |
   |    SourceList ( string )                                                       |
   +--------------------------------------------------------------------------------*/
  public function GetSourceList(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result','SourceList');
    return self::Call('MainTVAgent2','GetSourceList',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetState                                                            |
   |  Erwartet:                                                                     |
   |    Instance     ( ui4 ) ( Vorgabe = 0 )                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentState ( ui2 ) [ RPC2SAMSUNG_STATE_STOP|RPC2SAMSUNG_STATE_PLAY|RPC2SAMSUNG_STATE_PAUSE|RPC2SAMSUNG_STATE_TRANS|RPC2SAMSUNG_STATE_ERROR ]|
   +--------------------------------------------------------------------------------*/
  public function GetState($Instance=0){
    $states=array('STOPPED'=>RPC2SAMSUNG_STATE_STOP,'PAUSED_PLAYBACK'=>RPC2SAMSUNG_STATE_PAUSE,'PLAYING'=>RPC2SAMSUNG_STATE_PLAY,'TRANSITIONING'=>RPC2SAMSUNG_STATE_TRANS,'NO_MEDIA_PRESENT'=>RPC2SAMSUNG_STATE_ERROR);
    $v=self::GetTransportInfo($Instance);
    return ($v&&($s=$v['CurrentTransportState'])&&isset($a[$s]))?$a[$s]:RPC2SAMSUNG_STATE_ERROR;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: GetTransportInfo                                                    |
   |  Erwartet:                                                                     |
   |    Instance               ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )               |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    CurrentTransportState  ( string ) [ STOPPED|PAUSED_PLAYBACK|PLAYING|TRANSITIONING|NO_MEDIA_PRESENT ]|
   |    CurrentTransportStatus ( string ) [ OK|ERROR_OCCURRED ]                     |
   |    CurrentSpeed           ( string )                                           |
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
   |    PlayMode       ( string ) [ NORMAL ] ( Vorgabe = 'NORMAL' )                 |
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
   |  Funktion: ModifyChannelName                                                   |
   |  Erwartet:                                                                     |
   |    AntennaMode       ( ui4    )                                                |
   |    ChannelName       ( string )                                                |
   |    Channel           ( string ) ( Vorgabe = 'Master' )                         |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result            ( string )                                                |
   |    ReturnChannelName ( string )                                                |
   +--------------------------------------------------------------------------------*/
  public function ModifyChannelName(integer $AntennaMode,string $ChannelName,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'Channel'=>$Channel,'ChannelName'=>$ChannelName);
    $filter=array('Result','ReturnChannelName');
    return self::Call('MainTVAgent2','ModifyChannelName',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ModifyFavoriteChannel                                               |
   |  Erwartet:                                                                     |
   |    AntennaMode    ( ui4    )                                                   |
   |    FavoriteChList ( string )                                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result         ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function ModifyFavoriteChannel(integer $AntennaMode,string $FavoriteChList){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode,'FavoriteChList'=>$FavoriteChList);
    $filter=array('Result');
    return self::Call('MainTVAgent2','ModifyFavoriteChannel',$args,$filter);;
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
   |    Speed    ( ui4 ) ( Vorgabe = 1 )                                            |
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
   |  Funktion: PlayRecordedItem                                                    |
   |  Erwartet:                                                                     |
   |    UID    ( ui4    )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function PlayRecordedItem(integer $UID){
    if (!$this->GetOnlineState()) return null;
    $args=array('UID'=>$UID);
    $filter=array('Result');
    return self::Call('MainTVAgent2','PlayRecordedItem',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: PrepareForConnection                                                |
   |  Erwartet:                                                                     |
   |    RemoteProtocolInfo    ( string )                                            |
   |    PeerConnectionManager ( string )                                            |
   |    PeerConnectionID      ( i4     )                                            |
   |    Direction             ( string ) [ Input|Output ]                           |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    ConnectionID          ( i4     )                                            |
   |    AVTransportID         ( i4     )                                            |
   |    RcsID                 ( i4     )                                            |
   +--------------------------------------------------------------------------------*/
  public function PrepareForConnection(string $RemoteProtocolInfo,string $PeerConnectionManager,integer $PeerConnectionID,string $Direction){
    if (!$this->GetOnlineState()) return null;
    $args=array('RemoteProtocolInfo'=>$RemoteProtocolInfo,'PeerConnectionManager'=>$PeerConnectionManager,'PeerConnectionID'=>$PeerConnectionID,'Direction'=>$Direction);
    $filter=array('ConnectionID','AVTransportID','RcsID');
    return self::Call('ConnectionManager','PrepareForConnection',$args,$filter);;
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
   |  Funktion: RunBrowser                                                          |
   |  Erwartet:                                                                     |
   |    BrowserURL ( string )                                                       |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result     ( string )                                                       |
   +--------------------------------------------------------------------------------*/
  public function RunBrowser(string $BrowserURL){
    if (!$this->GetOnlineState()) return null;
    $args=array('BrowserURL'=>$BrowserURL);
    $filter=array('Result');
    return self::Call('MainTVAgent2','RunBrowser',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: Seek                                                                |
   |  Erwartet:                                                                     |
   |    Target   ( string )                                                         |
   |    Instance ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                             |
   |    Unit     ( string ) [ TRACK_NR|REL_TIME|ABS_TIME|ABS_COUNT|REL_COUNT|X_DLNA_REL_BYTE|FRAME ] ( Vorgabe = 'TRACK_NR' )|
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
   |  Funktion: SendBrowserCommand                                                  |
   |  Erwartet:                                                                     |
   |    BrowserCommand ( string )                                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result         ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function SendBrowserCommand(string $BrowserCommand){
    if (!$this->GetOnlineState()) return null;
    $args=array('BrowserCommand'=>$BrowserCommand);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SendBrowserCommand',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SendKeyCode                                                         |
   |  Erwartet:                                                                     |
   |    KeyCode        ( ui4    )                                                   |
   |    KeyDescription ( string )                                                   |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SendKeyCodeMultiScreenService(integer $KeyCode,string $KeyDescription){
    if (!$this->GetOnlineState()) return null;
    $args=array('KeyCode'=>$KeyCode,'KeyDescription'=>$KeyDescription);
    return self::Call('MultiScreenService','SendKeyCodeMultiScreenService',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SendKeyCode                                                         |
   |  Erwartet:                                                                     |
   |    KeyCode        ( ui4    )                                                   |
   |    KeyDescription ( string )                                                   |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SendKeyCodedial(integer $KeyCode,string $KeyDescription){
    if (!$this->GetOnlineState()) return null;
    $args=array('KeyCode'=>$KeyCode,'KeyDescription'=>$KeyDescription);
    return self::Call('dial','SendKeyCodedial',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SendMBRIRKey                                                        |
   |  Erwartet:                                                                     |
   |    ActivityIndex ( ui4    )                                                    |
   |    MBRDevice     ( string )                                                    |
   |    MBRIRKey      ( string )                                                    |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result        ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function SendMBRIRKey(integer $ActivityIndex,string $MBRDevice,string $MBRIRKey){
    if (!$this->GetOnlineState()) return null;
    $args=array('ActivityIndex'=>$ActivityIndex,'MBRDevice'=>$MBRDevice,'MBRIRKey'=>$MBRIRKey);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SendMBRIRKey',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetAVOff                                                            |
   |  Erwartet:                                                                     |
   |    AVOff  ( string )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function SetAVOff(string $AVOff){
    if (!$this->GetOnlineState()) return null;
    $args=array('AVOff'=>$AVOff);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetAVOff',$args,$filter);;
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
   |  Funktion: SetAntennaMode                                                      |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function SetAntennaMode(integer $AntennaMode){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetAntennaMode',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetBrightness                                                       |
   |  Erwartet:                                                                     |
   |    DesiredBrightness ( ui2 ) [ 0 bis 100 ]                                     |
   |    Instance          ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                       |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetBrightness(integer $DesiredBrightness,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'DesiredBrightness'=>$DesiredBrightness);
    $this->SetValueInteger('Brightness',$DesiredBrightness);
    return self::Call('RenderingControl','SetBrightness',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetChannelLock                                                      |
   |  Erwartet:                                                                     |
   |    AntennaMode ( ui4    )                                                      |
   |    ChannelList ( string )                                                      |
   |    Lock        ( string )                                                      |
   |    PIN         ( string )                                                      |
   |    StartTime   ( ui4    )                                                      |
   |    EndTime     ( ui4    )                                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function SetChannelLock(integer $AntennaMode,string $ChannelList,string $Lock,string $PIN,integer $StartTime,integer $EndTime){
    if (!$this->GetOnlineState()) return null;
    $args=array('AntennaMode'=>$AntennaMode,'ChannelList'=>$ChannelList,'Lock'=>$Lock,'PIN'=>$PIN,'StartTime'=>$StartTime,'EndTime'=>$EndTime);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetChannelLock',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetContrast                                                         |
   |  Erwartet:                                                                     |
   |    DesiredContrast ( ui2 ) [ 0 bis 100 ]                                       |
   |    Instance        ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                         |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetContrast(integer $DesiredContrast,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'DesiredContrast'=>$DesiredContrast);
    $this->SetValueInteger('Contrast',$DesiredContrast);
    return self::Call('RenderingControl','SetContrast',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetHTSAllSpeakerDistance                                            |
   |  Erwartet:                                                                     |
   |    AllSpeakerDistance ( string )                                               |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result             ( string )                                               |
   +--------------------------------------------------------------------------------*/
  public function SetHTSAllSpeakerDistance(string $AllSpeakerDistance){
    if (!$this->GetOnlineState()) return null;
    $args=array('AllSpeakerDistance'=>$AllSpeakerDistance);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetHTSAllSpeakerDistance',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetHTSAllSpeakerLevel                                               |
   |  Erwartet:                                                                     |
   |    AllSpeakerLevel ( string )                                                  |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result          ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function SetHTSAllSpeakerLevel(string $AllSpeakerLevel){
    if (!$this->GetOnlineState()) return null;
    $args=array('AllSpeakerLevel'=>$AllSpeakerLevel);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetHTSAllSpeakerLevel',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetHTSSoundEffect                                                   |
   |  Erwartet:                                                                     |
   |    SoundEffect ( string )                                                      |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( string )                                                      |
   +--------------------------------------------------------------------------------*/
  public function SetHTSSoundEffect(string $SoundEffect){
    if (!$this->GetOnlineState()) return null;
    $args=array('SoundEffect'=>$SoundEffect);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetHTSSoundEffect',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetMainTVChannel                                                    |
   |  Erwartet:                                                                     |
   |    ChannelListType ( string )                                                  |
   |    SatelliteID     ( ui4    )                                                  |
   |    Channel         ( string ) ( Vorgabe = 'Master' )                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result          ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function SetMainTVChannel(string $ChannelListType,integer $SatelliteID,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('ChannelListType'=>$ChannelListType,'SatelliteID'=>$SatelliteID,'Channel'=>$Channel);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetMainTVChannel',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetMainTVSource                                                     |
   |  Erwartet:                                                                     |
   |    Source ( string )                                                           |
   |    ID     ( ui4    )                                                           |
   |    UiID   ( ui4    )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function SetMainTVSource(string $Source,integer $ID,integer $UiID){
    if (!$this->GetOnlineState()) return null;
    $args=array('Source'=>$Source,'ID'=>$ID,'UiID'=>$UiID);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetMainTVSource',$args,$filter);;
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
   |    Instance    ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                          |
   |    NewPlayMode ( string ) [ NORMAL ] ( Vorgabe = 'NORMAL' )                    |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetPlayMode(integer $Instance,string $NewPlayMode){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    if(!isset($NewPlayMode))$NewPlayMode='NORMAL';
    $args=array('InstanceID'=>$Instance,'NewPlayMode'=>$NewPlayMode);
    return self::Call('AVTransport','SetPlayMode',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetRecordDuration                                                   |
   |  Erwartet:                                                                     |
   |    RecordDuration ( ui4    )                                                   |
   |    Channel        ( string ) ( Vorgabe = 'Master' )                            |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result         ( string )                                                   |
   +--------------------------------------------------------------------------------*/
  public function SetRecordDuration(integer $RecordDuration,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('Channel'=>$Channel,'RecordDuration'=>$RecordDuration);
    $filter=array('Result');
    return self::Call('MainTVAgent2','SetRecordDuration',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: SetRegionalVariant                                                  |
   |  Erwartet:                                                                     |
   |    AntennaMode   ( ui4    )                                                    |
   |    Channel       ( string ) ( Vorgabe = 'Master' )                             |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result        ( string )                                                    |
   |    LogicalNumber ( ui4    )                                                    |
   +--------------------------------------------------------------------------------*/
  public function SetRegionalVariant(integer $AntennaMode,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'Channel'=>$Channel);
    $filter=array('Result','LogicalNumber');
    return self::Call('MainTVAgent2','SetRegionalVariant',$args,$filter);;
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
   |  Funktion: SetSharpness                                                        |
   |  Erwartet:                                                                     |
   |    DesiredSharpness ( ui2 ) [ 0 bis 100 ]                                      |
   |    Instance         ( ui4 ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function SetSharpness(integer $DesiredSharpness,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'DesiredSharpness'=>$DesiredSharpness);
    $this->SetValueInteger('Sharpness',$DesiredSharpness);
    return self::Call('RenderingControl','SetSharpness',$args,null);;
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
   |    NewState     ( ui2 ) [ RPC2SAMSUNG_STATE_STOP|RPC2SAMSUNG_STATE_PLAY|RPC2SAMSUNG_STATE_PAUSE|RPC2SAMSUNG_STATE_NEXT|RPC2SAMSUNG_STATE_PREV ]|
   |    InstanceID   ( ui4 ) ( Vorgabe = 0 )                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    CurrentState ( ui2 ) [ RPC2SAMSUNG_STATE_STOP|RPC2SAMSUNG_STATE_PLAY|RPC2SAMSUNG_STATE_PAUSE|RPC2SAMSUNG_STATE_TRANS|RPC2SAMSUNG_STATE_ERROR ]|
   +--------------------------------------------------------------------------------*/
  public function SetState($NewState, $InstanceID=0){
    switch($NewState){
      case RPC2SAMSUNG_STATE_STOP : $s=$this->Stop($InstanceID)?RPC2SAMSUNG_STATE_STOP:RPC2SAMSUNG_STATE_ERROR; break;
      case RPC2SAMSUNG_STATE_PREV : $s=$this->Previous($InstanceID)?RPC2SAMSUNG_STATE_PLAY:RPC2SAMSUNG_STATE_STOP;break;
      case RPC2SAMSUNG_STATE_PLAY : $s=$this->Play($InstanceID)?RPC2SAMSUNG_STATE_PLAY:RPC2SAMSUNG_STATE_STOP; break;
      case RPC2SAMSUNG_STATE_PAUSE: $s=$this->Pause($InstanceID)?RPC2SAMSUNG_STATE_PAUSE:RPC2SAMSUNG_STATE_STOP;break;
      case RPC2SAMSUNG_STATE_NEXT : $s=$this->Next($InstanceID)?RPC2SAMSUNG_STATE_PLAY:RPC2SAMSUNG_STATE_STOP; break;
      default : return RPC2SAMSUNG_STATE_ERROR;
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
   |  Funktion: StartCloneView                                                      |
   |  Erwartet:                                                                     |
   |    ForcedFlag   ( string ) [ Normal|Forced|ADOff ]                             |
   |    DRMType      ( string )                                                     |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result       ( string )                                                     |
   |    CloneViewURL ( string )                                                     |
   +--------------------------------------------------------------------------------*/
  public function StartCloneView(string $ForcedFlag,string $DRMType){
    if (!$this->GetOnlineState()) return null;
    $args=array('ForcedFlag'=>$ForcedFlag,'DRMType'=>$DRMType);
    $filter=array('Result','CloneViewURL');
    return self::Call('MainTVAgent2','StartCloneView',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: StartExtSourceView                                                  |
   |  Erwartet:                                                                     |
   |    Source           ( string )                                                 |
   |    ID               ( ui4    )                                                 |
   |    ForcedFlag       ( string ) [ Normal|Forced|ADOff ]                         |
   |    DRMType          ( string )                                                 |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result           ( string )                                                 |
   |    ExtSourceViewURL ( string )                                                 |
   +--------------------------------------------------------------------------------*/
  public function StartExtSourceView(string $Source,integer $ID,string $ForcedFlag,string $DRMType){
    if (!$this->GetOnlineState()) return null;
    $args=array('Source'=>$Source,'ID'=>$ID,'ForcedFlag'=>$ForcedFlag,'DRMType'=>$DRMType);
    $filter=array('Result','ExtSourceViewURL');
    return self::Call('MainTVAgent2','StartExtSourceView',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: StartInstantRecording                                               |
   |  Erwartet:                                                                     |
   |    Channel ( string ) ( Vorgabe = 'Master' )                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result  ( string )                                                          |
   +--------------------------------------------------------------------------------*/
  public function StartInstantRecording(string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('Channel'=>$Channel);
    $filter=array('Result');
    return self::Call('MainTVAgent2','StartInstantRecording',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: StartSecondTVView                                                   |
   |  Erwartet:                                                                     |
   |    AntennaMode     ( ui4    )                                                  |
   |    ChannelListType ( string )                                                  |
   |    SatelliteID     ( ui4    )                                                  |
   |    ForcedFlag      ( string ) [ Normal|Forced|ADOff ]                          |
   |    DRMType         ( string )                                                  |
   |    Channel         ( string ) ( Vorgabe = 'Master' )                           |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    Result          ( string )                                                  |
   |    SecondTVURL     ( string )                                                  |
   +--------------------------------------------------------------------------------*/
  public function StartSecondTVView(integer $AntennaMode,string $ChannelListType,integer $SatelliteID,string $ForcedFlag,string $DRMType,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('AntennaMode'=>$AntennaMode,'ChannelListType'=>$ChannelListType,'SatelliteID'=>$SatelliteID,'Channel'=>$Channel,'ForcedFlag'=>$ForcedFlag,'DRMType'=>$DRMType);
    $filter=array('Result','SecondTVURL');
    return self::Call('MainTVAgent2','StartSecondTVView',$args,$filter);;
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
   |  Funktion: StopBrowser                                                         |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result ( string )                                                           |
   +--------------------------------------------------------------------------------*/
  public function StopBrowser(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result');
    return self::Call('MainTVAgent2','StopBrowser',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: StopRecord                                                          |
   |  Erwartet:                                                                     |
   |    Channel ( string ) ( Vorgabe = 'Master' )                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result  ( string )                                                          |
   +--------------------------------------------------------------------------------*/
  public function StopRecord(string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Channel))$Channel='Master';
    $args=array('Channel'=>$Channel);
    $filter=array('Result');
    return self::Call('MainTVAgent2','StopRecord',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: StopView                                                            |
   |  Erwartet:                                                                     |
   |    ViewURL ( string )                                                          |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result  ( string )                                                          |
   +--------------------------------------------------------------------------------*/
  public function StopView(string $ViewURL){
    if (!$this->GetOnlineState()) return null;
    $args=array('ViewURL'=>$ViewURL);
    $filter=array('Result');
    return self::Call('MainTVAgent2','StopView',$args,$filter);;
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
    );
    if(empty($this->_PlayModes))
      foreach($modes as $k=>$a)$this->_PlayModes[$a[0]][$a[1]][$a[2]]=$k;
    if(!$t=$this->GetTransportSettings($InstanceID))return false;
    list($this->_boRepeat,$this->_boShuffle,$this->_boAll)=$modes[$t['PlayMode']];
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_DLNA_GetBytePositionInfo                                          |
   |  Erwartet:                                                                     |
   |    Instance  ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                            |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    TrackSize ( string )                                                        |
   |    RelByte   ( string )                                                        |
   |    AbsByte   ( string )                                                        |
   +--------------------------------------------------------------------------------*/
  public function X_DLNA_GetBytePositionInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('TrackSize','RelByte','AbsByte');
    return self::Call('AVTransport','X_DLNA_GetBytePositionInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_GetAudioSelection                                                 |
   |  Erwartet:                                                                     |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    AudioPID      ( ui2    ) [ 0 bis 65535 ]                                    |
   |    AudioEncoding ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function X_GetAudioSelection(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('AudioPID','AudioEncoding');
    return self::Call('RenderingControl','X_GetAudioSelection',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_GetStoppedReason                                                  |
   |  Erwartet:                                                                     |
   |    Instance          ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                    |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    StoppedReason     ( string )                                                |
   |    StoppedReasonData ( string )                                                |
   +--------------------------------------------------------------------------------*/
  public function X_GetStoppedReason(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('StoppedReason','StoppedReasonData');
    return self::Call('AVTransport','X_GetStoppedReason',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_GetVideoSelection                                                 |
   |  Erwartet:                                                                     |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: Array mit folgenden Keys                                             |
   |    VideoPID      ( ui2    ) [ 0 bis 65535 ]                                    |
   |    VideoEncoding ( string )                                                    |
   +--------------------------------------------------------------------------------*/
  public function X_GetVideoSelection(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('VideoPID','VideoEncoding');
    return self::Call('RenderingControl','X_GetVideoSelection',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_SetAutoSlideShowMode                                              |
   |  Erwartet:                                                                     |
   |    AutoSlideShowMode ( string ) [ ON|OFF ]                                     |
   |    Instance          ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                    |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_SetAutoSlideShowMode(string $AutoSlideShowMode,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'AutoSlideShowMode'=>$AutoSlideShowMode);
    return self::Call('AVTransport','X_SetAutoSlideShowMode',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_SetSlideShowEffectHint                                            |
   |  Erwartet:                                                                     |
   |    SlideShowEffectHint ( string ) [ ON|OFF ]                                   |
   |    Instance            ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                  |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_SetSlideShowEffectHint(string $SlideShowEffectHint,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'SlideShowEffectHint'=>$SlideShowEffectHint);
    return self::Call('AVTransport','X_SetSlideShowEffectHint',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_UpdateAudioSelection                                              |
   |  Erwartet:                                                                     |
   |    AudioPID      ( ui2    ) [ 0 bis 65535 ]                                    |
   |    AudioEncoding ( string )                                                    |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_UpdateAudioSelection(integer $AudioPID,string $AudioEncoding,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'AudioPID'=>$AudioPID,'AudioEncoding'=>$AudioEncoding);
    return self::Call('RenderingControl','X_UpdateAudioSelection',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: X_UpdateVideoSelection                                              |
   |  Erwartet:                                                                     |
   |    VideoPID      ( ui2    ) [ 0 bis 65535 ]                                    |
   |    VideoEncoding ( string )                                                    |
   |    Instance      ( ui4    ) [ 0 bis 9 ] ( Vorgabe = 0 )                        |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function X_UpdateVideoSelection(integer $VideoPID,string $VideoEncoding,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(!isset($Instance))$Instance=0;
    $args=array('InstanceID'=>$Instance,'VideoPID'=>$VideoPID,'VideoEncoding'=>$VideoEncoding);
    return self::Call('RenderingControl','X_UpdateVideoSelection',$args,null);;
  }
}
?>