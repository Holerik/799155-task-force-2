<?php

use app\models\Task;
use yii\helpers\Url;
use yii\helpers\Html;

?>

<div class="left-menu">
    <h3 class="head-main head-task">Мои задания</h3>
    <ul class="side-menu-list">
        <?php foreach (Task::FILTER_LINKS[$contr] as $key => $value) :?>
        <li class="side-menu-item
            <?=strstr(Url::current(), $key) === false ? '' : 'side-menu-item--active';?>">
            <a href=<?='/my-tasks/' . $key?> class="link link--nav"><?=$value[0]?></a>
        </li>
        <?php endforeach;?>
    </ul>
</div>
<div class="left-column left-column--task">
    <h3 class="head-main head-regular"><?=Task::FILTER_LINKS[$contr][$code][1]?></h3>
    <?php foreach ($tasks as $task) :?>
    <div class="task-card">
            <div class="header-task">
            <a href=<?='/task/' . $task->id?>
                class="link link--block link--big"><?=Html::encode($task->name)?></a>
            <p class="price price--task"><?=Html::encode($task->budget) . ' ₽'?></p>
        </div>
        <p class="info-text"><span class="current-time">
            <?=Yii::$app->helpers->getTimeStr(Html::encode($task->add_date));?></span></p>
        <p class="task-text"><?=Html::encode($task->description)?></p>
        <div class="footer-task">
            <?php if (!empty($task->city)) :?>
            <p class="info-text town-text"><?=Html::encode($task->city)?></p>
            <?php endif;?>
            <p class="info-text category-text"><?=Html::encode($task->category)?></p>
            <a href=<?='/task/' . $task->id?> class="button button--black">Смотреть задание</a>
        </div>
    </div>
    <?php endforeach;?>
</div>
