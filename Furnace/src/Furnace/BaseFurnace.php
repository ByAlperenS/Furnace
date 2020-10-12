<?php


/*

Plugin Author: ByAlperenS

Messenger: Alperen Sancak
Facebook: Alperen Sancak
Discord: ByAlperenS#5361

*/


namespace Furnace;

use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\block\BlockBreakEvent as BBE;
use pocketmine\event\block\BlockPlaceEvent as BPE;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\event\player\PlayerInteractEvent as PIE;
use pocketmine\event\player\{PlayerJoinEvent as PJE, PlayerQuitEvent as PQE};
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\tile\Tile;
use pocketmine\tile\Furnace;
use pocketmine\block\BurningFurnace;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\inventory\FurnaceBurnEvent as FBE;
use pocketmine\event\inventory\FurnaceSmeltEvent as FSE;
use pocketmine\inventory\FurnaceInventory;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use Furnace\Menus\FurnaceUpgradeMenu;

// Needs EconomyAPI to work

use onebone\economyapi\EconomyAPI;

class BaseFurnace extends PluginBase implements Listener
{
    
    /** @var bool */
	private $cancel_send = true;

    public $title = C::GREEN . "FastFurnace > ";

    private static $instance;
	
	public function onLoad(){
		self::$instance = $this;
	}
	
	public static function getInstance(): BaseFurnace{
		return self::$instance;
	}

    public $config;

    public function onEnable()
    {
        $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if (!$economy instanceof EconomyAPI) {
            $this->getLogger()->critical("You need EconomyAPI (https://poggit.pmmp.io/p/EconomyAPI/)");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("Plugin Enable - ByAlperenS");
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "Furnaces.yml", Config::YAML);
    }

    public function onBlockPlace(BPE $e)
    {
        $p = $e->getPlayer();
        $block = $e->getBlock();
        $coordinate = $block->getX() . ":" . $block->getY() . ":" . $block->getZ();
        $this->config->set($coordinate, [
            "Level" => 0,
            "Owner" => $p->getName(),
            "Coordinates" => $coordinate
        ]);
        $this->config->save();
    }

    public function onPlayerQuit(PQE $e)
    {
        $p = $e->getPlayer();

        $this->config->save();
    }

    public function onBlockBreak(BBE $e)
    {
        $p = $e->getPlayer();
        $block = $e->getBlock();
        $itemhand = $p->getInventory()->getItemInHand();
        $coordinate = $block->getX() . ":" . $block->getY() . ":" . $block->getZ();

        if ($block->getId() == Block::FURNACE or $block->getId() == Block::BURNING_FURNACE) {
            if ($itemhand->getId() == Item::WOODEN_PICKAXE) {
                if ($p->getName() == $this->config->get($coordinate)["Owner"]) {
                    $e->setCancelled();
                    $menu = new FurnaceUpgradeMenu($coordinate);
                    $menu->sendTo($p);
                }else {
                    $e->setCancelled();
                    $p->sendMessage($this->title . C::GRAY . "You Are Not The Owner Of This Furnace !");
                }
            }else {
                if ($p->getName() == $this->config->get($coordinate)["Owner"]) {
                    $this->config->remove($coordinate);
                    $this->config->save();
                    $p->sendMessage($this->title . C::GRAY . "Furnace Level Have Been Reset !");
                }else {
                    $e->setCancelled();
                    $p->sendMessage($this->title . C::GRAY . "You Are Not The Owner Of This Furnace !");
                }   
            }
        }
    }

    public function onDataPacketSend(DataPacketSendEvent $event) : void{
		if($this->cancel_send && $event->getPacket() instanceof ContainerClosePacket){
			$event->setCancelled();
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		if($event->getPacket() instanceof ContainerClosePacket){
			$this->cancel_send = false;
			$event->getPlayer()->sendDataPacket($event->getPacket(), false, true);
			$this->cancel_send = true;
		}
	}

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function onFurnaceBurn(FBE $e)
    {
        $furnace = $e->getFurnace();
        $block = $e->getBlock();
        $coordinate = $block->getX() . ":" . $block->getY() . ":" . $block->getZ();
        $owner = $this->config->get($coordinate)["Owner"];
        $level = $this->config->get($coordinate)["Level"];
        $newcoordinate = $this->config->get($coordinate)["Coordinates"];
        $separate = explode(":", $newcoordinate);

        if ($furnace instanceof Furnace) {
            if ($separate[0] == $block->getX() and $separate[1] == $block->getY() and $separate[2] == $block->getZ()) { 
                switch ($level) {
                    case '0':
                        // Don't Touch
                        break;
                    case '1':
                        $e->setBurnTime(300);
                        $e->setBurning(true);
                        break;
                    case '2':
                        $e->setBurnTime(600);
                        $e->setBurning(true);
                        break;
                    case '3':
                        $e->setBurnTime(900);
                        $e->setBurning(true);
                        break;
                    
                    default:
                        break;
                }
            }
        }
    }

    public function onSmelt(FSE $e)
    {
        $block = $e->getBlock();
        $furnace = $e->getFurnace();
        $coordinate = $block->getX() . ":" . $block->getY() . ":" . $block->getZ();
        $owner = $this->config->get($coordinate)["Owner"];
        $level = $this->config->get($coordinate)["Level"];
        $newcoordinate = $this->config->get($coordinate)["Coordinates"];
        $separate = explode(":", $newcoordinate);

        if ($furnace instanceof Furnace) {
            if ($separate[0] == $block->getX() and $separate[1] == $block->getY() and $separate[2] == $block->getZ()) {
                switch ($level) {
                    case '0':
                        // Don't Touch
                        break;
                    case '1':
                        $itemid = $e->getResult()->getId();
                        $itemmeta = $e->getResult()->getDamage();
                        $itemcount = $e->getResult()->getCount() + 1;
                        $newitem = Item::get($itemid, $itemmeta, $itemcount);
                        $e->setResult($newitem);
                        break;
                    case '2':
                        $itemid = $e->getResult()->getId();
                        $itemmeta = $e->getResult()->getDamage();
                        $itemcount = $e->getResult()->getCount() + 2;
                        $newitem = Item::get($itemid, $itemmeta, $itemcount);
                        $e->setResult($newitem);
                        break;
                    case '3':
                        $itemid = $e->getResult()->getId();
                        $itemmeta = $e->getResult()->getDamage();
                        $itemcount = $e->getResult()->getCount() + 3;
                        $newitem = Item::get($itemid, $itemmeta, $itemcount);
                        $e->setResult($newitem);
                        break;
                    
                    default:
                        break;
                }
            }
        }
    }
}