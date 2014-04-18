<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;

/**
 * This action will load a form with the item data and save the changes.
 *
 * @author Jelmer <jelmer@sumocoders.be>
 */
class BackendPartnersEditPartner extends BackendBaseActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        $this->id = $this->getParameter('id', 'int');

        // does the item exist
        if ($this->id !== null && BackendPartnersModel::partnerExists($this->id)) {
            parent::execute();
            $this->getData();
            $this->loadForm();
            $this->validateForm();
            $this->parse();
            $this->display();
        } else {
            $this->redirect(BackendModel::createURLForAction('index', null, null, array(
                'error' => 'non-existing'
            )));
        }
    }

    /**
     * Get the data
     */
    private function getData()
    {
        $this->record = BackendPartnersModel::getPartner($this->id);

        // no item found, redirect to index
        if (empty($this->record)) {
            $this->redirect(BackendModel::createURLForAction('index', null, null, array(
                'error' => 'non-existing'
            )));
        }
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        $this->frm = new BackendForm('edit');
        $this->frm->addText('name', $this->record['name'], 255, 'inputText name', 'inputTextError name')->setAttribute(
            'required'
        );
        $this->frm->addImage('img', 'inputImage img', 'inputImageError img')->setAttribute('required');
        $this->frm->addText('url', $this->record['url'], 255, 'inputText url', 'inputTextError url')->setAttributes(
            array('type' => 'url', 'required')
        );
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        parent::parse();

        // assign this variable so it can be used in the template
        $this->tpl->assign('item', $this->record);
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
            if ($this->frm->getField('img')->isFilled()) {
                // image has the jpg/png extension
                $this->frm->getField('img')->isAllowedExtension(
                    array('jpg', 'png', 'gif'),
                    BL::err('JPGGIFAndPNGOnly')
                );
            }
            $this->frm->getField('url')->isFilled(BL::err('FieldIsRequired'));

            // no errors?
            if ($this->frm->isCorrect()) {
                $item['id'] = $this->record['id'];
                $item['name'] = $this->frm->getField('name')->getValue();
                $item['url'] = $this->frm->getField('url')->getValue();

                // update image if needed
                if ($this->frm->getField('img')->isFilled()) {
                    $fs = new Filesystem();
                    $basePath = FRONTEND_FILES_PATH . '/' . FrontendPartnersModel::IMAGE_PATH . $this->record['widget'];
                    $fs->remove(
                        $basePath . '/source/' . $this->record['img']
                    );
                    $fs->remove(
                        $basePath  . '/48x48/' . $this->record['img']
                    );

                    $item['img'] = md5(microtime(true)) . '.' . $this->frm->getField('img')->getExtension();
                    $this->frm->getField('img')->generateThumbnails(
                        FRONTEND_FILES_PATH . '/' . FrontendPartnersModel::IMAGE_PATH . '/' . $this->record['widget'],
                        $item['img']
                    );
                }
                BackendPartnersModel::updatePartner($item);

                // everything is saved, so redirect to the overview
                $this->redirect(
                    BackendModel::createURLForAction('edit', null, null, array(
                            'id' => $this->record['widget'],
                            'report' => 'added',
                            'var' => urlencode($item['title']),
                            'highlight' => 'row-' . $item['id']
                    ))
                );
            }
        }
    }
}