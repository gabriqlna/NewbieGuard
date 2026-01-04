<?php

namespace br\com\newbieguard;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $playerData;
    
    /** @var array */
    private $activeProtections = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
        // Carrega ou cria o arquivo de dados dos jogadores (players.yml)
        $this->playerData = new Config($this->getDataFolder() . "players.yml", Config::YAML);
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Tarefa repetitiva (roda a cada 20 ticks = 1 segundo)
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->handleCooldowns();
        }), 20);
    }

    public function onDisable(): void {
        // Salva o tempo restante de todos os jogadores online antes de desligar
        foreach ($this->activeProtections as $name => $time) {
            $this->playerData->set($name, $time);
        }
        $this->playerData->save();
    }

    /**
     * Gerencia a contagem regressiva dos jogadores protegidos
     */
    private function handleCooldowns(): void {
        foreach ($this->activeProtections as $name => $time) {
            $this->activeProtections[$name]--;

            if ($this->activeProtections[$name] <= 0) {
                $player = $this->getServer()->getPlayerExact($name);
                if ($player !== null) {
                    $player->sendMessage($this->getConfig()->get("msg-protection-end"));
                }
                unset($this->activeProtections[$name]);
                $this->playerData->remove($name); // Remove do arquivo se acabou
                $this->playerData->save();
            }
        }
    }

    /**
     * Ao entrar: Carrega o tempo salvo ou inicia novo
     */
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        // Verifica se tem tempo salvo
        if ($this->playerData->exists($name)) {
            $timeLeft = $this->playerData->get($name);
            if ($timeLeft > 0) {
                $this->activeProtections[$name] = $timeLeft;
                $player->sendMessage(str_replace("{TIME}", (string)ceil($timeLeft / 60), $this->getConfig()->get("msg-protection-start")));
            }
        } else {
            // Se não existe registro, dá o tempo configurado (em segundos)
            $minutes = $this->getConfig()->get("protection-time", 5);
            $seconds = $minutes * 60;
            $this->activeProtections[$name] = $seconds;
            $player->sendMessage(str_replace("{TIME}", (string)$minutes, $this->getConfig()->get("msg-protection-start")));
        }
    }

    /**
     * Ao sair: Salva o tempo restante
     */
    public function onQuit(PlayerQuitEvent $event): void {
        $name = $event->getPlayer()->getName();
        if (isset($this->activeProtections[$name])) {
            $this->playerData->set($name, $this->activeProtections[$name]);
            $this->playerData->save();
            unset($this->activeProtections[$name]);
        }
    }

    /**
     * Impede PvP
     */
    public function onAttack(EntityDamageByEntityEvent $event): void {
        $victim = $event->getEntity();
        $attacker = $event->getDamager();

        if (!$victim instanceof Player || !$attacker instanceof Player) {
            return;
        }

        // Caso 1: Atacante está protegido (Remove proteção)
        if (isset($this->activeProtections[$attacker->getName()])) {
            unset($this->activeProtections[$attacker->getName()]);
            $this->playerData->remove($attacker->getName());
            $this->playerData->save();
            $attacker->sendMessage($this->getConfig()->get("msg-protection-removed"));
            // O ataque prossegue, pois ele escolheu atacar
            return;
        }

        // Caso 2: Vítima está protegida (Cancela ataque)
        if (isset($this->activeProtections[$victim->getName()])) {
            $event->cancel();
            $attacker->sendMessage($this->getConfig()->get("msg-cant-pvp"));
        }
    }

    /**
     * Impede qualquer dano à vítima protegida (opcional, protege de queda/fogo também?)
     * Se quiser proteger APENAS de players, remova este método e mantenha apenas o onAttack.
     * Mas geralmente proteção de entrada protege de tudo.
     */
    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && isset($this->activeProtections[$entity->getName()])) {
            // Permite cancelar dano geral, exceto void (cair no vazio)
            if ($event->getCause() !== EntityDamageEvent::CAUSE_VOID) {
                $event->cancel();
            }
        }
    }

    /**
     * Impede quebrar blocos
     */
    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        if (isset($this->activeProtections[$player->getName()])) {
            $event->cancel();
            $player->sendMessage($this->getConfig()->get("msg-cant-build"));
        }
    }

    /**
     * Impede colocar blocos
     */
    public function onPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        if (isset($this->activeProtections[$player->getName()])) {
            $event->cancel();
            $player->sendMessage($this->getConfig()->get("msg-cant-build"));
        }
    }
}
