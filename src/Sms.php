<?php
/*
* This file is part of the Datapool CMS package.
* @package Datapool
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit/ MIT
*/
declare(strict_types=1);

namespace SourcePot\Sms;

class Sms implements \SourcePot\Datapool\Interfaces\Transmitter{
    
    public const ONEDIMSEPARATOR='|[]|';
    private $oc;
    
    private const ENTRY_EXPIRATION_SEC=3600;

    private $entryTable='';
    private $entryTemplate=['Read'=>['index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_MEMBER_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'],
                            'Write'=>['index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_CONTENTADMIN_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'],
                        ];

    public $transmitterDef=['Type'=>['@tag'=>'p','@default'=>'settings receiver','@Read'=>'NO_R'],
                            'Content'=>['provider'=>['@tag'=>'p','@element-content'=>'Messagebird','@excontainer'=>TRUE],
                                        'id'=>['@tag'=>'input','@type'=>'text','@default'=>'Add Messagebird id here...','@excontainer'=>TRUE],
                                        'key'=>['@tag'=>'input','@type'=>'password','@default'=>'Add Messagebird key here...','@excontainer'=>TRUE],
                                        'originator'=>['@tag'=>'input','@type'=>'text','@default'=>'Datapool','@excontainer'=>TRUE],
                                        'Save'=>['@tag'=>'button','@value'=>'save','@element-content'=>'Save','@default'=>'save'],
                                    ],
                                ];
 
    private $settings=[];
 
    public function __construct($oc){
        $this->oc=$oc;
        $table=str_replace(__NAMESPACE__,'',__CLASS__);
        $this->entryTable=strtolower(trim($table,'\\'));
    }

    Public function loadOc(array $oc):void
    {
        $this->oc=$oc;
    }
  
    public function init()
    {
        $this->entryTemplate=$this->oc['SourcePot\Datapool\Foundation\Database']->getEntryTemplateCreateTable($this->entryTable,__CLASS__);
        $this->oc['SourcePot\Datapool\Foundation\Definitions']->addDefintion('!'.__CLASS__,$this->transmitterDef);
        $this->settings=$this->getTransmitterSetting(__CLASS__);
    }

    public function job(array $vars):array
    {
        if (empty($this->settings['Content']['key'])){
            $vars['error']='Credentials are empty';
        } else {
            $messageBird= new \MessageBird\Client($this->settings['Content']['key']);
            $balance=get_object_vars($messageBird->balance->read());
            $this->oc['SourcePot\Datapool\Foundation\Signals']->updateSignal(__CLASS__,__FUNCTION__,'Balance ['.$balance['type'].']',$balance['amount'],'float');
        }
        return $vars;
    }
    public function getEntryTable(){
        return $this->entryTable;
    }
    
    public function getEntryTemplate(){
        return $this->entryTemplate;
    }

    /**
    * Transmitter interface functionality
    * @return boolean
    */
    public function send(string $recipient,array $entry):int{
        $sentEntriesCount=0;
        $userEntryTable=$this->oc['SourcePot\Datapool\Foundation\User']->getEntryTable();
        $recipient=$this->oc['SourcePot\Datapool\Foundation\Database']->entryById(['Source'=>$userEntryTable,'EntryId'=>$recipient],TRUE);
        $flatRecipient=$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($recipient);
        $sender=$this->oc['SourcePot\Datapool\Foundation\Database']->entryById(['Source'=>$userEntryTable,'EntryId'=>$_SESSION['currentUser']['EntryId']],TRUE);
        $flatUserContentKey=$this->getRelevantFlatUserContentKey();
        if (empty($flatRecipient[$flatUserContentKey])){
            $this->oc['logger']->log('warning','Failed to send sms: recipient mobile is empty');
        } else {
            $name=(isset($entry['Name']))?$entry['Name']:'';
            $smsArr=['Name'=>$name]+$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2flat($entry['Content']);
            $smsMsg=implode(' | ',$smsArr);
            $smsMsg=substr($smsMsg,0,512);
            $entry=['Content'=>['recipient'=>$flatRecipient[$flatUserContentKey],'body'=>trim($smsMsg,' |')]];
            $status=$this->entry2sms($entry,FALSE);
            if (empty($status['error'])){
                $sentEntriesCount++;
                $this->oc['logger']->log('info','SMS sent to: {recipient}',['recipient'=>$flatRecipient[$flatUserContentKey]]);
            } else {
                $this->oc['logger']->log('error','Failed to send sms: {error}',$status['error']);
            }
        }
        return $sentEntriesCount;
    }
    
    public function getRelevantFlatUserContentKey():string{
        $S=self::ONEDIMSEPARATOR;
        $flatUserContentKey='Content'.$S.'Contact details'.$S.'Mobile';
        return $flatUserContentKey;
    }

    private function getTransmitterSetting($callingClass){
        $EntryId=preg_replace('/\W/','_','OUTBOX-'.$callingClass);
        $setting=['Class'=>__CLASS__,'EntryId'=>$EntryId];
        $setting['Content']=[];
        return $this->oc['SourcePot\Datapool\Foundation\Filespace']->entryByIdCreateIfMissing($setting,TRUE);
    }

    private function getTransmitterSettingsWidgetHtml($arr):string
    {
        $setting=$this->getTransmitterSetting($arr['callingClass']);
        $html=$this->oc['SourcePot\Datapool\Foundation\Definitions']->entry2form($setting,FALSE);
        return $html;
    }
    
    public function transmitterPluginHtml(array $arr):string{
        $arr['html']=(isset($arr['html']))?$arr['html']:'';
        $formData=$this->oc['SourcePot\Datapool\Foundation\Element']->formProcessing(__CLASS__,__FUNCTION__);
        // get the balance
        $balanceBtnArr=['tag'=>'button','type'=>'submit','element-content'=>'Get balance','key'=>['textCredentials'],'callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__];
        $balanceMatrix=[''=>['Cmd'=>$balanceBtnArr,'Info'=>'Check your Messagebird credentials if this balance check fails']];
        if (isset($formData['cmd']['textCredentials'])){
            if (empty($this->settings['Content']['key'])){
                $balanceMatrix=['Balance'=>['Error'=>'credentials are empty...']];
            } else {
                $messageBird= new \MessageBird\Client($this->settings['Content']['key']);
                $balance=$messageBird->balance->read();
                $balanceMatrix=['Balance'=>get_object_vars($balance)];
            }
        } else if (isset($formData['cmd']['send'])){
            $sentCount=$this->send($formData['val']['recipient'],$formData['val']);
            if (!empty($sentCount)){
                $this->oc['logger']->log('notice','SMS sent: {sentCount}',['sentCount'=>$sentCount]);    
            }
        }
        if ($this->oc['SourcePot\Datapool\Foundation\Access']->isContentAdmin()){
            $settingsHtml=$this->getTransmitterSettingsWidgetHtml(['callingClass'=>__CLASS__]);
            $arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->app(['icon'=>'SMS Settings','html'=>$settingsHtml]);
            //
            $balanceHtml=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(['matrix'=>$balanceMatrix,'caption'=>'Balance','hideKeys'=>TRUE,'keep-element-content'=>TRUE]);
            $arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->app(['icon'=>'SMS balance','html'=>$balanceHtml,'open'=>!empty(key($balanceMatrix))]);
        }
        // Send message
        $availableRecipients=$this->oc['SourcePot\Datapool\Foundation\User']->getUserOptions([],$this->getRelevantFlatUserContentKey());
        $selectArr=['callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__,'options'=>$availableRecipients,'key'=>['recipient']];
        $smsMatrix=[];
        $smsMatrix['Recepient']['Value']=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->select($selectArr);
        $smsMatrix['Message']['Value']=$this->oc['SourcePot\Datapool\Foundation\Element']->element(['tag'=>'textarea','element-content'=>'I am a test message...','key'=>['Content','Message'],'callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__]);
        $smsMatrix['']['Value']=$this->oc['SourcePot\Datapool\Foundation\Element']->element(['tag'=>'button','type'=>'submit','element-content'=>'Send','key'=>['send'],'callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__]);
        $smsHtml=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(['matrix'=>$smsMatrix,'caption'=>'SMS test','keep-element-content'=>TRUE,'hideHeader'=>TRUE]);
        //
        $arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->app(['icon'=>'Create SMS','html'=>$smsHtml]);
        return $arr['html'];
    }
    
    /**
    * @return boolean
    */
    public function entry2sms($entry){
        $entry['Source']=$this->getEntryTable();
        $entry['Expires']=$this->oc['SourcePot\Datapool\Tools\MiscTools']->getDateTime('@'.strval(time()+self::ENTRY_EXPIRATION_SEC));
        $entry['Name']=substr($entry['Content']['body'],0,10).'...';
        // send message
        $MessageBird= new \MessageBird\Client($this->settings['Content']['key']);
        $Message= new \MessageBird\Objects\Message();
        $Message->originator=$entry['Group']=$this->settings['Content']['originator'];
        $Message->recipients=$entry['Folder']=[$entry['Content']['recipient']];
        $Message->body=$entry['Content']['body'];
        try{
            $result=$MessageBird->messages->create($Message);
            $status=['totalCount'=>$result->recipients->totalCount,
                    'totalSentCount'=>$result->recipients->totalSentCount,
                    'totalDeliveredCount'=>$result->recipients->totalDeliveredCount,
                    'totalDeliveryFailedCount'=>$result->recipients->totalDeliveryFailedCount
                    ];
            $this->oc['SourcePot\Datapool\Foundation\Database']->insertEntry($entry);
        } catch (\MessageBird\Exceptions\AuthenticateException $e){
            $status['error']='Wrong login';
        } catch (\MessageBird\Exceptions\BalanceException $e){
            $status['error']='No balance';
        } catch (\Exception $e){
            $status['error']=$e->getMessage();
        }
        return $status;
    }

}
?>