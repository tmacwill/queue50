<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="language" content="en" />

        <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    </head>
    <body style="padding-top: 50px">
        <div class="topbar" data-dropdown="dropdown">
            <div class="topbar-inner">
                <div class="container-fluid">
                    <h3 class="navbar-header">
                        <a class="brand" href="#">
                            This is CS50 Queue.
                        </a>
                    </h3>
                    <?php if (isset($_SESSION['user'])): ?>
                        <ul class="nav">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle header-dropdown-arrow"></a>
                                <ul class="dropdown-menu header-dropdown">
                                    <!--li><a href="/requests/respond">Section Change Requests</a></li-->
                                </ul>
                            </li>

                            <!--li class="dropdown">
                                <a href="#" class="dropdown-toggle">Preferences</a>
                                <ul class="dropdown-menu">
                                </ul>
                            </li-->
                        </ul>
                    <?php endif; ?>

                    <ul class="nav secondary-nav">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle change-app">CS50</a>
                            <!--ul class="dropdown-menu change-app-dropdown">
                                <li><a href="/requests/respond/1">CS91r</a></li>
                                <li><a href="/requests/respond/1">CS182</a></li>
                            </ul-->
                        </li>
                        <li><a href="/auth/login?return=<?= Yii::app()->request->requestUri ?>">Login</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php echo $content; ?>

    </body>
</html>
