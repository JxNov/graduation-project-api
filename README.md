<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<h1 align="center"><a href="https://github.com/JxNov/graduation-project-api" target="_blank">Tech4School - WD53 🫰</a></h1>
<br/>

<h2 align="center">📝 Introduction Project 📝</h2>

<p>This project is developed with the purpose of providing APIs for the Elearning platform. The project is developed using
Laravel 10 and MySQL database. The project is developed with the following features:</p>

* User Management
* Course Management
* Lesson Management
* Role Management
* Permission Management
* ... and many more

<br/>

<h2 align="center">🤝 Development Team Members 🤝</h2>
<p><a href="https://github.com/JustHieu15" target="_blank">
<b>Leader</b> | Nguyễn Trung Hiếu - PH31062
</a></p>
<p><a href="https://github.com/JxNov" target="_blank">
<b>Member</b> | Nguyễn Mạnh Dũng - PH30947
</a></p>
<p><a href="https://github.com/vdoan1909" target="_blank">
<b>Member</b> | Nguyễn Văn Đoàn - PH33201
</a></p>
<p><a href="https://github.com/dominhkien" target="_blank">
<b>Member</b> | Đỗ Minh Kiên - PH32981
</a></p>

<br/>

<h2 align="center">🛠️ Technologies and Tools 🛠️</h2>
<p align="center">
<span><img src="https://img.shields.io/badge/PHP-282C34?logo=php&logoColor=777BB4" alt="PHP logo" title="PHP" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Laravel-282C34?logo=laravel&logoColor=FF2D20" alt="Laravel logo" title="Laravel" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/MySQL-282C34?logo=mysql&logoColor=4479A1" alt="MySQL logo" title="MySQL" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Composer-282C34?logo=composer&logoColor=885630" alt="Composer logo" title="Composer" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Postman-282C34?logo=postman&logoColor=FF6C37" alt="Postman logo" title="Postman" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Git-282C34?logo=git&logoColor=F05032" alt="Git logo" title="Git" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/GitHub-282C34?logo=github&logoColor=181717" alt="GitHub logo" title="GitHub" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Visual%20Studio%20Code-282C34?logo=visual-studio-code&logoColor=007ACC" alt="Visual Studio Code logo" title="Visual Studio Code" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/PhpStorm-282C34?logo=phpstorm&logoColor=000000" alt="PhpStorm logo" title="PhpStorm" height="25" /></span>
&nbsp;
<span><img src="https://img.shields.io/badge/Trello-282C34?logo=trello&logoColor=0079BF" alt="Trello logo" title="Trello" height="25" /></span>
&nbsp;
</p>

<br/>

<h2 align="center">🔧 Setup Project 🔧</h2>

### Install composer dependencies

```bash
composer install
```

### Create a copy of your .env file

```bash
cp .env.example .env
```

### Generate an app encryption key

```bash
php artisan key:generate
```

### JWT Secret Key

```bash
php artisan jwt:secret
```

### Create an empty database for our application

Create a database in your local machine and update the database credentials in .env file

### Migrate the database

```bash
php artisan migrate
```

### Seed the database (Optional, If any)

```bash
php artisan db:seed
```

### Link storage folder

```bash
php artisan storage:link
```

### Start the development server

```bash
php artisan serve
```
