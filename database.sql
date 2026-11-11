CREATE DATABASE IF NOT EXISTS QLHocPhan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE QLHocPhan;

DROP TABLE IF EXISTS GiangVien;
CREATE TABLE GiangVien (
    IDGV     VARCHAR(20) NOT NULL PRIMARY KEY,
    Fullname VARCHAR(100) NOT NULL,
    Image    VARCHAR(255) DEFAULT NULL,
    SumClass INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yêu cầu 1: thêm dữ liệu vào bảng Giảng viên
INSERT INTO GiangVien (IDGV, Fullname, Image, SumClass) VALUES
('GV01', 'Nguyễn Thị Lan',    'https://img.lovepik.com/free-png/20211116/lovepik-teachers-educate-portraits-png-image_400965146_wh1200.png', 5),
('GV02', 'Trần Thị Mai',      'https://htmediagroup.vn/wp-content/uploads/2022/09/Anh-cty-9-min.jpg', 3),
('GV03', 'Lê Thị Hương',      'https://www.minhtunailspa.com/wp-content/uploads/2025/09/hinh-anh-chan-dung-nu-chup-anh-the-cuc-dep-3x4-2.webp', 4),
('GV04', 'Phạm Thị Ngọc',     'https://studiochupanhdep.com/Upload/Images/Album/anh-nu-doanh-nhan-22.jpg', 6),
('GV05', 'Võ Thị Thu',        'https://img3.thuthuatphanmem.vn/uploads/2019/07/05/anh-chan-dung-con-gai-toc-ngan_082837328.jpg', 2),
('GV06', 'Nguyễn Văn Hùng',   'https://htmediagroup.vn/wp-content/uploads/2022/11/Anh-giam-doc-nam-01-min.jpg', 7),
('GV07', 'Đặng Thị Hà',       'https://studiochupanhdep.com/Upload/Images/Album/hinh-anh-doanh-nhan.jpg', 4),
('GV08', 'Bùi Thị Linh',      'https://studiochupanhdep.com/Upload/Images/anh-chan-dung-ai-01.png', 3),
('GV09', 'Hoàng Thị Yến',     'https://studiochupanhdep.com/Upload/Images/Album/anh-nu-doanh-nhan-28.jpg', 5),
('GV10', 'Đỗ Thị Phương',     'https://htmediagroup.vn/wp-content/uploads/2022/09/Anh-cty-4-min.jpg', 6);
