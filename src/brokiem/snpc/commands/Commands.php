<?php /** @noinspection RedundantElseClauseInspection */

declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\form\FormManager;
use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\URLToSkinTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class Commands extends Command implements PluginOwned {

    public function __construct(string $name, private SimpleNPC $owner) {
        parent::__construct($name, "SimpleNPC commands");
        $this->setPermission("simplenpc.cmd");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return true;
        }

        /** @var SimpleNPC $plugin */
        $plugin = $this->getOwningPlugin();

        if (isset($args[0])) {
            switch (strtolower($args[0])) {
                case "ui":
                    if (!$sender->hasPermission("simplenpc.ui")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Только игрок может выполнять эту команду");
                        return true;
                    }

                    FormManager::getInstance()->sendUIForm($sender);
                    break;
                case "reload":
                    if (!$sender->hasPermission("simplenpc.reload")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    $plugin->initConfiguration();
                    $sender->sendMessage(TextFormat::GREEN . "Конфигурация SimpleNPC успешно перезагружена!");
                    break;
                case "id":
                    if (!$sender->hasPermission("simplenpc.id")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    if (!isset($plugin->idPlayers[$sender->getName()])) {
                        $plugin->idPlayers[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Ударьте по npc, у которого вы хотите увидеть ID");
                    } else {
                        unset($plugin->idPlayers[$sender->getName()]);
                        $sender->sendMessage(TextFormat::GREEN . "Tap to get NPC ID has been canceled");
                    }
                    break;
                case "spawn":
                case "add":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Only player can run this command!");
                        return true;
                    }

                    if (!$sender->hasPermission("simplenpc.spawn")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    if (isset($args[1])) {
                        if (array_key_exists(strtolower($args[1]) . "_snpc", SimpleNPC::getInstance()->getRegisteredNPC())) {
                            if (is_a(SimpleNPC::getInstance()->getRegisteredNPC()[strtolower($args[1]) . "_snpc"][0], CustomHuman::class, true)) {
                                if (isset($args[3])) {
                                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $args[3])) {
                                        $sender->sendMessage(TextFormat::RED . "Неверный формат файла skin url! (Поддерживается только PNG)");
                                        return true;
                                    }

                                    $id = NPCManager::getInstance()->spawnNPC(strtolower($args[1]) . "_snpc", $sender, $args[2], null, $sender->getSkin()->getSkinData());
                                    if ($id !== null) {
                                        $npc = $sender->getServer()->getWorldManager()->findEntity($id);

                                        if ($npc instanceof CustomHuman) {
                                            $plugin->getServer()->getAsyncPool()->submitTask(new URLToSkinTask($sender->getName(), $plugin->getDataFolder(), $args[3], $npc));
                                        }
                                    }

                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Создание " . ucfirst($args[1]) . " NPC с тегом $args[2] для вас...");
                                    return true;
                                } elseif (isset($args[2])) {
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Создание " . ucfirst($args[1]) . " NPC с тегом $args[2] для вас...");
                                    NPCManager::getInstance()->spawnNPC(strtolower($args[1]) . "_snpc", $sender, $args[2], null, $sender->getSkin()->getSkinData());
                                    return true;
                                }

                                NPCManager::getInstance()->spawnNPC(strtolower($args[1]) . "_snpc", $sender, $sender->getName(), null, $sender->getSkin()->getSkinData());
                            } else {
                                if (isset($args[2])) {
                                    NPCManager::getInstance()->spawnNPC(strtolower($args[1]) . "_snpc", $sender, $args[2]);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . "Создание " . ucfirst($args[1]) . " NPC с тегом $args[2] для вас...");
                                    return true;
                                }

                                NPCManager::getInstance()->spawnNPC(strtolower($args[1]) . "_snpc", $sender);
                            }
                            $sender->sendMessage(TextFormat::DARK_GREEN . "Создание " . ucfirst($args[1]) . " NPC без тега для вас...");
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Недопустимый тип сущности или сущность не зарегистрирована!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Использование: /snpc spawn <тип> необязательно: <nametag> <skinUrl>");
                    }
                    break;
                case "delete":
                case "remove":
                    if (!$sender->hasPermission("simplenpc.remove")) {
                        $sender->sendMessage(TextFormat::RED . "You don't have permission");
                        return true;
                    }

                    if (isset($args[1]) && is_numeric($args[1])) {
                        $entity = $plugin->getServer()->getWorldManager()->findEntity((int)$args[1]);

                        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
                            if ($entity->despawn()) {
                                $sender->sendMessage(TextFormat::GREEN . "NPC был успешно удален!");
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . "Удаление NPC было неудачным! (Файл не найден)");
                            }
                            return true;
                        }

                        $sender->sendMessage(TextFormat::YELLOW . "Сущность SimpleNPC с идентификатором: " . $args[1] . " не найдена!");
                        return true;
                    }

                    if (!isset($plugin->removeNPC[$sender->getName()])) {
                        $plugin->removeNPC[$sender->getName()] = true;
                        $sender->sendMessage(TextFormat::DARK_GREEN . "Ударьте нпс, которого вы хотите удалить");
                        return true;
                    }

                    unset($plugin->removeNPC[$sender->getName()]);
                    $sender->sendMessage(TextFormat::GREEN . "Удаление нпс ударом отменено");
                    break;
                case "edit":
                case "manage":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("Только игрок может выполнить эту команду!");
                        return true;
                    }

                    if (!$sender->hasPermission("simplenpc.edit")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    if (!isset($args[1]) || !is_numeric($args[1])) {
                        $sender->sendMessage(TextFormat::RED . "Использование: /snpc edit <id>");
                        return true;
                    }

                    FormManager::getInstance()->sendEditForm($sender, $args, (int)$args[1]);
                    break;
                case "list":
                    if (!$sender->hasPermission("simplenpc.list")) {
                        $sender->sendMessage(TextFormat::RED . "У тебя нет разрешения");
                        return true;
                    }

                    $entityNames = [];
                    foreach ($plugin->getServer()->getWorldManager()->getWorlds() as $world) {
                        $entityNames = array_map(static function(Entity $entity): string {
                            return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getWorld()->getFolderName() . ": " . $entity->getLocation()->getFloorX() . "/" . $entity->getLocation()->getFloorY() . "/" . $entity->getLocation()->getFloorZ();
                        }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                            return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                        }));
                    }

                    $sender->sendMessage("§cNPC List and Location: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames));
                    break;
                case "help":
                    $sender->sendMessage("\n§7---- ---- ---- - ---- ---- ----\n§eСписок команд:\n§2» /snpc spawn <type> <nametag> <skinUrl>\n§2» /snpc edit <id>\n§2» /snpc reload\n§2» /snpc ui\n§2» /snpc remove <id>\n§2» /snpc list\n§7---- ---- ---- - ---- ---- ----");
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "Субкоманда '$args[0]' не найдена! Попробуйте обратиться за помощью к команде '/snpc help'.");
                    break;
            }
        } else {
            $sender->sendMessage("§7---- ---- [ §3SimpleNPC§7 ] ---- ----\n§bAuthor: @brokiem\n§3Source Code: github.com/brokiem/SimpleNPC\nVersion " . $this->getOwningPlugin()->getDescription()->getVersion() . "\n§7---- ---- ---- - ---- ---- ----");
        }

        return true;
    }

    public function getOwningPlugin(): Plugin {
        return $this->owner;
    }
}