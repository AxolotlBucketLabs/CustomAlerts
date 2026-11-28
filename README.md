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
messages.join: "&i[&a+&i] &a{PLAYER}&f joined the game"
messages.first_join: "&i[&a+&i] &a{PLAYER}&d joined the game for the first time"
messages.quit: "&i[&c-&i] &c{PLAYER}&f left the game"
messages.kick: "&i[&c-&i] &c{PLAYER}&f left the game"
messages.world_change: "&i[&b>&i] &a{PLAYER}&f moved from &e{ORIGIN} to &b{TARGET}"
messages.death: "&i[&c!&i] &c{PLAYER} died"
messages.death.contact: "&i[&c!&i] &c{PLAYER} was killed by {BLOCK}"
messages.death.kill: "&i[&c!&i] &c{PLAYER} was slain by &e{KILLER}"
messages.death.projectile: "&i[&c!&i] &c{PLAYER} was shot by &e{KILLER}"
messages.death.suffocation: "&i[&c!&i] &c{PLAYER} suffocated in a wall"
messages.death.fall: "&i[&c!&i] &c{PLAYER} fell from a high place"
messages.death.fire: "&i[&c!&i] &c{PLAYER} burned to death"
messages.death.on_fire: "&i[&c!&i] &c{PLAYER} went up in flames"
messages.death.lava: "&i[&c!&i] &c{PLAYER} tried to swim in lava"
messages.death.drowning: "&i[&c!&i] &c{PLAYER} drowned"
messages.death.explosion: "&i[&c!&i] &c{PLAYER} blew up"
messages.death.void: "&i[&c!&i] &c{PLAYER} fell out of the world"
messages.death.suicide: "&i[&c!&i] &c{PLAYER} died"
messages.death.magic: "&i[&c!&i] &c{PLAYER} was killed by magic"
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
