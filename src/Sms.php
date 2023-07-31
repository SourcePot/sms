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
							  'Content'=>array('provider'=>array('@tag'=>'input','@type'=>'text','@default'=>'Messagebird','@excontainer'=>TRUE),
											   'id'=>array('@tag'=>'input','@type'=>'text','@default'=>'Add Messagebird id here...','@excontainer'=>TRUE),
											   'key'=>array('@tag'=>'input','@type'=>'password','@default'=>'Add Messagebird key here...','@excontainer'=>TRUE),
											   'originator'=>array('@tag'=>'input','@type'=>'text','@default'=>'Datapool','@excontainer'=>TRUE),
											   'url'=>array('@tag'=>'input','@type'=>'text','@default'=>'https://rest.messagebird.com/','@excontainer'=>TRUE),
											   'Save'=>array('@tag'=>'button','@value'=>'save','@element-content'=>'Save','@default'=>'save'),
											),
							);
 
    public function __construct($oc){
		$this->oc=$oc;
		$table=str_replace(__NAMESPACE__,'',__CLASS__);
		$this->entryTable=strtolower(trim($table,'\\'));
	}
	
	public function init($oc){
		$this->oc=$oc;
		$this->entryTemplate=$oc['SourcePot\Datapool\Foundation\Database']->getEntryTemplateCreateTable($this->entryTable,$this->entryTemplate);
		$oc['SourcePot\Datapool\Foundation\Definitions']->addDefintion('!'.__CLASS__,$this->transmitterDef);
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
            $html='Nothing here yet...';
            $arr['toReplace']['{{content}}']=$html;
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
        $arr['html'].='I am the sms plugin...';
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