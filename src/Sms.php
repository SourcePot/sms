<?php
/*
* This file is part of the Datapool CMS package.
* @package Datapool
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2023 to today Carsten Wallenhauer
* @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-v3
*/
declare(strict_types=1);

namespace SourcePot\Sms;

class Sms implements \SourcePot\Datapool\Interfaces\Transmitter,\SourcePot\Datapool\Interfaces\App{
	
	private $oc;
	
	private $entryTable='';
	private $entryTemplate=array('Read'=>array('index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_MEMBER_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'),
								 'Write'=>array('index'=>FALSE,'type'=>'SMALLINT UNSIGNED','value'=>'ALL_CONTENTADMIN_R','Description'=>'This is the entry specific Read access setting. It is a bit-array.'),
								 );

	public $transmitterDef=array('Type'=>array('@tag'=>'p','@default'=>'settings receiver','@Read'=>'NO_R'),
							  'Content'=>array('provider'=>array('@tag'=>'p','@element-content'=>'Messagebird','@excontainer'=>TRUE),
											   'id'=>array('@tag'=>'input','@type'=>'text','@default'=>'Add Messagebird id here...','@excontainer'=>TRUE),
											   'key'=>array('@tag'=>'input','@type'=>'password','@default'=>'Add Messagebird key here...','@excontainer'=>TRUE),
											   'originator'=>array('@tag'=>'input','@type'=>'text','@default'=>'Datapool','@excontainer'=>TRUE),
											   'Save'=>array('@tag'=>'button','@value'=>'save','@element-content'=>'Save','@default'=>'save'),
											),
							);
 
    private $settings=array();
 
    public function __construct($oc){
		$this->oc=$oc;
		$table=str_replace(__NAMESPACE__,'',__CLASS__);
		$this->entryTable=strtolower(trim($table,'\\'));
	}
	
	public function init($oc){
		$this->oc=$oc;
		$this->entryTemplate=$oc['SourcePot\Datapool\Foundation\Database']->getEntryTemplateCreateTable($this->entryTable,$this->entryTemplate);
		$oc['SourcePot\Datapool\Foundation\Definitions']->addDefintion('!'.__CLASS__,$this->transmitterDef);
        $this->settings=$this->getTransmitterSetting(__CLASS__);
	}

	public function getEntryTable(){
		return $this->entryTable;
	}
	
	public function getEntryTemplate(){
		return $this->entryTemplate;
	}

	/**
    * App interface functionality
	* @return boolean
	*/
	public function run(array|bool $arr=TRUE):array{
		if ($arr===TRUE){
			return array('Category'=>'Admin','Emoji'=>'&phone;','Label'=>'SMS','Read'=>'ADMIN_R','Class'=>__CLASS__);
		} else {
            $arr['callingClass']=__CLASS__;
            $arr['callingFunction']=__FUNCTION__;
            $arr=$this->getTransmitterSettingsWidget($arr);
            $arr['toReplace']['{{content}}']=$this->transmitterPluginHtml($arr);
			return $arr;
		}
	}

	/**
    * Transmitter interface functionality
	* @return boolean
	*/
    public function send(string $recipient,array $entry):int{
        $sentEntriesCount=0;
        //$this->oc['SourcePot\Datapool\Foundation\Logging']->addLog(array('msg'=>'Failed to send email: recipient emal address missing','priority'=>11,'callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__));
        return $sentEntriesCount;
    }
	
	private function getTransmitterSetting($callingClass){
		$EntryId=preg_replace('/\W/','_','OUTBOX-'.$callingClass);
		$setting=array('Class'=>__CLASS__,'EntryId'=>$EntryId);
		$setting['Content']=array();
		return $this->oc['SourcePot\Datapool\Foundation\Filespace']->entryByIdCreateIfMissing($setting,TRUE);
	}

	private function getTransmitterSettingsWidget($arr){
		$arr['html']=(isset($arr['html']))?$arr['html']:'';
		$setting=$this->getTransmitterSetting($arr['callingClass']);
		$arr['html'].=$this->oc['SourcePot\Datapool\Foundation\Definitions']->entry2form($setting,FALSE);
		return $arr;
	}
	
	public function transmitterPluginHtml(array $arr):string{
        $arr['html']=(isset($arr['html']))?$arr['html']:'';
        
        $balanceBtnArr=array('tag'=>'button','type'=>'submit','element-content'=>'Get balance','key'=>array('textCredentials'),'callingClass'=>__CLASS__,'callingFunction'=>__FUNCTION__);
        $balanceMatrix=array(''=>array('Cmd'=>$balanceBtnArr,'Info'=>'Check your Messagebird credentials if this balance check fails'));
        
        $formData=$this->oc['SourcePot\Datapool\Foundation\Element']->formProcessing(__CLASS__,__FUNCTION__);
        if (isset($formData['cmd']['textCredentials'])){
            $messageBird= new \MessageBird\Client($this->settings['Content']['key']);
            $balance=$messageBird->balance->read();
            $balanceMatrix=array('Balance'=>get_object_vars($balance));
        }
        $arr['html'].=$this->oc['SourcePot\Datapool\Tools\HTMLbuilder']->table(array('matrix'=>$balanceMatrix,'caption'=>'Balance','hideKeys'=>TRUE));
        
        /*
        ["Content"]=>
        array(3) {
        ["id"]=>
        string(36) "d2bc2126-b002-4793-918d-8309828a6e26"
        ["key"]=>
        string(25) "ylZcJ0YqncG7zqYoaKjqHk5ED"
        ["originator"]=>
        string(8) "Datapool"
        */
        
        
        
        
        return $arr['html'];
    }
    
    public function getRelevantFlatUserContentKey():string{
        $S=$this->oc['SourcePot\Datapool\Tools\MiscTools']->getSeparator();
		$flatUserContentKey='Content'.$S.'Contact details'.$S.'Email';
        return $flatUserContentKey;
    }

	/**
	* @return boolean
	*/
	public function entry2sms($entry,$isDebugging=FALSE){
        $debugArr=array('entry'=>$entry);
        
        
        
            
            
        
		if ($isDebugging){
			$this->oc['SourcePot\Datapool\Tools\MiscTools']->arr2file($debugArr);
		}
		return $success;
	}

}
?>