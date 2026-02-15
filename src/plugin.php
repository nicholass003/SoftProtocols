<?php

/*
 * Copyright (c) 2024 - present nicholass003
 *        _      _           _                ___   ___ ____
 *       (_)    | |         | |              / _ \ / _ \___ \
 *  _ __  _  ___| |__   ___ | | __ _ ___ ___| | | | | | |__) |
 * | '_ \| |/ __| '_ \ / _ \| |/ _` / __/ __| | | | | | |__ <
 * | | | | | (__| | | | (_) | | (_| \__ \__ \ |_| | |_| |__) |
 * |_| |_|_|\___|_| |_|\___/|_|\__,_|___/___/\___/ \___/____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  nicholass003
 * @link    https://github.com/nicholass003/
 *
 *
 */

declare(strict_types=1);

namespace Nicholass003\SoftProtocols;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\plugin\PluginBase;
use function in_array;

final class SoftProtocols extends PluginBase implements Listener{

	public const SUPPORTED_PROTOCOLS = [
		860, // v1.21.124
		859, // v1.21.120 - v1.21.123
	];

	public const MINECRAFT_VERSIONS = [
		860 => "v1.21.124",
		859 => "v1.21.120 - v1.21.123",
		819 => "v1.21.93",
		818 => "v1.21.90 - v1.20.92",
	];

	protected function onLoad() : void{
		$this->saveDefaultConfig();
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 * @handleCancelled true
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof RequestNetworkSettingsPacket){
			$protocolVersion = $packet->getProtocolVersion();
			if($protocolVersion !== ProtocolInfo::CURRENT_PROTOCOL && ($protocolVersion < ProtocolInfo::CURRENT_PROTOCOL || $protocolVersion > ProtocolInfo::CURRENT_PROTOCOL) && in_array($protocolVersion, $this->getConfig()->get("supported-protocols", self::SUPPORTED_PROTOCOLS), true)){
				$reflectionClass = new \ReflectionClass($packet);
				$protocolVersionProperty = $reflectionClass->getProperty("protocolVersion");
				$protocolVersionProperty->setAccessible(true);
				$protocolVersionProperty->setValue($packet, ProtocolInfo::CURRENT_PROTOCOL);
			}
		}
	}
}
