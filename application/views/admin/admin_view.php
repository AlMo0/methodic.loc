<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Административная панель</title>
    <script src="<?= base_url(); ?>assembly/jquery/jquery.js" type="application/javascript"></script>
    <script src="<?= base_url(); ?>assembly/bootstrap/dist/js/bootstrap.js" type="application/javascript"></script>
    <script src="<?= base_url(); ?>assembly/my_script/admin_script.js" type="application/javascript"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link href="<?= base_url(); ?>assembly/bootstrap/dist/css/bootstrap.css" rel="stylesheet" type="text/css">
    <link href="<?= base_url(); ?>assembly/my_style/main_admin.css" rel="stylesheet" type="text/css">
    <link href="<?= base_url(); ?>assembly/my_style/fonts_style.css" rel="stylesheet" type="text/css">
</head>
<body>
<nav class="navbar navbar-fixed-top navbar-background">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed text-danger" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"
                    aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Логотип сайта</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="active"><a href="#"><i class="fa fa-cog"></i> Конфигурация панели<span class="sr-only">(current)</span></a></li>
                <li><a href="/"><i class="fa fa-android"></i> Перейти на сайт</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-archive"></i> Дополнительная панель
                        <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Action</a></li>
                        <li><a href="#">Another action</a></li>
                        <li><a href="#">Something else here</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="#"><i class="fa fa-user"></i> Логин</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-language"></i> Язык сайта
                        <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Русский</a></li>
                        <li><a href="#">Украинский</a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container-fluid -->
</nav>
</div>
</nav>
<div class="container-fluid">
    <div class="row">
        <!-- Навигация по админ панели слева START -->
        <div id="navigation_left_menu" class="col-md-3 col-lg-2 col-sm-3">
            <div class="user_admin_panel">
                <img src="/assembly/img/profil.jpg" class="img-rounded" width="45px" height="45px">
                <span class="small">Alexandr Moskalenko</span>
            </div>
            <div class="menu_section">
                <h4 class="text-uppercase">Главная панель</h4>
                <ul class="list-unstyled">
                    <li><i class="fa fa-home"></i><span> Основная панель</span></li>
                    <li class="list_menu"><i class="fa fa-tasks"></i><span> Список задач </span><i class="fa fa-sort"></i></li>
                    <li class=""><i class="fa fa-check"></i><span> - выполненные</span></li>
                    <li class=""><i class="fa fa-refresh fa-spin"></i><span> - в процессе</span></li>
                    <li class=""><i class="fa fa-tasks"></i><span> - отложенные</span></li>
                    <li><i class="fa fa-archive"></i><span> Отчеты</span><span class="badge pull-right">2</span></li>
                    <li><i class="fa fa-users"></i><span> Исполнители</span><span class="badge pull-right">1</span></li>
                    <li><i class="fa fa-line-chart"></i><span> Графики задач</span><span class="badge pull-right">1</span></li>
                </ul>
            </div>
            <div class="menu_section">
                <h4 class="text-uppercase">Настройка сайта</h4>
                <ul class="list-unstyled">
                    <li><i class="fa fa-edit"></i><span> Редактор страниц</span></li>
                    <li><i class="fa fa-sitemap"></i><span> SEO - страниц</span></li>
                    <li class="list_menu"><i class="fa fa-inbox"></i><span> Менеджер файлов </span><i class="fa fa-sort"></i></li>
                    <li class=""><i class="fa fa-file-audio-o"></i><span> - аудио</span></li>
                    <li class=""><i class="fa fa-file-movie-o"></i><span> - видео</span></li>
                    <li class=""><i class="fa fa-file-photo-o"></i><span> - фото</span></li>
                    <li><i class="fa fa-cogs"></i><span> Модули сайта</span></li>
                    <li><i class="fa fa-language"></i><span> Язык сайта</span></li>
                </ul>
            </div>
            <div class="menu_section">
                <h4 class="text-uppercase">Конфигурация</h4>
                <ul class="list-unstyled">
                    <li><i class="fa fa-tachometer"></i><span> Мой аккаунт</span></li>
                    <li><i class="fa fa-key"></i><span> Уровени доступа</span></li>
                </ul>
            </div>
        </div>
        <!-- Навигация по админ панели слева END -->

        <!-- Содержимое основноного окна - контент START -->
        <div id="content_right_menu" class="col-md-10">
            <br>
            <ol class="breadcrumb">
                <li><a href="#">Главная панель</a></li>
                <li class="active">Основная панель</li>
            </ol>
            <div class="page-header">
                <h1>Основная панель
                    <small>Все параметеры административной панели</small>
                </h1>
            </div>
            <div>
            <span>
            <?= anchor('home/about', 'Главная страница сайта'); ?>
            </span>
            </div>
            <br>
        </div>
        <!-- Содержимое основноного окна - контент END -->
    </div>
</div>

<nav class="navbar-fixed-bottom navbar-background">
    <div class="container">
        <div class="well-sm text-center">
            <span>Административная панель навигации - 2016г.</span>
        </div>
    </div>
</nav>
</body>
</html>