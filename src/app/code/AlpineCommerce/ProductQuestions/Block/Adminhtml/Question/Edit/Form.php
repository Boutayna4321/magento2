<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Block\Adminhtml\Question\Edit;

use Magento\Backend\Block\Widget\Button\Toolbar;
use Magento\Backend\Block\Widget\Container;

class Form extends Container
{
    protected function _construct(): void
    {
        $this->setId('productquestions_question_edit_form');
        $this->setTemplate('question/form.phtml');
    }

    protected function _prepareLayout(): void
    {
        $this->addAnswersSection();
    }

    protected function addAnswersSection(): void
    {
        // Answers are managed through the UI component form
    }
}
