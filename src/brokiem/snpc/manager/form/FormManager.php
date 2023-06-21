<?php /** @noinspection TypeUnsafeComparisonInspection */

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\manager\form;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\entity\EmoteIds;
use brokiem\snpc\entity\WalkingHuman;
use brokiem\snpc\SimpleNPC;
use brokiem\snpc\task\async\URLToCapeTask;
use brokiem\snpc\task\async\URLToSkinTask;
use brokiem\snpc\libs\EasyUI\element\Button;
use brokiem\snpc\libs\EasyUI\element\Dropdown;
use brokiem\snpc\libs\EasyUI\element\Input;
use brokiem\snpc\libs\EasyUI\element\Option;
use brokiem\snpc\libs\EasyUI\utils\FormResponse;
use brokiem\snpc\libs\EasyUI\variant\CustomForm;
use brokiem\snpc\libs\EasyUI\variant\SimpleForm;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class FormManager {
    use SingletonTrait;

    public function sendUIForm(Player $sender): void {
        $form = new SimpleForm("Менеджер NPC");
        $simpleForm = new SimpleForm("Менеджер NPC");
        $cusForm = new CustomForm("Менеджер NPC");

        $plugin = SimpleNPC::getInstance();

        foreach (ButtonManager::getInstance()->getUIButtons() as $button) {
            $form->addButton(new Button($button["text"], $button["icon"], function(Player $sender) use ($simpleForm, $cusForm, $button, $plugin) {
                if ($button["function"] !== null) {
                    switch ($button["function"]) {
                        case "spawnNPC":
                            foreach (SimpleNPC::getInstance()->getRegisteredNPC() as $npcName => $saveNames) {
                                $simpleForm->addButton(new Button(ucfirst(str_replace(["_snpc", "_"], [" NPC", " "], $npcName)), null, function(Player $player) use ($saveNames, $npcName, $cusForm) {
                                    $dropdown = new Dropdown("Выберите NPC:");
                                    $dropdown->addOption(new Option(str_replace("_snpc", "", $npcName), ucfirst(str_replace(["_snpc", "_"], [" NPC", " "], $npcName))));
                                    $cusForm->addElement("type", $dropdown);

                                    $cusForm->addElement("nametag", new Input("NPC Тег: [string]\n" . 'Подсказка: Используйте (" ") если в имени есть пробелы'));
                                    if (is_a($saveNames[0], CustomHuman::class, true)) {
                                        $cusForm->addElement("skin", new Input("NPC Ссылка на скин (PNG) URL: [null/string]"));
                                    }
                                    $player->sendForm($cusForm);
                                }));
                            }
                            $simpleForm->setHeaderText("Выберите NPC:");
                            $sender->sendForm($simpleForm);
                            break;
                        case "editNPC":
                            $cusForm->addElement("snpcid_edit", new Input("Введите NPC ID"));
                            $sender->sendForm($cusForm);
                            break;
                        case "npcList":
                            if ($sender->hasPermission("simplenpc.list")) {
                                $list = "";
                                foreach ($plugin->getServer()->getWorldManager()->getWorlds() as $world) {
                                    $entityNames = array_map(static function(Entity $entity): string {
                                        return TextFormat::YELLOW . "ID: (" . $entity->getId() . ") " . TextFormat::GREEN . $entity->getNameTag() . " §7-- §b" . $entity->getWorld()->getFolderName() . ": " . $entity->getLocation()->getFloorX() . "/" . $entity->getLocation()->getFloorY() . "/" . $entity->getLocation()->getFloorZ();
                                    }, array_filter($world->getEntities(), static function(Entity $entity): bool {
                                        return $entity instanceof BaseNPC or $entity instanceof CustomHuman;
                                    }));

                                    $list .= "§cNPC Список и локации: (" . count($entityNames) . ")\n §f- " . implode("\n - ", $entityNames);
                                }

                                $simpleForm->setHeaderText($list);
                                $simpleForm->addButton(new Button("Напечатать", null, function(Player $sender) use ($list) {
                                    $sender->sendMessage($list);
                                }));
                                $sender->sendForm($simpleForm);
                            }
                            break;
                    }
                } else {
                    $plugin->getServer()->getCommandMap()->dispatch($sender, $button["command"]);
                }
            }));
        }

        $sender->sendForm($form);
        $cusForm->setSubmitListener(function(Player $player, FormResponse $response) use ($plugin) {
            $type = $response->getDropdownSubmittedOptionId("type") === null ? "" : strtolower($response->getDropdownSubmittedOptionId("type"));
            $nametag = $response->getInputSubmittedText("nametag") === null ? $player->getName() : $response->getInputSubmittedText("nametag");
            $skin = $response->getInputSubmittedText("skin") === "null" ? null : $response->getInputSubmittedText("skin");
            $npcEditId = $response->getInputSubmittedText("snpcid_edit");

            if ($npcEditId != "") {
                $plugin->getServer()->getCommandMap()->dispatch($player, "snpc edit $npcEditId");
                return;
            }

            if ($type == "") {
                $player->sendMessage(TextFormat::YELLOW . "Пожалуйтса, введите валидный тип NPC");
                return;
            }

            $plugin->getServer()->getCommandMap()->dispatch($player, "snpc add $type $nametag $skin");
        });
    }

    public function sendEditForm(Player $sender, array $args, int $entityId): void {
        $plugin = SimpleNPC::getInstance();
        $entity = $plugin->getServer()->getWorldManager()->findEntity($entityId);

        $customForm = new CustomForm("Менеджер NPC");
        $simpleForm = new SimpleForm("Менеджер NPC");

        if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
            $editUI = new SimpleForm("Менеджер NPC", "§aID:§2 $args[1]\n§aКласс: §2" . get_class($entity) . "\n§aТег: §2" . $entity->getNameTag() . "\n§aПозиция: §2" . $entity->getLocation()->getFloorX() . "/" . $entity->getLocation()->getFloorY() . "/" . $entity->getLocation()->getFloorZ());

            foreach (ButtonManager::getInstance()->getEditButtons() as $button) {
                if (empty($button["element"]) && !empty($button["additional"]) && $button["additional"]["button"]["force"]) {
                    $editUI->addButton(new Button($button["additional"]["button"]["text"], $button["additional"]["button"]["icon"], function(Player $sender) use ($entity, $button) {
                        switch ($button["additional"]["button"]["function"]) {
                            case "showNametag":
                                $entity->setNameTagAlwaysVisible();
                                $entity->setNameTagVisible();
                                $sender->sendMessage(TextFormat::GREEN . "Тег NPC теперь отображается (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "hideNametag":
                                $entity->setNameTagAlwaysVisible(false);
                                $entity->setNameTagVisible(false);
                                $sender->sendMessage(TextFormat::GREEN . "Тег NPC теперь скрыт (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "disableRotate":
                                $entity->setCanLookToPlayers(false);
                                $sender->sendMessage(TextFormat::GREEN . "Теперь NPC не поворачивается (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "EnableRotation":
                                $entity->setCanLookToPlayers(true);
                                $sender->sendMessage(TextFormat::GREEN . "Теперь NPC может поворачиваться (NPC ID: " . $entity->getId() . ")");
                                break;
                            case "setClickEmote":
                                $clickEmoteUI = new SimpleForm("Редактировать клик-эмоцию", "Пожалуйста, выберите клик-эмоцию.");
                                if ($entity->getClickEmoteId() !== null) $clickEmoteUI->addButton(new Button(
                                    "§cУдалить клик-эмоцию", null,
                                    function (Player $player) use ($entity) {
                                        $entity->setClickEmoteId(null);
                                        $player->sendMessage("§aКлик-эмоция успешно удалена у NPC ID: " . $entity->getId());
                                    }));
                                foreach (EmoteIds::EMOTES as $emoteName => $emoteId)
                                    $clickEmoteUI->addButton(new Button($emoteName, null,
                                        function (Player $player) use ($entity, $emoteId, $emoteName) {
                                            $entity->setClickEmoteId($emoteId);
                                            $player->sendMessage("§aДля NPC ID: " . $entity->getId() . " выставлена клик-эмоция §7" . $emoteName);
                                        }));
                                $sender->sendForm($clickEmoteUI);
                                break;
                            case "setEmote":
                                $emoteUI = new SimpleForm("Редактировать эмоцию", "Пожалуйста, выберете эмоцию.");
                                if ($entity->getEmoteId() !== null) $emoteUI->addButton(new Button(
                                    "§cУдалить эмоцию", null,
                                    function (Player $player) use ($entity) {
                                        $entity->setEmoteId(null);
                                        $player->sendMessage("§aЭмоция успешно удалена у NPC ID: " . $entity->getId());
                                    }));
                                foreach (EmoteIds::EMOTES as $emoteName => $emoteId)
                                    $emoteUI->addButton(new Button($emoteName, null,
                                        function (Player $player) use ($entity, $emoteId, $emoteName) {
                                            $entity->setEmoteId($emoteId);
                                            $player->sendMessage("§aДля NPC ID: " . $entity->getId() . " выставлена эмоция §7" . $emoteName);
                                        }));
                                $sender->sendForm($emoteUI);
                                break;
                            case "setArmor":
                                if ($entity instanceof CustomHuman) {
                                    $entity->applyArmorFrom($sender);
                                    $sender->sendMessage(TextFormat::GREEN . "Вы установили свою броню на NPC ID: " . $entity->getId());
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Только человекоподобный NPC может носить броню!");
                                }
                                break;
                            case "setHeld":
                                if ($entity instanceof CustomHuman) {
                                    if ($sender->getInventory()->getItemInHand()->equals(VanillaItems::AIR(), false ,false)) {
                                        $sender->sendMessage(TextFormat::RED . "Пожалуйста, держите предмет в руке!");
                                    } else {
                                        $entity->sendHeldItemFrom($sender);
                                        $sender->sendMessage(TextFormat::GREEN . "Вы вложили в руку '" . $sender->getInventory()->getItemInHand()->getVanillaName() . "' для NPC ID: " . $entity->getId());
                                    }
                                } else {
                                    $sender->sendMessage(TextFormat::RED . "Только человекоподобный NPC может держать предметы в руках!");
                                }
                                break;
                        }
                    }));

                    continue;
                }

                $editUI->addButton(new Button($button["text"], $button["icon"], function(Player $sender) use ($entity, $simpleForm, $editUI, $customForm, $button) {
                    if (!empty($button["element"]) && empty($button["additional"])) {
                        $customForm->addElement($button["element"]["id"], $button["element"]["element"]);
                        $sender->sendForm($customForm);
                    } elseif (empty($button["element"]) && !empty($button["additional"])) {
                        if ($button["additional"]["button"]["text"] === null) {
                            switch ($button["additional"]["button"]["function"]) {
                                case "commandList":
                                    $cmds = "Этот NPC (ID: {$entity->getId()}) пока без команд.";
                                    $commands = $entity->getCommandManager()->getAll();
                                    if (!empty($commands)) {
                                        $cmds = TextFormat::AQUA . "NPC ID: {$entity->getId()} списиок команд (" . count($commands) . ")\n";

                                        foreach ($commands as $cmd) {
                                            $cmds .= TextFormat::GREEN . "- " . $cmd . "\n";
                                        }
                                    }

                                    $simpleForm->setHeaderText($cmds);
                                    $simpleForm->addButton(new Button("Напечатать", null, function(Player $sender) use ($cmds) {
                                        $sender->sendMessage($cmds);
                                    }));
                                    $simpleForm->addButton(new Button("< Назад", null, function(Player $sender) use ($editUI) {
                                        $sender->sendForm($editUI);
                                    }));
                                    $sender->sendForm($simpleForm);
                                    break;
                                case "teleport":
                                    $simpleForm->addButton(new Button("Вас к NPC", null, function(Player $sender) use ($entity): void {
                                        $sender->teleport($entity->getLocation());
                                        $sender->sendMessage(TextFormat::GREEN . "Перемещено!");
                                    }));
                                    $simpleForm->addButton(new Button("NPC к Вам", null, function(Player $sender) use ($entity): void {
                                        $entity->teleport($sender->getLocation());
                                        if ($entity instanceof WalkingHuman) {
                                            $entity->randomPosition = $entity->getLocation()->asVector3();
                                        }
                                        $sender->sendMessage(TextFormat::GREEN . "Перемещено!");
                                    }));

                                    $sender->sendForm($simpleForm);
                                    break;
                            }
                        }
                    }
                }));
            }

            $customForm->setSubmitListener(function(Player $player, FormResponse $response) use ($plugin, $entity) {
                try {
                    $addcmd = $response->getInputSubmittedText("addcmd");
                } catch (InvalidArgumentException) {
                    $addcmd = null;
                }
                try {
                    $rmcmd = $response->getInputSubmittedText("removecmd");
                } catch (InvalidArgumentException) {
                    $rmcmd = null;
                }
                try {
                    $chnmtd = $response->getInputSubmittedText("changenametag");
                } catch (InvalidArgumentException) {
                    $chnmtd = null;
                }
                try {
                    $scale = $response->getInputSubmittedText("changescale");
                } catch (InvalidArgumentException) {
                    $scale = null;
                }
                try {
                    $skin = $response->getInputSubmittedText("changeskin");
                } catch (InvalidArgumentException) {
                    $skin = null;
                }
                try {
                    $cape = $response->getInputSubmittedText("changecape");
                } catch (InvalidArgumentException) {
                    $cape = null;
                }

                if ($rmcmd !== null) {
                    if (!in_array($rmcmd, $entity->getCommandManager()->getAll(), true)) {
                        $player->sendMessage(TextFormat::RED . "Команды '$rmcmd' нет в списке команд у NPC.");
                        return;
                    }

                    $entity->getCommandManager()->remove($rmcmd);
                    $player->sendMessage(TextFormat::GREEN . "Команда '$rmcmd' успешно удалена у (NPC ID: " . $entity->getId() . ")");
                } elseif ($addcmd !== null) {
                    if (in_array($addcmd, $entity->getCommandManager()->getAll(), true)) {
                        $player->sendMessage(TextFormat::RED . "Команда '$addcmd' уже добавлена.");
                        return;
                    }

                    $entity->getCommandManager()->add($addcmd);
                    $player->sendMessage(TextFormat::GREEN . "Команда '$addcmd' успешно добавлена для (NPC ID: " . $entity->getId() . ")");
                } elseif ($chnmtd !== null) {
                    $player->sendMessage(TextFormat::GREEN . "Вы успешно сменили тег с '{$entity->getNameTag()}' на '$chnmtd' для (NPC ID: " . $entity->getId() . ")");

                    $entity->setNameTag(str_replace("{line}", "\n", $chnmtd));
                    $entity->setNameTagAlwaysVisible();
                } elseif ($cape !== null) {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Только человекоподобный NPC может носить плащ!");
                        return;
                    }

                    $pCape = $player->getServer()->getPlayerExact($cape);

                    if ($pCape instanceof Player) {
                        $capeSkin = new Skin($entity->getSkin()->getSkinId(), $entity->getSkin()->getSkinData(), $pCape->getSkin()->getCapeData(), $entity->getSkin()->getGeometryName(), $entity->getSkin()->getGeometryData());
                        $entity->setSkin($capeSkin);
                        $entity->sendSkin();

                        $player->sendMessage(TextFormat::GREEN . "Вы успешно добавили плащ для (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToCapeTask($cape, $plugin->getDataFolder(), $entity, $player->getName()));
                } elseif ($skin !== null) {
                    if (!$entity instanceof CustomHuman) {
                        $player->sendMessage(TextFormat::RED . "Только у человекоподобного NPC может быть сменен скин!");
                        return;
                    }

                    $pSkin = $player->getServer()->getPlayerExact($skin);

                    if ($pSkin instanceof Player) {
                        $entity->setSkin($pSkin->getSkin());
                        $entity->sendSkin();

                        $player->sendMessage(TextFormat::GREEN . "Вы успешно сменили скин у (NPC ID: " . $entity->getId() . ")");
                        return;
                    }

                    if (!preg_match('/https?:\/\/[^?]*\.png(?![\w.\-_])/', $skin)) {
                        $player->sendMessage(TextFormat::RED . "Неправильный формат скина! (Только PNG поддерживается, ссылка только через https://)");
                        return;
                    }

                    $plugin->getServer()->getAsyncPool()->submitTask(new URLToSkinTask($player->getName(), $plugin->getDataFolder(), $skin, $entity));
                    $player->sendMessage(TextFormat::GREEN . "Вы успешно сменили скин у (NPC ID: " . $entity->getId() . ")");
                } elseif ($scale !== null) {
                    if ((float)$scale <= 0) {
                        $player->sendMessage("Размер должен быть больше, чем 0 :)");
                        return;
                    }

                    $entity->setScale((float)$scale);

                    $player->sendMessage(TextFormat::GREEN . "Вы успешно изменили размер на $scale для (NPC ID: " . $entity->getId() . ")");
                } else {
                    $player->sendMessage(TextFormat::RED . "Пожалуйста, введите действительное значение!");
                }
            });

            $sender->sendForm($editUI);
            return;
        }
        $sender->sendMessage(TextFormat::YELLOW . "NPC с ID: " . $args[1] . " не найден!");
    }
}
