<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo Năm Học Mới</title>
</head>

<body style="background-color: #f3f4f6; font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <div
            style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
            <div style="position: relative; height: 192px;">
                <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1" alt="Trường Học"
                    style="width: 100%; height: 100%; object-fit: cover;">
                <div
                    style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.5)); display: flex; align-items: flex-end; padding: 24px;">
                    <h1 style="color: #ffffff; font-size: 24px; font-weight: bold;">Chào mừng trở lại <span
                            style="color: #fbbf24;">Tech4School</span></h1>
                </div>
            </div>

            <div style="padding: 24px; line-height: 1.6;">
                <div>
                    <p style="color: #4b5563;">Kính gửi
                        <span style="font-weight: 600; color: #3b82f6;">
                            {{ $student->name }}
                        </span>,
                    </p>
                    <p style="color: #6b7280;">Chúng tôi hy vọng bạn nhận được email này trong tình trạng tốt! Chúng tôi
                        rất vui mừng chào đón bạn quay trở lại cho năm học mới.</p>
                </div>

                <div style="background-color: #eff6ff; padding: 16px; border-radius: 8px; margin-top: 16px;">
                    <h2 style="font-size: 16px; font-weight: 600; color: #1e3a8a;">Thông Tin Của Bạn</h2>
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #3b82f6; font-size: 16px;">📧</span>
                            <span style="color: #6b7280;"> {{ $student->email }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #3b82f6; font-size: 16px;">📅</span>
                            <span style="color: #6b7280;"> {{ $student->date_of_birth }}</span>
                        </div>
                    </div>
                </div>

                <div style="background-color: #fef3c7; padding: 16px; border-radius: 8px; margin-top: 16px;">
                    <h2 style="font-size: 16px; font-weight: 600; color: #b45309; margin-bottom: 8px;">Ngày Quan Trọng
                    </h2>
                    <p style="color: #92400e;">Năm Học Bắt Đầu: <span
                            style="font-weight: 600;">{{ $academicYear->start_date }}</span>
                    </p>
                </div>

                <div style="margin-top: 16px;">
                    <h2 style="font-size: 16px; font-weight: 600; color: #111827;">Sắp Tới Là Gì?</h2>
                    <ul style="list-style-type: disc; padding-left: 20px; color: #6b7280;">
                        <li>Xem lại lịch học của bạn</li>
                        <li>Chuẩn bị dụng cụ học tập</li>
                        <li>Tham gia các buổi định hướng</li>
                    </ul>
                </div>

                <div style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 16px;">
                    <p style="font-size: 14px; color: #9ca3af; text-align: center;">Nếu bạn có bất kỳ câu hỏi nào, đừng
                        ngần ngại liên hệ với chúng tôi.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
