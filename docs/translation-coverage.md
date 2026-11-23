# Translation coverage

The catalog is generated from Axolotl-PM `resources/translations/eng.ini` at commit [`77f832d`](https://github.com/axolotl-pm/PocketMine-MP/commit/77f832da28e5b52996207ca5a8d8b54fd827b77e).

| Group | Keys | How CustomAlerts can reach them |
| --- | ---: | --- |
| `pocketmine.*` | 306 | The server resolves these keys before sending many messages. The plugin changes selected event messages through event adapters. Other rendered output, console text and setup text has no generic translation-key hook. |
| Other keys | 352 | The resource-pack config can override client-rendered language strings. The packet adapter can replace a key only when it is present in an outgoing `TextPacket` and client translation is enabled. |
| **Total** | **658** | See the key-level metadata in [`translation-catalog.json`](../resources/translation-catalog.json). |

Each catalog entry records its original English value, translation side, numeric placeholder indices, and possible customization route. These routes describe the server/client translation boundary, not a guarantee that every key is emitted as a packet. The exact packet path depends on the call site and server language settings.

## Event adapters

| Event family | Configuration path | Behavior |
| --- | --- | --- |
| Join and quit | `messages.join`, `messages.quit` | Replace or hide broadcast messages. |
| Chat | `messages.chat` | Replace the chat formatter or cancel chat when `hide` is enabled. |
| Death | `messages.death` | Replace broadcast death text and death-screen text separately. |
| Kick | `messages.kick` | Replace quit broadcast, server reason, and player disconnect screen independently. |
| Login rejected by another plugin | `messages.login` | Replace the login kick message only when the event is already cancelled. |
| Server full, whitelist, ban | `messages.pre-login.*` | Replace only a kick flag already set by the server or another plugin; never creates a rejection. |

## Packet adapter

The text adapter rejects catalogued server-side keys and checks the `{%0}` placeholders of catalogued client-side keys. Keys outside the catalog are accepted unchecked with a warning. It processes only translation, translated-popup, and jukebox-popup packet types with `needsTranslation` enabled, and popup types keep their packet type. Numeric parameters are substituted from the packet. If a configured argument is missing, the original packet is kept and the plugin logs one warning for that key/argument pair.

The disconnect adapter matches an exact already-rendered message. This works only when the configured source text matches the server's current language. Event adapters should be preferred when available because they retain the original event context.

## Resource pack

Full `key=value` locale files in `plugin_data/CustomAlerts/translations/*.ini` are loaded by their Bedrock locale filename. The plugin includes `bg_BG`, `de_DE`, `en_US`, `es_ES`, `fr_FR`, `it_IT`, `ja_JP`, `ko_KR`, `lv_LV`, `nl_NL`, `ru_RU`, `tr_TR`, `vi_VN` and `zh_CN`. Every bundled key matches the pinned catalog, and the tests verify each value's placeholder indexes. English contains all 658 catalog keys, including 352 client-side entries. Translation coverage differs by language because the upstream locale files are incomplete. The plugin does not fill missing translations with English text. The game uses its normal language entry when a key is absent from the pack.

The generated pack includes both `languages.json` and `language_names.json`. This lists standard locale names and exposes custom IDs such as `lv_LV` in the game language selector.

The bundled locale files preserve server-side entries for reference, but only client-side entries are sent in the generated pack. The 306 `pocketmine.*` entries in the catalog cannot replace the global server language. User-added keys outside the catalog remain accepted without placeholder checks. The data-folder copies are preserved during plugin updates, so keys added by a newer core need to be merged manually and added to an updated plugin catalog. Locale placeholders in `{%0}` form are converted to Bedrock `.lang` placeholders when building the pack. Inline `resource-pack.translations` values override file entries.

The generated pack contains `manifest.json`, `texts/languages.json`, and one `texts/<locale>.lang` file per loaded locale. The pack is added at the top of the offered resource-pack stack so its translations take precedence over lower packs. Protocol tests can verify pack delivery and locale file contents. A real Bedrock client is still required to confirm displayed text for each locale and client version.

## Out of scope

- Global replacement of the server `Language` object.
- Console logs, setup screens shown before plugins load, or startup output.
- A generic replacement for server-translated strings whose source key is no longer present in the outgoing packet.
- Any resource-pack behavior beyond language entries configured by the operator.
