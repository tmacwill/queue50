<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="language" content="en" />

        <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    </head>
    <body>
        <div class="navbar">
            <div class="navbar-inner">
                <div class="container-fluid">
                    <a class="brand" href="#">
                        This is CS50 Queue. <b class="caret"></b>
                    </a>
                    <ul class="nav">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">Menu <b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="#">Leaderboard</a></li>
                            </ul>
                        </li>
                    </ul>

                    <ul class="nav pull-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">CS50 <b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="#">CS164</a></li>
                            </ul>
                        </li>
                        <li><a href="/auth/login?return=<?= Yii::app()->request->requestUri ?>">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php echo $content; ?>

    </body>
</html>
