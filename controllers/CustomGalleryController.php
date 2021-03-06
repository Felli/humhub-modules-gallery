<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\gallery\controllers;

use \humhub\modules\content\models\Content;
use \humhub\modules\gallery\models\CustomGallery;
use \humhub\modules\gallery\models\Media;
use \Yii;
use \yii\base\NotSupportedException;
use \yii\web\HttpException;
use \yii\web\UploadedFile;

/**
 * Description of a Custom Gallery Controller for the gallery module.
 *
 * @package humhub.modules.gallery.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class CustomGalleryController extends ListController
{

    /**
     *
     * @return redirect to /view.
     */
    public function actionIndex()
    {
        return $this->redirect('/gallery/custom-gallery/view');
    }

    /**
     * Action to render the custom gallery view specified by open-gallery-id.
     * @url-param 'open-gallery-id' id of the open gallery.
     *
     * @return The rendered view.
     */
    public function actionView()
    {
        return $this->renderGallery();
    }

    /**
     * Action to sort the media files.
     *
     * @return string the rendered view.
     */
    public function actionSort()
    {
        throw new NotSupportedException("Not yet implemented.");
    }

    /**
     * Action to edit a gallery.
     * @url-param 'item-id' the gallery's id.
     * @url-param 'open-gallery-id' id of the open gallery. Used for redirecting.
     *
     * @throws HttpException if insufficient permission.
     * @return string the redered html.
     */
    public function actionEdit()
    {
        $this->canWrite(true);

        $itemId = Yii::$app->request->get('item-id');
        $openGalleryId = Yii::$app->request->get('open-gallery-id');
        $visibility = Yii::$app->request->get('visibility');
        // default visibility is private
        $visibility = $visibility !== Content::VISIBILITY_PUBLIC ? Content::VISIBILITY_PRIVATE : Content::VISIBILITY_PUBLIC;
        // check if a gallery with the given id exists.
        $gallery = $this->module->getItemById($itemId);

        // if no gallery is found with the given id, a new one has to be created
        if (!($gallery instanceof CustomGallery)) {
            // create a new gallery
            $gallery = new CustomGallery();
            $gallery->type = CustomGallery::TYPE_CUSTOM_GALLERY;
            $gallery->content->container = $this->contentContainer;
        }

        $gallery_form_data = Yii::$app->request->post('CustomGallery');
        $content_form_data = Yii::$app->request->post('Content');
        // format visibility
        $content_form_data['visibility'] = $content_form_data['visibility'] != Content::VISIBILITY_PUBLIC ? Content::VISIBILITY_PRIVATE : Content::VISIBILITY_PUBLIC;

        if ($gallery_form_data !== null && $gallery->load(Yii::$app->request->post()) && $gallery->validate()) {
            if ($gallery->content->visibility != $content_form_data['visibility']) {
                // visibility has changed, this will also be changed for all contained objects
                $gallery->content->visibility = $content_form_data['visibility'];
                foreach($gallery->mediaList as $media) {
                    $media->content->visibility = $content_form_data['visibility'];
                    $media->save();
                }
            }
            $gallery->save();
            $this->view->saved();
            return $this->htmlRedirect($this->contentContainer->createUrl('/gallery/custom-gallery/view', ['open-gallery-id' => $openGalleryId]));
            // TODO: only load the changed element
        }

        // render modal
        return $this->renderPartial('/custom-gallery/modal_gallery_edit', [
                    'openGalleryId' => $openGalleryId,
                    'gallery' => $gallery,
                    'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * Handles the file upload for are particular UploadedFile
     */
    protected function handleMediaUpload(\humhub\modules\gallery\models\BaseGallery $parentGallery, yii\web\UploadedFile $cfile)
    {
        $media = new Media();
        // Save humhubfile
        $mediaUpload = new \humhub\modules\gallery\models\MediaUpload();
        $mediaUpload->setUploadedFile($cfile);
        $valid = $mediaUpload->validate();
        if ($valid) {
            $media->title = $mediaUpload->file_name;
            $media->content->container = $this->contentContainer;
            $media->gallery_id = $parentGallery->id;
            $media->content->visibility = $parentGallery->isPublic() ? Content::VISIBILITY_PUBLIC : Content::VISIBILITY_PRIVATE; 
            $valid = $media->validate();
            // connect media and file
            if ($valid) {
                $media->save();
                $mediaUpload->object_model = $media->className();
                $mediaUpload->object_id = $media->id;
                $mediaUpload->show_in_stream = false;
                $mediaUpload->save();
            }
        }
        $result = $mediaUpload->getInfoArray();
        // TODO: there is probably a better way to do so as upper method call is deprecated
        $result['error'] = !$valid;
        $result['errors'] = '';
        foreach ($mediaUpload->getErrors() as $error) {
            $result['errors'] .= $result['name'] . ' - ' . implode(', ', $error) . '\n';
        }

        return $result;
    }

    /**
     * Action to upload multiple files.
     * @url-param 'open-gallery-id' id of the open gallery the files should be stored in.
     *
     * @throws HttpException if insufficient permission.
     * @return multitype:string
     */
    public function actionUpload()
    {
        Yii::$app->response->format = 'json';
        $this->canWrite(true);
        $parentGallery = $this->getOpenGallery();

        $errors = false;
        $files = array();
        foreach (UploadedFile::getInstancesByName('files') as $cFile) {
            $result = $this->handleMediaUpload($parentGallery, $cFile);
            $errors = $errors | $result['error'];
            $files[] = $result;
        }

        if (!$errors) {
            $this->view->success('Upload complete');
        }

        return ['files' => $files];
    }

    /**
     * Render a specified custom gallery or the gallery list.
     * @url-param 'open-gallery-id' id of the open gallery. The gallery list is rendered if no gallery with this id is found.
     *
     * @param string $ajax
     *            render as ajax. default: false
     * @param string $openGalleryId
     *            the custom gallery to render.
     */
    protected function renderGallery($ajax = false, $openGalleryId = null)
    {
        $gallery = $this->getOpenGallery($openGalleryId);
        if ($gallery != null) {
            return $ajax ? $this->renderPartial("/custom-gallery/gallery_view", [
                        'gallery' => $gallery
                    ]) : $this->render("/custom-gallery/gallery_view", [
                        'gallery' => $gallery
            ]);
        } else {
            return parent::renderGallery($ajax);
        }
    }

    protected function getOpenGallery($openGalleryId = null)
    {
        $id = $openGalleryId == null ? Yii::$app->request->get('open-gallery-id') : $openGalleryId;
        return CustomGallery::findOne([
                    'id' => $id,
                    'type' => CustomGallery::TYPE_CUSTOM_GALLERY
        ]);
    }

}
