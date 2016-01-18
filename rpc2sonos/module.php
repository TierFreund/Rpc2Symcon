<?
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 |  Class           :rpc2sonos extends uRpcBase                                   |
 |  Version         :2.2                                                          |
 |  BuildDate       :Tue 19.01.2016 00:30:27                                      |
 |  Publisher       :(c)2016 Xaver Bauer                                          |
 |  Contact         :xaver65@gmail.com                                            |
 |  Desc            :PHP Classes to Control Sonos PLAY:3                          |
 |  port            :1400                                                         |
 |  base            :http://192.168.112.54:1400                                   |
 |  scpdurl         :/xml/device_description.xml                                  |
 |  modelName       :Sonos PLAY:3                                                 |
 |  deviceType      :urn:schemas-upnp-org:device:ZonePlayer:1                     |
 |  friendlyName    :192.168.112.54 - Sonos PLAY:3                                |
 |  manufacturer    :Sonos, Inc.                                                  |
 |  manufacturerURL :http://www.sonos.com                                         |
 |  modelNumber     :S3                                                           |
 |  modelURL        :http://www.sonos.com/products/zoneplayers/S3                 |
 |  UDN             :uuid:RINCON_B8E9373DABCE01400                                |
 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/

if (!DEFINED('RPC2SONOS_STATE_STOP')) {
  DEFINE('RPC2SONOS_STATE_STOP',0);
  DEFINE('RPC2SONOS_STATE_PREV',1);
  DEFINE('RPC2SONOS_STATE_PLAY',2);
  DEFINE('RPC2SONOS_STATE_PAUSE',3);
  DEFINE('RPC2SONOS_STATE_NEXT',4);
  DEFINE('RPC2SONOS_STATE_TRANS',5);
  DEFINE('RPC2SONOS_STATE_ERROR',6);
}
require_once( __DIR__ . '/../uRpcBase.class.php');
require_once( __DIR__ . '/../uRpcIo.class.php');
class rpc2sonos extends uRpcBase {
  protected $_boRepeat=false;
  protected $_boShuffle=false;
  // Name:string
  protected function GetServiceConnData($name){
    switch($name){
      case                     'AlarmClock' : return [1400,"urn:schemas-upnp-org:service:AlarmClock:1","/AlarmClock/Control","/AlarmClock/Event","/xml/AlarmClock1.xml"];
      case                  'MusicServices' : return [1400,"urn:schemas-upnp-org:service:MusicServices:1","/MusicServices/Control","/MusicServices/Event","/xml/MusicServices1.xml"];
      case               'DeviceProperties' : return [1400,"urn:schemas-upnp-org:service:DeviceProperties:1","/DeviceProperties/Control","/DeviceProperties/Event","/xml/DeviceProperties1.xml"];
      case               'SystemProperties' : return [1400,"urn:schemas-upnp-org:service:SystemProperties:1","/SystemProperties/Control","/SystemProperties/Event","/xml/SystemProperties1.xml"];
      case              'ZoneGroupTopology' : return [1400,"urn:schemas-upnp-org:service:ZoneGroupTopology:1","/ZoneGroupTopology/Control","/ZoneGroupTopology/Event","/xml/ZoneGroupTopology1.xml"];
      case                'GroupManagement' : return [1400,"urn:schemas-upnp-org:service:GroupManagement:1","/GroupManagement/Control","/GroupManagement/Event","/xml/GroupManagement1.xml"];
      case                          'QPlay' : return [1400,"urn:schemas-tencent-com:service:QPlay:1","/QPlay/Control","/QPlay/Event","/xml/QPlay1.xml"];
      case               'ContentDirectory' : return [1400,"urn:schemas-upnp-org:service:ContentDirectory:1","/MediaServer/ContentDirectory/Control","/MediaServer/ContentDirectory/Event","/xml/ContentDirectory1.xml"];
      case   'ConnectionManagerMediaServer' : return [1400,"urn:schemas-upnp-org:service:ConnectionManager:1","/MediaServer/ConnectionManager/Control","/MediaServer/ConnectionManager/Event","/xml/ConnectionManager1.xml"];
      case               'RenderingControl' : return [1400,"urn:schemas-upnp-org:service:RenderingControl:1","/MediaRenderer/RenderingControl/Control","/MediaRenderer/RenderingControl/Event","/xml/RenderingControl1.xml"];
      case 'ConnectionManagerMediaRenderer' : return [1400,"urn:schemas-upnp-org:service:ConnectionManager:1","/MediaRenderer/ConnectionManager/Control","/MediaRenderer/ConnectionManager/Event","/xml/ConnectionManager1.xml"];
      case                    'AVTransport' : return [1400,"urn:schemas-upnp-org:service:AVTransport:1","/MediaRenderer/AVTransport/Control","/MediaRenderer/AVTransport/Event","/xml/AVTransport1.xml"];
      case                          'Queue' : return [1400,"urn:schemas-sonos-com:service:Queue:1","/MediaRenderer/Queue/Control","/MediaRenderer/Queue/Event","/xml/Queue1.xml"];
      case          'GroupRenderingControl' : return [1400,"urn:schemas-upnp-org:service:GroupRenderingControl:1","/MediaRenderer/GroupRenderingControl/Control","/MediaRenderer/GroupRenderingControl/Event","/xml/GroupRenderingControl1.xml"];
    }
    return null;
  }

  public function Create(){
    parent::Create();
    IPS_SetProperty ($this->InstanceID, 'Port',1400 );
    IPS_SetProperty ($this->InstanceID, 'ConnectionType','curl');
    IPS_SetProperty ($this->InstanceID, 'Timeout',2);
    $this->RegisterPropertyInteger('IntervallRefresh', 60);
    $this->RegisterTimer('Refresh_All', 0, 'rpc2sonos_Update($_IPS[\'TARGET\']);');
  }

  public function ApplyChanges(){
    parent::ApplyChanges();
    $this->RegisterProfileBooleanEx('rpc2sonos.OnOff','Information','','',Array(Array(false,'Aus','',-1),Array(true,'Ein','',-1)));
    $this->RegisterVariableBoolean('Mute','Mute','rpc2sonos.OnOff');
    $this->RegisterVariableInteger('Volume','Volume','~Intensity.100');
    $this->RegisterVariableInteger('VolumeDB','VolumeDB','');
    $this->RegisterProfileInteger('rpc2sonos.10_10', '', '', '', -10, 10, 1);
    $this->RegisterVariableInteger('Bass','Bass','rpc2sonos.10_10');
    $this->RegisterVariableInteger('Treble','Treble','rpc2sonos.10_10');
    $this->RegisterVariableInteger('EQ','EQ','');
    $this->RegisterVariableBoolean('Loudness','Loudness','rpc2sonos.OnOff');
    $this->RegisterVariableBoolean('OutputFixed','OutputFixed','rpc2sonos.OnOff');
    $this->RegisterVariableBoolean('CrossfadeMode','CrossfadeMode','rpc2sonos.OnOff');
    $this->RegisterProfileIntegerEx('rpc2sonos.State','Status','','',array(Array(0,'Stop','', -1),Array(1,'Prev','', -1),Array(2,'Play','', -1),Array(3,'Pause','', -1),Array(4,'Next','', -1)));
    $this->RegisterVariableInteger('State','State','rpc2sonos.State');
    $this->RegisterVariableBoolean('Repeat','Repeat','rpc2sonos.OnOff');
    $this->RegisterVariableBoolean('Shuffle','Shuffle','rpc2sonos.OnOff');
    $this->RegisterVariableBoolean('GroupMute','GroupMute','rpc2sonos.OnOff');
    $this->RegisterVariableInteger('GroupVolume','GroupVolume','~Intensity.100');
    foreach(array('Mute','Volume','VolumeDB','Bass','Treble','EQ','Loudness','OutputFixed','CrossfadeMode','State','Repeat','Shuffle','GroupMute','GroupVolume') as $e)$this->EnableAction($e);
  }

  public function Test(){
    if (!parent::Test()) return false;
    return $this->Update(true);
  }
  // All:boolean
  public function Update(boolean $All){
    if($this->GetMute()==null)$this->SetValueBoolean('Mute',false);
    if($this->GetVolume()==null)$this->SetValueInteger('Volume',0);
    if($this->GetVolumeDB()==null)$this->SetValueInteger('VolumeDB',0);
    if($this->GetBass()==null)$this->SetValueInteger('Bass',0);
    if($this->GetTreble()==null)$this->SetValueInteger('Treble',0);
    if($this->GetEQ()==null)$this->SetValueInteger('EQ',0);
    if($this->GetLoudness()==null)$this->SetValueBoolean('Loudness',false);
    if($this->GetOutputFixed()==null)$this->SetValueBoolean('OutputFixed',false);
    if($this->GetCrossfadeMode()==null)$this->SetValueBoolean('CrossfadeMode',false);
    if($this->GetState()==null)$this->SetValueInteger('State',RPC2SONOS_STATE_STOP);
    if($this->GetRepeat()==null)$this->SetValueBoolean('Repeat',false);
    if($this->GetShuffle()==null)$this->SetValueBoolean('Shuffle',false);
    if($this->GetGroupMute()==null)$this->SetValueBoolean('GroupMute',false);
    if($this->GetGroupVolume()==null)$this->SetValueInteger('GroupVolume',0);
  }
  // Ident:string, Value:mixed
  public function RequestAction($Ident, $Value){
    switch($Ident) {
      case 'Mute'    : $this->SetMute($Value); break;
      case 'Volume'  : $this->SetVolume($Value); break;
      case 'VolumeDB': $this->SetVolumeDB($Value); break;
      case 'Bass'    : $this->SetBass($Value); break;
      case 'Treble'  : $this->SetTreble($Value); break;
      case 'EQ'      : $this->SetEQ($Value); break;
      case 'Loudness': $this->SetLoudness($Value); break;
      case 'OutputFixed': $this->SetOutputFixed($Value); break;
      case 'CrossfadeMode': $this->SetCrossfadeMode($Value); break;
      case 'State'   : $this->SetState($Value); break;
      case 'Repeat'  : $this->SetRepeat($Value); break;
      case 'Shuffle' : $this->SetShuffle($Value); break;
      case 'GroupMute': $this->SetGroupMute($Value); break;
      case 'GroupVolume': $this->SetGroupVolume($Value); break;
      default        : throw new Exception("Invalid Ident: $Ident");
    }
  }
  // AccountType:ui4, AccountToken:string, AccountKey:string
  public function AddAccountWithCredentialsX(integer $AccountType,string $AccountToken,string $AccountKey){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountToken'))$AccountToken=null;
    if(is_null('AccountKey'))$AccountKey=null;
    $args=array('AccountType'=>$AccountType,'AccountToken'=>$AccountToken,'AccountKey'=>$AccountKey);
    return self::Call('SystemProperties','AddAccountWithCredentialsX',$args,null);;
  }
  // AccountType:ui4, AccountID:string, AccountPassword:string
  public function AddAccountX(integer $AccountType,string $AccountID,string $AccountPassword){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountID'))$AccountID=null;
    if(is_null('AccountPassword'))$AccountPassword=null;
    $args=array('AccountType'=>$AccountType,'AccountID'=>$AccountID,'AccountPassword'=>$AccountPassword);
    $filter=array('AccountUDN');
    return self::Call('SystemProperties','AddAccountX',$args,$filter);;
  }
  // ChannelMapSet:string
  public function AddBondedZones(string $ChannelMapSet){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ChannelMapSet'))$ChannelMapSet=null;
    $args=array('ChannelMapSet'=>$ChannelMapSet);
    return self::Call('DeviceProperties','AddBondedZones',$args,null);;
  }
  // HTSatChanMapSet:string
  public function AddHTSatellite(string $HTSatChanMapSet){
    if (!$this->GetOnlineState()) return null;
    if(is_null('HTSatChanMapSet'))$HTSatChanMapSet=null;
    $args=array('HTSatChanMapSet'=>$HTSatChanMapSet);
    return self::Call('DeviceProperties','AddHTSatellite',$args,null);;
  }
  // MemberID:string, BootSeq:ui4
  public function AddMember(string $MemberID,integer $BootSeq){
    if (!$this->GetOnlineState()) return null;
    if(is_null('MemberID'))$MemberID=null;
    if(is_null('BootSeq'))$BootSeq=null;
    $args=array('MemberID'=>$MemberID,'BootSeq'=>$BootSeq);
    $filter=array('CurrentTransportSettings','GroupUUIDJoined','ResetVolumeAfter','VolumeAVTransportURI');
    return self::Call('GroupManagement','AddMember',$args,$filter);;
  }
  // QueueID:ui4, UpdateID:ui4, ContainerURI:string, ContainerMetaData:string, DesiredFirstTrackNumberEnqueued:ui4, EnqueueAsNext:boolean, NumberOfURIs:ui4, EnqueuedURIsAndMetaData:string
  public function AddMultipleURIs(integer $QueueID,integer $UpdateID,string $ContainerURI,string $ContainerMetaData,integer $DesiredFirstTrackNumberEnqueued,boolean $EnqueueAsNext,integer $NumberOfURIs,string $EnqueuedURIsAndMetaData){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('ContainerURI'))$ContainerURI=null;
    if(is_null('ContainerMetaData'))$ContainerMetaData=null;
    if(is_null('DesiredFirstTrackNumberEnqueued'))$DesiredFirstTrackNumberEnqueued=null;
    if(is_null('EnqueueAsNext'))$EnqueueAsNext=null;
    if(is_null('NumberOfURIs'))$NumberOfURIs=null;
    if(is_null('EnqueuedURIsAndMetaData'))$EnqueuedURIsAndMetaData=null;
    $args=array('QueueID'=>$QueueID,'UpdateID'=>$UpdateID,'ContainerURI'=>$ContainerURI,'ContainerMetaData'=>$ContainerMetaData,'DesiredFirstTrackNumberEnqueued'=>$DesiredFirstTrackNumberEnqueued,'EnqueueAsNext'=>$EnqueueAsNext,'NumberOfURIs'=>$NumberOfURIs,'EnqueuedURIsAndMetaData'=>$EnqueuedURIsAndMetaData);
    $filter=array('FirstTrackNumberEnqueued','NumTracksAdded','NewQueueLength','NewUpdateID');
    return self::Call('Queue','AddMultipleURIs',$args,$filter);;
  }
  // UpdateID:ui4, NumberOfURIs:ui4, EnqueuedURIs:string, EnqueuedURIsMetaData:string, ContainerURI:string, ContainerMetaData:string, DesiredFirstTrackNumberEnqueued:ui4, EnqueueAsNext:boolean, Instance:ui4
  public function AddMultipleURIsToQueue(integer $UpdateID,integer $NumberOfURIs,string $EnqueuedURIs,string $EnqueuedURIsMetaData,string $ContainerURI,string $ContainerMetaData,integer $DesiredFirstTrackNumberEnqueued,boolean $EnqueueAsNext,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('NumberOfURIs'))$NumberOfURIs=null;
    if(is_null('EnqueuedURIs'))$EnqueuedURIs=null;
    if(is_null('EnqueuedURIsMetaData'))$EnqueuedURIsMetaData=null;
    if(is_null('ContainerURI'))$ContainerURI=null;
    if(is_null('ContainerMetaData'))$ContainerMetaData=null;
    if(is_null('DesiredFirstTrackNumberEnqueued'))$DesiredFirstTrackNumberEnqueued=null;
    if(is_null('EnqueueAsNext'))$EnqueueAsNext=null;
    $args=array('InstanceID'=>$Instance,'UpdateID'=>$UpdateID,'NumberOfURIs'=>$NumberOfURIs,'EnqueuedURIs'=>$EnqueuedURIs,'EnqueuedURIsMetaData'=>$EnqueuedURIsMetaData,'ContainerURI'=>$ContainerURI,'ContainerMetaData'=>$ContainerMetaData,'DesiredFirstTrackNumberEnqueued'=>$DesiredFirstTrackNumberEnqueued,'EnqueueAsNext'=>$EnqueueAsNext);
    $filter=array('FirstTrackNumberEnqueued','NumTracksAdded','NewQueueLength','NewUpdateID');
    return self::Call('AVTransport','AddMultipleURIsToQueue',$args,$filter);;
  }
  // AccountType:ui4, AccountToken:string, AccountKey:string, OAuthDeviceID:string
  public function AddOAuthAccountX(integer $AccountType,string $AccountToken,string $AccountKey,string $OAuthDeviceID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountToken'))$AccountToken=null;
    if(is_null('AccountKey'))$AccountKey=null;
    if(is_null('OAuthDeviceID'))$OAuthDeviceID=null;
    $args=array('AccountType'=>$AccountType,'AccountToken'=>$AccountToken,'AccountKey'=>$AccountKey,'OAuthDeviceID'=>$OAuthDeviceID);
    $filter=array('AccountUDN');
    return self::Call('SystemProperties','AddOAuthAccountX',$args,$filter);;
  }
  // QueueID:ui4, UpdateID:ui4, EnqueuedURI:string, EnqueuedURIMetaData:string, DesiredFirstTrackNumberEnqueued:ui4, EnqueueAsNext:boolean
  public function AddURI(integer $QueueID,integer $UpdateID,string $EnqueuedURI,string $EnqueuedURIMetaData,integer $DesiredFirstTrackNumberEnqueued,boolean $EnqueueAsNext){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('EnqueuedURI'))$EnqueuedURI=null;
    if(is_null('EnqueuedURIMetaData'))$EnqueuedURIMetaData=null;
    if(is_null('DesiredFirstTrackNumberEnqueued'))$DesiredFirstTrackNumberEnqueued=null;
    if(is_null('EnqueueAsNext'))$EnqueueAsNext=null;
    $args=array('QueueID'=>$QueueID,'UpdateID'=>$UpdateID,'EnqueuedURI'=>$EnqueuedURI,'EnqueuedURIMetaData'=>$EnqueuedURIMetaData,'DesiredFirstTrackNumberEnqueued'=>$DesiredFirstTrackNumberEnqueued,'EnqueueAsNext'=>$EnqueueAsNext);
    $filter=array('FirstTrackNumberEnqueued','NumTracksAdded','NewQueueLength','NewUpdateID');
    return self::Call('Queue','AddURI',$args,$filter);;
  }
  // EnqueuedURI:string, EnqueuedURIMetaData:string, DesiredFirstTrackNumberEnqueued:ui4, EnqueueAsNext:boolean, Instance:ui4
  public function AddURIToQueue(string $EnqueuedURI,string $EnqueuedURIMetaData,integer $DesiredFirstTrackNumberEnqueued,boolean $EnqueueAsNext,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('EnqueuedURI'))$EnqueuedURI=null;
    if(is_null('EnqueuedURIMetaData'))$EnqueuedURIMetaData=null;
    if(is_null('DesiredFirstTrackNumberEnqueued'))$DesiredFirstTrackNumberEnqueued=null;
    if(is_null('EnqueueAsNext'))$EnqueueAsNext=null;
    $args=array('InstanceID'=>$Instance,'EnqueuedURI'=>$EnqueuedURI,'EnqueuedURIMetaData'=>$EnqueuedURIMetaData,'DesiredFirstTrackNumberEnqueued'=>$DesiredFirstTrackNumberEnqueued,'EnqueueAsNext'=>$EnqueueAsNext);
    $filter=array('FirstTrackNumberEnqueued','NumTracksAdded','NewQueueLength');
    return self::Call('AVTransport','AddURIToQueue',$args,$filter);;
  }
  // ObjectID:string, UpdateID:ui4, EnqueuedURI:string, EnqueuedURIMetaData:string, AddAtIndex:ui4, Instance:ui4
  public function AddURIToSavedQueue(string $ObjectID,integer $UpdateID,string $EnqueuedURI,string $EnqueuedURIMetaData,integer $AddAtIndex,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('EnqueuedURI'))$EnqueuedURI=null;
    if(is_null('EnqueuedURIMetaData'))$EnqueuedURIMetaData=null;
    if(is_null('AddAtIndex'))$AddAtIndex=null;
    $args=array('InstanceID'=>$Instance,'ObjectID'=>$ObjectID,'UpdateID'=>$UpdateID,'EnqueuedURI'=>$EnqueuedURI,'EnqueuedURIMetaData'=>$EnqueuedURIMetaData,'AddAtIndex'=>$AddAtIndex);
    $filter=array('NumTracksAdded','NewQueueLength','NewUpdateID');
    return self::Call('AVTransport','AddURIToSavedQueue',$args,$filter);;
  }
  // QueueOwnerID:string
  public function AttachQueue(string $QueueOwnerID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueOwnerID'))$QueueOwnerID=null;
    $args=array('QueueOwnerID'=>$QueueOwnerID);
    $filter=array('QueueID','QueueOwnerContext');
    return self::Call('Queue','AttachQueue',$args,$filter);;
  }

  public function Backup(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('Queue','Backup',null,null);;
  }
  // Instance:ui4
  public function BackupQueue(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','BackupQueue',$args,null);;
  }
  // Instance:ui4
  public function BecomeCoordinatorOfStandaloneGroup(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','BecomeCoordinatorOfStandaloneGroup',$args,null);;
  }
  // CurrentCoordinator:string, CurrentGroupID:string, OtherMembers:string, TransportSettings:string, CurrentURI:string, CurrentURIMetaData:string, SleepTimerState:string, AlarmState:string, StreamRestartState:string, CurrentQueueTrackList:string, Instance:ui4
  public function BecomeGroupCoordinator(string $CurrentCoordinator,string $CurrentGroupID,string $OtherMembers,string $TransportSettings,string $CurrentURI,string $CurrentURIMetaData,string $SleepTimerState,string $AlarmState,string $StreamRestartState,string $CurrentQueueTrackList,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CurrentCoordinator'))$CurrentCoordinator=null;
    if(is_null('CurrentGroupID'))$CurrentGroupID=null;
    if(is_null('OtherMembers'))$OtherMembers=null;
    if(is_null('TransportSettings'))$TransportSettings=null;
    if(is_null('CurrentURI'))$CurrentURI=null;
    if(is_null('CurrentURIMetaData'))$CurrentURIMetaData=null;
    if(is_null('SleepTimerState'))$SleepTimerState=null;
    if(is_null('AlarmState'))$AlarmState=null;
    if(is_null('StreamRestartState'))$StreamRestartState=null;
    if(is_null('CurrentQueueTrackList'))$CurrentQueueTrackList=null;
    $args=array('InstanceID'=>$Instance,'CurrentCoordinator'=>$CurrentCoordinator,'CurrentGroupID'=>$CurrentGroupID,'OtherMembers'=>$OtherMembers,'TransportSettings'=>$TransportSettings,'CurrentURI'=>$CurrentURI,'CurrentURIMetaData'=>$CurrentURIMetaData,'SleepTimerState'=>$SleepTimerState,'AlarmState'=>$AlarmState,'StreamRestartState'=>$StreamRestartState,'CurrentQueueTrackList'=>$CurrentQueueTrackList);
    return self::Call('AVTransport','BecomeGroupCoordinator',$args,null);;
  }
  // CurrentCoordinator:string, CurrentGroupID:string, OtherMembers:string, CurrentURI:string, CurrentURIMetaData:string, SleepTimerState:string, AlarmState:string, StreamRestartState:string, CurrentAVTTrackList:string, CurrentQueueTrackList:string, CurrentSourceState:string, ResumePlayback:boolean, Instance:ui4
  public function BecomeGroupCoordinatorAndSource(string $CurrentCoordinator,string $CurrentGroupID,string $OtherMembers,string $CurrentURI,string $CurrentURIMetaData,string $SleepTimerState,string $AlarmState,string $StreamRestartState,string $CurrentAVTTrackList,string $CurrentQueueTrackList,string $CurrentSourceState,boolean $ResumePlayback,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CurrentCoordinator'))$CurrentCoordinator=null;
    if(is_null('CurrentGroupID'))$CurrentGroupID=null;
    if(is_null('OtherMembers'))$OtherMembers=null;
    if(is_null('CurrentURI'))$CurrentURI=null;
    if(is_null('CurrentURIMetaData'))$CurrentURIMetaData=null;
    if(is_null('SleepTimerState'))$SleepTimerState=null;
    if(is_null('AlarmState'))$AlarmState=null;
    if(is_null('StreamRestartState'))$StreamRestartState=null;
    if(is_null('CurrentAVTTrackList'))$CurrentAVTTrackList=null;
    if(is_null('CurrentQueueTrackList'))$CurrentQueueTrackList=null;
    if(is_null('CurrentSourceState'))$CurrentSourceState=null;
    if(is_null('ResumePlayback'))$ResumePlayback=null;
    $args=array('InstanceID'=>$Instance,'CurrentCoordinator'=>$CurrentCoordinator,'CurrentGroupID'=>$CurrentGroupID,'OtherMembers'=>$OtherMembers,'CurrentURI'=>$CurrentURI,'CurrentURIMetaData'=>$CurrentURIMetaData,'SleepTimerState'=>$SleepTimerState,'AlarmState'=>$AlarmState,'StreamRestartState'=>$StreamRestartState,'CurrentAVTTrackList'=>$CurrentAVTTrackList,'CurrentQueueTrackList'=>$CurrentQueueTrackList,'CurrentSourceState'=>$CurrentSourceState,'ResumePlayback'=>$ResumePlayback);
    return self::Call('AVTransport','BecomeGroupCoordinatorAndSource',$args,null);;
  }
  // UpdateURL:string, Flags:ui4, ExtraOptions:string
  public function BeginSoftwareUpdate(string $UpdateURL,integer $Flags,string $ExtraOptions){
    if (!$this->GetOnlineState()) return null;
    if(is_null('UpdateURL'))$UpdateURL=null;
    if(is_null('Flags'))$Flags=null;
    if(is_null('ExtraOptions'))$ExtraOptions=null;
    $args=array('UpdateURL'=>$UpdateURL,'Flags'=>$Flags,'ExtraOptions'=>$ExtraOptions);
    return self::Call('ZoneGroupTopology','BeginSoftwareUpdate',$args,null);;
  }
  // ObjectID:string, BrowseFlag:string, Filter:string, StartingIndex:ui4, RequestedCount:ui4, SortCriteria:string
  public function BrowseContentDirectory(string $ObjectID,string $BrowseFlag,string $Filter,integer $StartingIndex,integer $RequestedCount,string $SortCriteria){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('BrowseFlag'))$BrowseFlag=null;
    if(is_null('Filter'))$Filter=null;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('RequestedCount'))$RequestedCount=null;
    if(is_null('SortCriteria'))$SortCriteria=null;
    $args=array('ObjectID'=>$ObjectID,'BrowseFlag'=>$BrowseFlag,'Filter'=>$Filter,'StartingIndex'=>$StartingIndex,'RequestedCount'=>$RequestedCount,'SortCriteria'=>$SortCriteria);
    $filter=array('Result','NumberReturned','TotalMatches','UpdateID');
    return self::Call('ContentDirectory','BrowseContentDirectory',$args,$filter);;
  }
  // QueueID:ui4, StartingIndex:ui4, RequestedCount:ui4
  public function BrowseQueue(integer $QueueID,integer $StartingIndex,integer $RequestedCount){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('RequestedCount'))$RequestedCount=null;
    $args=array('QueueID'=>$QueueID,'StartingIndex'=>$StartingIndex,'RequestedCount'=>$RequestedCount);
    $filter=array('Result','NumberReturned','TotalMatches','UpdateID');
    return self::Call('Queue','BrowseQueue',$args,$filter);;
  }
  // CurrentCoordinator:string, NewCoordinator:string, NewTransportSettings:string, Instance:ui4
  public function ChangeCoordinator(string $CurrentCoordinator,string $NewCoordinator,string $NewTransportSettings,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CurrentCoordinator'))$CurrentCoordinator=null;
    if(is_null('NewCoordinator'))$NewCoordinator=null;
    if(is_null('NewTransportSettings'))$NewTransportSettings=null;
    $args=array('InstanceID'=>$Instance,'CurrentCoordinator'=>$CurrentCoordinator,'NewCoordinator'=>$NewCoordinator,'NewTransportSettings'=>$NewTransportSettings);
    return self::Call('AVTransport','ChangeCoordinator',$args,null);;
  }
  // NewTransportSettings:string, CurrentAVTransportURI:string, Instance:ui4
  public function ChangeTransportSettings(string $NewTransportSettings,string $CurrentAVTransportURI,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('NewTransportSettings'))$NewTransportSettings=null;
    if(is_null('CurrentAVTransportURI'))$CurrentAVTransportURI=null;
    $args=array('InstanceID'=>$Instance,'NewTransportSettings'=>$NewTransportSettings,'CurrentAVTransportURI'=>$CurrentAVTransportURI);
    return self::Call('AVTransport','ChangeTransportSettings',$args,null);;
  }
  // UpdateType:string, CachedOnly:boolean, Version:string
  public function CheckForUpdate(string $UpdateType,boolean $CachedOnly,string $Version){
    if (!$this->GetOnlineState()) return null;
    if(is_null('UpdateType'))$UpdateType=null;
    if(is_null('CachedOnly'))$CachedOnly=null;
    if(is_null('Version'))$Version=null;
    $args=array('UpdateType'=>$UpdateType,'CachedOnly'=>$CachedOnly,'Version'=>$Version);
    $filter=array('UpdateItem');
    return self::Call('ZoneGroupTopology','CheckForUpdate',$args,$filter);;
  }
  // NewSleepTimerDuration:string, Instance:ui4
  public function ConfigureSleepTimer(string $NewSleepTimerDuration,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('NewSleepTimerDuration'))$NewSleepTimerDuration=null;
    $args=array('InstanceID'=>$Instance,'NewSleepTimerDuration'=>$NewSleepTimerDuration);
    return self::Call('AVTransport','ConfigureSleepTimer',$args,null);;
  }
  // StartLocalTime:string, Duration:string, Recurrence:string, Enabled:boolean, RoomUUID:string, ProgramURI:string, ProgramMetaData:string, PlayMode:string, Volume:ui2, IncludeLinkedZones:boolean
  public function CreateAlarm(string $StartLocalTime,string $Duration,string $Recurrence,boolean $Enabled,string $RoomUUID,string $ProgramURI,string $ProgramMetaData,string $PlayMode,integer $Volume,boolean $IncludeLinkedZones){
    if (!$this->GetOnlineState()) return null;
    if(is_null('StartLocalTime'))$StartLocalTime=null;
    if(is_null('Duration'))$Duration=null;
    if(is_null('Recurrence'))$Recurrence=null;
    if(is_null('Enabled'))$Enabled=null;
    if(is_null('RoomUUID'))$RoomUUID=null;
    if(is_null('ProgramURI'))$ProgramURI=null;
    if(is_null('ProgramMetaData'))$ProgramMetaData=null;
    if(is_null('PlayMode'))$PlayMode=null;
    if(is_null('Volume'))$Volume=null;
    if(is_null('IncludeLinkedZones'))$IncludeLinkedZones=null;
    $args=array('StartLocalTime'=>$StartLocalTime,'Duration'=>$Duration,'Recurrence'=>$Recurrence,'Enabled'=>$Enabled,'RoomUUID'=>$RoomUUID,'ProgramURI'=>$ProgramURI,'ProgramMetaData'=>$ProgramMetaData,'PlayMode'=>$PlayMode,'Volume'=>$Volume,'IncludeLinkedZones'=>$IncludeLinkedZones);
    $filter=array('AssignedID');
    return self::Call('AlarmClock','CreateAlarm',$args,$filter);;
  }
  // ContainerID:string, Elements:string
  public function CreateObject(string $ContainerID,string $Elements){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ContainerID'))$ContainerID=null;
    if(is_null('Elements'))$Elements=null;
    $args=array('ContainerID'=>$ContainerID,'Elements'=>$Elements);
    $filter=array('ObjectID','Result');
    return self::Call('ContentDirectory','CreateObject',$args,$filter);;
  }
  // QueueOwnerID:string, QueueOwnerContext:string, QueuePolicy:string
  public function CreateQueue(string $QueueOwnerID,string $QueueOwnerContext,string $QueuePolicy){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueOwnerID'))$QueueOwnerID=null;
    if(is_null('QueueOwnerContext'))$QueueOwnerContext=null;
    if(is_null('QueuePolicy'))$QueuePolicy=null;
    $args=array('QueueOwnerID'=>$QueueOwnerID,'QueueOwnerContext'=>$QueueOwnerContext,'QueuePolicy'=>$QueuePolicy);
    $filter=array('QueueID');
    return self::Call('Queue','CreateQueue',$args,$filter);;
  }
  // Title:string, EnqueuedURI:string, EnqueuedURIMetaData:string, Instance:ui4
  public function CreateSavedQueue(string $Title,string $EnqueuedURI,string $EnqueuedURIMetaData,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Title'))$Title=null;
    if(is_null('EnqueuedURI'))$EnqueuedURI=null;
    if(is_null('EnqueuedURIMetaData'))$EnqueuedURIMetaData=null;
    $args=array('InstanceID'=>$Instance,'Title'=>$Title,'EnqueuedURI'=>$EnqueuedURI,'EnqueuedURIMetaData'=>$EnqueuedURIMetaData);
    $filter=array('NumTracksAdded','NewQueueLength','AssignedObjectID','NewUpdateID');
    return self::Call('AVTransport','CreateSavedQueue',$args,$filter);;
  }
  // ChannelMapSet:string
  public function CreateStereoPair(string $ChannelMapSet){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ChannelMapSet'))$ChannelMapSet=null;
    $args=array('ChannelMapSet'=>$ChannelMapSet);
    return self::Call('DeviceProperties','CreateStereoPair',$args,null);;
  }
  // NewCoordinator:string, RejoinGroup:boolean, Instance:ui4
  public function DelegateGroupCoordinationTo(string $NewCoordinator,boolean $RejoinGroup,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('NewCoordinator'))$NewCoordinator=null;
    if(is_null('RejoinGroup'))$RejoinGroup=null;
    $args=array('InstanceID'=>$Instance,'NewCoordinator'=>$NewCoordinator,'RejoinGroup'=>$RejoinGroup);
    return self::Call('AVTransport','DelegateGroupCoordinationTo',$args,null);;
  }
  // ID:ui4
  public function DestroyAlarm(integer $ID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ID'))$ID=null;
    $args=array('ID'=>$ID);
    return self::Call('AlarmClock','DestroyAlarm',$args,null);;
  }
  // ObjectID:string
  public function DestroyObject(string $ObjectID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ObjectID'))$ObjectID=null;
    $args=array('ObjectID'=>$ObjectID);
    return self::Call('ContentDirectory','DestroyObject',$args,null);;
  }

  public function DoPostUpdateTasks(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('SystemProperties','DoPostUpdateTasks',null,null);;
  }
  // AccountType:ui4, AccountID:string, NewAccountMd:string
  public function EditAccountMd(integer $AccountType,string $AccountID,string $NewAccountMd){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountID'))$AccountID=null;
    if(is_null('NewAccountMd'))$NewAccountMd=null;
    $args=array('AccountType'=>$AccountType,'AccountID'=>$AccountID,'NewAccountMd'=>$NewAccountMd);
    return self::Call('SystemProperties','EditAccountMd',$args,null);;
  }
  // AccountType:ui4, AccountID:string, NewAccountPassword:string
  public function EditAccountPasswordX(integer $AccountType,string $AccountID,string $NewAccountPassword){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountID'))$AccountID=null;
    if(is_null('NewAccountPassword'))$NewAccountPassword=null;
    $args=array('AccountType'=>$AccountType,'AccountID'=>$AccountID,'NewAccountPassword'=>$NewAccountPassword);
    return self::Call('SystemProperties','EditAccountPasswordX',$args,null);;
  }
  // RDMValue:boolean
  public function EnableRDM(boolean $RDMValue){
    if (!$this->GetOnlineState()) return null;
    if(is_null('RDMValue'))$RDMValue=null;
    $args=array('RDMValue'=>$RDMValue);
    return self::Call('SystemProperties','EnableRDM',$args,null);;
  }
  // Mode:string, Options:string
  public function EnterConfigMode(string $Mode,string $Options){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Mode'))$Mode=null;
    if(is_null('Options'))$Options=null;
    $args=array('Mode'=>$Mode,'Options'=>$Options);
    $filter=array('State');
    return self::Call('DeviceProperties','EnterConfigMode',$args,$filter);;
  }
  // Options:string
  public function ExitConfigMode(string $Options){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Options'))$Options=null;
    $args=array('Options'=>$Options);
    return self::Call('DeviceProperties','ExitConfigMode',$args,null);;
  }
  // ObjectID:string, Prefix:string
  public function FindPrefix(string $ObjectID,string $Prefix){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('Prefix'))$Prefix=null;
    $args=array('ObjectID'=>$ObjectID,'Prefix'=>$Prefix);
    $filter=array('StartingIndex','UpdateID');
    return self::Call('ContentDirectory','FindPrefix',$args,$filter);;
  }

  public function GetAlbumArtistDisplayOption(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('AlbumArtistDisplayOption');
    return self::Call('ContentDirectory','GetAlbumArtistDisplayOption',null,$filter);;
  }
  // ObjectID:string
  public function GetAllPrefixLocations(string $ObjectID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ObjectID'))$ObjectID=null;
    $args=array('ObjectID'=>$ObjectID);
    $filter=array('TotalPrefixes','PrefixAndIndexCSV','UpdateID');
    return self::Call('ContentDirectory','GetAllPrefixLocations',$args,$filter);;
  }

  public function GetAutoplayLinkedZones(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('IncludeLinkedZones');
    return self::Call('DeviceProperties','GetAutoplayLinkedZones',null,$filter);;
  }

  public function GetAutoplayRoomUUID(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('RoomUUID');
    return self::Call('DeviceProperties','GetAutoplayRoomUUID',null,$filter);;
  }

  public function GetAutoplayVolume(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentVolume');
    return self::Call('DeviceProperties','GetAutoplayVolume',null,$filter);;
  }
  // Instance:ui4
  public function GetBass(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentBass');
    $CurrentBass=self::Call('RenderingControl','GetBass',$args,$filter);;
    $this->SetValueInteger('Bass',$CurrentBass);
    return $CurrentBass;
  }

  public function GetBrowseable(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('IsBrowseable');
    return self::Call('ContentDirectory','GetBrowseable',null,$filter);;
  }

  public function GetButtonState(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('State');
    return self::Call('DeviceProperties','GetButtonState',null,$filter);;
  }
  // Instance:ui4
  public function GetCrossfadeMode(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CrossfadeMode');
    $CrossfadeMode=self::Call('AVTransport','GetCrossfadeMode',$args,$filter);;
    $this->SetValueBoolean('CrossfadeMode',$CrossfadeMode);
    return $CrossfadeMode;
  }

  public function GetCurrentConnectionIDsConnectionManager(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('ConnectionIDs');
    return self::Call('ConnectionManagerMediaRenderer','GetCurrentConnectionIDsConnectionManager',null,$filter);;
  }
  // ConnectionID:i4
  public function GetCurrentConnectionInfoConnectionManager(integer $ConnectionID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ConnectionID'))$ConnectionID=null;
    $args=array('ConnectionID'=>$ConnectionID);
    $filter=array('RcsID','AVTransportID','ProtocolInfo','PeerConnectionManager','PeerConnectionID','Direction','Status');
    return self::Call('ConnectionManagerMediaRenderer','GetCurrentConnectionInfoConnectionManager',$args,$filter);;
  }
  // Instance:ui4
  public function GetCurrentTransportActions(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('Actions');
    return self::Call('AVTransport','GetCurrentTransportActions',$args,$filter);;
  }

  public function GetDailyIndexRefreshTime(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentDailyIndexRefreshTime');
    return self::Call('AlarmClock','GetDailyIndexRefreshTime',null,$filter);;
  }
  // Instance:ui4
  public function GetDeviceCapabilities(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('PlayMedia','RecMedia','RecQualityModes');
    return self::Call('AVTransport','GetDeviceCapabilities',$args,$filter);;
  }
  // EQType:string, Instance:ui4
  public function GetEQ(string $EQType,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('EQType'))$EQType=null;
    $args=array('InstanceID'=>$Instance,'EQType'=>$EQType);
    $filter=array('CurrentValue');
    $CurrentValue=self::Call('RenderingControl','GetEQ',$args,$filter);;
    $this->SetValueInteger('EQ',$CurrentValue);
    return $CurrentValue;
  }

  public function GetFormat(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentTimeFormat','CurrentDateFormat');
    return self::Call('AlarmClock','GetFormat',null,$filter);;
  }
  // Instance:ui4
  public function GetGroupMute(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentMute');
    $CurrentMute=self::Call('GroupRenderingControl','GetGroupMute',$args,$filter);;
    $this->SetValueBoolean('GroupMute',$CurrentMute);
    return $CurrentMute;
  }
  // Instance:ui4
  public function GetGroupVolume(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentVolume');
    $CurrentVolume=self::Call('GroupRenderingControl','GetGroupVolume',$args,$filter);;
    $this->SetValueInteger('GroupVolume',$CurrentVolume);
    return $CurrentVolume;
  }
  // Instance:ui4
  public function GetHeadphoneConnected(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentHeadphoneConnected');
    return self::Call('RenderingControl','GetHeadphoneConnected',$args,$filter);;
  }

  public function GetHouseholdID(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentHouseholdID');
    return self::Call('DeviceProperties','GetHouseholdID',null,$filter);;
  }
  // TimeStamp:string
  public function GetHouseholdTimeAtStamp(string $TimeStamp){
    if (!$this->GetOnlineState()) return null;
    if(is_null('TimeStamp'))$TimeStamp=null;
    $args=array('TimeStamp'=>$TimeStamp);
    $filter=array('HouseholdUTCTime');
    return self::Call('AlarmClock','GetHouseholdTimeAtStamp',$args,$filter);;
  }

  public function GetLEDState(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentLEDState');
    return self::Call('DeviceProperties','GetLEDState',null,$filter);;
  }

  public function GetLastIndexChange(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('LastIndexChange');
    return self::Call('ContentDirectory','GetLastIndexChange',null,$filter);;
  }
  // Instance:ui4, Channel:string
  public function GetLoudness(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentLoudness');
    $CurrentLoudness=self::Call('RenderingControl','GetLoudness',$args,$filter);;
    $this->SetValueBoolean('Loudness',$CurrentLoudness);
    return $CurrentLoudness;
  }
  // Instance:ui4
  public function GetMediaInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('NrTracks','MediaDuration','CurrentURI','CurrentURIMetaData','NextURI','NextURIMetaData','PlayMedium','RecordMedium','WriteStatus');
    return self::Call('AVTransport','GetMediaInfo',$args,$filter);;
  }
  // Instance:ui4, Channel:string
  public function GetMute(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentMute');
    $CurrentMute=self::Call('RenderingControl','GetMute',$args,$filter);;
    $this->SetValueBoolean('Mute',$CurrentMute);
    return $CurrentMute;
  }
  // Instance:ui4
  public function GetOutputFixed(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentFixed');
    $CurrentFixed=self::Call('RenderingControl','GetOutputFixed',$args,$filter);;
    $this->SetValueBoolean('OutputFixed',$CurrentFixed);
    return $CurrentFixed;
  }
  // Instance:ui4
  public function GetPositionInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('Track','TrackDuration','TrackMetaData','TrackURI','RelTime','AbsTime','RelCount','AbsCount');
    return self::Call('AVTransport','GetPositionInfo',$args,$filter);;
  }

  public function GetProtocolInfoConnectionManager(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Source','Sink');
    return self::Call('ConnectionManagerMediaRenderer','GetProtocolInfoConnectionManager',null,$filter);;
  }

  public function GetRDM(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('RDMValue');
    return self::Call('SystemProperties','GetRDM',null,$filter);;
  }
  // Instance:ui4
  public function GetRemainingSleepTimerDuration(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('RemainingSleepTimerDuration','CurrentSleepTimerGeneration');
    return self::Call('AVTransport','GetRemainingSleepTimerDuration',$args,$filter);;
  }
  // InstanceID:ui4
  protected function GetRepeat($InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    return $this->_boRepeat;
  }
  // Instance:ui4
  public function GetRunningAlarmProperties(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('AlarmID','GroupID','LoggedStartTime');
    return self::Call('AVTransport','GetRunningAlarmProperties',$args,$filter);;
  }

  public function GetSearchCapabilities(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('SearchCaps');
    return self::Call('ContentDirectory','GetSearchCapabilities',null,$filter);;
  }
  // ServiceId:ui4, Username:string
  public function GetSessionId(integer $ServiceId,string $Username){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ServiceId'))$ServiceId=null;
    if(is_null('Username'))$Username=null;
    $args=array('ServiceId'=>$ServiceId,'Username'=>$Username);
    $filter=array('SessionId');
    return self::Call('MusicServices','GetSessionId',$args,$filter);;
  }

  public function GetShareIndexInProgress(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('IsIndexing');
    return self::Call('ContentDirectory','GetShareIndexInProgress',null,$filter);;
  }
  // InstanceID:ui4
  protected function GetShuffle($InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    return $this->_boShuffle;
  }
  // Instance:ui4
  public function GetSonarStatus(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('SonarEnabled','SonarCalibrationAvailable');
    return self::Call('RenderingControl','GetSonarStatus',$args,$filter);;
  }

  public function GetSortCapabilities(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('SortCaps');
    return self::Call('ContentDirectory','GetSortCapabilities',null,$filter);;
  }
  // Instance:ui4
  public function GetState($Instance=0){
    $states=array('STOPPED'=>RPC2SONOS_STATE_STOP,'PLAYING'=>RPC2SONOS_STATE_PLAY,'PAUSED_PLAYBACK'=>RPC2SONOS_STATE_PAUSE,'TRANSITIONING'=>RPC2SONOS_STATE_TRANS);
    $v=self::GetTransportInfo($Instance);
    return ($v&&($s=$v['CurrentTransportState'])&&isset($a[$s]))?$a[$s]:RPC2SONOS_STATE_ERROR;
  }
  // VariableName:string
  public function GetString(string $VariableName){
    if (!$this->GetOnlineState()) return null;
    if(is_null('VariableName'))$VariableName=null;
    $args=array('VariableName'=>$VariableName);
    $filter=array('StringValue');
    return self::Call('SystemProperties','GetString',$args,$filter);;
  }
  // Instance:ui4
  public function GetSupportsOutputFixed(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentSupportsFixed');
    return self::Call('RenderingControl','GetSupportsOutputFixed',$args,$filter);;
  }

  public function GetSystemUpdateID(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Id');
    return self::Call('ContentDirectory','GetSystemUpdateID',null,$filter);;
  }

  public function GetTimeNow(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentUTCTime','CurrentLocalTime','CurrentTimeZone','CurrentTimeGeneration');
    return self::Call('AlarmClock','GetTimeNow',null,$filter);;
  }

  public function GetTimeServer(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentTimeServer');
    return self::Call('AlarmClock','GetTimeServer',null,$filter);;
  }

  public function GetTimeZone(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Index','AutoAdjustDst');
    return self::Call('AlarmClock','GetTimeZone',null,$filter);;
  }

  public function GetTimeZoneAndRule(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('Index','AutoAdjustDst','CurrentTimeZone');
    return self::Call('AlarmClock','GetTimeZoneAndRule',null,$filter);;
  }
  // Index:i4
  public function GetTimeZoneRule(integer $Index){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Index'))$Index=null;
    $args=array('Index'=>$Index);
    $filter=array('TimeZone');
    return self::Call('AlarmClock','GetTimeZoneRule',$args,$filter);;
  }
  // Instance:ui4
  public function GetTransportInfo(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentTransportState','CurrentTransportStatus','CurrentSpeed');
    return self::Call('AVTransport','GetTransportInfo',$args,$filter);;
  }
  // Instance:ui4
  public function GetTransportSettings(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('PlayMode','RecQualityMode');
    return self::Call('AVTransport','GetTransportSettings',$args,$filter);;
  }
  // Instance:ui4
  public function GetTreble(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('CurrentTreble');
    $CurrentTreble=self::Call('RenderingControl','GetTreble',$args,$filter);;
    $this->SetValueInteger('Treble',$CurrentTreble);
    return $CurrentTreble;
  }

  public function GetUseAutoplayVolume(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('UseVolume');
    return self::Call('DeviceProperties','GetUseAutoplayVolume',null,$filter);;
  }
  // Instance:ui4, Channel:string
  public function GetVolume(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentVolume');
    $CurrentVolume=self::Call('RenderingControl','GetVolume',$args,$filter);;
    $this->SetValueInteger('Volume',$CurrentVolume);
    return $CurrentVolume;
  }
  // Instance:ui4, Channel:string
  public function GetVolumeDB(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('CurrentVolume');
    $CurrentVolume=self::Call('RenderingControl','GetVolumeDB',$args,$filter);;
    $this->SetValueInteger('VolumeDB',$CurrentVolume);
    return $CurrentVolume;
  }
  // Instance:ui4, Channel:string
  public function GetVolumeDBRange(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    $filter=array('MinValue','MaxValue');
    return self::Call('RenderingControl','GetVolumeDBRange',$args,$filter);;
  }
  // AccountType:ui4
  public function GetWebCode(integer $AccountType){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    $args=array('AccountType'=>$AccountType);
    $filter=array('WebCode');
    return self::Call('SystemProperties','GetWebCode',$args,$filter);;
  }

  public function GetZoneAttributes(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentZoneName','CurrentIcon','CurrentConfiguration');
    return self::Call('DeviceProperties','GetZoneAttributes',null,$filter);;
  }

  public function GetZoneGroupAttributes(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentZoneGroupName','CurrentZoneGroupID','CurrentZonePlayerUUIDsInGroup');
    return self::Call('ZoneGroupTopology','GetZoneGroupAttributes',null,$filter);;
  }

  public function GetZoneGroupState(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('ZoneGroupState');
    return self::Call('ZoneGroupTopology','GetZoneGroupState',null,$filter);;
  }

  public function GetZoneInfo(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('SerialNumber','SoftwareVersion','DisplaySoftwareVersion','HardwareVersion','IPAddress','MACAddress','CopyrightInfo','ExtraInfo','HTAudioIn');
    return self::Call('DeviceProperties','GetZoneInfo',null,$filter);;
  }
  // SettingID:ui4, SettingURI:string
  public function ImportSetting(integer $SettingID,string $SettingURI){
    if (!$this->GetOnlineState()) return null;
    if(is_null('SettingID'))$SettingID=null;
    if(is_null('SettingURI'))$SettingURI=null;
    $args=array('SettingID'=>$SettingID,'SettingURI'=>$SettingURI);
    return self::Call('DeviceProperties','ImportSetting',$args,null);;
  }

  public function ListAlarms(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('CurrentAlarmList','CurrentAlarmListVersion');
    return self::Call('AlarmClock','ListAlarms',null,$filter);;
  }

  public function ListAvailableServices(){
    if (!$this->GetOnlineState()) return null;
    $filter=array('AvailableServiceDescriptorList','AvailableServiceTypeList','AvailableServiceListVersion');
    return self::Call('MusicServices','ListAvailableServices',null,$filter);;
  }
  // TargetAccountType:ui4, TargetAccountID:string, TargetAccountPassword:string
  public function MigrateTrialAccountX(integer $TargetAccountType,string $TargetAccountID,string $TargetAccountPassword){
    if (!$this->GetOnlineState()) return null;
    if(is_null('TargetAccountType'))$TargetAccountType=null;
    if(is_null('TargetAccountID'))$TargetAccountID=null;
    if(is_null('TargetAccountPassword'))$TargetAccountPassword=null;
    $args=array('TargetAccountType'=>$TargetAccountType,'TargetAccountID'=>$TargetAccountID,'TargetAccountPassword'=>$TargetAccountPassword);
    return self::Call('SystemProperties','MigrateTrialAccountX',$args,null);;
  }
  // Instance:ui4
  public function Next(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Next',$args,null);;
  }
  // Instance:ui4
  public function NextProgrammedRadioTracks(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','NextProgrammedRadioTracks',$args,null);;
  }
  // DeletedURI:string, Instance:ui4
  public function NotifyDeletedURI(string $DeletedURI,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DeletedURI'))$DeletedURI=null;
    $args=array('InstanceID'=>$Instance,'DeletedURI'=>$DeletedURI);
    return self::Call('AVTransport','NotifyDeletedURI',$args,null);;
  }
  // Instance:ui4
  public function Pause(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Pause',$args,null);;
  }
  // Instance:ui4, Speed:ui4
  public function Play(integer $Instance,integer $Speed){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Speed'=>$Speed);
    return self::Call('AVTransport','Play',$args,null);;
  }
  // Instance:ui4
  public function Previous(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Previous',$args,null);;
  }
  // AccountType:ui4, AccountID:string, AccountPassword:string
  public function ProvisionCredentialedTrialAccountX(integer $AccountType,string $AccountID,string $AccountPassword){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountID'))$AccountID=null;
    if(is_null('AccountPassword'))$AccountPassword=null;
    $args=array('AccountType'=>$AccountType,'AccountID'=>$AccountID,'AccountPassword'=>$AccountPassword);
    $filter=array('IsExpired','AccountUDN');
    return self::Call('SystemProperties','ProvisionCredentialedTrialAccountX',$args,$filter);;
  }
  // AccountType:ui4
  public function ProvisionTrialAccount(integer $AccountType){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    $args=array('AccountType'=>$AccountType);
    $filter=array('AccountUDN');
    return self::Call('SystemProperties','ProvisionTrialAccount',$args,$filter);;
  }
  // Seed:string
  public function QPlayAuth(string $Seed){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Seed'))$Seed=null;
    $args=array('Seed'=>$Seed);
    $filter=array('Code','MID','DID');
    return self::Call('QPlay','QPlayAuth',$args,$filter);;
  }
  // RampType:string, DesiredVolume:ui2, ResetVolumeAfter:boolean, ProgramURI:string, Instance:ui4, Channel:string
  public function RampToVolume(string $RampType,integer $DesiredVolume,boolean $ResetVolumeAfter,string $ProgramURI,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('RampType'))$RampType=null;
    if(is_null('DesiredVolume'))$DesiredVolume=null;
    if(is_null('ResetVolumeAfter'))$ResetVolumeAfter=null;
    if(is_null('ProgramURI'))$ProgramURI=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'RampType'=>$RampType,'DesiredVolume'=>$DesiredVolume,'ResetVolumeAfter'=>$ResetVolumeAfter,'ProgramURI'=>$ProgramURI);
    $filter=array('RampTime');
    return self::Call('RenderingControl','RampToVolume',$args,$filter);;
  }
  // AccountType:ui4, AccountUID:ui4, AccountToken:string, AccountKey:string
  public function RefreshAccountCredentialsX(integer $AccountType,integer $AccountUID,string $AccountToken,string $AccountKey){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountUID'))$AccountUID=null;
    if(is_null('AccountToken'))$AccountToken=null;
    if(is_null('AccountKey'))$AccountKey=null;
    $args=array('AccountType'=>$AccountType,'AccountUID'=>$AccountUID,'AccountToken'=>$AccountToken,'AccountKey'=>$AccountKey);
    return self::Call('SystemProperties','RefreshAccountCredentialsX',$args,null);;
  }
  // AlbumArtistDisplayOption:string
  public function RefreshShareIndex(string $AlbumArtistDisplayOption){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AlbumArtistDisplayOption'))$AlbumArtistDisplayOption=null;
    $args=array('AlbumArtistDisplayOption'=>$AlbumArtistDisplayOption);
    return self::Call('ContentDirectory','RefreshShareIndex',$args,null);;
  }
  // MobileDeviceName:string, MobileDeviceUDN:string, MobileIPAndPort:string
  public function RegisterMobileDevice(string $MobileDeviceName,string $MobileDeviceUDN,string $MobileIPAndPort){
    if (!$this->GetOnlineState()) return null;
    if(is_null('MobileDeviceName'))$MobileDeviceName=null;
    if(is_null('MobileDeviceUDN'))$MobileDeviceUDN=null;
    if(is_null('MobileIPAndPort'))$MobileIPAndPort=null;
    $args=array('MobileDeviceName'=>$MobileDeviceName,'MobileDeviceUDN'=>$MobileDeviceUDN,'MobileIPAndPort'=>$MobileIPAndPort);
    return self::Call('ZoneGroupTopology','RegisterMobileDevice',$args,null);;
  }
  // VariableName:string
  public function Remove(string $VariableName){
    if (!$this->GetOnlineState()) return null;
    if(is_null('VariableName'))$VariableName=null;
    $args=array('VariableName'=>$VariableName);
    return self::Call('SystemProperties','Remove',$args,null);;
  }
  // AccountType:ui4, AccountID:string
  public function RemoveAccount(integer $AccountType,string $AccountID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountType'))$AccountType=null;
    if(is_null('AccountID'))$AccountID=null;
    $args=array('AccountType'=>$AccountType,'AccountID'=>$AccountID);
    return self::Call('SystemProperties','RemoveAccount',$args,null);;
  }
  // QueueID:ui4, UpdateID:ui4
  public function RemoveAllTracks(integer $QueueID,integer $UpdateID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    $args=array('QueueID'=>$QueueID,'UpdateID'=>$UpdateID);
    $filter=array('NewUpdateID');
    return self::Call('Queue','RemoveAllTracks',$args,$filter);;
  }
  // Instance:ui4
  public function RemoveAllTracksFromQueue(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','RemoveAllTracksFromQueue',$args,null);;
  }
  // ChannelMapSet:string
  public function RemoveBondedZones(string $ChannelMapSet){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ChannelMapSet'))$ChannelMapSet=null;
    $args=array('ChannelMapSet'=>$ChannelMapSet);
    return self::Call('DeviceProperties','RemoveBondedZones',$args,null);;
  }
  // SatRoomUUID:string
  public function RemoveHTSatellite(string $SatRoomUUID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('SatRoomUUID'))$SatRoomUUID=null;
    $args=array('SatRoomUUID'=>$SatRoomUUID);
    return self::Call('DeviceProperties','RemoveHTSatellite',$args,null);;
  }
  // MemberID:string
  public function RemoveMember(string $MemberID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('MemberID'))$MemberID=null;
    $args=array('MemberID'=>$MemberID);
    return self::Call('GroupManagement','RemoveMember',$args,null);;
  }
  // ObjectID:string, UpdateID:ui4, Instance:ui4
  public function RemoveTrackFromQueue(string $ObjectID,integer $UpdateID,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    $args=array('InstanceID'=>$Instance,'ObjectID'=>$ObjectID,'UpdateID'=>$UpdateID);
    return self::Call('AVTransport','RemoveTrackFromQueue',$args,null);;
  }
  // QueueID:ui4, UpdateID:ui4, StartingIndex:ui4, NumberOfTracks:ui4
  public function RemoveTrackRange(integer $QueueID,integer $UpdateID,integer $StartingIndex,integer $NumberOfTracks){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('NumberOfTracks'))$NumberOfTracks=null;
    $args=array('QueueID'=>$QueueID,'UpdateID'=>$UpdateID,'StartingIndex'=>$StartingIndex,'NumberOfTracks'=>$NumberOfTracks);
    $filter=array('NewUpdateID');
    return self::Call('Queue','RemoveTrackRange',$args,$filter);;
  }
  // UpdateID:ui4, StartingIndex:ui4, NumberOfTracks:ui4, Instance:ui4
  public function RemoveTrackRangeFromQueue(integer $UpdateID,integer $StartingIndex,integer $NumberOfTracks,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('NumberOfTracks'))$NumberOfTracks=null;
    $args=array('InstanceID'=>$Instance,'UpdateID'=>$UpdateID,'StartingIndex'=>$StartingIndex,'NumberOfTracks'=>$NumberOfTracks);
    $filter=array('NewUpdateID');
    return self::Call('AVTransport','RemoveTrackRangeFromQueue',$args,$filter);;
  }
  // QueueID:ui4, StartingIndex:ui4, NumberOfTracks:ui4, InsertBefore:ui4, UpdateID:ui4
  public function ReorderTracks(integer $QueueID,integer $StartingIndex,integer $NumberOfTracks,integer $InsertBefore,integer $UpdateID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('NumberOfTracks'))$NumberOfTracks=null;
    if(is_null('InsertBefore'))$InsertBefore=null;
    if(is_null('UpdateID'))$UpdateID=null;
    $args=array('QueueID'=>$QueueID,'StartingIndex'=>$StartingIndex,'NumberOfTracks'=>$NumberOfTracks,'InsertBefore'=>$InsertBefore,'UpdateID'=>$UpdateID);
    $filter=array('NewUpdateID');
    return self::Call('Queue','ReorderTracks',$args,$filter);;
  }
  // StartingIndex:ui4, NumberOfTracks:ui4, InsertBefore:ui4, UpdateID:ui4, Instance:ui4
  public function ReorderTracksInQueue(integer $StartingIndex,integer $NumberOfTracks,integer $InsertBefore,integer $UpdateID,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('StartingIndex'))$StartingIndex=null;
    if(is_null('NumberOfTracks'))$NumberOfTracks=null;
    if(is_null('InsertBefore'))$InsertBefore=null;
    if(is_null('UpdateID'))$UpdateID=null;
    $args=array('InstanceID'=>$Instance,'StartingIndex'=>$StartingIndex,'NumberOfTracks'=>$NumberOfTracks,'InsertBefore'=>$InsertBefore,'UpdateID'=>$UpdateID);
    return self::Call('AVTransport','ReorderTracksInQueue',$args,null);;
  }
  // ObjectID:string, UpdateID:ui4, TrackList:string, NewPositionList:string, Instance:ui4
  public function ReorderTracksInSavedQueue(string $ObjectID,integer $UpdateID,string $TrackList,string $NewPositionList,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('TrackList'))$TrackList=null;
    if(is_null('NewPositionList'))$NewPositionList=null;
    $args=array('InstanceID'=>$Instance,'ObjectID'=>$ObjectID,'UpdateID'=>$UpdateID,'TrackList'=>$TrackList,'NewPositionList'=>$NewPositionList);
    $filter=array('QueueLengthChange','NewQueueLength','NewUpdateID');
    return self::Call('AVTransport','ReorderTracksInSavedQueue',$args,$filter);;
  }
  // AccountUDN:string, NewAccountID:string, NewAccountPassword:string, AccountToken:string, AccountKey:string, OAuthDeviceID:string
  public function ReplaceAccountX(string $AccountUDN,string $NewAccountID,string $NewAccountPassword,string $AccountToken,string $AccountKey,string $OAuthDeviceID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountUDN'))$AccountUDN=null;
    if(is_null('NewAccountID'))$NewAccountID=null;
    if(is_null('NewAccountPassword'))$NewAccountPassword=null;
    if(is_null('AccountToken'))$AccountToken=null;
    if(is_null('AccountKey'))$AccountKey=null;
    if(is_null('OAuthDeviceID'))$OAuthDeviceID=null;
    $args=array('AccountUDN'=>$AccountUDN,'NewAccountID'=>$NewAccountID,'NewAccountPassword'=>$NewAccountPassword,'AccountToken'=>$AccountToken,'AccountKey'=>$AccountKey,'OAuthDeviceID'=>$OAuthDeviceID);
    $filter=array('NewAccountUDN');
    return self::Call('SystemProperties','ReplaceAccountX',$args,$filter);;
  }
  // QueueID:ui4, UpdateID:ui4, ContainerURI:string, ContainerMetaData:string, CurrentTrackIndex:ui4, NewCurrentTrackIndices:string, NumberOfURIs:ui4, EnqueuedURIsAndMetaData:string
  public function ReplaceAllTracks(integer $QueueID,integer $UpdateID,string $ContainerURI,string $ContainerMetaData,integer $CurrentTrackIndex,string $NewCurrentTrackIndices,integer $NumberOfURIs,string $EnqueuedURIsAndMetaData){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('UpdateID'))$UpdateID=null;
    if(is_null('ContainerURI'))$ContainerURI=null;
    if(is_null('ContainerMetaData'))$ContainerMetaData=null;
    if(is_null('CurrentTrackIndex'))$CurrentTrackIndex=null;
    if(is_null('NewCurrentTrackIndices'))$NewCurrentTrackIndices=null;
    if(is_null('NumberOfURIs'))$NumberOfURIs=null;
    if(is_null('EnqueuedURIsAndMetaData'))$EnqueuedURIsAndMetaData=null;
    $args=array('QueueID'=>$QueueID,'UpdateID'=>$UpdateID,'ContainerURI'=>$ContainerURI,'ContainerMetaData'=>$ContainerMetaData,'CurrentTrackIndex'=>$CurrentTrackIndex,'NewCurrentTrackIndices'=>$NewCurrentTrackIndices,'NumberOfURIs'=>$NumberOfURIs,'EnqueuedURIsAndMetaData'=>$EnqueuedURIsAndMetaData);
    $filter=array('NewQueueLength','NewUpdateID');
    return self::Call('Queue','ReplaceAllTracks',$args,$filter);;
  }

  public function ReportAlarmStartedRunning(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('ZoneGroupTopology','ReportAlarmStartedRunning',null,null);;
  }
  // MemberID:string, ResultCode:i4
  public function ReportTrackBufferingResult(string $MemberID,integer $ResultCode){
    if (!$this->GetOnlineState()) return null;
    if(is_null('MemberID'))$MemberID=null;
    if(is_null('ResultCode'))$ResultCode=null;
    $args=array('MemberID'=>$MemberID,'ResultCode'=>$ResultCode);
    return self::Call('GroupManagement','ReportTrackBufferingResult',$args,null);;
  }
  // DeviceUUID:string, DesiredAction:string
  public function ReportUnresponsiveDevice(string $DeviceUUID,string $DesiredAction){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DeviceUUID'))$DeviceUUID=null;
    if(is_null('DesiredAction'))$DesiredAction=null;
    $args=array('DeviceUUID'=>$DeviceUUID,'DesiredAction'=>$DesiredAction);
    return self::Call('ZoneGroupTopology','ReportUnresponsiveDevice',$args,null);;
  }
  // SortOrder:string
  public function RequestResort(string $SortOrder){
    if (!$this->GetOnlineState()) return null;
    if(is_null('SortOrder'))$SortOrder=null;
    $args=array('SortOrder'=>$SortOrder);
    return self::Call('ContentDirectory','RequestResort',$args,null);;
  }
  // Instance:ui4
  public function ResetBasicEQ(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    $filter=array('Bass','Treble','Loudness','LeftVolume','RightVolume');
    return self::Call('RenderingControl','ResetBasicEQ',$args,$filter);;
  }
  // EQType:string, Instance:ui4
  public function ResetExtEQ(string $EQType,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('EQType'))$EQType=null;
    $args=array('InstanceID'=>$Instance,'EQType'=>$EQType);
    return self::Call('RenderingControl','ResetExtEQ',$args,null);;
  }

  public function ResetThirdPartyCredentials(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('SystemProperties','ResetThirdPartyCredentials',null,null);;
  }
  // Instance:ui4, Channel:string
  public function RestoreVolumePriorToRamp(integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel);
    return self::Call('RenderingControl','RestoreVolumePriorToRamp',$args,null);;
  }
  // AlarmID:ui4, LoggedStartTime:string, Duration:string, ProgramURI:string, ProgramMetaData:string, PlayMode:string, Volume:ui2, IncludeLinkedZones:boolean, Instance:ui4
  public function RunAlarm(integer $AlarmID,string $LoggedStartTime,string $Duration,string $ProgramURI,string $ProgramMetaData,string $PlayMode,integer $Volume,boolean $IncludeLinkedZones,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('AlarmID'))$AlarmID=null;
    if(is_null('LoggedStartTime'))$LoggedStartTime=null;
    if(is_null('Duration'))$Duration=null;
    if(is_null('ProgramURI'))$ProgramURI=null;
    if(is_null('ProgramMetaData'))$ProgramMetaData=null;
    if(is_null('PlayMode'))$PlayMode=null;
    if(is_null('Volume'))$Volume=null;
    if(is_null('IncludeLinkedZones'))$IncludeLinkedZones=null;
    $args=array('InstanceID'=>$Instance,'AlarmID'=>$AlarmID,'LoggedStartTime'=>$LoggedStartTime,'Duration'=>$Duration,'ProgramURI'=>$ProgramURI,'ProgramMetaData'=>$ProgramMetaData,'PlayMode'=>$PlayMode,'Volume'=>$Volume,'IncludeLinkedZones'=>$IncludeLinkedZones);
    return self::Call('AVTransport','RunAlarm',$args,null);;
  }
  // QueueID:ui4, Title:string, ObjectID:string
  public function SaveAsSonosPlaylist(integer $QueueID,string $Title,string $ObjectID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('QueueID'))$QueueID=null;
    if(is_null('Title'))$Title=null;
    if(is_null('ObjectID'))$ObjectID=null;
    $args=array('QueueID'=>$QueueID,'Title'=>$Title,'ObjectID'=>$ObjectID);
    $filter=array('AssignedObjectID');
    return self::Call('Queue','SaveAsSonosPlaylist',$args,$filter);;
  }
  // Title:string, ObjectID:string, Instance:ui4
  public function SaveQueue(string $Title,string $ObjectID,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Title'))$Title=null;
    if(is_null('ObjectID'))$ObjectID=null;
    $args=array('InstanceID'=>$Instance,'Title'=>$Title,'ObjectID'=>$ObjectID);
    $filter=array('AssignedObjectID');
    return self::Call('AVTransport','SaveQueue',$args,$filter);;
  }
  // Target:string, Instance:ui4, Unit:string
  public function Seek(string $Target,integer $Instance,string $Unit){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Target'))$Target=null;
    $args=array('InstanceID'=>$Instance,'Unit'=>$Unit,'Target'=>$Target);
    return self::Call('AVTransport','Seek',$args,null);;
  }
  // ChannelMapSet:string
  public function SeparateStereoPair(string $ChannelMapSet){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ChannelMapSet'))$ChannelMapSet=null;
    $args=array('ChannelMapSet'=>$ChannelMapSet);
    return self::Call('DeviceProperties','SeparateStereoPair',$args,null);;
  }
  // CurrentURI:string, CurrentURIMetaData:string, Instance:ui4
  public function SetAVTransportURI(string $CurrentURI,string $CurrentURIMetaData,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CurrentURI'))$CurrentURI=null;
    if(is_null('CurrentURIMetaData'))$CurrentURIMetaData=null;
    $args=array('InstanceID'=>$Instance,'CurrentURI'=>$CurrentURI,'CurrentURIMetaData'=>$CurrentURIMetaData);
    return self::Call('AVTransport','SetAVTransportURI',$args,null);;
  }
  // AccountUDN:string, AccountNickname:string
  public function SetAccountNicknameX(string $AccountUDN,string $AccountNickname){
    if (!$this->GetOnlineState()) return null;
    if(is_null('AccountUDN'))$AccountUDN=null;
    if(is_null('AccountNickname'))$AccountNickname=null;
    $args=array('AccountUDN'=>$AccountUDN,'AccountNickname'=>$AccountNickname);
    return self::Call('SystemProperties','SetAccountNicknameX',$args,null);;
  }
  // IncludeLinkedZones:boolean
  public function SetAutoplayLinkedZones(boolean $IncludeLinkedZones){
    if (!$this->GetOnlineState()) return null;
    if(is_null('IncludeLinkedZones'))$IncludeLinkedZones=null;
    $args=array('IncludeLinkedZones'=>$IncludeLinkedZones);
    return self::Call('DeviceProperties','SetAutoplayLinkedZones',$args,null);;
  }
  // RoomUUID:string
  public function SetAutoplayRoomUUID(string $RoomUUID){
    if (!$this->GetOnlineState()) return null;
    if(is_null('RoomUUID'))$RoomUUID=null;
    $args=array('RoomUUID'=>$RoomUUID);
    return self::Call('DeviceProperties','SetAutoplayRoomUUID',$args,null);;
  }
  // Volume:ui2
  public function SetAutoplayVolume(integer $Volume){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Volume'))$Volume=null;
    $args=array('Volume'=>$Volume);
    return self::Call('DeviceProperties','SetAutoplayVolume',$args,null);;
  }
  // DesiredBass:i2, Instance:ui4
  public function SetBass(integer $DesiredBass,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredBass'))$DesiredBass=null;
    $args=array('InstanceID'=>$Instance,'DesiredBass'=>$DesiredBass);
    $this->SetValueInteger('Bass',$DesiredBass);
    return self::Call('RenderingControl','SetBass',$args,null);;
  }
  // Browseable:boolean
  public function SetBrowseable(boolean $Browseable){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Browseable'))$Browseable=null;
    $args=array('Browseable'=>$Browseable);
    return self::Call('ContentDirectory','SetBrowseable',$args,null);;
  }
  // ChannelMap:string, Instance:ui4
  public function SetChannelMap(string $ChannelMap,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('ChannelMap'))$ChannelMap=null;
    $args=array('InstanceID'=>$Instance,'ChannelMap'=>$ChannelMap);
    return self::Call('RenderingControl','SetChannelMap',$args,null);;
  }
  // CrossfadeMode:boolean, Instance:ui4
  public function SetCrossfadeMode(boolean $CrossfadeMode,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CrossfadeMode'))$CrossfadeMode=null;
    $args=array('InstanceID'=>$Instance,'CrossfadeMode'=>$CrossfadeMode);
    $this->SetValueBoolean('CrossfadeMode',$CrossfadeMode);
    return self::Call('AVTransport','SetCrossfadeMode',$args,null);;
  }
  // DesiredDailyIndexRefreshTime:string
  public function SetDailyIndexRefreshTime(string $DesiredDailyIndexRefreshTime){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredDailyIndexRefreshTime'))$DesiredDailyIndexRefreshTime=null;
    $args=array('DesiredDailyIndexRefreshTime'=>$DesiredDailyIndexRefreshTime);
    return self::Call('AlarmClock','SetDailyIndexRefreshTime',$args,null);;
  }
  // EQType:string, DesiredValue:i2, Instance:ui4
  public function SetEQ(string $EQType,integer $DesiredValue,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('EQType'))$EQType=null;
    if(is_null('DesiredValue'))$DesiredValue=null;
    $args=array('InstanceID'=>$Instance,'EQType'=>$EQType,'DesiredValue'=>$DesiredValue);
    $this->SetValueString('EQ',$EQType);
    return self::Call('RenderingControl','SetEQ',$args,null);;
  }
  // DesiredTimeFormat:string, DesiredDateFormat:string
  public function SetFormat(string $DesiredTimeFormat,string $DesiredDateFormat){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredTimeFormat'))$DesiredTimeFormat=null;
    if(is_null('DesiredDateFormat'))$DesiredDateFormat=null;
    $args=array('DesiredTimeFormat'=>$DesiredTimeFormat,'DesiredDateFormat'=>$DesiredDateFormat);
    return self::Call('AlarmClock','SetFormat',$args,null);;
  }
  // DesiredMute:boolean, Instance:ui4
  public function SetGroupMute(boolean $DesiredMute,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredMute'))$DesiredMute=null;
    $args=array('InstanceID'=>$Instance,'DesiredMute'=>$DesiredMute);
    $this->SetValueBoolean('GroupMute',$DesiredMute);
    return self::Call('GroupRenderingControl','SetGroupMute',$args,null);;
  }
  // DesiredVolume:ui2, Instance:ui4
  public function SetGroupVolume(integer $DesiredVolume,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredVolume'))$DesiredVolume=null;
    $args=array('InstanceID'=>$Instance,'DesiredVolume'=>$DesiredVolume);
    $this->SetValueInteger('GroupVolume',$DesiredVolume);
    return self::Call('GroupRenderingControl','SetGroupVolume',$args,null);;
  }
  // DesiredLEDState:string
  public function SetLEDState(string $DesiredLEDState){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredLEDState'))$DesiredLEDState=null;
    $args=array('DesiredLEDState'=>$DesiredLEDState);
    return self::Call('DeviceProperties','SetLEDState',$args,null);;
  }
  // DesiredLoudness:boolean, Instance:ui4, Channel:string
  public function SetLoudness(boolean $DesiredLoudness,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredLoudness'))$DesiredLoudness=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredLoudness'=>$DesiredLoudness);
    $this->SetValueBoolean('Loudness',$DesiredLoudness);
    return self::Call('RenderingControl','SetLoudness',$args,null);;
  }
  // DesiredMute:boolean, Instance:ui4, Channel:string
  public function SetMute(boolean $DesiredMute,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredMute'))$DesiredMute=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredMute'=>$DesiredMute);
    $this->SetValueBoolean('Mute',$DesiredMute);
    return self::Call('RenderingControl','SetMute',$args,null);;
  }
  // NextURI:string, NextURIMetaData:string, Instance:ui4
  public function SetNextAVTransportURI(string $NextURI,string $NextURIMetaData,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('NextURI'))$NextURI=null;
    if(is_null('NextURIMetaData'))$NextURIMetaData=null;
    $args=array('InstanceID'=>$Instance,'NextURI'=>$NextURI,'NextURIMetaData'=>$NextURIMetaData);
    return self::Call('AVTransport','SetNextAVTransportURI',$args,null);;
  }
  // DesiredFixed:boolean, Instance:ui4
  public function SetOutputFixed(boolean $DesiredFixed,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredFixed'))$DesiredFixed=null;
    $args=array('InstanceID'=>$Instance,'DesiredFixed'=>$DesiredFixed);
    $this->SetValueBoolean('OutputFixed',$DesiredFixed);
    return self::Call('RenderingControl','SetOutputFixed',$args,null);;
  }
  // NewPlayMode:string, Instance:ui4
  public function SetPlayMode(string $NewPlayMode,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('NewPlayMode'))$NewPlayMode=null;
    $args=array('InstanceID'=>$Instance,'NewPlayMode'=>$NewPlayMode);
    return self::Call('AVTransport','SetPlayMode',$args,null);;
  }
  // Adjustment:i4, Instance:ui4
  public function SetRelativeGroupVolume(integer $Adjustment,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Adjustment'))$Adjustment=null;
    $args=array('InstanceID'=>$Instance,'Adjustment'=>$Adjustment);
    $filter=array('NewVolume');
    return self::Call('GroupRenderingControl','SetRelativeGroupVolume',$args,$filter);;
  }
  // Adjustment:i4, Instance:ui4, Channel:string
  public function SetRelativeVolume(integer $Adjustment,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Adjustment'))$Adjustment=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'Adjustment'=>$Adjustment);
    $filter=array('NewVolume');
    return self::Call('RenderingControl','SetRelativeVolume',$args,$filter);;
  }
  // Repeat:boolean, InstanceID:ui4
  protected function SetRepeat($boRepeat, $InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    $this->SetPlayModes($this->_PlayModes[$this->_boRepeat=$boRepeat][$this->_boShuffle][$this->_boAll]);
  }
  // Shuffle:boolean, InstanceID:ui4
  protected function SetShuffle($boShuffle, $InstanceID=0){
    if(empty($this->_PlayModes))$this->UpdatePlayMode($InstanceID);
    $this->SetPlayMode($this->_PlayModes[$this->_boRepeat][$this->_boShuffle=$boShuffle][$this->_boAll]);
  }
  // CalibrationID:string, Coefficients:string, Instance:ui4
  public function SetSonarCalibrationX(string $CalibrationID,string $Coefficients,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('CalibrationID'))$CalibrationID=null;
    if(is_null('Coefficients'))$Coefficients=null;
    $args=array('InstanceID'=>$Instance,'CalibrationID'=>$CalibrationID,'Coefficients'=>$Coefficients);
    return self::Call('RenderingControl','SetSonarCalibrationX',$args,null);;
  }
  // SonarEnabled:boolean, Instance:ui4
  public function SetSonarStatus(boolean $SonarEnabled,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('SonarEnabled'))$SonarEnabled=null;
    $args=array('InstanceID'=>$Instance,'SonarEnabled'=>$SonarEnabled);
    return self::Call('RenderingControl','SetSonarStatus',$args,null);;
  }
  // NewState:ui2, InstanceID:ui4
  public function SetState($NewState, $InstanceID=0){
    switch($NewState){
      case RPC2SONOS_STATE_STOP : $s=$this->Stop($InstanceID)?RPC2SONOS_STATE_STOP:RPC2SONOS_STATE_ERROR; break;
      case RPC2SONOS_STATE_PREV : $s=$this->Previous($InstanceID)?RPC2SONOS_STATE_PLAY:RPC2SONOS_STATE_STOP;break;
      case RPC2SONOS_STATE_PLAY : $s=$this->Play($InstanceID)?RPC2SONOS_STATE_PLAY:RPC2SONOS_STATE_STOP; break;
      case RPC2SONOS_STATE_PAUSE: $s=$this->Pause($InstanceID)?RPC2SONOS_STATE_PAUSE:RPC2SONOS_STATE_STOP;break;
      case RPC2SONOS_STATE_NEXT : $s=$this->Next($InstanceID)?RPC2SONOS_STATE_PLAY:RPC2SONOS_STATE_STOP; break;
      default : return RPC2SONOS_STATE_ERROR;
    }
    return $s;
  }
  // VariableName:string, StringValue:string
  public function SetString(string $VariableName,string $StringValue){
    if (!$this->GetOnlineState()) return null;
    if(is_null('VariableName'))$VariableName=null;
    if(is_null('StringValue'))$StringValue=null;
    $args=array('VariableName'=>$VariableName,'StringValue'=>$StringValue);
    return self::Call('SystemProperties','SetString',$args,null);;
  }
  // DesiredTime:string, TimeZoneForDesiredTime:string
  public function SetTimeNow(string $DesiredTime,string $TimeZoneForDesiredTime){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredTime'))$DesiredTime=null;
    if(is_null('TimeZoneForDesiredTime'))$TimeZoneForDesiredTime=null;
    $args=array('DesiredTime'=>$DesiredTime,'TimeZoneForDesiredTime'=>$TimeZoneForDesiredTime);
    return self::Call('AlarmClock','SetTimeNow',$args,null);;
  }
  // DesiredTimeServer:string
  public function SetTimeServer(string $DesiredTimeServer){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredTimeServer'))$DesiredTimeServer=null;
    $args=array('DesiredTimeServer'=>$DesiredTimeServer);
    return self::Call('AlarmClock','SetTimeServer',$args,null);;
  }
  // Index:i4, AutoAdjustDst:boolean
  public function SetTimeZone(integer $Index,boolean $AutoAdjustDst){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Index'))$Index=null;
    if(is_null('AutoAdjustDst'))$AutoAdjustDst=null;
    $args=array('Index'=>$Index,'AutoAdjustDst'=>$AutoAdjustDst);
    return self::Call('AlarmClock','SetTimeZone',$args,null);;
  }
  // DesiredTreble:i2, Instance:ui4
  public function SetTreble(integer $DesiredTreble,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredTreble'))$DesiredTreble=null;
    $args=array('InstanceID'=>$Instance,'DesiredTreble'=>$DesiredTreble);
    $this->SetValueInteger('Treble',$DesiredTreble);
    return self::Call('RenderingControl','SetTreble',$args,null);;
  }
  // UseVolume:boolean
  public function SetUseAutoplayVolume(boolean $UseVolume){
    if (!$this->GetOnlineState()) return null;
    if(is_null('UseVolume'))$UseVolume=null;
    $args=array('UseVolume'=>$UseVolume);
    return self::Call('DeviceProperties','SetUseAutoplayVolume',$args,null);;
  }
  // DesiredVolume:ui2, Instance:ui4, Channel:string
  public function SetVolume(integer $DesiredVolume,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredVolume'))$DesiredVolume=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredVolume'=>$DesiredVolume);
    $this->SetValueInteger('Volume',$DesiredVolume);
    return self::Call('RenderingControl','SetVolume',$args,null);;
  }
  // DesiredVolume:i2, Instance:ui4, Channel:string
  public function SetVolumeDB(integer $DesiredVolume,integer $Instance,string $Channel){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('DesiredVolume'))$DesiredVolume=null;
    $args=array('InstanceID'=>$Instance,'Channel'=>$Channel,'DesiredVolume'=>$DesiredVolume);
    $this->SetValueInteger('VolumeDB',$DesiredVolume);
    return self::Call('RenderingControl','SetVolumeDB',$args,null);;
  }
  // DesiredZoneName:string, DesiredIcon:string, DesiredConfiguration:string
  public function SetZoneAttributes(string $DesiredZoneName,string $DesiredIcon,string $DesiredConfiguration){
    if (!$this->GetOnlineState()) return null;
    if(is_null('DesiredZoneName'))$DesiredZoneName=null;
    if(is_null('DesiredIcon'))$DesiredIcon=null;
    if(is_null('DesiredConfiguration'))$DesiredConfiguration=null;
    $args=array('DesiredZoneName'=>$DesiredZoneName,'DesiredIcon'=>$DesiredIcon,'DesiredConfiguration'=>$DesiredConfiguration);
    return self::Call('DeviceProperties','SetZoneAttributes',$args,null);;
  }
  // Instance:ui4
  public function SnapshotGroupVolume(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('GroupRenderingControl','SnapshotGroupVolume',$args,null);;
  }
  // Duration:string, Instance:ui4
  public function SnoozeAlarm(string $Duration,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('Duration'))$Duration=null;
    $args=array('InstanceID'=>$Instance,'Duration'=>$Duration);
    return self::Call('AVTransport','SnoozeAlarm',$args,null);;
  }
  // ProgramURI:string, ProgramMetaData:string, Volume:ui2, IncludeLinkedZones:boolean, ResetVolumeAfter:boolean, Instance:ui4
  public function StartAutoplay(string $ProgramURI,string $ProgramMetaData,integer $Volume,boolean $IncludeLinkedZones,boolean $ResetVolumeAfter,integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    if(is_null('ProgramURI'))$ProgramURI=null;
    if(is_null('ProgramMetaData'))$ProgramMetaData=null;
    if(is_null('Volume'))$Volume=null;
    if(is_null('IncludeLinkedZones'))$IncludeLinkedZones=null;
    if(is_null('ResetVolumeAfter'))$ResetVolumeAfter=null;
    $args=array('InstanceID'=>$Instance,'ProgramURI'=>$ProgramURI,'ProgramMetaData'=>$ProgramMetaData,'Volume'=>$Volume,'IncludeLinkedZones'=>$IncludeLinkedZones,'ResetVolumeAfter'=>$ResetVolumeAfter);
    return self::Call('AVTransport','StartAutoplay',$args,null);;
  }
  // Instance:ui4
  public function Stop(integer $Instance){
    if (!$this->GetOnlineState()) return null;
    if(is_null('Instance'))$Instance=0;
    $args=array('InstanceID'=>$Instance);
    return self::Call('AVTransport','Stop',$args,null);;
  }
  // IncludeControllers:boolean, Type:string
  public function SubmitDiagnostics(boolean $IncludeControllers,string $Type){
    if (!$this->GetOnlineState()) return null;
    if(is_null('IncludeControllers'))$IncludeControllers=null;
    if(is_null('Type'))$Type=null;
    $args=array('IncludeControllers'=>$IncludeControllers,'Type'=>$Type);
    $filter=array('DiagnosticID');
    return self::Call('ZoneGroupTopology','SubmitDiagnostics',$args,$filter);;
  }
  // ID:ui4, StartLocalTime:string, Duration:string, Recurrence:string, Enabled:boolean, RoomUUID:string, ProgramURI:string, ProgramMetaData:string, PlayMode:string, Volume:ui2, IncludeLinkedZones:boolean
  public function UpdateAlarm(integer $ID,string $StartLocalTime,string $Duration,string $Recurrence,boolean $Enabled,string $RoomUUID,string $ProgramURI,string $ProgramMetaData,string $PlayMode,integer $Volume,boolean $IncludeLinkedZones){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ID'))$ID=null;
    if(is_null('StartLocalTime'))$StartLocalTime=null;
    if(is_null('Duration'))$Duration=null;
    if(is_null('Recurrence'))$Recurrence=null;
    if(is_null('Enabled'))$Enabled=null;
    if(is_null('RoomUUID'))$RoomUUID=null;
    if(is_null('ProgramURI'))$ProgramURI=null;
    if(is_null('ProgramMetaData'))$ProgramMetaData=null;
    if(is_null('PlayMode'))$PlayMode=null;
    if(is_null('Volume'))$Volume=null;
    if(is_null('IncludeLinkedZones'))$IncludeLinkedZones=null;
    $args=array('ID'=>$ID,'StartLocalTime'=>$StartLocalTime,'Duration'=>$Duration,'Recurrence'=>$Recurrence,'Enabled'=>$Enabled,'RoomUUID'=>$RoomUUID,'ProgramURI'=>$ProgramURI,'ProgramMetaData'=>$ProgramMetaData,'PlayMode'=>$PlayMode,'Volume'=>$Volume,'IncludeLinkedZones'=>$IncludeLinkedZones);
    return self::Call('AlarmClock','UpdateAlarm',$args,null);;
  }

  public function UpdateAvailableServices(){
    if (!$this->GetOnlineState()) return null;
    return self::Call('MusicServices','UpdateAvailableServices',null,null);;
  }
  // ObjectID:string, CurrentTagValue:string, NewTagValue:string
  public function UpdateObject(string $ObjectID,string $CurrentTagValue,string $NewTagValue){
    if (!$this->GetOnlineState()) return null;
    if(is_null('ObjectID'))$ObjectID=null;
    if(is_null('CurrentTagValue'))$CurrentTagValue=null;
    if(is_null('NewTagValue'))$NewTagValue=null;
    $args=array('ObjectID'=>$ObjectID,'CurrentTagValue'=>$CurrentTagValue,'NewTagValue'=>$NewTagValue);
    return self::Call('ContentDirectory','UpdateObject',$args,null);;
  }
  // InstanceID:ui4
  protected function UpdatePlayMode($InstanceID=0){
    static $modes=array(
        'NORMAL'=>array(false,false,false),
        'REPEAT_ALL'=>array(true,false,true),
        'REPEAT_ONE'=>array(true,false,false),
        'SHUFFLE_NOREPEAT'=>array(false,true,false),
        'SHUFFLE'=>array(false,true,false),
        'SHUFFLE_REPEAT_ONE'=>array(true,true,false),
    );
    if(empty($this->_PlayModes))
      foreach($modes as $k=>$a)$this->_PlayModes[$a[0]][$a[1]][$a[2]]=$k;
    if(!$t=$this->GetTransportSettings($InstanceID))return false;
    list($this->_boRepeat,$this->_boShuffle,$this->_boAll)=$modes[$t['PlayMode']];
  }
}
?>