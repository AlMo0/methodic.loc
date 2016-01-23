<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сайт об автомобилях и новых технологиях</title>
    <script src="../vendor/components/jquery/jquery.js" ></script>
    <script src="../vendor/twbs/bootstrap/dist/js/bootstrap.js" type="application/javascript"></script>
    <link href="../vendor/twbs/bootstrap/dist/css/bootstrap.css" rel="stylesheet" type="text/css">
    <link href="../web/css/main.css" rel="stylesheet" type="text/css">
</head>
<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Логотип</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li><a href="#">Главная <span class="sr-only">(current)</span></a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Авто новости <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Новинки 2015 года</a></li>
                        <li><a href="#">События</a></li>
                        <li><a href="#">Авто шоу</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Новости авто рынка</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Авто в кредит</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Ремонт автомобилей <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Частые поломки авто</a></li>
                        <li><a href="#">Как бортировать колесо</a></li>
                        <li><a href="#">Как доехать без топлива</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Куда деть запаску</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Тонировать или как?</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Советы автолюбителям <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Правильно собрать аптечку</a></li>
                        <li><a href="#">Как правильно оформить ДТП?</a></li>
                        <li><a href="#">Зачем знать ПДД?</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Где сдать на права</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="#">Зеленая карта</a></li>
                    </ul>
                </li>
                <li><a href="#">F.A.Q.</a></li>
            </ul>
            <!--<form class="navbar-form navbar-right" role="search">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Search" size="12">
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>-->
            <ul class="nav navbar-nav navbar-right">
                <li><a href="#">Войти</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Язык сайта <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Русский</a></li>
                        <li><a href="#">Украинский</a></li>
                    </ul>
                </li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
<!-- Конец шапки сайта (меню) -->

<!-- Основной контент сайта -->

<div class="container-fluid">

    <div class="page-header">
        <h1>Example page header <small>Subtext for header</small></h1>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-4">
            <div class="thumbnail">
                <img src="..." alt="...">
                <div class="caption">
                    <h3>Thumbnail label</h3>
                    <p>...</p>
                    <p><a href="#" class="btn btn-primary" role="button">Button</a> <a href="#" class="btn btn-default" role="button">Button</a></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="thumbnail">
                <img src="..." alt="...">
                <div class="caption">
                    <h3>Thumbnail label</h3>
                    <p>...</p>
                    <p><a href="#" class="btn btn-primary" role="button">Button</a> <a href="#" class="btn btn-default" role="button">Button</a></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="thumbnail">
                <img src="..." alt="...">
                <div class="caption">
                    <h3>Thumbnail label</h3>
                    <p>...</p>
                    <p><a href="#" class="btn btn-primary" role="button">Button</a> <a href="#" class="btn btn-default" role="button">Button</a></p>
                </div>
            </div>
        </div>

    </div>

</div>
</body>
</html>