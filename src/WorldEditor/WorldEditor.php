<?php

namespace WorldEditor;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

class WorldEditor extends PluginBase implements Listener, CommandExecutor{
	private $sessions;
	public function onEnable(){
	     $this->Datas = array();
                $this->sessions = array();
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                if(!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder());
                $this->config = new Config($this->getDataFolder()."config.yml", CONFIG::YAML, array(
                        "block-limit" => -1,
			"wand-item" => 271,
                ));
                $this->config->save();
                $this->wanditem = $this->config->get("wand-item");
                $this->prifks = "§5[§eWE§6P§5]§2";
	}

        public function onBreak(BlockBreakEvent $event){
                $player = $event->getPlayer();
                if($player->getInventory()->getItemInHand()->getID() == $this->wanditem and $player->isOp() and $this->session($player)["wand-usage"] === true){
                $this->ID[$player->getName()] = $event->getBlock()->getId();
                $this->Meta[$player->getName()] = $event->getBlock()->getDamage();
                $this->setPosition1($player, $event->getBlock());
                $event->setCancelled(true);
                }
        }

        public function onTouch(PlayerInteractEvent $event){
                $player = $event->getPlayer();
                if($player->getInventory()->getItemInHand()->getID() == $this->wanditem and $player->isOp() and $this->session($player)["wand-usage"] === true and $event->getBlock()->getID() != Block::AIR){
                $this->ID2[$player->getName()] = $event->getBlock()->getId();
                $this->Meta2[$player->getName()] = $event->getBlock()->getDamage();
                $this->setPosition2($player, $event->getBlock());
                $event->setCancelled(true);
                }
        }
        
        public function maketree($player, $size){
        	$level = $player->getLevel();
        	$x = $player->getX();
        	$y = $player->getY();
        	$z = $player->getZ();
        	//$level->setBlock(new Vector3($x, $y, $z), Block::get(17, 0));
        }

	public function session(Player $player){
		if(!isset($this->sessions[$player->getName()])){
			$this->sessions[$player->getName()] = array(
				"selection" => array(false,false),
				"clipboard" => false,
				"block-limit" => $this->config->get("block-limit"),
				"wand-usage" => true,
			);
		}
		return $this->sessions[$player->getName()];
	}

	public function setPosition1($player,$position){
                if(isset($this->sessions[$player->getName()])){
                $this->sessions[$player->getName()]["selection"][0] = array(round($position->x), round($position->y), round($position->z),$position->level);
                $count = $this->countBlocks($this->sessions[$player->getName()]["selection"]);
                if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
                $session["selection"] = $this->sessions[$player->getName()]["selection"];
                }else{
                $session = $this->session($player);
                $session["selection"][0] = array(round($position->x), round($position->y), round($position->z),$position->level);
                $count = $this->countBlocks($session["selection"]);
                if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
                }
		$player->sendMessage($this->prifks."The first place(".$session["selection"][0][0].", ".$session["selection"][0][1].", ".$session["selection"][0][2].")".$count."[".$this->ID[$player->getName()].":".$this->Meta[$player->getName()]."]");
                $this->sessions[$player->getName()]["selection"] = $session["selection"];
                return true;
	}

	public function setPosition2($player,$position){
                if(isset($this->sessions[$player->getName()])){
                $this->sessions[$player->getName()]["selection"][1] = array(round($position->x), round($position->y), round($position->z),$position->level);
                $count = $this->countBlocks($this->sessions[$player->getName()]["selection"]);
                if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
                $session["selection"] = $this->sessions[$player->getName()]["selection"];
                }else{
                $session = $this->session($player);
		$session["selection"][1] = array(round($position->x), round($position->y), round($position->z),$position->level);
		$count = $this->countBlocks($session["selection"]);
		if($count === false){
			$count = "";
		}else{
			$count = " ($count)";
		}
                }

		$player->sendMessage($this->prifks."Second place(".$session["selection"][1][0].", ".$session["selection"][1][1].", ".$session["selection"][1][2].")".$count."[".$this->ID2[$player->getName()].":".$this->Meta2[$player->getName()]."]");
                $this->sessions[$player->getName()]["selection"] = $session["selection"];
		return true;
	}

        public function onCommand(CommandSender $sender, Command $command, $label, array $args){
                $username = $sender->getName();
                $cmd = $command->getName();
                if($cmd{0} === "/"){
                $cmd = substr($cmd, 1);
                }
		switch($cmd){
			case "paste":
                                if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				$session = $this->session($sender);

				$this->W_paste($session["clipboard"], new Position($sender->getX(), $sender->getY(), $sender->getZ(), $sender->getlevel()),$sender);
				return true;
                                break;
			case "copy":
                                if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				$session = $this->session($sender);
				$count = $this->countBlocks($session["selection"], $startX, $startY, $startZ);
				if($count > $session["block-limit"] and $session["block-limit"] > 0){
					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to copy $count block(s).");
                                        return true;
					break;
				}

				$blocks = $this->W_copy($session["selection"], $sender);
				if(count($blocks) > 0){
					$offset = array($startX - $sender->getx() - 0.5, $startY - $sender->gety(), $startZ - $sender->getz() - 0.5);
					$session["clipboard"] = array($offset, $blocks);
                                        $this->sessions[$sender->getName()]["clipboard"] = $session["clipboard"];
				}
                                return true;
				break;
			case "cut":
				if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				$session = $this->session($sender);
				$count = $this->countBlocks($session["selection"], $startX, $startY, $startZ);
				if($count > $session["block-limit"] and $session["block-limit"] > 0){
					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to cut $count block(s).");
                                        return true;
					break;
				}

				$blocks = $this->W_cut($session["selection"], $sender);
				if(count($blocks) > 0){
					$offset = array($startX - $sender->getx() - 0.5, $startY - $sender->gety(), $startZ - $sender->getz() - 0.5);
					$session["clipboard"] = array($offset, $blocks);
                                        $this->sessions[$sender->getName()]["clipboard"] = $session["clipboard"];
				}
                                return true;
				break;
			case "toggleeditwand":
				if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				$session = $this->session($sender);
				$session["wand-usage"] = $session["wand-usage"] == true ? false:true;
                                $this->sessions[$sender->getName()]["wand-usage"] = $session["wand-usage"];
				$sender->sendMessage($this->prifks."The wand".($session["wand-usage"] === true ? "enabled":"disabled")."It was set to.");
                                return true;
				break;
			case "wand":
				if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				if($sender->getInventory()->getItem($this->config->get("wand-item"))->getID() === Item::get($this->config->get("wand-item"))->getID()){
					$sender->sendMessage($this->prifks."You already have a wand.");
                                        return true;
					break;
				}elseif($sender->getGamemode() === 1){
					$sender->sendMessage($this->prifks."You are a creative mode");
				}else{
                                        $sender->getInventory()->addItem(Item::get($this->config->get("wand-item")));
                                        $sender->sendMessage($this->prifks."The first place to break the block , please specify the second location by tapping the block.");
				}
                                return true;
				break;
			case "desel":
				if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                                }
				$session = $this->session($sender);
				$session["selection"] = array(false, false);
                                $this->sessions[$sender->getName()]["selection"] = $session["selection"];
				$sender->sendMessage($this->prifks."You have successfully deleted the selected");
                                return true;
				break;
			case "limit":
				if(!isset($args[0]) or trim($args[0]) === ""){
					$sender->sendMessage($this->prifks."Tips: //limit <limit>");
                                        return true;
					break;
				}
				$limit = intval($args[0]);
				if($limit < 0){
					$limit = -1;
				}
				if($this->config->get("block-limit") > 0){
					$limit = $limit === -1 ? $this->config->get("block-limit"):min($this->config->get("block-limit"), $limit);
				}
				$session["block-limit"] = $limit;
                                $this->sessions[$sender->getName()]["block-limit"] = $session["block-limit"];
				$sender->sendMessage($this->prifks."The number of blocks limit".($limit === -1 ? "infinite":$limit)." It has been changed to.");
                                return true;
				break;
			case "pos1":
                                if(!($sender instanceof Player)){
			        $sender->sendMessage($this->prifks."Please use in the game.");
			        return true;
			        break;
                                }
                                $this->setPosition1($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getlevel()));
                                return true;
                                break;
			case "pos2":
                                if(!($sender instanceof Player)){
                                $sender->sendMessage($this->prifks."Please use in the game.");
                                return true;
                                break;
                  
