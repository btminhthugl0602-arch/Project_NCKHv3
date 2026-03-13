-- Add configurable max number of teams a student can join per event.
ALTER TABLE sukien
ADD COLUMN soNhomToiDaSV INT NOT NULL DEFAULT 1
COMMENT 'So doi toi da moi sinh vien duoc tham gia trong su kien'
AFTER isActive;
