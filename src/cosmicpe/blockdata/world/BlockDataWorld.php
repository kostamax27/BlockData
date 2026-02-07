<?php

declare(strict_types=1);

namespace cosmicpe\blockdata\world;

use cosmicpe\blockdata\BlockData;
use LevelDB;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;

final class BlockDataWorld{

	private BigEndianNbtSerializer $serializer;

	private World $world;

	private LevelDB $database;

	/** @var array<int, BlockDataChunk> */
	private array $chunks = [];

	public function __construct(string $directory, World $world){
		$this->serializer = new BigEndianNbtSerializer();
		$this->world = $world;

		$this->database = new LevelDB(Path::join($directory, $world->getFolderName()), [
			"compression" => LEVELDB_SNAPPY_COMPRESSION,
			"block_size" => 64 * 1024
		]);
	}

	public function getWorld() : World{
		return $this->world;
	}

	public function getBlockData(Vector3 $pos) : ?BlockData{
		return $this->getBlockDataAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
	}

	public function getBlockDataAt(int $x, int $y, int $z) : ?BlockData{
		return $this->chunks[World::chunkHash($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)]->getBlockDataAt($x, $y, $z);
	}

	public function setBlockData(Vector3 $pos, ?BlockData $data) : void{
		$this->setBlockDataAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $data);
	}

	public function setBlockDataAt(int $x, int $y, int $z, ?BlockData $data) : void{
		$this->chunks[World::chunkHash($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)]->setBlockDataAt($x, $y, $z, $data);
	}

	public function loadChunk(int $chunkX, int $chunkZ) : void{
		$this->chunks[World::chunkHash($chunkX, $chunkZ)] = new BlockDataChunk($this->database, $this->serializer);
	}

	public function unloadChunk(int $chunkX, int $chunkZ, bool $save = true) : void{
		$hash = World::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$hash])){
			if($save){
				$this->chunks[$hash]->save();
			}
			unset($this->chunks[$hash]);
		}
	}

	public function save() : void{
		foreach($this->chunks as $chunk){
			$chunk->save();
		}
	}

	public function close() : void{
		$save = $this->world->getAutoSave();
		foreach($this->chunks as $hash => $chunk){
			World::getXZ($hash, $chunkX, $chunkZ);
			$this->unloadChunk($chunkX, $chunkZ, $save);
		}
		unset($this->database);
	}
}