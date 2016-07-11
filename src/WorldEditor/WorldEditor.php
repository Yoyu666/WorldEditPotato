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
 			"wand-item" => 392,
                 ));
                 $this->config->save();
                 $this->wanditem = $this->config->get("wand-item");
                 $this->prifks = "§5[§eWE§6Potato§5]§2";
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
 +                $count = $this->countBlocks($session["selection"]);
 +                if($count === false){
 +			$count = "";
 +		}else{
 +			$count = " ($count)";
 +		}
 +                }
 +
 +		$player->sendMessage($this->prifks."The first place(".$session["selection"][0][0].", ".$session["selection"][0][1].", ".$session["selection"][0][2].")".$count."[".$this->ID[$player->getName()].":".$this->Meta[$player->getName()]."]");
 +                $this->sessions[$player->getName()]["selection"] = $session["selection"];
 +                return true;
 +	}
 +
 +	public function setPosition2($player,$position){
 +                if(isset($this->sessions[$player->getName()])){
 +                $this->sessions[$player->getName()]["selection"][1] = array(round($position->x), round($position->y), round($position->z),$position->level);
 +                $count = $this->countBlocks($this->sessions[$player->getName()]["selection"]);
 +                if($count === false){
 +			$count = "";
 +		}else{
 +			$count = " ($count)";
 +		}
 +                $session["selection"] = $this->sessions[$player->getName()]["selection"];
 +                }else{
 +                $session = $this->session($player);
 +		$session["selection"][1] = array(round($position->x), round($position->y), round($position->z),$position->level);
 +		$count = $this->countBlocks($session["selection"]);
 +		if($count === false){
 +			$count = "";
 +		}else{
 +			$count = " ($count)";
 +		}
 +                }
 +		$player->sendMessage($this->prifks."Second place(".$session["selection"][1][0].", ".$session["selection"][1][1].", ".$session["selection"][1][2].")".$count."[".$this->ID2[$player->getName()].":".$this->Meta2[$player->getName()]."]");
 +                $this->sessions[$player->getName()]["selection"] = $session["selection"];
 +		return true;
 +	}
 +
 +        public function onCommand(CommandSender $sender, Command $command, $label, array $args){
 +                $username = $sender->getName();
 +                $cmd = $command->getName();
 +                if($cmd{0} === "/"){
 +                $cmd = substr($cmd, 1);
 +                }
 +		switch($cmd){
 +			case "paste":
 +                                if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game.");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +
 +				$this->W_paste($session["clipboard"], new Position($sender->getX(), $sender->getY(), $sender->getZ(), $sender->getlevel()),$sender);
 +				return true;
 +                                break;
 +			case "copy":
 +                                if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game.");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$count = $this->countBlocks($session["selection"], $startX, $startY, $startZ);
 +				if($count > $session["block-limit"] and $session["block-limit"] > 0){
 +					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to copy $count block(s).");
 +                                        return true;
 +					break;
 +				}
 +
 +				$blocks = $this->W_copy($session["selection"], $sender);
 +				if(count($blocks) > 0){
 +					$offset = array($startX - $sender->getx() - 0.5, $startY - $sender->gety(), $startZ - $sender->getz() - 0.5);
 +					$session["clipboard"] = array($offset, $blocks);
 +                                        $this->sessions[$sender->getName()]["clipboard"] = $session["clipboard"];
 +				}
 +                                return true;
 +				break;
 +			case "cut":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game.");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$count = $this->countBlocks($session["selection"], $startX, $startY, $startZ);
 +				if($count > $session["block-limit"] and $session["block-limit"] > 0){
 +					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to cut $count block(s).");
 +                                        return true;
 +					break;
 +				}
 +
 +				$blocks = $this->W_cut($session["selection"], $sender);
 +				if(count($blocks) > 0){
 +					$offset = array($startX - $sender->getx() - 0.5, $startY - $sender->gety(), $startZ - $sender->getz() - 0.5);
 +					$session["clipboard"] = array($offset, $blocks);
 +                                        $this->sessions[$sender->getName()]["clipboard"] = $session["clipboard"];
 +				}
 +                                return true;
 +				break;
 +			case "toggleeditwand":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$session["wand-usage"] = $session["wand-usage"] == true ? false:true;
 +                                $this->sessions[$sender->getName()]["wand-usage"] = $session["wand-usage"];
 +				$sender->sendMessage($this->prifks."The Wand".($session["wand-usage"] === true ? "enabled":"disabled")."It was set to");
 +                                return true;
 +				break;
 +			case "wand":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				if($sender->getInventory()->getItem($this->config->get("wand-item"))->getID() === Item::get($this->config->get("wand-item"))->getID()){
 +					$sender->sendMessage($this->prifks."You already have a wand");
 +                                        return true;
 +					break;
 +				}elseif($sender->getGamemode() === 1){
 +					$sender->sendMessage($this->prifks."You are a creative mode");
 +				}else{
 +                                        $sender->getInventory()->addItem(Item::get($this->config->get("wand-item")));
 +                                        $sender->sendMessage($this->prifks."The first place to break the block, please specify the second location by tapping the block");
 +				}
 +                                return true;
 +				break;
 +			case "desel":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$session["selection"] = array(false, false);
 +                                $this->sessions[$sender->getName()]["selection"] = $session["selection"];
 +				$sender->sendMessage($this->prifks."You have successfully deleted the selected");
 +                                return true;
 +				break;
 +			case "limit":
 +				if(!isset($args[0]) or trim($args[0]) === ""){
 +					$sender->sendMessage($this->prifks."Tip: //limit <limit>");
 +                                        return true;
 +					break;
 +				}
 +				$limit = intval($args[0]);
 +				if($limit < 0){
 +					$limit = -1;
 +				}
 +				if($this->config->get("block-limit") > 0){
 +					$limit = $limit === -1 ? $this->config->get("block-limit"):min($this->config->get("block-limit"), $limit);
 +				}
 +				$session["block-limit"] = $limit;
 +                                $this->sessions[$sender->getName()]["block-limit"] = $session["block-limit"];
 +				$sender->sendMessage($this->prifks."The number of blocks limit".($limit === -1 ? "infinite":$limit)."It has been changed to");
 +                                return true;
 +				break;
 +			case "pos1":
 +                                if(!($sender instanceof Player)){
 +			        $sender->sendMessage($this->prifks."Please use in the game");
 +			        return true;
 +			        break;
 +                                }
 +                                $this->setPosition1($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getlevel()));
 +                                return true;
 +                                break;
 +			case "pos2":
 +                                if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +                                $this->setPosition2($sender, new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getlevel()));
 +                                return true;
 +                                break;
 +
 +			case "hsphere":
 +				$filled = false;
 +			case "sphere":
 +                                if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				if(!isset($filled)){
 +					$filled = true;
 +				}
 +				if(!isset($args[0]) or $args[0] == ""){
 +					$sender->sendMessage($this->prifks."Tip: //$cmd <block> <radius>.");
 +                                        return true;
 +					break;
 +				}
 +                                if(!isset($args[1]) or $args[1] == ""){
 +					$sender->sendMessage($this->prifks."Tip: //$cmd <block> <radius>.");
 +                                        return true;
 +					break;
 +				}
 +				$radius = abs(floatval($args[1]));
 +
 +				$session = $this->session($sender);
 +				$items = Item::fromString($args[0], true);
 +
 +				foreach($items as $item){
 +					if($item->getID() > 0xff){
 +						$sender->sendMessage($this->prifks."Not be executed");
 +					}
 +				}
 +				$this->W_sphere(new Position($sender->getX() - 0.5, $sender->getY(), $sender->getZ() - 0.5, $sender->getlevel()), $items, $radius, $radius, $radius, $filled, $sender);
 +                                return true;
 +				break;
 +			case "flower":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +                                if ($args[0] == "small"){
 +                                $player = $this->getServer()->getPlayer($sender->getName());
 +                		$this->getServer()->dispatchCommand($player, "/set 0,0,0,31:1,37,38,38:1,38:2,38:3,38:4,38:5,38:6,38:7,38:8");
 +                		return true;
 +                		break;
 +                	}
 +                	$sender->sendMessage($this->prifks."Tip: //flower <small>");
 +                	return true;
 +                	break;
 +                                
 +			case "set":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$count = $this->countBlocks($session["selection"]);
 +				if($count > $session["block-limit"] and $session["block-limit"] > 0){
 +					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to change $count block(s).");
 +                                        return true;
 +					break;
 +				}
 +				if(!isset($args[0]) or $args[0] == ""){
 +					$sender->sendMessage($this->prifks."Tip: //$cmd <block>.");
 +                                        return true;
 +					break;
 +				}
 +				$items = Item::fromString($args[0], true);
 +				foreach($items as $item){
 +					if($item->getID() > 0xff){
 +						$sender->sendMessage($this->prifks."Not be executed");
 +					}
 +				}
 +				$this->W_set($session["selection"], $items, $sender);
 +                                return true;
 +				break;
 +            case "undo":
 +                if(!($sender instanceof Player)){
 +                    $sender->sendMessage($this->prifks."Please use in the game");
 +                    break;
 +                }
 +                $this->W_undo($sender);
 +                return true;
 +                break;
 +            case "tree":
 +                	if(!($sender instanceof Player)){
 +                		$sender->sendMessage($this->prifks."Please use in the game");
 +                		break;
 +                	}
 +                	if ($args[0] == "small"){
 +                		$sender->sendMessage($this->prifks."SmallTree");
 +                		$this->maketree($sender, "small");
 +                		return true;
 +                		break;
 +                	}
 +                	if ($args[0] == "normal"){
 +                		$sender->sendMessage($this->prifks."NormalTree");
 +                		$this->maketree($sender, "normal");
 +                		return true;
 +                		break;
 +                	}
 +                	if ($args[0] == "big"){
 +                		$sender->sendMessage($this->prifks."BigTree");
 +                		$this->maketree($sender, "big");
 +                		return true;
 +                		break;
 +                	}
 +                	$sender->sendMessage($this->prifks."Tip: //tree <small,normal,big>");
 +                	return true;
 +                	break;
 +			case "replace":
 +				if(!($sender instanceof Player)){
 +                                $sender->sendMessage($this->prifks."Please use in the game");
 +                                return true;
 +                                break;
 +                                }
 +				$session = $this->session($sender);
 +				$count = $this->countBlocks($session["selection"]);
 +				if($count > $session["block-limit"] and $session["block-limit"] > 0){
 +					$sender->sendMessage($this->prifks."Block limit of ".$session["block-limit"]." exceeded, tried to change $count block(s).");
 +                                        return true;
 +					break;
 +				}
 +				if(!isset($args[0]) or $args[0] == ""){
 +					$sender->sendMessage($this->prifks."Tip: //$cmd <block1> <block2>.");
 +                                        return true;
 +					break;
 +				}
 +                                if(!isset($args[1]) or $args[1] == ""){
 +					$sender->sendMessage($this->prifks."Tip: //$cmd <block1> <block2>.");
 +                                        return true;
 +					break;
 +				}
 +				$item1 = Item::fromString($args[0]);
 +				if($item1->getID() > 0xff){
 +					$sender->sendMessage($this->prifks."Not be executed");
 +                                        return true;
 +					break;
 +				}
 +				$items2 = Item::fromString($args[1], true);
 +				foreach($items2 as $item){
 +					if($item->getID() > 0xff){
 +						$sender->sendMessage($this->prifks."pNot be executed");
 +					}
 +				}
 +
 +				$this->W_replace($session["selection"], $item1, $items2, $sender);
 +                                return true;
 +				break;
 +			default:
 +			case "help":
 +				$sender->sendMessage($this->prifks."//cut, //copy, //paste, //sphere, //hsphere, //desel, //limit, //pos1, //pos2, //set, //replace, //help, //wand, //toggleeditwand");
 +                                return true;
 +				break;
 +		}
 +            return true;
 +	}
 +
 +	private function countBlocks($selection, &$startX = null, &$startY = null, &$startZ = null){
 +		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
 +			return false;
 +		}
 +		$startX = min($selection[0][0], $selection[1][0]);
 +		$endX = max($selection[0][0], $selection[1][0]);
 +		$startY = min($selection[0][1], $selection[1][1]);
 +		$endY = max($selection[0][1], $selection[1][1]);
 +		$startZ = min($selection[0][2], $selection[1][2]);
 +		$endZ = max($selection[0][2], $selection[1][2]);
 +		return ($endX - $startX + 1) * ($endY - $startY + 1) * ($endZ - $startZ + 1);
 +	}
 +
 +	private function W_paste($clipboard, Position $pos, $player){
 +		$this->Datas = array();
 +		if(count($clipboard) !== 2){
 +			$player->sendMessage($this->prifks."Please refer to the copy first");
 +			return false;
 +		}
 +		$clipboard[0][0] += $pos->x - 0.5;
 +		$clipboard[0][1] += $pos->y;
 +		$clipboard[0][2] += $pos->z - 0.5;
 +		$offset = array_map("round", $clipboard[0]);
 +		$count = 0;
 +
 +		foreach($clipboard[1] as $x => $i){
 +			foreach($i as $y => $j){
 +				foreach($j as $z => $block){
 +                    $dx = $x + $offset[0];
 +                    $dy = $y + $offset[1];
 +                    $dz = $z + $offset[2];
 +				$data = array($dx, $dy, $dz, $player->getLevel(), $player->getLevel()->getBlock(new Vector3($x + $offset[0], $y + $offset[1], $z + $offset[2]))->getID(), $player->getLevel()->getBlock(new Vector3($x + $offset[0], $y + $offset[1], $z + $offset[2]))->getDamage());
 +				array_push($this->Datas, $data);
 +					$b = Block::get(ord($block{0}), ord($block{1}));
 +					$pos->level->setBlock(new Vector3($x + $offset[0], $y + $offset[1], $z + $offset[2]), $b, false);
 +                                        $count++;
 +					unset($b);
 +				}
 +			}
 +		}
 +		$player->sendMessage($this->prifks.$count."The number of the block was paste");
 +		return true;
 +	}
 +
 +	private function W_copy($selection, $player){
 +		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
 +			$player->sendMessage($this->prifks."Please select a range before");
 +			return array();
 +		}
 +		$level = $selection[0][3];
 +
 +		$blocks = array();
 +		$startX = min($selection[0][0], $selection[1][0]);
 +		$endX = max($selection[0][0], $selection[1][0]);
 +		$startY = min($selection[0][1], $selection[1][1]);
 +		$endY = max($selection[0][1], $selection[1][1]);
 +		$startZ = min($selection[0][2], $selection[1][2]);
 +		$endZ = max($selection[0][2], $selection[1][2]);
 +		$count = $this->countBlocks($selection);
 +		for($x = $startX; $x <= $endX; ++$x){
 +			$blocks[$x - $startX] = array();
 +			for($y = $startY; $y <= $endY; ++$y){
 +				$blocks[$x - $startX][$y - $startY] = array();
 +				for($z = $startZ; $z <= $endZ; ++$z){
 +					$b = $level->getBlock(new Vector3($x, $y, $z));
 +					$blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
 +					unset($b);
 +				}
 +			}
 +		}
 +		$player->sendMessage($this->prifks.$count."I copied the number of blocks");
 +		return $blocks;
 +	}
 +
 +	private function W_cut($selection, $player){
 +		 $this->Datas = array();
 +		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
 +			$player->sendMessage($this->prifks."Please select a range before");
 +			return array();
 +		}
 +		$totalCount = $this->countBlocks($selection);
 +		if($totalCount > 524288){
 +			$send = false;
 +		}else{
 +			$send = true;
 +		}
 +		$level = $selection[0][3];
 +
 +		$blocks = array();
 +		$startX = min($selection[0][0], $selection[1][0]);
 +		$endX = max($selection[0][0], $selection[1][0]);
 +		$startY = min($selection[0][1], $selection[1][1]);
 +		$endY = max($selection[0][1], $selection[1][1]);
 +		$startZ = min($selection[0][2], $selection[1][2]);
 +		$endZ = max($selection[0][2], $selection[1][2]);
 +		$count = $this->countBlocks($selection);
 +		for($x = $startX; $x <= $endX; ++$x){
 +			$blocks[$x - $startX] = array();
 +			for($y = $startY; $y <= $endY; ++$y){
 +				$blocks[$x - $startX][$y - $startY] = array();
 +				for($z = $startZ; $z <= $endZ; ++$z){
 +					$data = array($x, $y, $z, $player->getLevel(), $level->getBlock(new Vector3($x, $y, $z))->getID(), $level->getBlock(new Vector3($x, $y, $z))->getDamage());
 +					array_push($this->Datas, $data);
 +					$b = $level->getBlock(new Vector3($x, $y, $z));
 +					$blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getDamage());
 +					$level->setBlock(new Vector3($x, $y, $z), Block::get(0), false, $send);
 +					unset($b);
 +				}
 +			}
 +		}
 +		if($send === false){
 +			$forceSend = function($X, $Y, $Z){
 +				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
 +			};
 +			$forceSend->bindTo($level, $level);
 +			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
 +				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
 +					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
 +						$forceSend($X,$Y,$Z);
 +					}
 +				}
 +			}
 +		}
 +		$player->sendMessage($this->prifks.$count."It was deleted blocks");
 +		return $blocks;
 +	}
 +
 +	private function W_set($selection, $blocks, $player){
 +		 $this->Datas = array();
 +		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
 +			$player->sendMessage($this->prifks."Please select a range before");
 +			return false;
 +		}
 +		$totalCount = $this->countBlocks($selection);
 +		if($totalCount > 524288){
 +			$send = false;
 +		}else{
 +			$send = true;
 +		}
 +		$level = $selection[0][3];
 +		$bcnt = count($blocks) - 1;
 +		if($bcnt < 0){
 +			$player->sendMessage($this->prifks."Incorrect blocks.");
 +			return false;
 +		}
 +		$startX = min($selection[0][0], $selection[1][0]);
 +		$endX = max($selection[0][0], $selection[1][0]);
 +		$startY = min($selection[0][1], $selection[1][1]);
 +		$endY = max($selection[0][1], $selection[1][1]);
 +		$startZ = min($selection[0][2], $selection[1][2]);
 +		$endZ = max($selection[0][2], $selection[1][2]);
 +		$count = 0; //$count = $this->countBlocks($selection);
 +		for($x = $startX; $x <= $endX; ++$x){
 +			for($y = $startY; $y <= $endY; ++$y){
 +				for($z = $startZ; $z <= $endZ; ++$z){
 +					$b = $blocks[mt_rand(0, $bcnt)];
 +					$data = array($x, $y, $z, $player->getLevel(), $level->getBlock(new Vector3($x, $y, $z))->getID(), $level->getBlock(new Vector3($x, $y, $z))->getDamage());
 +					array_push($this->Datas, $data);
 +					 $level->setBlock(new Vector3($x, $y, $z), $b->getBlock(), false, $send);
 +                                         $count++;
 +				}
 +			}
 +		}
 +		if($send === false){
 +			$forceSend = function($X, $Y, $Z){
 +				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
 +			};
 +			$forceSend->bindTo($level, $level);
 +			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
 +				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
 +					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
 +						$forceSend($X,$Y,$Z);
 +					}
 +				}
 +			}
 +		}
 +		$player->sendMessage($this->prifks.$count."It has changed the number of block");
 +		return true;
 +	}
 +
 +	private function W_replace($selection, Item $block1, $blocks2, $player){
 +		 $this->Datas = array();
 +		if(!is_array($selection) or $selection[0] === false or $selection[1] === false or $selection[0][3] !== $selection[1][3]){
 +			$player->sendMessage($this->prifks."Please select a range before");
 +			return false;
 +		}
 +
 +		$totalCount = $this->countBlocks($selection);
 +		if($totalCount > 524288){
 +			$send = false;
 +		}else{
 +			$send = true;
 +		}
 +		$level = $selection[0][3];
 +		$id1 = $block1->getID();
 +		$meta1 = $block1->getDamage();
 +
 +		$bcnt2 = count($blocks2) - 1;
 +		if($bcnt2 < 0){
 +			$player->sendMessage($this->prifks."Incorrect blocks.");
 +			return false;
 +		}
 +
 +		$startX = min($selection[0][0], $selection[1][0]);
 +		$endX = max($selection[0][0], $selection[1][0]);
 +		$startY = min($selection[0][1], $selection[1][1]);
 +		$endY = max($selection[0][1], $selection[1][1]);
 +		$startZ = min($selection[0][2], $selection[1][2]);
 +		$endZ = max($selection[0][2], $selection[1][2]);
 +		$count = 0;
 +		for($x = $startX; $x <= $endX; ++$x){
 +			for($y = $startY; $y <= $endY; ++$y){
 +				for($z = $startZ; $z <= $endZ; ++$z){
 +					$data = array($x, $y, $z, $player->getLevel(), $level->getBlock(new Vector3($x, $y, $z))->getID(), $level->getBlock(new Vector3($x, $y, $z))->getDamage());
 +					array_push($this->Datas, $data);
 +					$b = $level->getBlock(new Vector3($x, $y, $z));
 +					if($b->getID() === $id1 and ($meta1 === false or $b->getDamage() === $meta1)){
 +						$level->setBlock($b, $blocks2[mt_rand(0, $bcnt2)]->getBlock(), false, $send);
 +                                                $count++;
 +					}
 +					unset($b);
 +				}
 +			}
 +		}
 +		if($send === false){
 +			$forceSend = function($X, $Y, $Z){
 +				$this->changedCount[$X.":".$Y.":".$Z] = 4096;
 +			};
 +			$forceSend->bindTo($level, $level);
 +			for($X = $startX >> 4; $X <= ($endX >> 4); ++$X){
 +				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++$Y){
 +					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++$Z){
 +						$forceSend($X,$Y,$Z);
 +					}
 +				}
 +			}
 +		}
 +		$player->sendMessage($this->prifks.$count."It has changed the number of the block");
 +		return true;
 +	}
 +
 +	public function lengthSq($x, $y, $z){
 +		return ($x * $x) + ($y * $y) + ($z * $z);
 +	}
 +
 +	private function W_sphere(Position $pos, $blocks, $radiusX, $radiusY, $radiusZ, $filled = true, $player){
 +		 $this->Datas = array();
 +		$count = 0;
 +
 +        $radiusX += 0.5;
 +        $radiusY += 0.5;
 +        $radiusZ += 0.5;
 +
 +        $invRadiusX = 1 / $radiusX;
 +        $invRadiusY = 1 / $radiusY;
 +        $invRadiusZ = 1 / $radiusZ;
 +
 +        $ceilRadiusX = (int) ceil($radiusX);
 +        $ceilRadiusY = (int) ceil($radiusY);
 +        $ceilRadiusZ = (int) ceil($radiusZ);
 +
 +		$bcnt = count($blocks) - 1;
 +
 +        $nextXn = 0;
 +		$breakX = false;
 +		for($x = 0; $x <= $ceilRadiusX and $breakX === false; ++$x){
 +			$xn = $nextXn;
 +			$nextXn = ($x + 1) * $invRadiusX;
 +			$nextYn = 0;
 +			$breakY = false;
 +			for($y = 0; $y <= $ceilRadiusY and $breakY === false; ++$y){
 +				$yn = $nextYn;
 +				$nextYn = ($y + 1) * $invRadiusY;
 +				$nextZn = 0;
 +				$breakZ = false;
 +				for($z = 0; $z <= $ceilRadiusZ; ++$z){
 +					$zn = $nextZn;
 +					$nextZn = ($z + 1) * $invRadiusZ;
 +					$distanceSq = $this->lengthSq($xn, $yn, $zn);
 +					if($distanceSq > 1){
 +						if($z === 0){
 +							if($y === 0){
 +								$breakX = true;
 +								$breakY = true;
 +								break;
 +							}
 +							$breakY = true;
 +							break;
 +						}
 +						break;
 +					}
 +
 +					if($filled === false){
 +						if($this->lengthSq($nextXn, $yn, $zn) <= 1 and $this->lengthSq($xn, $nextYn, $zn) <= 1 and $this->lengthSq($xn, $yn, $nextZn) <= 1){
 +							continue;
 +						}
 +					}
 +					$data = array($pos->add($x, $y, $z)->x, $pos->add($x, $y, $z)->y, $pos->add($x, $y, $z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, $y, $z)->x, $pos->add($x, $y, $z)->y, $pos->add($x, $y, $z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, $y, $z)->x, $pos->add($x, $y, $z)->y, $pos->add($x, $y, $z)->z))->getDamage());
 +					$data2 = array($pos->add(-$x, $y, $z)->x, $pos->add(-$x, $y, $z)->y, $pos->add(-$x, $y, $z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, $y, $z)->x, $pos->add(-$x, $y, $z)->y, $pos->add(-$x, $y, $z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, $y, $z)->x, $pos->add(-$x, $y, $z)->y, $pos->add(-$x, $y, $z)->z))->getDamage());
 +					$data3 = array($pos->add($x, -$y, $z)->x, $pos->add($x, -$y, $z)->y, $pos->add($x, -$y, $z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, -$y, $z)->x, $pos->add($x, -$y, $z)->y, $pos->add($x, -$y, $z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, -$y, $z)->x, $pos->add($x, -$y, $z)->y, $pos->add($x, -$y, $z)->z))->getDamage());
 +					$data4 = array($pos->add($x, $y, -$z)->x, $pos->add($x, $y, -$z)->y, $pos->add($x, $y, -$z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, $y, -$z)->x, $pos->add($x, $y, -$z)->y, $pos->add($x, $y, -$z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, $y, -$z)->x, $pos->add($x, $y, -$z)->y, $pos->add($x, $y, -$z)->z))->getDamage());
 +					$data5 = array($pos->add(-$x, -$y, $z)->x, $pos->add(-$x, -$y, $z)->y, $pos->add(-$x, -$y, $z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, -$y, $z)->x, $pos->add(-$x, -$y, $z)->y, $pos->add(-$x, -$y, $z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, -$y, $z)->x, $pos->add(-$x, -$y, $z)->y, $pos->add(-$x, -$y, $z)->z))->getDamage());
 +					$data6 = array($pos->add($x, -$y, -$z)->x, $pos->add($x, -$y, -$z)->y, $pos->add($x, -$y, -$z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, -$y, -$z)->x, $pos->add($x, -$y, -$z)->y, $pos->add($x, -$y, -$z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add($x, -$y, -$z)->x, $pos->add($x, -$y, -$z)->y, $pos->add($x, -$y, -$z)->z))->getDamage());
 +					$data7 = array($pos->add(-$x, $y, -$z)->x, $pos->add(-$x, $y, -$z)->y, $pos->add(-$x, $y, -$z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, $y, -$z)->x, $pos->add(-$x, $y, -$z)->y, $pos->add(-$x, $y, -$z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, $y, -$z)->x, $pos->add(-$x, $y, -$z)->y, $pos->add(-$x, $y, -$z)->z))->getDamage());
 +					$data8 = array($pos->add(-$x, -$y, -$z)->x, $pos->add(-$x, -$y, -$z)->y, $pos->add(-$x, -$y, -$z)->z, $pos->getLevel(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, -$y, -$z)->x, $pos->add(-$x, -$y, -$z)->y, $pos->add(-$x, -$y, -$z)->z))->getID(), $pos->getLevel()->getBlock(new Vector3($pos->add(-$x, -$y, -$z)->x, $pos->add(-$x, -$y, -$z)->y, $pos->add(-$x, -$y, -$z)->z))->getDamage());
 +					array_push($this->Datas, $data, $data2, $data3, $data4, $data5, $data6, $data7, $data8);
 +
 +					$pos->level->setBlock($pos->add($x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add(-$x, $y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add($x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add($x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add(-$x, -$y, $z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add($x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add(-$x, $y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$pos->level->setBlock($pos->add(-$x, -$y, -$z), $blocks[mt_rand(0, $bcnt)]->getBlock(), false);
 +					$count++;
 +				}
 +			}
 +		}
 +
 +		$player->sendMessage($this->prifks.$count."It has changed the number of the block");
 +		return true;
 +	}
 +    public function W_undo($player){
 +            $count = 0;
 +            foreach($this->Datas as $data){
 +            $x = $data[0];
 +            $y = $data[1];
 +            $z = $data[2];
 +            $level = $data[3];
 +            $id = $data[4];
 +            $meta = $data[5];
 +            $level->setBlock(new Vector3($x, $y, $z), Block::get($id,$meta));
 +            $count++;
 +            }
 +            $player->sendMessage($this->prifks.$count."It was to restore the number of blocks");
 +    }
 +}
