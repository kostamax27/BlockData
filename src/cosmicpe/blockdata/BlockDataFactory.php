<?php

declare(strict_types=1);

namespace cosmicpe\blockdata;

use InvalidArgumentException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Utils;

final class BlockDataFactory{

	private const TAG_BLOCK_TYPE = "Type";
	private const TAG_BLOCK_DATA = "Data";

	/** @phpstan-var array<string, class-string<BlockData>> */
	private static array $types = [];

	/** @phpstan-var array<class-string<BlockData>, string> */
	private static array $class_types = [];

	/**
	 * @phpstan-param class-string<BlockData> $class
	 */
	public static function register(string $type, string $class) : void{
		Utils::testValidInstance($class, BlockData::class);
		self::$types[$type] = $class;
		self::$class_types[$class] = $type;
	}

	public static function nbtDeserialize(CompoundTag $tag) : ?BlockData{
		return isset(self::$types[$type = $tag->getString(self::TAG_BLOCK_TYPE)]) ? self::$types[$type]::nbtDeserialize($tag->getCompoundTag(self::TAG_BLOCK_DATA)) : null;
	}

	public static function nbtSerialize(BlockData $data) : CompoundTag{
		if(!isset(self::$class_types[$class = get_class($data)])){
			throw new InvalidArgumentException("BlockData type " . $class . " is not registered");
		}
		return CompoundTag::create()
			->setString(self::TAG_BLOCK_TYPE, self::$class_types[$class])
			->setTag(self::TAG_BLOCK_DATA, $data->nbtSerialize());
	}
}