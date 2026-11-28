# CustomAlerts

CustomAlerts replaces selected broadcasts sent to everyone on the server. It supports first join, join, quit, kick, world change and death messages. Each player sees the same configured text.

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

Configuration version 3 uses one flat key for each message. Version 2 and older files are not migrated. Move an old file aside to generate the new defaults. A missing key normally keeps the server's default message. A missing death-cause key uses `messages.death`, and a first join without `messages.first_join` uses `messages.join`. If the attacker cannot be identified, the generic death message is used.

```yaml
config-version: 3
messages.join: "&2[{TIME}] &a{PLAYER}&e joined the game."
messages.first_join: "&2[{TIME}] &a{PLAYER}&d joined the game for the first time."
messages.quit: "&4[{TIME}] &c{PLAYER}&e has left the game"
messages.kick: "&8[&e!&8] &f{PLAYER} &7was disconnected"
messages.world_change: "&2[{TIME}] &a{PLAYER}&e moved from &c{ORIGIN}&e to &a{TARGET}"
messages.death: "&c{PLAYER} died"
messages.death.contact: "&cOops! {PLAYER} was killed by {BLOCK}"
messages.death.kill: "&9{PLAYER} &ewas killed by &c{KILLER}"
messages.death.projectile: "&c{PLAYER} was killed by {KILLER} by arrow"
messages.death.suffocation: "&c{PLAYER} suffocated"
messages.death.fall: "&c{PLAYER} fell from a high place"
messages.death.fire: "&c{PLAYER} went up in flames"
messages.death.on_fire: "&c{PLAYER} burned"
messages.death.lava: "&c{PLAYER} tried to swim in lava"
messages.death.drowning: "&c{PLAYER} drowned"
messages.death.explosion: "&c{PLAYER} exploded"
messages.death.void: "&c{PLAYER} fell into the void"
messages.death.suicide: "&c{PLAYER} committed suicide"
messages.death.magic: "&c{PLAYER} was killed by a spell"
```

Use `&` color codes in message strings. Available placeholders depend on the message:

| Placeholder | Value |
| --- | --- |
| `{PLAYER}` | Player username. |
| `{TIME}` | Server time in `HH:MM:SS` format. |
| `{TOTALPLAYERS}` | Number of online players. |
| `{MAXPLAYERS}` | Maximum player count. |
| `{ORIGIN}`, `{TARGET}` | Source and destination world display names. |
| `{BLOCK}` | Block that caused contact damage. |
| `{KILLER}` | Attacking player or mob name. |

The plugin leaves chat, messages sent to one player, server-list MOTD and startup output unchanged. It does not choose a different language for each client. Write message strings in the language you want everyone to see.

## Commands and permissions

`/customalerts reload` reloads the configuration. It requires `customalerts.command.reload`, which defaults to operators.

## Development checks

```sh
composer test
composer analyse
```

## License

CustomAlerts is licensed under the MIT License.
