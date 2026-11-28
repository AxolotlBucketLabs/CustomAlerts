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

GitHub Actions builds and validates the plugin PHAR. Install either the PHAR or development folder, start the server, then edit `plugin_data/CustomAlerts/config.yml`. Restart the server to apply configuration changes.

## Configuration

The configuration uses one flat key for each message. Old nested `messages:` settings are not read. A string sets the custom message, while `false` hides the broadcast. An omitted key keeps PocketMine's default message when one exists. Optional alerts such as `messages.world_change` stay disabled when omitted. A missing death-cause key uses `messages.death`, and a missing `messages.first_join` uses `messages.join`. An explicit `false` suppresses the alert instead of using its fallback. If the attacker cannot be identified, the generic death message is used.

For example, set `messages.quit: false` to hide quit broadcasts.

```yaml
messages.join: "&8[&a+&8] &2[{TIME}] &a{PLAYER}&e joined the game."
messages.first_join: "&8[&a+&8] &2[{TIME}] &a{PLAYER}&d joined the game for the first time."
messages.quit: "&8[&c-&8] &4[{TIME}] &c{PLAYER}&e has left the game."
messages.kick: "&8[&c-&8] &f{PLAYER} &7was disconnected"
messages.world_change: "&8[&b>&8] &2[{TIME}] &a{PLAYER}&e moved from &c{ORIGIN}&e to &a{TARGET}"
messages.death: "&8[&c!&8] &c{PLAYER} died"
messages.death.contact: "&8[&c!&8] &cOops! {PLAYER} was killed by {BLOCK}"
messages.death.kill: "&8[&c!&8] &9{PLAYER} &ewas killed by &c{KILLER}"
messages.death.projectile: "&8[&c!&8] &c{PLAYER} was killed by a projectile from {KILLER}"
messages.death.suffocation: "&8[&c!&8] &c{PLAYER} suffocated"
messages.death.fall: "&8[&c!&8] &c{PLAYER} fell from a high place"
messages.death.fire: "&8[&c!&8] &c{PLAYER} went up in flames"
messages.death.on_fire: "&8[&c!&8] &c{PLAYER} burned"
messages.death.lava: "&8[&c!&8] &c{PLAYER} tried to swim in lava"
messages.death.drowning: "&8[&c!&8] &c{PLAYER} drowned"
messages.death.explosion: "&8[&c!&8] &c{PLAYER} exploded"
messages.death.void: "&8[&c!&8] &c{PLAYER} fell into the void"
messages.death.suicide: "&8[&c!&8] &c{PLAYER} committed suicide"
messages.death.magic: "&8[&c!&8] &c{PLAYER} was killed by a spell"
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

## Development checks

```sh
composer test
composer analyse
```

## License

CustomAlerts is licensed under the MIT License.
