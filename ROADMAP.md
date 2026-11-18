# CustomAlerts Roadmap

## Mục tiêu

Xây dựng plugin cho phép quản trị viên tùy chỉnh thông báo hiển thị cho người chơi qua cấu hình, không cần sửa source PocketMine-MP hoặc Axolotl-PM. Các thông báo phổ biến sẽ dùng adapter theo event. Các chuỗi còn giữ khóa dịch trong packet sẽ dùng bộ ghi đè chung.

## Cơ sở kỹ thuật

Audit `stable` tại commit [`77f832d`](https://github.com/axolotl-pm/PocketMine-MP/commit/77f832da28e5b52996207ca5a8d8b54fd827b77e) ghi nhận:

- `resources/translations/eng.ini` có 658 khóa, gồm 306 khóa `pocketmine.*` và 352 khóa vanilla, client hoặc setup.
- Server khởi tạo `Language` trước khi nạp plugin. API công khai không có setter để thay bảng dịch toàn cục.
- `DataPacketSendEvent` cho phép plugin đọc và thay packet gửi đến người chơi trên đường gửi packet mặc định. `TextPacket` và `DisconnectPacket` có trường nội dung có thể xử lý.
- Khóa vanilla có thể còn nguyên trong packet để client dịch. Khóa `pocketmine.*` được dịch phía server trước khi gửi, nên packet có thể không còn khóa ban đầu.
- Chuỗi giao diện do Minecraft client quản lý cần resource pack. Chuỗi log console, thông báo khởi động và trình cài đặt không nằm trong luồng packet gửi cho người chơi.

## Phạm vi

Plugin tập trung vào thông báo gửi tới người chơi, gồm chat, join/quit, death, kick, login, kết quả lệnh và một phần chuỗi dịch còn khóa trong packet. Resource pack là phần tùy chọn cho các chuỗi client-side như tên item, enchantment, potion và nhãn giao diện.

Plugin không thay thế đối tượng dịch toàn cục của server. Phiên bản đầu cũng không nhắm tới log console hoặc thông báo khởi động.

## Các giai đoạn

### Giai đoạn 0: Chốt nền tảng và hợp đồng cấu hình — Hoàn tất

- Đã xác nhận API 5, PHP 8.1+, PHP `zip`, Axolotl-PM stable commit [`77f832d`](https://github.com/axolotl-pm/PocketMine-MP/commit/77f832da28e5b52996207ca5a8d8b54fd827b77e), cùng BedrockProtocol `64.0.0+bedrock-1.26.60`.
- Đã tạo catalog 658 khóa từ `eng.ini`, ghi side dịch, giá trị English, placeholder và tuyến xử lý có thể có.
- Đã chốt schema YAML cho event, packet, bản dịch resource pack, reload và fallback.
- Khóa `pocketmine.*` được phân biệt với khóa client-side; khóa không hỗ trợ hoặc thiếu đối số giữ nguyên packet gốc.

**Hoàn tất khi** cấu hình có schema rõ ràng và từng nhóm chuỗi trong audit có đường xử lý hoặc được ghi rõ ngoài phạm vi.

### Giai đoạn 1: Adapter cho thông báo phổ biến — Đã triển khai

- Đã triển khai join/quit qua `PlayerJoinEvent` và `PlayerQuitEvent`.
- Đã triển khai chat qua formatter riêng, death broadcast/screen, kick broadcast/reason/screen, login bị từ chối và cờ pre-login server-full/whitelist/ban.
- Đã thêm placeholder có tên, các công tắc `enabled`, ẩn thông báo join/quit/chat và giữ nguyên mặc định khi tắt.
- GOLEM network quan sát được thông báo join và quit đã cấu hình giữa hai client kết nối cùng server; packet `Text` mang đúng nội dung `JOIN OK ...` và `QUIT OK ...`.
- Việc nạp plugin và reload cấu hình đã smoke-test trên server; GOLEM xác nhận luồng packet, không xác nhận cách Minecraft retail vẽ giao diện.
- Mẫu cấu hình đã được viết lại bằng tiếng Việt với bảng màu nhất quán; các override vẫn tắt mặc định. Phản hồi lệnh reload cũng dùng cùng định dạng.

**Hoàn tất khi** quản trị viên đổi được các thông báo trên chỉ bằng cấu hình và plugin giữ đúng dữ liệu của từng event.

### Giai đoạn 2: Bộ ghi đè packet theo khóa dịch — Đã triển khai

- Đã triển khai `DataPacketSendEvent` cho `TextPacket` translation/popup/jukebox có `needsTranslation`.
- Đã thêm thay thế `DisconnectPacket` bằng so khớp chuỗi nguồn chính xác vì nội dung đã được dịch trước khi gửi.
- Catalog giới hạn packet override vào khóa client-side; tham số thiếu giữ packet gốc và chỉ cảnh báo một lần cho mỗi cặp khóa/đối số.
- GOLEM kích hoạt `/kill` trên server thử nghiệm và capture nhận packet `Text` có nội dung `CustomAlert PACKET OVERRIDE`, xác nhận override `death.attack.generic` tới được client protocol.
- Đã giữ nguyên người nhận và các packet không khớp. GOLEM đọc packet sau khi nhận, không xác nhận cách Minecraft retail hiển thị nó.

**Hoàn tất khi** override không làm mất tham số, loại packet, người nhận hoặc thông báo không nằm trong cấu hình.

### Giai đoạn 3: Resource pack cho ngôn ngữ phía client — Đã kiểm tra trao đổi protocol

- Đã tạo `.mcpack` từ full locale `.ini` hoặc các override inline trong YAML, gồm `manifest.json`, `texts/languages.json`, `texts/<locale>.lang`.
- Đã thêm file `vi_VN.ini` đủ 658 khóa và cơ chế tự chép/nạp locale file trong thư mục dữ liệu; chỉ khóa client-side được đưa vào pack, khóa server-side vẫn giữ trong file để tham khảo.
- Đã thêm pack qua `PlayerResourcePackOfferEvent`; có thể yêu cầu người chơi chấp nhận khi pack được tạo thành công.
- GOLEM network hoàn tất trao đổi pack bắt buộc: capture ghi nhận một pack `CustomAlerts Language Overrides`, các trạng thái tải pack đến `COMPLETED`, rồi client spawn thành công.
- Archive nhận qua network có `texts/en_US.lang` với các giá trị cấu hình `enchantment.protect.all` và `death.attack.generic`; server cũng đã nạp `ZippedResourcePack`.
- GOLEM là protocol client, không render giao diện như Minecraft retail; chưa xác nhận enchantment name hoặc nhãn client-side hiển thị trong UI.

**Hoàn tất khi** server tạo và nạp pack, một client protocol hoàn tất handshake và archive chứa đúng locale/key đã cấu hình. Kết quả render trên Minecraft retail được ghi là phạm vi xác nhận riêng.

### Giai đoạn 4: Kiểm tra tương thích và phát hành — Kiểm tra source/server/protocol hoàn tất, chưa tạo release

- Đã chạy PHP lint, `tests/run.php`, PHPStan level 8 và build PHAR.
- Đã dựng server từ source commit mục tiêu, nạp/enabled plugin, chạy `/customalerts reload`, tạo pack và nạp pack bằng API server.
- Đã chạy [BedrockDebugProxy GOLEM network](https://github.com/NhanAZ/BedrockDebugProxy/tree/919edf3437f1631fb010097da465478760928207) với server local Axolotl-PM `5.51.3+dev` / Bedrock `v26.60`: kiểm tra join/quit qua hai client, death packet override qua `/kill`, pack handshake bắt buộc và spawn.
- GOLEM xác nhận dữ liệu packet và resource-pack archive trên wire; không xác nhận Minecraft retail render chuỗi trong chat, màn hình chết hoặc UI.
- Đã ghi nhóm ngoài phạm vi và quy trình sinh lại catalog khi đổi upstream.
- Chưa tạo release hoặc công bố tương thích ngoài revision server đã kiểm tra.

**Hoàn tất khi** plugin có kết quả kiểm tra server và network protocol phù hợp với phạm vi đã công bố; mọi tuyên bố về render retail phải có kiểm tra riêng.

## Quy tắc tương thích

- Ưu tiên event API khi event cung cấp nội dung và dữ liệu ngữ cảnh phù hợp.
- Dùng packet hook cho các thông báo client-bound có định dạng nhận diện được.
- Dùng resource pack cho chuỗi mà client tự hiển thị từ bộ ngôn ngữ Bedrock.
- Giữ nguyên nội dung khi plugin không có override tương ứng.
- Rà lại danh mục khóa và packet liên quan mỗi khi đổi phiên bản Axolotl-PM mục tiêu.

## Trạng thái hiện tại

Code plugin, build PHAR và roadmap đã có trong workspace. PHP lint, `tests/run.php`, PHPStan level 8, smoke test server và GOLEM network đều chạy qua trên Axolotl-PM `5.51.3+dev` (Bedrock `v26.60`). GOLEM xác nhận join/quit, chat, phản hồi reload, death packet override có mã màu, required resource-pack handshake, nội dung archive và spawn. Mẫu cấu hình và phản hồi lệnh đã được Việt hóa, tô màu; override vẫn tắt mặc định. Minecraft retail chưa được dùng nên kết quả render trong game chưa được xác nhận; chưa phát hành artifact ra ngoài.
