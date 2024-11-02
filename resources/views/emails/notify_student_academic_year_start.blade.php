<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo Năm Học Mới</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="max-w-2xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="relative h-48">
                <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1" alt="Trường Học"
                    class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/50 flex items-end p-6">
                    <h1 class="text-white text-3xl font-bold">Chào mừng trở lại <span
                            class="text-yellow-300">Tech4School</span></h1>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="space-y-2">
                    <p class="text-gray-700">Kính gửi <span
                            class="font-semibold text-blue-600">{{ $student->name }}</span>,
                    </p>
                    <p class="text-gray-600">Chúng tôi hy vọng bạn nhận được email này trong tình trạng tốt! Chúng tôi
                        rất vui mừng chào đón bạn quay trở lại cho năm học mới.</p>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg space-y-2">
                    <h2 class="text-lg font-semibold text-blue-800">Thông Tin Của Bạn</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                            <span class="text-gray-600">{{ $student->email }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-600">{{ $student->date_of_birth }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h2 class="text-lg font-semibold text-yellow-800 mb-2">Ngày Quan Trọng</h2>
                    <p class="text-yellow-700">Năm Học Bắt Đầu: <span
                            class="font-semibold">{{ \Carbon\Carbon::parse($academicYear->start_date)->format('d/m/Y') }}</span>
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800">Sắp Tới Là Gì?</h2>
                    <ul class="list-disc list-inside text-gray-600 space-y-2">
                        <li>Xem lại lịch học của bạn</li>
                        <li>Chuẩn bị dụng cụ học tập</li>
                        <li>Tham gia các buổi định hướng</li>
                    </ul>
                </div>

                <div class="border-t pt-6">
                    <p class="text-sm text-gray-500 text-center">Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ
                        với chúng tôi.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
