<?php

/* @var $this yii\web\View */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$this->title = 'Whitepages - Official Site | Find People, Phone Numbers, Addresses &amp; More';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>Find people, contact info & background checks</h1>

        <p class="lead">Trusted by over 35 million people every month</p>

        <p>
        
        <?php $form = ActiveForm::begin([
            'id' => 'search-form',
            'layout' => 'horizontal',
            'options' => ['enctype' => 'multipart/form-data'],
            ]); ?>

            <?= $form->field($model, 'csvFile')->fileInput(['autofocus' => true]) ?>

            <div class="form-group">
                <?= Html::submitButton('Upload and Search', ['class' => 'btn btn-primary', 'name' => 'search-button']) ?>
            </div>

        <?php ActiveForm::end(); ?>

        </p>

    </div>
    
</div>
