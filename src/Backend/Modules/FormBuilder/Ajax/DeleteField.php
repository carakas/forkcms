<?php

namespace Backend\Modules\FormBuilder\Ajax;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Modules\FormBuilder\Engine\Model as BackendFormBuilderModel;

/**
 * Delete a field via ajax.
 */
class DeleteField extends BackendBaseAJAXAction
{
    public function execute(): void
    {
        parent::execute();

        // get parameters
        $formId = trim($this->getRequest()->request->getInt('form_id'));
        $fieldId = trim($this->getRequest()->request->getInt('field_id'));

        // invalid form id
        if (!BackendFormBuilderModel::exists($formId)) {
            $this->output(self::BAD_REQUEST, null, 'form does not exist');
        } else {
            // invalid fieldId
            if (!BackendFormBuilderModel::existsField($fieldId, $formId)) {
                $this->output(self::BAD_REQUEST, null, 'field does not exist');
            } else {
                // get field
                $field = BackendFormBuilderModel::getField($fieldId);

                // submit button cannot be deleted
                if ($field['type'] == 'submit') {
                    $this->output(self::BAD_REQUEST, null, 'submit button cannot be deleted');
                } else {
                    // delete field
                    BackendFormBuilderModel::deleteField($fieldId);

                    // success output
                    $this->output(self::OK, null, 'field deleted');
                }
            }
        }
    }
}
