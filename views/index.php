<?php
//echo 'Index';
?>
<h1>Список проектов</h1>
<div>
    <?php
        if (isset($error)) {
            echo $error;
        }
    ?>
</div>
<form action="/projects/add" method="post">
    <input type="text" name="name">
    <input type="submit" value="Отправить">
</form>
<hr>
<?php
 if (isset($projects)) {
     foreach ($projects as $project) {
?>
        <div><?= $project['name'] ?></div>
<?php
     }
 }
?>
