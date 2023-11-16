<?php
namespace Recomdoai\Catalog\Block\Adminhtml\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ModeselectOptions implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Select Mode')],
            ['value' => '1', 'label' => __('Sandbox')],
            ['value' => '2', 'label' => __('Production')],
            // Add more options as needed
        ];
    }
}
