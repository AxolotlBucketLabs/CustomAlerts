# CustomAlerts Roadmap

## Mục tiêu

Cho phép quản trị viên thay thế hoặc ẩn một số thông báo hệ thống được gửi tới toàn server bằng cấu hình. Tất cả người chơi nhận cùng nội dung đã cấu hình.

## Phạm vi hiện tại

- Thông báo người chơi vào server.
- Thông báo người chơi rời server.
- Thông báo người chơi chết, gồm tên người hoặc mob gây sát thương khi có.
- Thông báo người chơi bị kick.
- Reload cấu hình bằng `/customalerts reload`.

Plugin dùng các event công khai của Axolotl-PM. Nội dung chat của người chơi và tin nhắn gửi riêng được giữ nguyên.

## Ngoài phạm vi

- Dịch riêng theo ngôn ngữ của từng client.
- Ghi đè packet hoặc thông báo giao diện do client dịch.
- Phát resource pack ngôn ngữ.
- Thay đổi tin nhắn đăng nhập, màn hình kick hoặc lý do disconnect gửi riêng cho một người.
- Thay đổi console log, thông báo khởi động hoặc nội dung của plugin khác.

Các phạm vi này cần những đường xử lý và kiểm tra riêng. Chỉ thêm lại khi có nhu cầu rõ ràng và một kế hoạch kiểm chứng phù hợp.
