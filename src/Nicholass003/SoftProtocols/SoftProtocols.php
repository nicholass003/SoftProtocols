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

use Nicholass003\SoftProtocols\Network\Handler\SessionStartPacketHandlerProtocols;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\plugin\PluginBase;
use function in_array;

final class SoftProtocols extends PluginBase implements Listener{

	/** @var array<string, array{session: bool, buffer: string}> */
	private array $pendingSessions = [];

	public const SUPPORTED_PROTOCOLS = [
		818, // v1.21.90 - v1.21.92
		819, // v1.21.93
	];

	public const MINECRAFT_VERSIONS = [
		818 => "v1.21.90 - v1.20.92",
		819 => "v1.21.93",
	];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority HIGHEST
	 * @handleCancelled true
	 */
	public function onPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof RequestNetworkSettingsPacket){
			$protocolVersion = $packet->getProtocolVersion();
			if($protocolVersion !== ProtocolInfo::CURRENT_PROTOCOL && ($protocolVersion < ProtocolInfo::CURRENT_PROTOCOL || $protocolVersion > ProtocolInfo::CURRENT_PROTOCOL) && in_array($protocolVersion, self::SUPPORTED_PROTOCOLS, true)){
				$session = $event->getOrigin();
				$session->setHandler(null);
				if(isset($this->pendingSessions[$session->getIp()]["session"])){
					$session->setHandler(new SessionStartPacketHandlerProtocols(
						$session,
						function() use($session) : void{
							$reflectionClass = new \ReflectionClass($session);
							$reflectionMethod = $reflectionClass->getMethod("onSessionStartSuccess");
							$reflectionMethod->setAccessible(true);
							$reflectionMethod->invoke($session);
						}
					));
					unset($this->pendingSessions[$session->getIp()]);
					return;
				}
				$this->pendingSessions[$session->getIp()]["session"] = true;
				$session->handleDataPacket(RequestNetworkSettingsPacket::create(ProtocolInfo::CURRENT_PROTOCOL), $this->pendingSessions[$session->getIp()]["buffer"]);
				$event->cancel();
			}
		}
	}

	public function onDataPacketDecode(DataPacketDecodeEvent $event) : void{
		if($event->getPacketId() === ProtocolInfo::REQUEST_NETWORK_SETTINGS_PACKET){
			$this->pendingSessions[$event->getOrigin()->getIp()]["buffer"] = $event->getPacketBuffer();
		}
	}
}