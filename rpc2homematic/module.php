<?
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 |  Class           :rpc2homematic extends uRpcBase                               |
 |  Version         :2.2                                                          |
 |  BuildDate       :Tue 19.01.2016 00:01:30                                      |
 |  Publisher       :(c)2016 Xaver Bauer                                          |
 |  Contact         :xaver65@gmail.com                                            |
 |  Desc            :PHP Classes to Control Homematic CCU2                        |
 |  port            :2001                                                         |
 |  base            :http://192.168.112.15:2001                                   |
 |  scpdurl         :/hm_description.xml                                          |
 |  modelName       :Homematic CCU                                                |
 |  deviceType      :urn:schemas-upnp-org:device:Homematic:1                      |
 |  friendlyName    :Homematic CCU                                                |
 |  manufacturer    :Homematic, EQ3                                               |
 |  manufacturerURL :http://www.eq-3.de/                                          |
 |  modelNumber     :CCU2                                                         |
 |  modelURL        :http://www.eq-3.de/zentralen-und-gateways.html               |
 |  UDN             :uuid:HM_CCU                                                  |
 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/

require_once( __DIR__ . '/../uRpcBase.class.php');
require_once( __DIR__ . '/../uRpcIo.class.php');
class rpc2homematic extends uRpcBase {
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
      case 'Control' : return [2001,"urn:schemas-upnp-org:service:Control:1","/","","/hm_control.xml"];
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
    IPS_SetProperty ($this->InstanceID, 'Port',2001 );
    IPS_SetProperty ($this->InstanceID, 'ConnectionType','curl');
    IPS_SetProperty ($this->InstanceID, 'Timeout',2);
    $this->RegisterPropertyInteger('IntervallRefresh', 60);
    $this->RegisterTimer('Refresh_All', 0, 'rpc2homematic_Update($_IPS[\'TARGET\']);');
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: ApplyChanges                                                        |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function ApplyChanges(){
    parent::ApplyChanges();
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
   |  Funktion: activateLinkParamset                                                |
   |  Erwartet:                                                                     |
   |    Address      ( string  )                                                    |
   |    Peer_address ( string  )                                                    |
   |    Long_press   ( boolean ) [ true|false ]                                     |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function activateLinkParamset(string $Address,string $Peer_address,boolean $Long_press){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Peer_address'))$Peer_address=null;
    if(is_null('Long_press'))$Long_press=null;
    $args=array('Address'=>$Address,'Peer_address'=>$Peer_address,'Long_press'=>$Long_press);
    return self::Call('Control','activateLinkParamset',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: addDevice                                                           |
   |  Erwartet:                                                                     |
   |    Serial_number     ( string )                                                |
   |                                                                                |
   |  Liefert:                                                                      |
   |    DeviceDescription ( array  )                                                |
   +--------------------------------------------------------------------------------*/
  public function addDevice(string $Serial_number){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Serial_number'))$Serial_number=null;
    $args=array('Serial_number'=>$Serial_number);
    $filter=array('DeviceDescription');
    return self::Call('Control','addDevice',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: addLink                                                             |
   |  Erwartet:                                                                     |
   |    Sender      ( string )                                                      |
   |    Receiver    ( string )                                                      |
   |    Description ( string )                                                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function addLink(string $Sender,string $Receiver,string $Description){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Sender'))$Sender=null;
    if(is_null('Receiver'))$Receiver=null;
    if(is_null('Description'))$Description=null;
    $args=array('Sender'=>$Sender,'Receiver'=>$Receiver,'Description'=>$Description);
    return self::Call('Control','addLink',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: changekey                                                           |
   |  Erwartet:                                                                     |
   |    Passphrase ( string )                                                       |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function changekey(string $Passphrase){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Passphrase'))$Passphrase=null;
    $args=array('Passphrase'=>$Passphrase);
    return self::Call('Control','changekey',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: clearConfigCache                                                    |
   |  Erwartet:                                                                     |
   |    Address ( string )                                                          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function clearConfigCache(string $Address){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    $args=array('Address'=>$Address);
    return self::Call('Control','clearConfigCache',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: deleteDevice                                                        |
   |  Erwartet:                                                                     |
   |    Address ( string  )                                                         |
   |    Flags   ( integer )                                                         |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function deleteDevice(string $Address,integer $Flags){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Flags'))$Flags=null;
    $args=array('Address'=>$Address,'Flags'=>$Flags);
    return self::Call('Control','deleteDevice',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: determineParameter                                                  |
   |  Erwartet:                                                                     |
   |    Address      ( string )                                                     |
   |    Paramset_key ( string )                                                     |
   |    Parameter_id ( string )                                                     |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function determineParameter(string $Address,string $Paramset_key,string $Parameter_id){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Paramset_key'))$Paramset_key=null;
    if(is_null('Parameter_id'))$Parameter_id=null;
    $args=array('Address'=>$Address,'Paramset_key'=>$Paramset_key,'Parameter_id'=>$Parameter_id);
    return self::Call('Control','determineParameter',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getAllMetadata                                                      |
   |  Erwartet:                                                                     |
   |    Object_id ( string )                                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Struct    ( array  )                                                        |
   +--------------------------------------------------------------------------------*/
  public function getAllMetadata(string $Object_id){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Object_id'))$Object_id=null;
    $args=array('Object_id'=>$Object_id);
    $filter=array('Struct');
    return self::Call('Control','getAllMetadata',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getDeviceDescription                                                |
   |  Erwartet:                                                                     |
   |    Address           ( string )                                                |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Description_array ( array  )                                                |
   +--------------------------------------------------------------------------------*/
  public function getDeviceDescription(string $Address){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    $args=array('Address'=>$Address);
    $filter=array('Description_array');
    return self::Call('Control','getDeviceDescription',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getInstallMode                                                      |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    InstallMode ( integer )                                                     |
   +--------------------------------------------------------------------------------*/
  public function getInstallMode(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('InstallMode');
    return self::Call('Control','getInstallMode',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getKeyMismatchDevice                                                |
   |  Erwartet:                                                                     |
   |    Reset          ( boolean ) [ true|false ]                                   |
   |                                                                                |
   |  Liefert:                                                                      |
   |    MismatchDevice ( string  )                                                  |
   +--------------------------------------------------------------------------------*/
  public function getKeyMismatchDevice(boolean $Reset){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Reset'))$Reset=null;
    $args=array('Reset'=>$Reset);
    $filter=array('MismatchDevice');
    return self::Call('Control','getKeyMismatchDevice',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getLinkInfo                                                         |
   |  Erwartet:                                                                     |
   |    Sender   ( string )                                                         |
   |    Receiver ( string )                                                         |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Info     ( array  )                                                         |
   +--------------------------------------------------------------------------------*/
  public function getLinkInfo(string $Sender,string $Receiver){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Sender'))$Sender=null;
    if(is_null('Receiver'))$Receiver=null;
    $args=array('Sender'=>$Sender,'Receiver'=>$Receiver);
    $filter=array('Info');
    return self::Call('Control','getLinkInfo',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getLinkPeers                                                        |
   |  Erwartet:                                                                     |
   |    Address   ( string )                                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    LinkPeers ( array  )                                                        |
   +--------------------------------------------------------------------------------*/
  public function getLinkPeers(string $Address){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    $args=array('Address'=>$Address);
    $filter=array('LinkPeers');
    return self::Call('Control','getLinkPeers',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getLinks                                                            |
   |  Erwartet:                                                                     |
   |    Address ( string  )                                                         |
   |    Flags   ( integer )                                                         |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Link    ( array   )                                                         |
   +--------------------------------------------------------------------------------*/
  public function getLinks(string $Address,integer $Flags){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Flags'))$Flags=null;
    $args=array('Address'=>$Address,'Flags'=>$Flags);
    $filter=array('Link');
    return self::Call('Control','getLinks',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getMetadata                                                         |
   |  Erwartet:                                                                     |
   |    Object_id      ( string  )                                                  |
   |    Data_id        ( string  )                                                  |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result_variant ( variant )                                                  |
   +--------------------------------------------------------------------------------*/
  public function getMetadata(string $Object_id,string $Data_id){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Object_id'))$Object_id=null;
    if(is_null('Data_id'))$Data_id=null;
    $args=array('Object_id'=>$Object_id,'Data_id'=>$Data_id);
    $filter=array('Result_variant');
    return self::Call('Control','getMetadata',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getParamset                                                         |
   |  Erwartet:                                                                     |
   |    Address      ( string )                                                     |
   |    Paramset_key ( string )                                                     |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Paramset     ( array  )                                                     |
   +--------------------------------------------------------------------------------*/
  public function getParamset(string $Address,string $Paramset_key){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Paramset_key'))$Paramset_key=null;
    $args=array('Address'=>$Address,'Paramset_key'=>$Paramset_key);
    $filter=array('Paramset');
    return self::Call('Control','getParamset',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getParamsetDescription                                              |
   |  Erwartet:                                                                     |
   |    Address           ( string )                                                |
   |    Paramset_type     ( string )                                                |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Description_array ( array  )                                                |
   +--------------------------------------------------------------------------------*/
  public function getParamsetDescription(string $Address,string $Paramset_type){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Paramset_type'))$Paramset_type=null;
    $args=array('Address'=>$Address,'Paramset_type'=>$Paramset_type);
    $filter=array('Description_array');
    return self::Call('Control','getParamsetDescription',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getParamsetId                                                       |
   |  Erwartet:                                                                     |
   |    Address ( string )                                                          |
   |    Type    ( string )                                                          |
   |                                                                                |
   |  Liefert:                                                                      |
   |    ID      ( string )                                                          |
   +--------------------------------------------------------------------------------*/
  public function getParamsetId(string $Address,string $Type){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Type'))$Type=null;
    $args=array('Address'=>$Address,'Type'=>$Type);
    $filter=array('ID');
    return self::Call('Control','getParamsetId',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getServiceMessages                                                  |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result_array ( array )                                                      |
   +--------------------------------------------------------------------------------*/
  public function getServiceMessages(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Result_array');
    return self::Call('Control','getServiceMessages',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: getValue                                                            |
   |  Erwartet:                                                                     |
   |    Address   ( string )                                                        |
   |    Value_key ( string )                                                        |
   |                                                                                |
   |  Liefert:                                                                      |
   |    ValueType ( string )                                                        |
   +--------------------------------------------------------------------------------*/
  public function getValue(string $Address,string $Value_key){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Value_key'))$Value_key=null;
    $args=array('Address'=>$Address,'Value_key'=>$Value_key);
    $filter=array('ValueType');
    return self::Call('Control','getValue',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: init                                                                |
   |  Erwartet:                                                                     |
   |    Url          ( string )                                                     |
   |    Interface_id ( string )                                                     |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function init(string $Url,string $Interface_id){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Url'))$Url=null;
    if(is_null('Interface_id'))$Interface_id=null;
    $args=array('Url'=>$Url,'Interface_id'=>$Interface_id);
    return self::Call('Control','init',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: listBidcosInterfaces                                                |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Interfaces ( array )                                                        |
   +--------------------------------------------------------------------------------*/
  public function listBidcosInterfaces(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Interfaces');
    return self::Call('Control','listBidcosInterfaces',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: listDevices                                                         |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Description_Array of DeviceDescription ( Array of DeviceDescription )       |
   +--------------------------------------------------------------------------------*/
  public function listDevices(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Description_Array of DeviceDescription');
    return self::Call('Control','listDevices',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: listTeams                                                           |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    DeviceDescriptions ( array )                                                |
   +--------------------------------------------------------------------------------*/
  public function listTeams(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('DeviceDescriptions');
    return self::Call('Control','listTeams',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: logLevel                                                            |
   |  Erwartet:                                                                     |
   |    Level ( integer )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Level ( integer )                                                           |
   +--------------------------------------------------------------------------------*/
  public function logLevel(integer $Level){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Level'))$Level=null;
    $args=array('Level'=>$Level);
    $filter=array('Level');
    return self::Call('Control','logLevel',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: putParamset                                                         |
   |  Erwartet:                                                                     |
   |    Address      ( string )                                                     |
   |    Paramset_key ( string )                                                     |
   |    Paramset     ( array  )                                                     |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function putParamset(string $Address,string $Paramset_key,integer $Paramset){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Paramset_key'))$Paramset_key=null;
    if(is_null('Paramset'))$Paramset=null;
    $args=array('Address'=>$Address,'Paramset_key'=>$Paramset_key,'Paramset'=>$Paramset);
    return self::Call('Control','putParamset',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: removeLink                                                          |
   |  Erwartet:                                                                     |
   |    Sender   ( string )                                                         |
   |    Receiver ( string )                                                         |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function removeLink(string $Sender,string $Receiver){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Sender'))$Sender=null;
    if(is_null('Receiver'))$Receiver=null;
    $args=array('Sender'=>$Sender,'Receiver'=>$Receiver);
    return self::Call('Control','removeLink',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: reportValueUsage                                                    |
   |  Erwartet:                                                                     |
   |    Address     ( string  )                                                     |
   |    Value_id    ( string  )                                                     |
   |    Ref_counter ( integer )                                                     |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Result      ( boolean ) [ true|false ]                                      |
   +--------------------------------------------------------------------------------*/
  public function reportValueUsage(string $Address,string $Value_id,integer $Ref_counter){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Value_id'))$Value_id=null;
    if(is_null('Ref_counter'))$Ref_counter=null;
    $args=array('Address'=>$Address,'Value_id'=>$Value_id,'Ref_counter'=>$Ref_counter);
    $filter=array('Result');
    return self::Call('Control','reportValueUsage',$args,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: restoreConfigToDevice                                               |
   |  Erwartet:                                                                     |
   |    Address ( string )                                                          |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function restoreConfigToDevice(string $Address){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    $args=array('Address'=>$Address);
    return self::Call('Control','restoreConfigToDevice',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: rssiInfo                                                            |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Info ( array )                                                              |
   +--------------------------------------------------------------------------------*/
  public function rssiInfo(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Info');
    return self::Call('Control','rssiInfo',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: searchDevices                                                       |
   |  Erwartet: nichts                                                              |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Count ( integer )                                                           |
   +--------------------------------------------------------------------------------*/
  public function searchDevices(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Count');
    return self::Call('Control','searchDevices',null,$filter);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setBidcosInterface                                                  |
   |  Erwartet:                                                                     |
   |    Device_address    ( string  )                                               |
   |    Interface_address ( string  )                                               |
   |    Rooming           ( boolean ) [ true|false ]                                |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setBidcosInterface(string $Device_address,string $Interface_address,boolean $Rooming){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Device_address'))$Device_address=null;
    if(is_null('Interface_address'))$Interface_address=null;
    if(is_null('Rooming'))$Rooming=null;
    $args=array('Device_address'=>$Device_address,'Interface_address'=>$Interface_address,'Rooming'=>$Rooming);
    return self::Call('Control','setBidcosInterface',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setInstallMode                                                      |
   |  Erwartet:                                                                     |
   |    On ( boolean ) [ true|false ]                                               |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setInstallMode(boolean $On){
    if (!$this->GetOnlineState()) return null;
    if(is_null('On'))$On=null;
    $args=array('On'=>$On);
    return self::Call('Control','setInstallMode',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setLinkInfo                                                         |
   |  Erwartet:                                                                     |
   |    Sender      ( string )                                                      |
   |    Receiver    ( string )                                                      |
   |    Name        ( string )                                                      |
   |    Description ( string )                                                      |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setLinkInfo(string $Sender,string $Receiver,string $Name,string $Description){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Sender'))$Sender=null;
    if(is_null('Receiver'))$Receiver=null;
    if(is_null('Name'))$Name=null;
    if(is_null('Description'))$Description=null;
    $args=array('Sender'=>$Sender,'Receiver'=>$Receiver,'Name'=>$Name,'Description'=>$Description);
    return self::Call('Control','setLinkInfo',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setMetadata                                                         |
   |  Erwartet:                                                                     |
   |    Object_id ( string  )                                                       |
   |    Data_id   ( string  )                                                       |
   |    Value     ( variant )                                                       |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setMetadata(string $Object_id,string $Data_id,integer $Value){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Object_id'))$Object_id=null;
    if(is_null('Data_id'))$Data_id=null;
    if(is_null('Value'))$Value=null;
    $args=array('Object_id'=>$Object_id,'Data_id'=>$Data_id,'Value'=>$Value);
    return self::Call('Control','setMetadata',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setTeam                                                             |
   |  Erwartet:                                                                     |
   |    Address      ( string )                                                     |
   |    Team_address ( string )                                                     |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setTeam(string $Address,string $Team_address){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Team_address'))$Team_address=null;
    $args=array('Address'=>$Address,'Team_address'=>$Team_address);
    return self::Call('Control','setTeam',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setTempKey                                                          |
   |  Erwartet:                                                                     |
   |    Passphrase ( string )                                                       |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setTempKey(string $Passphrase){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Passphrase'))$Passphrase=null;
    $args=array('Passphrase'=>$Passphrase);
    return self::Call('Control','setTempKey',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: setValue                                                            |
   |  Erwartet:                                                                     |
   |    Address   ( string )                                                        |
   |    Value_key ( string )                                                        |
   |    ValueType ( string )                                                        |
   |                                                                                |
   |  Liefert: nichts                                                               |
   +--------------------------------------------------------------------------------*/
  public function setValue(string $Address,string $Value_key,string $ValueType){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Address'))$Address=null;
    if(is_null('Value_key'))$Value_key=null;
    if(is_null('ValueType'))$ValueType=null;
    $args=array('Address'=>$Address,'Value_key'=>$Value_key,'ValueType'=>$ValueType);
    return self::Call('Control','setValue',$args,null);;
  }
  /*--------------------------------------------------------------------------------+
   |  Funktion: updateFirmware                                                      |
   |  Erwartet:                                                                     |
   |    Devices ( array )                                                           |
   |                                                                                |
   |  Liefert:                                                                      |
   |    Status  ( array )                                                           |
   +--------------------------------------------------------------------------------*/
  public function updateFirmware(integer $Devices){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Devices'))$Devices=null;
    $args=array('Devices'=>$Devices);
    $filter=array('Status');
    return self::Call('Control','updateFirmware',$args,$filter);;
  }
}
?>