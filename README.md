# CustomAlerts

CustomAlerts lets a PocketMine-MP or Axolotl-PM server owner change player-facing messages from YAML and full locale files. It uses server events for join, quit, chat, death, kick and login messages, packet overrides for recognized client translation keys, and a generated Bedrock resource pack for client language strings.

## Requirements and installation

- Axolotl-PM or PocketMine-MP API 5 with PHP 8.3 or newer.
- PHP `zip` extension.
- Packet overrides were checked against Axolotl-PM stable commit [`77f832d`](https://github.com/axolotl-pm/PocketMine-MP/commit/77f832da28e5b52996207ca5a8d8b54fd827b77e), version 5.51.3, with BedrockProtocol `64.0.0+bedrock-1.26.60`.

Build the plugin PHAR with PHP:

```sh
php -dphar.readonly=0 tools/build-plugin.php
```

Copy `build/CustomAlerts.phar` to the server's `plugins/` directory and start the server once. Edit `plugin_data/CustomAlerts/config.yml`, then run `/customalerts reload`. A `ca` alias is available. The reload permission is `customalerts.command.reload` and defaults to operators.

After a successful reload the sender sees how many messages and packet overrides are active and whether the resource pack was built. Configuration warnings are listed in chat, up to ten of them, and all of them are also written to the console. A failed reload shows the error and keeps the previous configuration running.

## Configuration

The configuration uses `config-version: 2`. An older `config.yml` is rejected with an error, so move it aside to generate the new default.

Each message is a single key. A missing key keeps the server's message, a string replaces it, and `false` hides it. Only `join`, `quit`, `chat`, `death.broadcast` and `kick.broadcast` can be hidden. Hiding chat cancels the chat event for everyone.

The default `config.yml` sets every message to an English template, so installing the plugin changes these messages right away. Set `custom-messages: false` to turn off every `messages.*` entry at once and keep the server's own messages. Packet overrides and the resource pack are not affected by this switch.

```yaml
config-version: 2
messages:
  join: "§8[§a+§8] §f{display_name} §7joined the server"
  quit: false
  death:
    screen: "§cYou died."
  pre-login:
    banned:
      reason: "§cYou are banned from this server."
```

The available paths are `join`, `quit`, `chat`, `death.broadcast`, `death.screen`, `kick.broadcast`, `kick.reason`, `kick.screen`, `login` and `pre-login.<server-full|whitelist|banned>.<reason|screen>`, all under `messages`. The login message applies only when another plugin has already rejected the login, and pre-login messages apply only to a rejection that is already set.

The sample templates in `resources/config.yml` use a consistent color style. Bedrock formatting codes such as `§8`, `§a` and `§f` pass through in event, packet and disconnect messages.

Named event placeholders:

| Placeholder | Value |
| --- | --- |
| `{player}` | Player username |
| `{display_name}` | Player display name |
| `{world}` | World folder name |
| `{message}` | Chat text |
| `{reason}` | Existing disconnect or quit reason where available |
| `{screen}` | Existing disconnect screen message |
| `{killer}` | Attacking player's display name, the attacking mob's name, or `messages.death.killer-fallback` (default `environment`) |
| `{x}`, `{y}`, `{z}` | Player block coordinates |

Pre-login messages only have `{player}`, because no player object exists yet. A placeholder that belongs to another message, such as `{killer}` in `join`, produces a warning on load. Unknown placeholders are left unchanged and logged once, unless another plugin registers them through the API below.

For a client-side translation packet, use the numeric argument placeholders from the source key in the same `{%0}` form as locale files. For example:

```yaml
packets:
  text:
    "death.attack.generic": "§8[§cX§8] §f{%0} §7died"
```

Keys from [`translation-catalog.json`](resources/translation-catalog.json) have their placeholders checked against the source translation, and server-side keys are rejected. Keys missing from the catalog are accepted with a warning and are not checked. Overrides apply when a matching key is still present in a `TextPacket` with client translation enabled. Translation packets become plain messages, while popup and jukebox popup packets keep their packet type. If the server forces its own language or sends an already-rendered string, that key cannot be recovered from the packet and the override is skipped.

Disconnect packet messages are already rendered strings. They can be replaced only by matching the exact message currently sent by the server:

```yaml
packets:
  disconnect:
    "You are banned from this server.": "§cYou cannot join this server."
```

### Full locale files

The plugin includes a full Vietnamese file with all 658 keys from the audited Axolotl-PM translation catalog. On first start it copies the file to `plugin_data/CustomAlerts/translations/vi_VN.ini`. Edit that data-folder copy; `/customalerts reload` rebuilds the pack and the plugin does not overwrite your edits during updates. The copy also does not automatically gain keys added by later core versions: merge the newer upstream locale file and update the plugin's translation catalog before those keys can be used. Add another locale as a file named after its Bedrock locale, such as `en_US.ini` or `fr_FR.ini`.

Enable the resource pack to offer the client-side entries from those files:

```yaml
resource-pack:
  enabled: true
  required: false
  translations: {}
```

Locale files use PocketMine's `key=value` format and `{%0}`-style placeholders. The pack builder converts placeholders to Bedrock `.lang` syntax. Inline `resource-pack.translations` entries remain available and override the same key from a locale file.

The full file also retains server-side `pocketmine.*` entries for reference, but a plugin cannot replace the server's global `Language` object. CustomAlerts leaves server-side entries and catalogued entries with mismatched placeholders out of the resource pack. Keys that are not in the catalog, such as other Bedrock client strings, are packaged without placeholder checks. Supported event messages remain configurable under `messages.*`. Players who are already connected need to reconnect to receive an updated pack. `required: true` asks players to accept this pack. Older generated packs in the data folder are removed when a new one is built.

## Plugin API

Other plugins can get the API from the plugin manager. Declare `softdepend: [CustomAlerts]` or `depend: [CustomAlerts]` so CustomAlerts loads first.

```php
use AxolotlBucketLabs\CustomAlerts\alert\AlertContext;
use AxolotlBucketLabs\CustomAlerts\CustomAlerts;

$plugin = $this->getServer()->getPluginManager()->getPlugin("CustomAlerts");
if($plugin instanceof CustomAlerts){
	$plugin->getPlaceholders()->register($this, "rank", fn(AlertContext $context) : ?string => $this->getRankName($context->getPlayerName()));
}
```

Registered placeholders can be used in any `messages.*` template. Built-in names cannot be registered. Register from `onEnable`, because the owning plugin must be enabled. Its placeholders are removed automatically when it is disabled. `AlertContext::getPlayer()` is `null` for pre-login messages.

Listen for `AxolotlBucketLabs\CustomAlerts\alert\AlertRenderEvent` to change a rendered message before it is applied. Cancelling the event keeps the server's original message.

```php
public function onAlertRender(AlertRenderEvent $event) : void{
	if($event->getType() === AlertType::Join && $event->getPlayer()?->hasPermission("myplugin.vip") === true){
		$event->setMessage("§6VIP " . $event->getMessage());
	}
}
```

Call `CustomAlerts::reloadAlerts()` to reload the configuration from code. It returns a `ReloadResult` with `isSuccessful()`, `getError()`, `getWarnings()`, the loaded `getConfig()` and the built `getLanguagePack()`.

## Coverage and limitations

The translation catalog records all 658 keys in the audited Axolotl-PM `eng.ini` and the placeholder indices for each key. It contains 306 server-side and 352 client-side keys. `pocketmine.*` entries are translated by the server before many packets are sent, so they are only customizable through a supported semantic event adapter. Other entries can be intercepted if they remain in an outgoing translation packet, or customized with a Bedrock resource pack if rendered by the client. See [translation coverage](docs/translation-coverage.md).

CustomAlerts does not replace the server's global `Language` object. It cannot generically alter startup/setup text, console logs, or server-translated output after its source key has been discarded. Packet classes live in the internal `pocketmine\network\mcpe` namespace, so packet behavior is verified for the exact Axolotl-PM revision above; the API manifest's `5.0.0` floor does not claim packet compatibility across every API 5 release.

## Development checks

```sh
composer test
composer analyse
composer build
```

Regenerate the catalog when changing the server source revision:

```sh
php tools/generate-translation-catalog.php /path/to/PocketMine-MP/resources/translations/eng.ini resources/translation-catalog.json
```

## License

CustomAlerts code is licensed under the MIT License. The bundled `vi_VN.ini` translation data has its own attribution and LGPL license in [`resources/translations`](resources/translations/NOTICE.md).
