<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Ui\DataProvider;

use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Question\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class QuestionFormDataProvider extends ModifierPoolDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();

        $questionId = (int) $request->getParam($requestFieldName);
        if ($questionId) {
            $this->collection->addFieldToFilter('question_id', $questionId);
        }
    }

    public function getData()
    {
        $data = parent::getData();
        $items = $data['items'] ?? [];

        if (isset($items[0])) {
            $questionId = (int) $items[0]['question_id'];
            $item = $items[0];

            $officialAnswer = $this->getOfficialAnswerText($questionId);
            if ($officialAnswer !== null) {
                $item['official_answer'] = $officialAnswer;
            }

            $item['answers'] = $this->getAnswersData($questionId);

            return [$questionId => $item];
        }

        return [];
    }

    private function getOfficialAnswerText(int $questionId): ?string
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AnswerInterface::QUESTION_ID, $questionId)
            ->create();

        $answers = $this->answerRepository->getList($searchCriteria)->getItems();

        foreach ($answers as $answer) {
            if ($answer->getIsOfficial()) {
                return $answer->getAnswer();
            }
        }

        return null;
    }

    private function getAnswersData(int $questionId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AnswerInterface::QUESTION_ID, $questionId)
            ->create();

        $answers = $this->answerRepository->getList($searchCriteria)->getItems();

        $result = [];
        foreach ($answers as $answer) {
            $result[] = [
                'answer_id' => $answer->getId(),
                'answer' => $answer->getAnswer(),
            ];
        }

        return $result;
    }
}
