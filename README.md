# Mini Assessment WordPress Plugin

WordPress Plugin theo kiến trúc headless để quản lý Assessment, Question và Answer thông qua REST API `assessment/v1`.

## Chức năng

- Custom database tables với migration khi activate/nâng cấp.
- Public API cho dữ liệu publish có phân trang và tìm kiếm.
- Role matrix cấu hình được cho thao tác Assessment, Question và Answer.
- Role `assessment_manager` và các trang quản lý trong WordPress Admin.
- JWT access token 15 phút và refresh-token HttpOnly xoay vòng 7 ngày.
- Logging tối thiểu cho lỗi database, không ghi credentials, token hoặc request payload.

## Cài đặt

1. Copy thư mục này vào `wp-content/plugins/wp-assessment-plugin`.
2. Kích hoạt **Mini Assessment Plugin** trong WordPress Admin.
3. Mở **Mini Assessment** trong wp-admin để cấu hình role permission và quản lý dữ liệu.

## Xác thực

JWT đã được tích hợp trực tiếp trong Mini Assessment Plugin. Khách hàng **không cần cài plugin JWT Authentication riêng**, không cần tạo JWT secret mới và không cần cấu hình endpoint bổ sung. Chỉ cần cài/kích hoạt Mini Assessment Plugin là các endpoint `/auth/login`, `/auth/refresh`, `/auth/logout` và `/auth/me` sẵn sàng sử dụng.

`POST /wp-json/assessment/v1/auth/login` trả access token ngắn hạn và thiết lập refresh cookie HttpOnly. Gửi access token bằng `Authorization: Bearer <token>` khi gọi API cần bảo vệ. SPA tự refresh access token; refresh cookie được xoay vòng sau mỗi lần sử dụng.

Plugin ký JWT bằng `AUTH_KEY` có sẵn của WordPress. Nếu website chưa khai báo riêng `AUTH_KEY`, plugin tự dùng `wp_salt( 'auth' )` của WordPress, vì vậy không chặn quá trình cài đặt. Ở production chỉ cần dùng HTTPS và đặt origin frontend được phép qua filter `wp_assessment_allowed_origins`.
