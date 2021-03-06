<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 * 
 * @package humhub.modules.gallery.views
 * @since 1.0
 * @author Sebastian Stumpf
 */
?>

<?php

use \humhub\modules\gallery\assets\Assets;
use \yii\helpers\Html;

$bundle = Assets::register($this);

$title = Yii::t('GalleryModule.base', 'of posted media files');
// fallback description
$description = Yii::t('GalleryModule.base', 'This gallery contains all posted media.');

if ($this->context->contentContainer instanceof humhub\modules\space\models\Space) {
    $description = Yii::t('GalleryModule.base', 'This gallery contains all posted media files from the space.');
} elseif ($this->context->contentContainer instanceof \humhub\modules\user\models\User) {
    $description = Yii::t('GalleryModule.base', 'This gallery contains all posted media files from the profile.');
}
?>

<div id="gallery-container" class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('GalleryModule.base', '<strong>Gallery</strong> ') . Html::encode($title) ?></div>

    <div class="panel-body">
        <div class="row">
            <div class="col-sm-12 gallery-description">
                <?= Html::encode($description) ?><br />
            </div>
        </div>
        <div style="padding: 10px 0 10px 0;" class="row">
            <div class="col-sm-1">
                <a class="btn btn-default btn-sm" data-ui-loader href="<?= $this->context->contentContainer->createUrl('/gallery/list') ?>">
                    <i class="glyphicon glyphicon-arrow-left"></i> <?= Yii::t('GalleryModule.base', 'Back to the list') ?></a>
            </div>
        </div>
        <div class="row">
            <?= humhub\modules\gallery\widgets\GalleryList::widget([
                'entryList' => $gallery->getFileList(),
                'parentGallery' => $gallery
            ]) ?>
        </div>
    </div>
</div>