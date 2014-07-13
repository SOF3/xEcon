<?php

namespace xecon\entity;

use xecon\account\Account;
use xecon\Main;

trait Entity{
	/** @var string */
	private $folder;
	/** @var Account[] */
	protected $accounts = [];
	/** @var Account[] */
	protected $liabilities = [];
	/** @var Main */
	protected $main;
	protected function initializeXEconEntity($folder, Main $main){
		$this->folder = $folder;
		if(!is_dir($folder)){
			$this->initAsDefault();
		}
		else{
			$this->init();
		}
		$this->main = $main;
	}
	public function finalize(){
		$this->save();
	}
	private function init(){
		$data = json_decode(file_get_contents($this->getFolder()."general.json"));
		foreach($data["accounts"] as $account=>$data){
			$this->accounts[$account] = new Account($account, $data["amount"], $this, $this->getInventory($account));
			$this->accounts[$account]->setMaxContainable($data["max-containable"]);
		}
	}
	private function initAsDefault(){
		$this->initDefaultAccounts();
	}
	public function getInventory($account){
		return $this->accounts[$account]->getInventory();
	}
	public function getFolder(){
		return $this->folder;
	}
	/**
	 * @return Main
	 */
	public function getMain(){
		return $this->main;
	}
	protected function getFolderByName($name){
		return $this->main->getEntDir().$this->getAbsolutePrefix()."@#@!%".$name;
	}
	protected function addAccount($name, $defaultAmount, $maxContainable = PHP_INT_MAX, $minAmount = 0){
		$this->accounts[$name] = new Account($name, $defaultAmount, $this, $this->getInventory($name));
		$this->accounts[$name]->setMaxContainable($maxContainable);
		$this->accounts[$name]->setMinAmount($minAmount);
	}
	protected function addLiability($name, $maxAmount, $default = 0){
		$this->liabilities[$name] = new Account($name, $default, $this, null);
		$this->liabilities[$name]->setMaxContainable($maxAmount);
	}
	public function save(){
//		file_put_contents($this->folder."hook.json", json_encode(get_class($this)));
		$data = [];
		$data["accounts"] = [];
		foreach($this->accounts as $acc){
			$data["accounts"][$acc->getName()] = [
				"amount" => $acc->getAmount(),
				"max-containable" => $acc->getMaxContainable()
			];
		}
		file_put_contents($this->folder."general.json", json_encode($data, JSON_PRETTY_PRINT|JSON_BIGINT_AS_STRING));
	}
	/**
	 * @param $name
	 * @return bool|Account
	 */
	public function getAccount($name){
		return isset($this->accounts[$name]) ? $this->accounts[$name]:false;
	}
	public function getAccounts(){
		return $this->accounts;
	}
	public function getNetBalance(){ // no idea why I put this here. well, this might get handy later.
		$balance = 0;
		foreach($this->accounts as $acc){
			$balance += $acc->getAmount();
		}
		foreach($this->liabilities as $l){
			$balance -= $l->getAmount();
		}
		return $balance;
	}
	public abstract function getName();
	public abstract function getAbsolutePrefix();
	public abstract function sendMessage($msg);
	protected abstract function initDefaultAccounts();
}