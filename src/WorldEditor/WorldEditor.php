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
                $this->prifks = "§5[§bWE§f-§6Potato§5]§2";
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
