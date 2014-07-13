<?php

namespace xecon\account;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
//use pocketmine\item\Item;
use pocketmine\network\protocol;
use xecon\entity\Entity;

class Account implements InventoryHolder{
	/** @var string */
	private $name;
	/** @var float */
	private $amount;
	/** @var Entity */
	private $entity;
	/** @var DummyInventory */
	private $inventory;
	private $maxContainable = 1000;
	private $minAmount = 0;
//	/** @var int[] */
//	private $inventoryMoneySlots = [];
	private $containerTypes = [];
	/**
	 * @param string $name
	 * @param float $amount
	 * @param Entity $entity
	 * @param Inventory|null $inventory
	 * @param string[] $containerTypes
	 */
	public function __construct($name, $amount, Entity $entity, Inventory $inventory = null, array $containerTypes = []){
		$this->name = $name;
		$this->amount = $amount;
		$this->entity = $entity;
		$this->inventory = (!($inventory instanceof Inventory)) ? new DummyInventory($this):$inventory;
		foreach($containerTypes as $type){
			$maxContainable = constant($type."::PER_AMOUNT") * constant($type."::MAX_STACK");
			$this->containerTypes[$maxContainable] = $type;
		}
		krsort($this->containerTypes, SORT_NUMERIC);
	}
	public function getMaxContainable(){
		return $this->maxContainable;
	}
	public function setMaxContainable($cnt){
		$this->maxContainable = $cnt;
	}
	public function setMinAmount($a = 0){
		$this->minAmount = $a;
	}
	public function getName(){
		return $this->name;
	}
	public function getAmount(){
		return $this->amount;
	}
	/**
	 * This raw function is only for internal use. Do NOT call this method. Call Account::pay() instead.
	 * @param $amount
	 * @return bool
	 */
	public function add($amount){
		return $this->setAmount($this->getAmount() + $amount);
	}
	/**
	 * This raw function is only for internal use. Calling this method is discouraged unless logging of transactions is unwanted. Call Account::pay() instead.
	 * @param int $amount
	 * @return bool
	 */
	public function take($amount){
		return $this->setAmount($this->getAmount() - $amount);
	}
	/**
	 * This raw function is only for internal use. Calling this method is discouraged unless logging of transactions is unwanted. Call Account::pay() instead.
	 * @param int $amount
	 * @return bool
	 */
	public function setAmount($amount){
		if($amount > $this->maxContainable or $amount < $this->minAmount){
			return false;
		}
		$this->amount = $amount;
//		$this->tidyInventory($amount);
		return true;
	}
	public function getInventory(){
		return $this->inventory;
	}
//	public function tidyInventory($new){
//		$this->clearInventoryMoney();
//		$this->addInventoryMoney($new);
//	}
//	protected function clearInventoryMoney(){
//		while(count($this->inventoryMoneySlots) > 0){
//			$this->getInventory()->setItem(array_shift($this->inventoryMoneySlots), Item::get(0));
//		}
//	}
//	protected function addInventoryMoney($amount){
//		$curAmt = $amount;
//		$items = [];
//		$availableSlotsLeft = $this->getInventory()->all(Item::get(0));
//		foreach($this->containerTypes as $type){
//			$maxStack = constant($type."::MAX_STACK");
//			$perAmount = constant($type."::PER_AMOUNT");
//			if($perAmount > $curAmt){
//				continue;
//			}
//			$count = 0;
//			while($curAmt >= $perAmount and $count < $maxStack * 16 and $availableSlotsLeft - ($count / 16) > 0){
//				$count++;
//				$curAmt -= $perAmount;
//			}
//			$items[$type] = $count;
//			if($availableSlotsLeft === 0 or $curAmt === 0){
//				break;
//			}
//		}
//		if($curAmt > 0){
//			$this->entity->sendMessage("Your \$$curAmt has been dropped due to your {$this->getName()} inventory is full.");
//		}
////		$slots = [];
////		foreach($items as $type => $count){
////			$id = constant($type."::ID");
////			$amount = (int) floor($count / 16);
////			$meta = $count % 16;
////			// TODO this complex maths got my head exploded.
////		}
//	}
	/**
	 * This is an API method. You are encouraged to
	 * use this method (with $account as \xecon\Main::getService()->getService($serviceName))
	 * instead of Account::add(), Account::take() or Account::setAmount(). Look at
	 * <a href="https://github.com/LegendOfMCPE/xEcon/wiki/developer's%20guide">the article about
	 * <i>double entry</i> on the wiki</a> for why using this method is encouraged.
	 * @param Account $other
	 * @param number $amount
	 * @param string $detail
	 * @return bool
	 */
	public function pay(Account $other, $amount, $detail = "None"){
		if($other->take($amount) and $this->add($amount)){
			$this->getEntity()->getMain()->logTransaction($this, $other, $amount, $detail);
			return true;
		}
		return false;
	}
	public function canPay($amount){
		return ($this->amount - $amount) >= $this->minAmount;
	}
	/**
	 * @return \xecon\entity\Entity
	 */
	public function getEntity(){
		return $this->entity;
	}
}