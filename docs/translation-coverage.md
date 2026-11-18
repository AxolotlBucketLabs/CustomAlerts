# Translation coverage

The catalog is generated from Axolotl-PM `resources/translations/eng.ini` at commit `77f832da28e5b52996207ca5a8d8b54fd827b77e`.

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

The text adapter accepts only catalogued client-side keys, and processes only translation, translated-popup, and jukebox-popup packet types with `needsTranslation` enabled. Numeric parameters are substituted from the packet. If a configured argument is missing, the original packet is kept and the plugin logs one warning for that key/argument pair.

The disconnect adapter matches an exact already-rendered message. This works only when the configured source text matches the server's current language. Event adapters should be preferred when available because they retain the original event context.

## Resource pack

Full `key=value` locale files in `plugin_data/CustomAlerts/translations/*.ini` are loaded by their Bedrock locale filename. The included `vi_VN.ini` contains all 658 audited keys from the pinned core catalog, while only its 352 catalogued client-side entries are sent in the generated resource pack. The 306 server-side entries remain in the file for reference and event-specific configuration; they cannot replace the global server language. The data-folder copy is preserved during plugin updates, so keys added by a newer core need to be merged manually and added to an updated plugin catalog. Locale placeholders in `{%0}` form are converted to Bedrock `.lang` placeholders when building the pack. Inline `resource-pack.translations` values override file entries.

The generated pack contains `manifest.json`, `texts/languages.json`, and one `texts/<locale>.lang` file per loaded locale. The pack is added at the top of the offered resource-pack stack so its translations take precedence over lower packs. A real Bedrock-client check is still required to confirm the displayed result for each locale and client version.

## Out of scope

- Global replacement of the server `Language` object.
- Console logs, setup screens shown before plugins load, or startup output.
- A generic replacement for server-translated strings whose source key is no longer present in the outgoing packet.
- Any resource-pack behavior beyond language entries configured by the operator.
