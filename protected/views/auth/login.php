<div class="container">
    <h1 class="auth-header">This is CS50.</h1>
    <form id="form-login" method="post" action='/auth/authenticate?<?php if (isset($_GET["return"])) echo "return={$_GET["return"]}"; ?>'>
        <input name="email" type="email" placeholder="Email" />
        <input name="password" type="password" placeholder="Password" />
        <input type="submit" class="btn primary" value="Log in" />
    </form>
</div>
