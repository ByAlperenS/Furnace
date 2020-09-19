<?php

namespace Furnace\Menus;

use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\TextFormat as C;
use Furnace\BaseFurnace;

class FurnaceUpgradeMenu
{

    public $price = [
        "Level-1" => 2500,
        "Level-2" => 4500,
        "Level-3" => 6500
    ];

    public $title = C::GREEN . "FastFurnace > ";

    private $menu;

    private $coordinate;

    public function __construct($coordinate)
    {
        $config = BaseFurnace::getInstance()->getConfig();
        $owner = $config->get($coordinate)["Owner"];
        $level = $config->get($coordinate)["Level"];

        $menu = new InvMenu(InvMenu::TYPE_CHEST);
        $menu->setName("Furnace Settings");
        $menu->readonly();
        $menu->setListener([$this, "onClick"]);
        for ($i=0; $i < 27; $i++) { 
            $menu->getInventory()->setItem($i, Item::get(160,8,1)->setCustomName("A"));
        }
        $menu->getInventory()->setItem(11, Item::get(61,0,1)->setCustomName(C::YELLOW . "Furnace Owner: " . C::GRAY . $owner));
        $menu->getInventory()->setItem(13, Item::get(384,0,1)->setCustomName(C::YELLOW . "Furnace Level: " . C::GRAY . $level));

        switch ($level) {
            case '0':
                $menu->getInventory()->setItem(15, Item::get(388,0,1)->setCustomName(C::YELLOW . "Furnace Level Upgrade")->setLore([C::YELLOW . "Price: " . C::GRAY . $this->price["Level-1"]]));
                break;
            case '1':
                $menu->getInventory()->setItem(15, Item::get(388,0,1)->setCustomName(C::YELLOW . "Furnace Level Upgrade")->setLore([C::YELLOW . "Price: " . C::GRAY . $this->price["Level-2"]]));
                break;
            case '2':
                $menu->getInventory()->setItem(15, Item::get(388,0,1)->setCustomName(C::YELLOW . "Furnace Level Upgrade")->setLore([C::YELLOW . "Price: " . C::GRAY . $this->price["Level-3"]]));
                break;
            
            default:
                $menu->getInventory()->setItem(15, Item::get(388,0,1)->setCustomName(C::YELLOW . "Furnace Is Already Maximum Level !"));
                break;
        }

        $menu->getInventory()->setItem(22, Item::get(339,0,1)->setCustomName(C::RED . "If You Break The Furnace The Levels Will Reset !"));
        $this->menu = $menu;
        $this->coordinate = $coordinate;
    }

    public function onClick(Player $p, Item $itemClick, Item $itemClickWith, SlotChangeACtion $action): bool
    {
        $config = BaseFurnace::getInstance()->getConfig();
        $owner = $config->get($this->coordinate)["Owner"];
        $level = $config->get($this->coordinate)["Level"];

        if ($itemClick->getId() == 388) {
            switch ($level) {
                case '0':
                    if (EconomyAPI::getInstance()->myMoney($p) >= $this->price["Level-1"]) {
                        EconomyAPI::getInstance()->reduceMoney($p, $this->price["Level-1"]);
                        $new = str_replace($config->get($this->coordinate)["Level"], 1, $config->get($this->coordinate)["Level"]);
                        $config->set($this->coordinate, [
                            "Level" => $new,
                            "Owner" => $p->getName(),
                            "Coordinates" => $this->coordinate
                        ]);
                        $config->save();
                        $p->removeWindow($action->getInventory());
                        $p->sendMessage($this->title . C::GRAY . "The Furnace Level Has Been Raised Successfully !");
                    }else {
                        $p->sendMessage($this->title . C::GRAY . "You Have No Money !");
                        $p->removeWindow($action->getInventory());
                    }
                    break;
                case '1':
                    if (EconomyAPI::getInstance()->myMoney($p) >= $this->price["Level-2"]) {
                        EconomyAPI::getInstance()->reduceMoney($p, $this->price["Level-2"]);
                        $new = str_replace($config->get($this->coordinate)["Level"], 2, $config->get($this->coordinate)["Level"]);
                        $config->set($this->coordinate, [
                            "Level" => $new,
                            "Owner" => $p->getName(),
                            "Coordinates" => $this->coordinate
                        ]);
                        $config->save();
                        $p->removeWindow($action->getInventory());
                        $p->sendMessage($this->title . C::GRAY . "The Furnace Level Has Been Raised Successfully !");
                    }else {
                        $p->sendMessage($this->title . C::GRAY . "You Have No Money !");
                        $p->removeWindow($action->getInventory());
                    }
                    break;
                case '2':
                    if (EconomyAPI::getInstance()->myMoney($p) >= $this->price["Level-3"]) {
                        EconomyAPI::getInstance()->reduceMoney($p, $this->price["Level-3"]);
                        $new = str_replace($config->get($this->coordinate)["Level"], 3, $config->get($this->coordinate)["Level"]);
                        $config->set($this->coordinate, [
                            "Level" => $new,
                            "Owner" => $p->getName(),
                            "Coordinates" => $this->coordinate
                        ]);
                        $config->save();
                        $p->removeWindow($action->getInventory());
                        $p->sendMessage($this->title . C::GRAY . "The Furnace Level Has Been Raised Successfully !");
                    }else {
                        $p->sendMessage($this->title . C::GRAY . "You Have No Money !");
                        $p->removeWindow($action->getInventory());
                    }
                    break;
                
                default:
                    $p->removeWindow($action->getInventory());
                    $p->sendMessage($this->title . C::GRAY . "Furnace Is Already Maximum Level !");
                    break;
            }
        }
        return true;
    }

    public function sendTo(Player $p): void
    {
        $this->menu->send($p);
    }
}