<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name;
?>
<br />
<div class="row index">
    <div class="span12">
        <h1>Добро пожаловать в <?php echo CHtml::encode(Yii::app()->name); ?></h1>
    </div>
</div>
<div class="row">
    <div class="span12">
        <div class="group-iconed tests">
            <h2>Тесты</h2>
            <ul class="inline-list">
                <?php foreach ($tests as $test): ?>
                    <li><a href="<?php echo $test->getUrl() ?>"><?php echo $test->title ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
