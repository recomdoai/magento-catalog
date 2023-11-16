<?php

namespace Recomdoai\Catalog\Block\Adminhtml\System\Config\Connection;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ConnectwithRecomodoai extends Field
{

    /**
     * @var string
     */
    protected $buttonLabel = 'Connect With RecomodoAi';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $url = $this->_urlBuilder->getUrl('');
        $html = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setClass('primary')
            ->setLabel($this->buttonLabel)
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}
