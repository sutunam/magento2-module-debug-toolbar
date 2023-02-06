<?php

declare(strict_types=1);

namespace Smile\DebugToolbar\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Smile\DebugToolbar\Helper\Config as ConfigHelper;

class Loader implements ArgumentInterface
{
    protected ConfigHelper $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Check whether the module is enabled.
     */
    public function isToolbarEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }
}