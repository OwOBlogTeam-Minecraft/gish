<?php

/*                             Copyright (c) 2017-2018 TeaTech All right Reserved.
 *
 *      ████████████  ██████████           ██         ████████  ██           ██████████    ██          ██
 *           ██       ██                 ██  ██       ██        ██          ██        ██   ████        ██
 *           ██       ██                ██    ██      ██        ██          ██        ██   ██  ██      ██
 *           ██       ██████████       ██      ██     ██        ██          ██        ██   ██    ██    ██
 *           ██       ██              ████████████    ██        ██          ██        ██   ██      ██  ██
 *           ██       ██             ██          ██   ██        ██          ██        ██   ██        ████
 *           ██       ██████████    ██            ██  ████████  ██████████   ██████████    ██          ██
**/

namespace gish;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\entity\Snowball;
use pocketmine\entity\PrimedTNT;

class Main extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener
{
	private $tsapi = null;
	
	public $players = [];
	public $tps;
	
	public function onEnable()
	{
		if(!$this->getServer()->getPluginManager()->getPlugin("TSeriesAPI"))
		{
			$this->getLogger()->info(self::NORMAL_PRE."§c服务器无法找到所依赖的插件!");
			$this->getLogger()->info(self::NORMAL_PRE."§c本插件已卸载.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return null;
		}
		else $this->tsapi = $this->getServer()->getPluginManager()->getPlugin("TSeriesAPI")->setMeEnable($this);
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		/* $this->getLogger()->info("§9┌────────────────────────────┐");
		$this->getLogger()->info("§9│§a鱼竿枪已启用，准备开战吧    §9│§b──插件by: Wii54");
		$this->getLogger()->info("§9└────────────────────────────┘"); */
		$this->getLogger()->info("§a本插件已通过PM开发者§bTeaclon§f(§e锤子§f)§a兼容至 §6PM v1.4.x §a及更高游戏版本.");
		$this->getLogger()->info("§e原作者: §bWii54");
		$this->getLogger()->info("§c注意: 本插件仅对源代码的§e缩进格式§c以及§e原本不兼容的API进行调整§f/§e更改§c, §l并没有修改原作者的版权§r§c, 请其他开发者注意此事项.");
	}
	
	
	
	
	
	public function onEntityShootBow(EntityShootBowEvent $event)
	{
		$event->getProjectile()->setMotion($event->getProjectile()->getMotion()->multiply(11));
		$this->tsapi->getTaskManager()->createCallbackTask($event->getProjectile(), "scheduleDelayedTask", "close", [], 4, \false);
	}
	
	public function onPlayerTouch(PlayerInteractEvent $event)
	{
		$p   = $event->getPlayer();
		$id  = $event->getItem()->getId();
		// $t   = time();
		$bid = $event->getBlock()->getId();
		$this->tps = $this->getServer()->getTicksPerSecond();
		switch($id) 
		{
			case "346":
				$type = "Snowball";
				$gun = 290;
				if(($bid !== 0))
				{
					if(in_array($p->getName(), $this->players)) $p->sendMessage("§4鱼竿枪太热了, 要冷一冷!");
					else
					{
						$this->players[] = $p->getName();
						$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleDelayedTask", "removePlayer", [$p->getName()], $this->tps / 3, \false);
						$this->trhowBow($p, $type, $gun, 4, 3);
					}
				}
				else
				{
					$this->trhowBow($p, $type, $gun, 4, 3);
					$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleDelayedTask", "trhowBow", [$p, $type, $gun, 4, 3], $this->tps * (1.3 / 3), \false);
					$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleDelayedTask", "trhowBow", [$p, $type, $gun, 4, 3], $this->tps * (2.8 / 3), \false);
					$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleDelayedTask", "trhowBow", [$p, $type, $gun, 4, 3], $this->tps * (4 / 3), \false);
				}
			return false;
		}
	}
	
	public function onExplosionPrime(ExplosionPrimeEvent $event)
	{
		$event->setBlockBreaking(false);
	}
	
	public function onEntityDespawn(EntityDespawnEvent $event)
	{
		$entity = $event->getEntity();
		if($event->getType() === 81)
		{
			// $player = $entity->shootingEntity;
			// if(!$player instanceof Player) return;
			if($entity->getMaxHealth() == 3)
			{
				$posTo = $entity->getPosition();
				$level = $entity->getLevel();
				$v3 = $entity->getPosition()->add(0, 1, 0);
				$chunk = $entity->chunk;
				$nbt = $this->getNBT($v3);
				$tnt = new PrimedTNT($chunk, $nbt);
				$tnt->setPosition($v3);
				$tnt->spawnToAll();
				$this->tsapi->getTaskManager()->createCallbackTask($tnt, "scheduleDelayedTask", "explode", [], $this->tps / 6, \false);
				$tnt->kill();
				$entity->kill();
			}
		}
	}
	
	// 当玩家射出的雪球砸到其他玩家时触发这个事件;
	public function onShootDamage(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			if($event->getDamager() instanceof PrimedTNT) $event->getEntity()->setOnFire(4);
			if($event->getCause() == 2 and $event->getDamage() == 25) $event->setDamage(4);
			if($event->getCause() == 2 and $event->getDamage() == 0)
			{
				$event->getEntity()->setHealth($event->getEntity()->getHealth() - 2);
				$event->setCancelled();
			}
		}
	}
	
	
	// 当玩家手持钓鱼竿时触发这个事件(手持物品ID为346时);
	public function onGunsHold(PlayerItemHeldEvent $event)
	{
		$item = $event->getItem()->getID();
		switch($item)
		{
			case "346":
				$event->getPlayer()->sendMessage("点击钓鱼键开火");
				return false;
			break;
		}
	}
	
	
	
	
	
	
	
	
	
	public function removePlayer(string $name)
	{
		$founded = array_search($name, $this->players);
		if($founded !== false) array_splice($this->players, $founded, 1);
		unset($founded);
	}
	
	
	public function trhowBow(Player $p, $type, int $gun, $s, $time = 5)
	{
		$nbt = new CompoundTag("", 
		[
			new ListTag("Pos", 
			[
				new DoubleTag("", $p->x),
				new DoubleTag("", $p->y + $p->getEyeHeight()),
				new DoubleTag("", $p->z)
			]),
			new ListTag("Motion", 
			[
				new DoubleTag("", -sin($p->yaw / 180 * M_PI) * cos($p->pitch / 180 * M_PI)),
				new DoubleTag("", -sin($p->pitch / 180 * M_PI)),
				new DoubleTag("", cos($p->yaw / 180 * M_PI) * cos($p->pitch / 180 * M_PI))
			]),
			new ListTag("Rotation", 
			[
				new FloatTag("", $p->yaw),
				new FloatTag("", $p->pitch)
			]),
		]); 
		$snowball = \pocketmine\entity\Entity::createEntity($type, $p->getLevel(), $nbt, $p);
		$snowball->setMotion($snowball->getMotion()->multiply($s));
		
		if($gun == 331)
		{
			$snowball->setOnFire(4);
			$snowball->setMaxHealth(2);
		}
		if($gun == 318) $snowball->setMaxHealth(3);
		
		$this->tsapi->getTaskManager()->createCallbackTask($snowball, "scheduleDelayedTask", "close", [], $this->tps * $time, \false);
		
		if($snowball instanceof Projectile)
		{
			$p->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($snowball));
			($projectileEv->isCancelled()) ? $snowball->kill() : $snowball->spawnToAll();
		}
		else $snowball->spawnToAll();
	}
	
	
	
	
	
	
	
	
	public function getNBT($v3)
	{
		$nbt = new CompoundTag("", 
		[
			new ListTag("Pos", 
			[
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			new Enum("Motion", 
			[
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			new Enum("Rotation", 
			[
				new FloatTag("", 0),
				new FloatTag("", 0)
			]),
		]);
		return $nbt;
	}
}
?>