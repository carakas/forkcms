<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This action will add a post to the blog module.
 *
 * @author Jelmer Prins <jelmer@sumocoders.be>
 */
class BackendPartnerModuleAdd extends BackendBaseActionAdd
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        $this->frm = new BackendForm('add');
        $this->frm->addText('name', null, 255, 'inputText name', 'inputTextError name')->setAttribute('required');
        $this->frm->addImage('img', 'inputImage img', 'inputImageError img')->setAttribute('required');
        $this->frm->addText('url', null, 255, 'inputText url', 'inputTextError url')->setAttributes(
            array('type' => 'url', 'required')
        );
    }

    /**
     * Validate the form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();
            // validation
            $this->frm->getField('name')->isFilled(BL::err('NameIsRequired'));
            $this->frm->getField('img')->isFilled(BL::err('FieldIsRequired'));
            $this->frm->getField('url')->isFilled(BL::err('FieldIsRequired'));
            // no errors?
            if ($this->frm->isCorrect()) {

                $item['name'] = $this->frm->getField('name')->getValue();
                $item['url'] = $this->frm->getField('url')->getValue();
                $item['img'] = md5(microtime(true)) . '.' . $this->frm->getField('img')->getExtension();
                $this->frm->getField('img')->createThumbnail(
                    FRONTEND_FILES_PATH . '/' . FrontendPartnerModuleModel::THUMBNAIL_PATH . '/' . $item['img'],
                    180,
                    180
                );
                $this->frm->getField('img')->moveFile(
                    FRONTEND_FILES_PATH . '/' . FrontendPartnerModuleModel::IMAGE_PATH . '/' . $item['img']
                );
                $item['id'] = BackendPartnerModuleModel::insert($item);
                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('index') . '&report=added&var=' . urlencode(
                        $item['name']
                    ) . '&highlight=row-' . $item['id']
                );
            }
        }
    }
}
