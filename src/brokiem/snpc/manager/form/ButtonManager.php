<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\manager\form;

use brokiem\snpc\libs\EasyUI\element\Input;
use pocketmine\utils\SingletonTrait;

class ButtonManager {
    use SingletonTrait;

    public function getUIButtons(): array {
        return [
            "Reload Config" => [
                "text" => "Перезагрузить конфиг",
                "icon" => null,
                "command" => "snpc reload",
                "function" => null
            ], "Spawn NPC" => [
                "text" => "Установить NPC",
                "icon" => null,
                "command" => null,
                "function" => "spawnNPC"
            ], "Edit NPC" => [
                "text" => "Редактирование NPC",
                "icon" => null,
                "command" => null,
                "function" => "editNPC"
            ],
            "Get NPC ID" => [
                "text" => "Узнать NPC ID",
                "icon" => null,
                "command" =>
                    "snpc id",
                "function" => null
            ], "Remove NPC" => [
                "text" => "Удалить NPC",
                "icon" => null,
                "command" => "snpc remove",
                "function" => null
            ], "List NPC" => [
                "text" => "Список NPC",
                "icon" => null,
                "command" => null,
                "function" => "npcList"
            ]
        ];
    }

    public function getEditButtons(): array {
        return [
            "Add Command" => [
                "text" => "Добавить команду",
                "icon" => null,
                "element" => [
                    "id" => "addcmd",
                    "element" => new Input("Используйте {player} для имени игрока, но не используйте слеш [/]\nВы также можете использовать команду /rca\n\n\nВведите команду здесь. (Команда выполняется консолью)")
                ], "additional" => []
            ], "Remove Command" => [
                "text" => "Удалить команду",
                "icon" => null,
                "element" => [
                    "id" => "removecmd",
                    "element" => new Input("Введите здесь команду")
                ], "additional" => []
            ], "Change Nametag" => [
                "text" => "Изменить тег",
                "icon" => null,
                "element" => [
                    "id" => "changenametag", "element" => new Input("Введите новый тег здесь")
                ], "additional" => []
            ], "Change Skin" => [
                "text" => "Изменить скин\n(Только для Human NPC)",
                "icon" => null, "element" => [
                    "id" => "changeskin",
                    "element" => new Input("Введите URL-адрес скина или имя онлайн-игрока")
                ], "additional" => []
            ], "Change Cape" => [
                "text" => "Изменить плащ\n(Только для Human NPC)",
                "icon" => null,
                "element" => [
                    "id" => "changecape",
                    "element" => new Input("Введите URL-адрес плаща или имя онлайн-игрока")
                ], "additional" => []
            ], "Change Scale/Size" => [
                "text" => "Изменить размер",
                "icon" => null,
                "element" => [
                    "id" => "changescale",
                    "element" => new Input("Введите новый размер (мин.=0.01")
                ],
                "additional" => []
            ], "Set Armor" => [
                "text" => "Установить броню\n(Вы должны надеть броню)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Установить броню\n(Вы должны надеть броню)",
                        "icon" => null,
                        "function" => "setArmor",
                        "force" => true
                    ]
                ]
            ], "Set Held Item" => [
                "text" => "Установить удерживаемый предмет\n (Вы должны удерживать предмет)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Установить удерживаемый предмет\n(Вы должны удерживать предмет)",
                        "icon" => null,
                        "function" => "setHeld",
                        "force" => true
                    ]
                ]
            ], "Disable Rotate" => [
                "text" => "Отключить поворот\n(Смотреть на игрока)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Отключить поворот\n(Смотреть на игрока)",
                        "icon" => null,
                        "function" => "disableRotate",
                        "force" => true
                    ]
                ]
            ], "Enable Rotate" => [
                "text" => "Включить поворот\n(Смотреть на игрока)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Включить поворот\n(Смотреть на игрока)",
                        "icon" => null,
                        "function" => "EnableRotation",
                        "force" => true
                    ]
                ]
            ], "Show Nametag" => [
                "text" => "Показать тег",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Показать тег",
                        "icon" => null,
                        "function" => "showNametag",
                        "force" => true
                    ]
                ]
            ], "Hide Nametag" => [
                "text" => "Скрыть тег",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Скрыть тег",
                        "icon" => null,
                        "function" => "hideNametag",
                        "force" => true
                    ]
                ]
            ], "Set Click-Emote" => [
                "text" => "Установить эмоцию по клику",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Установить эмоцию по клику",
                        "icon" => null,
                        "function" => "setClickEmote",
                        "force" => true
                    ]
                ]
            ], "Set Emote" => [
                "text" => "Установить эмоцию",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Установить эмоцию",
                        "icon" => null,
                        "function" => "setEmote",
                        "force" => true
                    ]
                ]
            ], "Command List" => [
                "text" => "Список команд",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "",
                    "button" => [
                        "text" => null,
                        "icon" => null,
                        "function" => "commandList",
                        "force" => false
                    ]
                ]
            ], "Teleport" => [
                "text" => "Телепорт",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "",
                    "button" => [
                        "text" => null,
                        "icon" => null,
                        "function" => "teleport",
                        "force" => false
                    ]
                ]
            ]
        ];
    }
}