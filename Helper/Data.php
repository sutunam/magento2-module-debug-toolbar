<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 */
namespace Smile\DebugToolbar\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State as AppState;
use Magento\PageCache\Model\Config as PageCacheConfig;
use Smile\DebugToolbar\Block\Toolbar;

/**
 * Helper: Data
 *
 * @package   Smile\DebugToolbar\Helper
 * @copyright 2017 Smile
 */
class Data extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var float[]
     */
    protected $timers = [];

    /**
     * @var string
     */
    protected $toolbarId;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * Number of tables
     * @var int
     */
    protected $tableCount = 0;

    /**
     * Data constructor.
     *
     * @param Context       $context
     * @param DirectoryList $directoryList
     * @param AppState      $appState
     */
    public function __construct(
        Context       $context,
        DirectoryList $directoryList,
        AppState      $appState
    ) {
        parent::__construct($context);

        $this->directoryList = $directoryList;
        $this->appState      = $appState;
    }

    /**
     * Set a timer
     *
     * @param string $code
     *
     * @return $this
     */
    public function startTimer($code)
    {
        $this->timers[$code] = microtime(true);

        return $this;
    }

    /**
     * get a timer
     *
     * @param string $code
     *
     * @return float
     */
    public function getTimer($code)
    {
        if (!array_key_exists($code, $this->timers)) {
            $this->startTimer($code);
        }
        return microtime(true) - $this->timers[$code];
    }

    /**
     * Set a value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setValue($key, $value)
    {
        $this->values[$key] = $value;

        return $this;
    }

    /**
     * Get a value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     * @throws \Exception
     */
    public function getValue($key, $default = null)
    {
        if (!array_key_exists($key, $this->values)) {
            return $default;
        }

        return $this->values[$key];
    }

    /**
     * Display Human Size
     *
     * @param int $value
     *
     * @return string
     */
    public function displayHumanSize($value)
    {
        $unit = 'o';

        if ($value > 1024) {
            $value/= 1024.;
            $unit = 'Ko';
        }

        if ($value > 1024) {
            $value/= 1024.;
            $unit = 'Mo';
        }

        if ($value > 1024) {
            $value/= 1024.;
            $unit = 'Go';
        }

        return number_format($value, 3, '.', '').' '.$unit;
    }

     /**
     * Display Human Size in Ko
     *
     * @param int $value
     *
     * @return string
     */
    public function displayHumanSizeKo($value)
    {
        return number_format($value/1024., 3, '.', '').' Ko';
    }

    /**
     * Display Human Time
     *
     * @param int $value time in seconds
     *
     * @return string
     */
    public function displayHumanTime($value)
    {
        $unit = 's';

        if ($value > 120) {
            $value/= 60.;
            $unit = 'm';
        }

        if ($value > 120) {
            $value/= 60.;
            $unit = 'h';
        }

        if ($value < 1) {
            $value*= 1000.;
            $unit = 'ms';
        }

        return number_format($value, 3, '.', '').' '.$unit;
    }

    /**
     * Display a human time in ms
     *
     * @param float $value
     *
     * @return string
     */
    public function displayHumanTimeMs($value)
    {
        return number_format(10000*$value, 3, '.', '').' ms';
    }

    /**
     * Init the toolbar id
     *
     * @param $actionName
     *
     * @return string
     * @throws \Exception
     * @SuppressWarnings("PMD.StaticAccess")
     */
    public function initToolbarId($actionName)
    {
        if (!is_null($this->toolbarId)) {
            throw new \Exception('The toolbar id has already been set');
        }

        $date = \DateTime::createFromFormat('U.u', microtime(true));

        $values = [
            'st',
            $date->format('Ymd_His'),
            $date->format('u'),
            uniqid(),
            $this->appState->getAreaCode(),
            $actionName,
        ];

        $this->toolbarId = implode('-', $values);

        return $this->toolbarId;
    }

    /**
     * Get toolbarId
     *
     * @return string
     * @throws \Exception
     */
    public function getToolbarId()
    {
        if (is_null($this->toolbarId)) {
            throw new \Exception('The toolbar id has not been set');
        }

        return $this->toolbarId;
    }

    /**
     * Get a new table id
     *
     * @return string
     */
    public function getNewTableId()
    {
        $this->tableCount++;

        return $this->toolbarId.'_table_'.$this->tableCount;
    }

    /**
     * Get the toolbar storage folder
     *
     * @return string
     */
    public function getToolbarFolder()
    {
        $folder = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'smile_toolbar' . DIRECTORY_SEPARATOR;

        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        return $folder;
    }

    /**
     * Save the current toolbar
     *
     * @param Toolbar $toolbarBlock
     *
     * @return void
     */
    public function saveToolbar(Toolbar $toolbarBlock)
    {
        $filename = $this->getToolbarFolder().$toolbarBlock->getToolbarId().'.html';

        file_put_contents($filename, $toolbarBlock->toHtml());
    }

    /**
     * Clean the old toolbars
     *
     * @param int $nbToKeep
     *
     * @return void
     */
    public function cleanOldToolbars($nbToKeep)
    {
        $list = $this->getListToolbars();

        if (count($list) > $nbToKeep) {
            $toDelete = array_slice($list, 0, count($list)-$nbToKeep);

            $folder = $this->getToolbarFolder();
            foreach ($toDelete as $file) {
                unlink($folder . $file);
            }
        }
    }

    /**
     * Get the list of all the stored toolbars
     *
     * @return string[]
     */
    public function getListToolbars()
    {
        $folder = $this->getToolbarFolder();

        $list = array_diff(scandir($folder), ['.', '..']);

        foreach ($list as $key => $value) {
            if (!is_file($folder.$value) || is_dir($folder.$value)) {
                unset($list[$key]);
            }
        }

        sort($list);

        return $list;
    }

    /**
     * Get the content of all the stored toolbars
     *
     * @return string[]
     */
    public function getContentToolbars()
    {
        $list = $this->getListToolbars();

        $contents = [];

        $folder = $this->getToolbarFolder();
        foreach ($list as $filename) {
            $key = explode('.', $filename)[0];
            $contents[$key] = file_get_contents($folder.$filename);
        }

        return $contents;
    }

    /**
     * Get the Full Page Cache mode
     *
     * @return string
     */
    public function getFullPageCacheMode()
    {
        $key = 'system/full_page_cache/caching_application';

        return $this->scopeConfig->getValue($key) == PageCacheConfig::VARNISH ? 'varnish' : 'build-in';
    }
}
