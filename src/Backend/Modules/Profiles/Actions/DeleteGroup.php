<?php

namespace Backend\Modules\Profiles\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionDelete as BackendBaseActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Profiles\Engine\Model as BackendProfilesModel;
use Backend\Modules\Profiles\Form\GroupDeleteType;

/**
 * This action will delete a profile group.
 */
class DeleteGroup extends BackendBaseActionDelete
{
    public function execute(): void
    {
        $deleteForm = $this->createForm(GroupDeleteType::class);
        $deleteForm->handleRequest($this->getRequest());
        if (!$deleteForm->isSubmitted() || !$deleteForm->isValid()) {
            $this->redirect(BackendModel::createURLForAction(
                'Groups',
                null,
                null,
                ['error' => 'something-went-wrong']
            ));

            return;
        }
        $deleteFormData = $deleteForm->getData();

        $this->id = (int) $deleteFormData['id'];

        // does the item exist
        if ($this->id === 0 || !BackendProfilesModel::existsGroup($this->id)) {
            $this->redirect(BackendModel::createURLForAction('Groups', null, null, ['error' => 'non-existing']));

            return;
        }

        parent::execute();

        $group = BackendProfilesModel::getGroup($this->id);

        BackendProfilesModel::deleteGroup($this->id);

        $this->redirect(BackendModel::createURLForAction(
            'Groups',
            null,
            null,
            ['report' => 'deleted', 'var' => $group['name']]
        ));
    }
}
