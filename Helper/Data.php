<?php
/**
 * Scommerce security_security_base helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_SecurityBase
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\SecurityBase\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Scommerce\Core\Helper\Data as CoreHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Model\Exception;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * variable to check if extension is enable or not
     *
     * @var bool
     */
    const ENABLED = 'security_base/general/enabled';

    /**
     * variable to get licence key
     *
     * @var string
     */
    const LICENSE_KEY = 'security_base/general/license_key';

    /**
     * @var CoreHelper
     */
    protected $_coreHelper;

    /** @var ResultFactory */
    protected $resultFactory;

    protected $_storeManager;

    /**
     * @param Context $context
     * @param CoreHelper $coreHelper
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_coreHelper = $coreHelper;
        $this->resultFactory = $resultFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * returns whether module is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null)
    {
        $enabled = $this->isSetFlag(self::ENABLED,ScopeInterface::SCOPE_STORE, $storeId);
        return $this->isCliMode() ? $enabled : $enabled && $this->isLicenseValid();
    }

    public function getNoRouteRedirect()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectUrl = "scommercesecurity/wronglicense/index";
        $redirect->setPath($redirectUrl);
        return $redirect;
    }

    /**
     * Returns license key administration configuration option
     * @param int $storeId
     * @return string
     */
    public function getLicenseKey($storeId = null)
    {
        return $this->getValue(self::LICENSE_KEY,ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Check if running in cli mode
     *
     * @return bool
     */
    protected function isCliMode()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Helper method for retrieve config value by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return mixed
     */
    protected function getValue($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Helper method for retrieve config flag by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return bool
     */
    protected function isSetFlag($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * Returns whether license key is valid or not
     *
     * @return bool
     */
    public function isLicenseValid()
    {
        $sku = strtolower(str_replace('\\Helper\\Data', '', str_replace('Scommerce\\', '', get_class())));
        return $this->baseIsLicenseValid($this->getLicenseKey(), $sku);
    }

    private function baseIsLicenseValid($licenseKey,$sku)
    {
        $licenseKey = is_string($licenseKey)?$licenseKey:'';
        $url = $this->_storeManager->getDefaultStoreView()->getBaseUrl();;
        $website = $this->_coreHelper->getWebsite($url);
        $sku= $this->_coreHelper->getSKU($sku);
        return password_verify($website.'_'.$sku, $licenseKey ?? '');
    }
}
