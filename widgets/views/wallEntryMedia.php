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

use humhub\modules\file\libs\FileHelper;
use humhub\libs\Html;
?>

<div class="pull-left">
    <?php if ($previewImage->applyFile($file)): ?>
        <?= $previewImage->renderGalleryLink(['style' => 'padding-right:12px']); ?>
    <?php else: ?>
        <i class="fa <?= $media->getIconClass(); ?> fa-fw" style="font-size:40px"></i>
    <?php endif; ?>
</div>

<strong><?= FileHelper::createLink($file, null, ['style' => 'text-decoration: underline']); ?></strong><br />
<small><?= Yii::t('GalleryModule.base', 'Size: {size}', ['size' => Yii::$app->formatter->asShortSize($fileSize, 1)]); ?></small><br />

<?php if (!empty($media->description)): ?>
    <br />
    <?= Html::encode($media->description); ?>
    <br />
<?php endif; ?>

<br />

<?= Html::a(Yii::t('GalleryModule.base', 'Open Gallery'), $galleryUrl, ['class' => 'btn btn-sm btn-default', 'data-ui-loader' => '']); ?>

<div class="clearfix"></div>