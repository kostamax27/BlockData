<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use BadMethodCallException;
use pocketmine\plugin\Plugin;
use pocketmine\world\World;

final class BlockDataWorldManager{

	public static function create(Plugin $plugin, ?string $directory = null) : BlockDataWorldManager{
		static $created = [];
		if(isset($created[$name = $plugin->getName()])){
			throw new BadMethodCallException("Tried to create BlockDataWorldManager twice as " . $name);
		}

		$created[$name] = true;
		$instance = new self($directory ?? $plugin->getDataFolder());
		$plugin->getServer()->getPluginManager()->registerEvents(new BlockDataWorldListener($plugin, $instance), $plugin);
		return $instance;
	}

	/** @var array<int, BlockDataWorld> */
	private array $worlds = [];

	private function __construct(
		private string $directory,
	){}

	public function isLoaded(World $world) : bool{
		return isset($this->worlds[$world->getId()]);
	}

	public function load(World $world) : BlockDataWorld{
		return $this->worlds[$world->getId()] ??= new BlockDataWorld($this->directory, $world);
	}

	public function unload(World $world) : void{
		$this->worlds[$id = $world->getId()]->close();
		unset($this->worlds[$id]);
	}

	public function unloadAll() : void{
		foreach($this->worlds as $instance){
			$this->unload($instance->getWorld());
		}
	}

	public function get(World $world) : BlockDataWorld{
		return $this->worlds[$world->getId()];
	}

	public function save(World $world) : void{
		$this->worlds[$world->getId()]->save();
	}
}