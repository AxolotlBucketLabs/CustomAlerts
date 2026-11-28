# CustomAlerts

CustomAlerts replaces or hides selected server-wide system broadcasts. It supports join, quit, death and kick broadcasts through Axolotl-PM events. Every player receives the same configured message.

[![CI](https://github.com/AxolotlBucketLabs/CustomAlerts/actions/workflows/ci.yml/badge.svg)](https://github.com/AxolotlBucketLabs/CustomAlerts/actions/workflows/ci.yml) [![License](https://img.shields.io/github/license/AxolotlBucketLabs/CustomAlerts)](LICENSE) [![Discord](https://img.shields.io/discord/1568295845374206052?label=Discord&logo=discord&logoColor=white&color=5865F2)](https://discord.gg/Pk4FrNvHnM)

## Requirements and installation

- The manifest declares Axolotl-PM API `5.0.0`. CI targets Axolotl-PM `5.51.2` with PHP `8.4`.
- PHP `^8.4` with the `yaml` extension.

For folder development, install DevTools as `plugins/DevTools.phar` and place the CustomAlerts project folder under `plugins/`. Build a standalone plugin PHAR with:

```text
/devtools build plugin CustomAlerts
```

GitHub Actions builds and validates the plugin PHAR. Install either the PHAR or development folder, start the server, then edit `plugin_data/CustomAlerts/config.yml`. Run `/customalerts reload` to apply changes. The `ca` alias is available. The reload permission is `customalerts.command.reload` and defaults to operators.

## Configuration

The configuration uses `config-version: 2`. Version 1 configuration files are not migrated. Move an old file aside to generate the new defaults, then reapply its message text.

```yaml
config-version: 2
messages:
  join: "§8[§a+§8] §f{display_name} §7joined the server"
  quit: "§8[§c-§8] §f{display_name} §7left the server"
  death:
    broadcast: "§8[§cX§8] §f{player} §7died in §f{world}"
    killer-fallback: "the server"
  kick:
    broadcast: "§8[§e!§8] §f{display_name} §7was disconnected"
```

Each message can be removed to keep the server default or set to `false` to hide that broadcast. Templates use Bedrock `§` color codes. `messages.death.killer-fallback` supplies `{killer}` when the death cause has no player or mob attacker.

The available placeholders are:

| Placeholder | Value |
| --- | --- |
| `{player}` | Player username. |
| `{display_name}` | Player display name. |
| `{world}` | World folder name. |
| `{x}`, `{y}`, `{z}` | Player block coordinates. |
| `{killer}` | Attacking player's display name, attacking mob name, or the configured fallback. |

The plugin leaves player chat, messages sent to one player, client interface translations, console output and startup messages unchanged. It does not select a different language for each client. Write the configured templates in the language you want all players to see.

## Commands and permissions

`/customalerts reload` reloads the configuration. It requires `customalerts.command.reload`, which defaults to operators.

## Development checks

```sh
composer test
composer analyse
```

## License

CustomAlerts is licensed under the MIT License.
