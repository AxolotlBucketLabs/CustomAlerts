# CustomAlerts

CustomAlerts lets a PocketMine-MP or Axolotl-PM server owner change player-facing messages from YAML and full locale files. It uses server events for join, quit, chat, death, kick and login messages, packet overrides for recognized client translation keys, and a generated Bedrock resource pack for client language strings.

## Requirements and installation

- Axolotl-PM or PocketMine-MP API 5 with PHP 8.1 or newer.
- PHP `zip` extension.
- Packet overrides were checked against Axolotl-PM stable commit [`77f832d`](https://github.com/axolotl-pm/PocketMine-MP/commit/77f832da28e5b52996207ca5a8d8b54fd827b77e), version 5.51.3, with BedrockProtocol `64.0.0+bedrock-1.26.60`.

Build the plugin PHAR with PHP:

```sh
php -dphar.readonly=0 tools/build-plugin.php
```

Copy `build/CustomAlerts.phar` to the server's `plugins/` directory and start the server once. Edit `plugin_data/CustomAlerts/config.yml`, then run `/customalerts reload`. A `ca` alias is available. The reload permission is `customalerts.command.reload` and defaults to operators.

## Configuration

All message sections are disabled by default, so installing the plugin preserves the server's existing output. Enable only the messages you want to replace. Set `hide: true` on join, quit or chat to suppress that event's announcement. Setting chat `hide: true` cancels the chat event for everyone.

The sample templates in `resources/config.yml` use a consistent Vietnamese color style. Bedrock formatting codes such as `§8`, `§a` and `§f` pass through in event, packet and disconnect messages.

Named event placeholders:

| Placeholder | Value |
| --- | --- |
| `{player}` | Player username |
| `{display_name}` | Player display name |
| `{world}` | World folder name |
| `{message}` | Chat text |
| `{reason}` | Existing disconnect or quit reason where available |
| `{screen}` | Existing disconnect screen message |
| `{killer}` | Attacking player's display name, or `environment` |
| `{x}`, `{y}`, `{z}` | Player coordinates |

For a client-side translation packet, use the numeric argument placeholders from the source key. For example:

```yaml
packets:
  text:
    "death.attack.generic": "§8[§cX§8] §f{0} §7đã gục ngã"
```

Only known client-side keys from [`translation-catalog.json`](resources/translation-catalog.json) are accepted. Overrides apply when a matching key is still present in a `TextPacket` with client translation enabled. If the server forces its own language or sends an already-rendered string, that key cannot be recovered from the packet and the override is skipped.

Disconnect packet messages are already rendered strings. They can be replaced only by matching the exact message currently sent by the server:

```yaml
packets:
  disconnect:
    "You are banned from this server.": "§cBạn không thể tham gia máy chủ này."
```

### Full locale files

The plugin includes a full Vietnamese file with all 658 keys from the audited Axolotl-PM translation catalog. On first start it copies the file to `plugin_data/CustomAlerts/translations/vi_VN.ini`. Edit that data-folder copy; `/customalerts reload` rebuilds the pack and the plugin does not overwrite your edits during updates. The copy also does not automatically gain keys added by later core versions: merge the newer upstream locale file and update the plugin's translation catalog before those keys can be used. Add another locale as a file named after its Bedrock locale, such as `en_US.ini` or `fr_FR.ini`.

Enable the resource pack to offer all catalogued client-side entries from those files:

```yaml
resource-pack:
  enabled: true
  required: false
  translations: {}
```

Locale files use PocketMine's `key=value` format and `{%0}`-style placeholders. The pack builder converts placeholders to Bedrock `.lang` syntax. Inline `resource-pack.translations` entries remain available and override the same key from a locale file.

The full file also retains server-side `pocketmine.*` entries for reference, but a plugin cannot replace the server's global `Language` object. CustomAlerts packages only catalogued client-side entries in the resource pack; supported event messages remain configurable under `messages.*`. Players who are already connected need to reconnect to receive an updated pack. `required: true` asks players to accept this pack.

## Coverage and limitations

The translation catalog records all 658 keys in the audited Axolotl-PM `eng.ini` and the placeholder indices for each key. It contains 306 server-side and 352 client-side keys. `pocketmine.*` entries are translated by the server before many packets are sent, so they are only customizable through a supported semantic event adapter. Other entries can be intercepted if they remain in an outgoing translation packet, or customized with a Bedrock resource pack if rendered by the client. See [translation coverage](docs/translation-coverage.md).

CustomAlerts does not replace the server's global `Language` object. It cannot generically alter startup/setup text, console logs, or server-translated output after its source key has been discarded. Packet classes live in the internal `pocketmine\network\mcpe` namespace, so packet behavior is verified for the exact Axolotl-PM revision above; the API manifest's `5.0.0` floor does not claim packet compatibility across every API 5 release.

## Development checks

```sh
php tests/run.php
php -dphar.readonly=0 tools/build-plugin.php
```

Regenerate the catalog when changing the server source revision:

```sh
php tools/generate-translation-catalog.php /path/to/PocketMine-MP/resources/translations/eng.ini resources/translation-catalog.json
```

## License

CustomAlerts code is licensed under the MIT License. The bundled `vi_VN.ini` translation data has its own attribution and LGPL license in [`resources/translations`](resources/translations/NOTICE.md).
